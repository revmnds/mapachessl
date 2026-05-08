<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_requests', function (Blueprint $table) {
            $table->id();
            $table->string('session_token')->unique();
            $table->string('domain')->nullable();
            $table->boolean('is_wildcard')->default(false);
            $table->string('email')->nullable();
            $table->enum('challenge_type', ['http', 'dns'])->nullable();
            $table->text('challenge_token')->nullable();
            $table->string('challenge_filename')->nullable();
            $table->integer('current_step')->default(1);
            $table->enum('status', ['in_progress', 'completed', 'failed', 'expired'])->default('in_progress');
            $table->timestamp('generation_started_at')->nullable();
            $table->unsignedInteger('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->text('certificate_pem')->nullable();
            $table->text('private_key_pem')->nullable();
            $table->text('chain_pem')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('domain');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_requests');
    }
};
