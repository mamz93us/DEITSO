<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('email_domain_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('local_part');
            $table->string('full_address');
            $table->unsignedBigInteger('quota_mb')->nullable();
            $table->foreignUlid('assigned_employee_id')->nullable()
                ->references('id')->on('employees')->nullOnDelete();
            $table->enum('status', ['active', 'suspended', 'deleted'])->default('active')->index();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->unique(['email_domain_id', 'local_part']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_accounts');
    }
};
