<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_cards', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->enum('visit_type', ['online', 'offline', 'any'])->default('any')->index();
            $table->enum('technician_seniority', ['junior', 'mid', 'senior', 'any'])->default('any')->index();
            $table->unsignedBigInteger('hourly_rate_minor');
            $table->string('currency', 3)->default('EGP');
            $table->unsignedBigInteger('minimum_charge_minor')->default(0);
            $table->unsignedInteger('billing_increment_minutes')->default(15);
            $table->boolean('is_default')->default(false);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['organization_id', 'is_default']);
            $table->index(['organization_id', 'visit_type', 'technician_seniority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_cards');
    }
};
