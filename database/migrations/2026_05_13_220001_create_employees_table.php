<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('branch_id')->nullable()
                ->references('id')->on('branches')->nullOnDelete();
            $table->foreignUlid('department_id')->nullable()
                ->references('id')->on('departments')->nullOnDelete();

            // 1:1 link to users. Unique to enforce one-to-one.
            $table->foreignUlid('user_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('code', 32);
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('position')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('mobile', 32)->nullable();
            $table->string('personal_email')->nullable();

            $table->foreignUlid('manager_employee_id')->nullable()
                ->references('id')->on('employees')->nullOnDelete();

            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->enum('status', ['active', 'on_leave', 'terminated'])
                ->default('active')->index();

            $table->json('custom_fields')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'branch_id']);
            $table->index(['organization_id', 'department_id']);
            $table->index(['organization_id', 'manager_employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
