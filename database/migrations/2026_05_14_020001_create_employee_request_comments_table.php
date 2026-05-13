<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_request_comments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('employee_request_id')
                ->constrained('employee_requests')->cascadeOnDelete();
            $table->foreignUlid('user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();

            // Author's role at the time of writing — informational only.
            $table->enum('author_role', [
                'requester', 'manager', 'admin', 'procurement', 'system',
            ])->index();

            $table->text('body');
            $table->boolean('is_internal')->default(false);

            $table->timestamps();

            $table->index(['employee_request_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_request_comments');
    }
};
