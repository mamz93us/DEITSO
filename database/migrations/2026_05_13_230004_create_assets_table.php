<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('branch_id')->nullable()
                ->references('id')->on('branches')->nullOnDelete();
            $table->foreignUlid('asset_model_id')->nullable()
                ->references('id')->on('asset_models')->nullOnDelete();
            $table->foreignUlid('category_id')->constrained('asset_categories')->cascadeOnDelete();

            $table->string('code', 64);
            $table->string('serial_number')->nullable()->index();
            $table->string('name')->nullable();

            $table->enum('status', [
                'in_stock', 'deployed', 'in_maintenance',
                'retired', 'scrapped', 'lost',
            ])->default('in_stock')->index();

            $table->enum('tracking_mode', ['serialized', 'bulk', 'license'])
                ->default('serialized');

            $table->unsignedInteger('quantity')->default(1);

            // Current assignment shortcut (full history lives in asset_assignments — Sprint 4).
            $table->foreignUlid('assigned_employee_id')->nullable()
                ->references('id')->on('employees')->nullOnDelete();
            $table->foreignUlid('assigned_branch_id')->nullable()
                ->references('id')->on('branches')->nullOnDelete();

            $table->foreignUlid('supplier_id')->nullable()
                ->references('id')->on('suppliers')->nullOnDelete();
            $table->string('purchase_order_reference')->nullable();

            // source_request_id populated by Employee Request fulfillment (Sprint 7).
            $table->ulid('source_request_id')->nullable()->index();

            // device_id populated when the Windows agent enrolls the device (Phase 2).
            $table->ulid('device_id')->nullable()->index();

            $table->date('purchase_date')->nullable();
            $table->unsignedBigInteger('purchase_cost_minor')->nullable();
            $table->string('currency', 3)->default('EGP');
            $table->string('vendor')->nullable();
            $table->date('warranty_until')->nullable();

            $table->string('depreciation_method', 64)->nullable();
            $table->unsignedInteger('depreciation_months')->nullable();

            $table->json('custom_fields')->nullable();
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'category_id']);
            $table->index(['organization_id', 'assigned_employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
