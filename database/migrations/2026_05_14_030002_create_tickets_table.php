<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 32);
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('branch_id')->nullable()
                ->references('id')->on('branches')->nullOnDelete();
            $table->foreignUlid('category_id')->nullable()
                ->references('id')->on('ticket_categories')->nullOnDelete();

            $table->foreignUlid('related_asset_id')->nullable()
                ->references('id')->on('assets')->nullOnDelete();
            $table->foreignUlid('related_employee_id')->nullable()
                ->references('id')->on('employees')->nullOnDelete();

            $table->foreignUlid('requester_user_id')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreignUlid('assigned_user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();

            $table->enum('priority', ['low', 'normal', 'high', 'urgent', 'critical'])
                ->default('normal')->index();
            $table->string('state')->index();
            $table->enum('source', ['web', 'portal', 'email', 'whatsapp', 'phone', 'walk_in'])
                ->default('web')->index();

            $table->string('subject');
            $table->text('description');

            $table->foreignUlid('sla_policy_id')->nullable()
                ->references('id')->on('sla_policies')->nullOnDelete();
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('sla_response_due_at')->nullable()->index();
            $table->timestamp('sla_resolution_due_at')->nullable()->index();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Accumulated time spent in WaitingCustomer (in seconds) for SLA pause math.
            $table->unsignedBigInteger('paused_seconds_total')->default(0);
            $table->timestamp('paused_at')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'state']);
            $table->index(['organization_id', 'assigned_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
