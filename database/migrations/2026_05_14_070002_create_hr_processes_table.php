<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_processes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 32);
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('branch_id')->nullable()
                ->references('id')->on('branches')->nullOnDelete();

            $table->enum('type', ['onboarding', 'offboarding'])->index();
            $table->foreignUlid('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('template_id')->nullable()
                ->references('id')->on('hr_workflow_templates')->nullOnDelete();

            $table->foreignUlid('initiated_by_user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->date('target_date')->nullable();

            $table->string('state')->index();

            $table->timestamp('completed_at')->nullable();
            $table->foreignUlid('completed_by_user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();

            $table->string('handover_pdf_path')->nullable();
            $table->text('notes')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'type', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_processes');
    }
};
