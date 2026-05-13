<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('code', 32);
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('branch_id')->nullable()
                ->references('id')->on('branches')->nullOnDelete();
            $table->foreignUlid('customer_branch_id')->nullable()
                ->references('id')->on('branches')->nullOnDelete();

            $table->enum('type', ['online', 'offline'])->index();
            $table->string('state')->index();

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();

            $table->foreignUlid('technician_user_id')->nullable()
                ->references('id')->on('users')->nullOnDelete();
            $table->json('additional_technicians')->nullable();

            $table->decimal('checkin_lat', 10, 7)->nullable();
            $table->decimal('checkin_lng', 10, 7)->nullable();
            $table->timestamp('checkin_at')->nullable();
            $table->decimal('checkout_lat', 10, 7)->nullable();
            $table->decimal('checkout_lng', 10, 7)->nullable();
            $table->timestamp('checkout_at')->nullable();

            $table->foreignUlid('travel_zone_id')->nullable(); // FK added in Sprint 11

            $table->text('summary')->nullable();
            $table->text('internal_notes')->nullable();

            $table->unsignedBigInteger('total_charge_minor')->default(0);
            $table->string('currency', 3)->default('EGP');
            $table->boolean('is_billable')->default(true);

            $table->softDeletes();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
