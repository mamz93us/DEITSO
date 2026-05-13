<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_comments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();

            $table->enum('author_type', ['staff', 'end_user', 'system'])->index();
            $table->text('body');
            $table->boolean('is_internal')->default(false);

            $table->timestamps();

            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_comments');
    }
};
