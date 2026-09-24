<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            // Last time the user's page polled; a request waiting in line without a
            // visitor loses its place instead of holding a worker for 30 minutes
            $table->timestamp('last_seen_at')->nullable()->after('job_started_at');
            // Refreshed every few seconds by the running job; a job killed by a redeploy
            // or crash stops beating and the user can retry right away
            $table->timestamp('heartbeat_at')->nullable()->after('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->dropColumn(['last_seen_at', 'heartbeat_at']);
        });
    }
};
