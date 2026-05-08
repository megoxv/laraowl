<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Issue extends Model
{
    protected $fillable = [
        'project_id',
        'hash',
        'type',
        'title',
        'message',
        'status',
        'priority',
        'assigned_to',
        'occurrences_count',
        'users_count',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(IssueActivity::class);
    }
}
