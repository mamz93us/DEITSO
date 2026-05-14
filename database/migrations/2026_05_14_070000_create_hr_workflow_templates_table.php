<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_workflow_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['onboarding', 'offboarding'])->index();
            $table->json('name');
            $table->text('description')->nullable();
            $table->foreignUlid('department_id')->nullable()
                ->references('id')->on('departments')->nullOnDelete();
            $table->string('position_tag')->nullable()->index();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['organization_id', 'type', 'is_active']);
            $table->index(['organization_id', 'type', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_workflow_templates');
    }
};
