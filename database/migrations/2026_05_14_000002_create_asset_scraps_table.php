<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_scraps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();

            $table->enum('reason', [
                'end_of_life', 'damaged', 'lost', 'sold', 'donated', 'other',
            ])->index();
            $table->string('disposal_method')->nullable();
            $table->unsignedBigInteger('residual_value_minor')->nullable();
            $table->string('currency', 3)->default('EGP');

            $table->foreignUlid('approved_by_user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_scraps');
    }
};
