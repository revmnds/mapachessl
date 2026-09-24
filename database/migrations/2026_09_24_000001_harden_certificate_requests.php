<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->string('generation_attempt', 36)->nullable()->after('generation_started_at');
            $table->timestamp('job_started_at')->nullable()->after('generation_attempt');
        });

        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->dropColumn('email');
        });

        // Email step removed: old steps 3-5 are now 2-4
        DB::table('certificate_requests')
            ->where('current_step', '>=', 3)
            ->decrement('current_step');

        // Private keys are now stored encrypted
        DB::table('certificate_requests')
            ->whereNotNull('private_key_pem')
            ->orderBy('id')
            ->each(function ($row) {
                DB::table('certificate_requests')
                    ->where('id', $row->id)
                    ->update(['private_key_pem' => Crypt::encryptString($row->private_key_pem)]);
            });
    }

    public function down(): void
    {
        DB::table('certificate_requests')
            ->whereNotNull('private_key_pem')
            ->orderBy('id')
            ->each(function ($row) {
                DB::table('certificate_requests')
                    ->where('id', $row->id)
                    ->update(['private_key_pem' => Crypt::decryptString($row->private_key_pem)]);
            });

        DB::table('certificate_requests')
            ->where('current_step', '>=', 2)
            ->increment('current_step');

        Schema::table('certificate_requests', function (Blueprint $table) {
            $table->string('email')->nullable();
            $table->dropColumn(['generation_attempt', 'job_started_at']);
        });
    }
};
