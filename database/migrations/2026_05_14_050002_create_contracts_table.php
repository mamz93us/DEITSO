<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('customer_organization_id')->nullable()
                ->references('id')->on('organizations')->nullOnDelete();
            $table->string('code', 32);
            $table->json('name');
            $table->decimal('included_hours_per_month', 8, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->enum('status', ['active', 'expired', 'cancelled'])->default('active')->index();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });

        Schema::create('contracted_hours_ledger', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('visit_id')->nullable()
                ->references('id')->on('visits')->nullOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->decimal('hours_consumed', 8, 2);
            $table->timestamps();

            $table->index(['contract_id', 'period_year', 'period_month'], 'contracted_hours_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracted_hours_ledger');
        Schema::dropIfExists('contracts');
    }
};
