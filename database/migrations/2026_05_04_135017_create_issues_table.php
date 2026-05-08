<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('hash')->index(); // Unique fingerprint of the exception
            $table->string('type')->default('exception');
            $table->string('title');
            $table->text('message');
            $table->string('status')->default('open'); // open, resolved, ignored
            $table->timestamp('resolved_at')->nullable();
            $table->string('priority')->default('none'); // none, low, medium, high
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedInteger('occurrences_count')->default(0);
            $table->unsignedInteger('users_count')->default(0);

            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'hash']);
        });

        Schema::table('records', function (Blueprint $table) {
            $table->foreignId('issue_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('issue_id');
        });
        Schema::dropIfExists('issues');
    }
};
