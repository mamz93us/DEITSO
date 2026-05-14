<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_workflow_template_tasks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('template_id')->constrained('hr_workflow_templates')->cascadeOnDelete();
            $table->unsignedInteger('order_index')->default(0);

            $table->json('title');
            $table->text('description')->nullable();

            $table->enum('type', [
                'manual', 'assign_asset', 'assign_accessory', 'create_email',
                'assign_license', 'grant_access', 'custom_action',
                'collect_asset', 'delete_email', 'suspend_email',
                'revoke_license', 'disable_user', 'data_backup',
            ])->index();

            $table->json('config')->nullable();

            $table->enum('assignee_role', [
                'it_technician', 'hr', 'procurement', 'manager', 'requester', 'other',
            ])->default('it_technician');

            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('due_offset_days')->default(0);

            $table->timestamps();

            $table->index(['template_id', 'order_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_workflow_template_tasks');
    }
};
