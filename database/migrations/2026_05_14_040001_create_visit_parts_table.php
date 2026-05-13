<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_parts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('asset_id')->nullable()
                ->references('id')->on('assets')->nullOnDelete();
            $table->string('description')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_cost_minor')->default(0);
            $table->string('currency', 3)->default('EGP');
            $table->timestamps();
        });

        Schema::create('visit_ticket', function (Blueprint $table) {
            $table->foreignUlid('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('ticket_id')->constrained()->cascadeOnDelete();
            $table->primary(['visit_id', 'ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_ticket');
        Schema::dropIfExists('visit_parts');
    }
};
