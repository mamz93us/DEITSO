<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_models', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // organization_id NULL = system-wide template available to all orgs.
            $table->foreignUlid('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUlid('category_id')->constrained('asset_categories')->cascadeOnDelete();
            $table->string('manufacturer')->nullable();
            $table->string('model_name');
            $table->json('specs')->nullable(); // cpu, ram, storage, screen_size, os …
            $table->unsignedBigInteger('default_unit_cost_minor')->nullable();
            $table->string('currency', 3)->default('EGP');
            $table->foreignUlid('preferred_supplier_id')->nullable()
                ->references('id')->on('suppliers')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['organization_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_models');
    }
};
