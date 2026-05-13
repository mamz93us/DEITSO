<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->json('name'); // translatable
            $table->string('code', 32);
            $table->foreignUlid('parent_id')->nullable()
                ->references('id')->on('asset_categories')->nullOnDelete();

            // Three modes: serialized (1 row per physical unit),
            // bulk (one row with quantity, e.g. cables),
            // license (software seats — handled in Sprint 6).
            $table->enum('tracking_mode', ['serialized', 'bulk', 'license'])
                ->default('serialized')->index();

            $table->json('custom_fields_schema')->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_categories');
    }
};
