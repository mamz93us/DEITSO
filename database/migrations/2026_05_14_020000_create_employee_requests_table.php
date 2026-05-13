<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 32);

            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('branch_id')->nullable()
                ->references('id')->on('branches')->nullOnDelete();
            $table->foreignUlid('requester_employee_id')
                ->constrained('employees')->cascadeOnDelete();

            $table->enum('type', [
                'new_asset',
                'new_accessory',
                'upgrade_existing',
                'new_license',
                'other',
            ])->index();

            $table->foreignUlid('category_id')->nullable()
                ->references('id')->on('asset_categories')->nullOnDelete();
            $table->foreignUlid('asset_model_id')->nullable()
                ->references('id')->on('asset_models')->nullOnDelete();
            $table->foreignUlid('related_asset_id')->nullable()
                ->references('id')->on('assets')->nullOnDelete();
            $table->string('license_name')->nullable();

            $table->string('title');
            $table->text('description');
            $table->text('justification')->nullable();

            $table->enum('urgency', ['low', 'normal', 'high', 'urgent'])
                ->default('normal')->index();

            $table->unsignedBigInteger('estimated_cost_minor')->nullable();
            $table->string('currency', 3)->default('EGP');

            // spatie/laravel-model-states persists the state-class FQN here.
            $table->string('state')->index();

            // Manager approval
            $table->foreignUlid('manager_approval_user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->timestamp('manager_approval_at')->nullable();
            $table->text('manager_approval_notes')->nullable();

            // Admin approval
            $table->foreignUlid('admin_approval_user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->timestamp('admin_approval_at')->nullable();
            $table->text('admin_approval_notes')->nullable();

            // Fulfillment
            $table->foreignUlid('assigned_procurement_user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->timestamp('fulfilled_at')->nullable();
            $table->foreignUlid('fulfillment_asset_id')->nullable()
                ->references('id')->on('assets')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'state']);
            $table->index(['organization_id', 'requester_employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_requests');
    }
};
