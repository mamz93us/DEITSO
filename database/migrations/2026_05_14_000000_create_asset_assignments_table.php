<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();

            // Polymorphic holder: employee | branch | user.
            $table->string('assigned_to_type', 64);
            $table->char('assigned_to_id', 26);
            $table->index(['assigned_to_id', 'assigned_to_type'], 'asset_assignments_assignee_index');

            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('from_at')->useCurrent();
            $table->timestamp('to_at')->nullable()->index(); // null = current holder

            $table->foreignUlid('assigned_by_user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->string('reason')->nullable();

            $table->timestamps();

            $table->index(['asset_id', 'to_at']);
            $table->index(['organization_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};
