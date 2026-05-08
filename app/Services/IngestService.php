<?php

namespace App\Services;

use App\Events\ProjectDataIngested;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Record;
use Illuminate\Support\Facades\DB;

class IngestService
{
    protected AlertService $alertService;

    protected SecurityService $securityService;

    public function __construct(AlertService $alertService, SecurityService $securityService)
    {
        $this->alertService = $alertService;
        $this->securityService = $securityService;
    }

    /**
     * Process incoming records and handle issue grouping.
     */
    public function ingest(Project $project, array $records): void
    {
        DB::transaction(function () use ($project, $records) {
            foreach ($records as $data) {
                $type = $data['t'] ?? null;
                if (! $type) {
                    continue;
                }

                // The entire $data array is effectively the payload in this flat format
                $record = $project->records()->create([
                    'type' => $type,
                    'payload' => $data,
                    'fingerprint' => $this->calculateFingerprint($type, $data),
                    'created_at' => now(),
                ]);

                if ($type === 'exception') {
                    $this->handleException($project, $record);
                }

                if ($type === 'request') {
                    $this->securityService->analyze($project, $record);
                }

                if ($type === 'security-audit') {
                    $this->securityService->audit($project, $record);
                }

                if ($type === 'heartbeat') {
                    $slug = $data['slug'] ?? 'default';
                    $heartbeat = $project->heartbeats()->firstOrCreate(
                        ['slug' => $slug],
                        [
                            'name' => $data['name'] ?? ucfirst($slug),
                            'interval_minutes' => $data['interval'] ?? 15,
                            'status' => 'active',
                        ]
                    );

                    $heartbeat->update([
                        'last_seen_at' => now(),
                        'status' => 'active',
                    ]);
                }

                $this->checkThresholds($project, $record);
            }
        });

        // Broadcast real-time update to the frontend
        ProjectDataIngested::dispatch($project);
    }

    /**
     * Check if a record exceeds any performance thresholds.
     */
    protected function checkThresholds(Project $project, Record $record): void
    {
        $payload = $record->payload;
        $duration = $payload['duration'] ?? null;

        if ($duration === null) {
            return;
        }

        // Find applicable thresholds for this project and type
        $thresholdType = match ($record->type) {
            'request' => 'route',
            'job-attempt', 'queued-job' => 'job',
            'command' => 'command',
            'scheduled-task' => 'scheduled-task',
            'query' => 'query',
            default => null,
        };

        if (! $thresholdType) {
            return;
        }

        $key = match ($thresholdType) {
            'route' => $payload['route_path'] ?? $payload['path'] ?? '/',
            'job' => $payload['name'] ?? $payload['job'] ?? 'Unknown',
            'command', 'scheduled-task' => $payload['command'] ?? 'Unknown',
            'query' => $payload['sql'] ?? 'Unknown',
            default => null,
        };

        if (! $key) {
            return;
        }

        $threshold = $project->thresholds()
            ->where('type', $thresholdType)
            ->where('key', $key)
            ->where('is_enabled', true)
            ->first();

        if ($threshold && $duration > $threshold->value) {
            $this->handleSlowPerformance($project, $record, $threshold);
        }
    }

    /**
     * Handle performance threshold violations by creating issues and notifying.
     */
    protected function handleSlowPerformance(Project $project, Record $record, $threshold): void
    {
        $hash = md5('slow_performance_'.$threshold->type.'_'.$threshold->key);
        $title = 'Slow '.ucfirst($threshold->type).': '.$threshold->key;
        $message = 'Duration: '.$record->payload['duration'].'ms (Threshold: '.$threshold->value.'ms)';

        $issue = $project->issues()->firstOrCreate(
            ['hash' => $hash],
            [
                'title' => $title,
                'message' => $message,
                'status' => 'open',
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]
        );

        $issue->increment('occurrences_count');
        $issue->update([
            'last_seen_at' => now(),
            'message' => $message, // Update with latest duration
        ]);

        if ($issue->wasRecentlyCreated) {
            $this->alertService->notifySlowPerformance($issue);
        }

        $record->update(['issue_id' => $issue->id]);
    }

    /**
     * Group exceptions into unique issues based on hash and detect spikes.
     */
    protected function handleException(Project $project, Record $record): void
    {
        $payload = $record->payload;
        $hash = $payload['_group'] ?? md5($payload['class'].$payload['message'].($payload['file'] ?? '').($payload['line'] ?? ''));

        $issue = $project->issues()->firstOrCreate(
            ['hash' => $hash],
            [
                'title' => $payload['class'],
                'message' => $payload['message'],
                'status' => 'open',
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]
        );

        $issue->increment('occurrences_count');
        $issue->update(['last_seen_at' => now()]);

        if ($issue->wasRecentlyCreated) {
            $this->alertService->notifyNewIssue($issue);
        }

        $record->update(['issue_id' => $issue->id]);

        // Detect Spike
        $this->detectErrorSpike($project);
    }

    /**
     * Detect sudden surge in errors.
     */
    protected function detectErrorSpike(Project $project): void
    {
        $windowMinutes = $project->settings['spike_window'] ?? 5;
        $threshold = $project->settings['spike_threshold'] ?? 50;

        $count = $project->records()
            ->where('type', 'exception')
            ->where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->count();

        if ($count >= $threshold) {
            // Avoid spamming - only alert once every window
            $lastAlert = $project->settings['last_spike_alert_at'] ?? null;
            if (! $lastAlert || now()->diffInMinutes($lastAlert) >= $windowMinutes) {
                $this->alertService->notifyErrorSpike($project, $count, $windowMinutes);

                // Update last alert time
                $settings = $project->settings ?? [];
                $settings['last_spike_alert_at'] = now();
                $project->update(['settings' => $settings]);
            }
        }
    }

    /**
     * Calculate a unique fingerprint for grouping and fast lookup.
     */
    protected function calculateFingerprint(string $type, array $payload): ?string
    {
        if (isset($payload['_group'])) {
            return $payload['_group'];
        }

        $id = match ($type) {
            'request' => ($payload['method'] ?? 'GET').($payload['route_path'] ?? $payload['path'] ?? '/'),
            'exception' => ($payload['class'] ?? '').($payload['message'] ?? ''),
            'query' => $payload['sql'] ?? '',
            'job', 'job-attempt', 'queued-job' => $payload['job'] ?? $payload['name'] ?? $payload['job_class'] ?? '',
            'scheduled-task' => $payload['command'] ?? '',
            'mail' => $payload['mailable'] ?? '',
            'notification' => ($payload['notification'] ?? '').($payload['channel'] ?? ''),
            default => null,
        };

        return $id ? md5($id) : null;
    }
}
