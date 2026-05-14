<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_process_tasks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('hr_process_id')->constrained('hr_processes')->cascadeOnDelete();
            $table->unsignedInteger('order_index')->default(0);

            // Snapshot fields (copied from template at process start so future
            // template edits don't retro-mutate active processes).
            $table->json('title');
            $table->text('description')->nullable();
            $table->string('type', 64)->index();
            $table->json('config')->nullable();

            $table->foreignUlid('assigned_to_user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();

            // 6-state machine — Pending, Blocked, InProgress, Completed, Skipped, Failed.
            $table->string('state')->index();

            // Type-specific result payload (e.g. assign_asset stores asset_id).
            $table->json('result')->nullable();

            $table->foreignUlid('linked_request_id')->nullable()
                ->references('id')->on('employee_requests')->nullOnDelete();

            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignUlid('completed_by_user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['hr_process_id', 'order_index']);
            $table->index(['hr_process_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_process_tasks');
    }
};
