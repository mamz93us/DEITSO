<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_transfers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('branch_id')->nullable()
                ->references('id')->on('branches')->nullOnDelete();

            $table->foreignUlid('from_employee_id')->nullable()
                ->references('id')->on('employees')->nullOnDelete();
            $table->foreignUlid('to_employee_id')->nullable()
                ->references('id')->on('employees')->nullOnDelete();
            $table->foreignUlid('from_branch_id')->nullable()
                ->references('id')->on('branches')->nullOnDelete();
            $table->foreignUlid('to_branch_id')->nullable()
                ->references('id')->on('branches')->nullOnDelete();

            $table->foreignUlid('requested_by_user_id')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreignUlid('approved_by_user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();

            // spatie/laravel-model-states persists the state-class FQN in this column.
            $table->string('state')->index();

            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('transferred_at')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'state']);
            $table->index(['asset_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_transfers');
    }
};
