<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable audit log of every action performed against the WHM/cPanel API.
 * Never updated, never soft-deleted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_actions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('mail_server_id')->nullable()
                ->references('id')->on('mail_servers')->nullOnDelete();
            $table->foreignUlid('email_account_id')->nullable()
                ->references('id')->on('email_accounts')->nullOnDelete();

            $table->enum('action', [
                'create', 'reset_password', 'suspend', 'unsuspend', 'delete', 'change_quota', 'connection_check',
            ])->index();

            $table->foreignUlid('actor_user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->ipAddress('actor_ip')->nullable();

            $table->enum('status', ['success', 'failed'])->index();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['organization_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_actions');
    }
};
