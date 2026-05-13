<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_domains', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('host')->unique();
            $table->enum('type', ['platform_subdomain', 'custom_domain'])->index();
            $table->foreignUlid('dns_provider_id')->nullable()
                ->references('id')->on('dns_provider_accounts')->nullOnDelete();
            $table->string('verification_token', 64)->nullable();
            // dns_status / tls_status drive the lifecycle. Indexed because Caddy
            // hits the allow-list endpoint with very high frequency.
            $table->enum('dns_status', [
                'pending_propagation', 'verified', 'failed',
            ])->default('pending_propagation')->index();
            $table->enum('tls_status', [
                'pending', 'provisioning', 'active', 'failed',
            ])->default('pending');
            $table->boolean('is_primary')->default(false);
            $table->timestamp('last_checked_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['organization_id', 'is_primary']);
            // Composite index for the Caddy allow callback (host + status).
            $table->index(['host', 'dns_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_domains');
    }
};
