<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dns_provider_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->enum('provider', ['godaddy', 'cloudflare', 'route53'])->index();
            $table->string('name');
            $table->string('base_domain');
            // Encrypted JSON blob containing key/secret/region/etc. — handled via Crypt cast.
            $table->text('credentials_encrypted')->nullable();
            // Sandbox/production toggle for providers that expose an OTE environment.
            $table->enum('environment', ['production', 'ote', 'sandbox'])->default('production');
            $table->boolean('is_default')->default(false);
            $table->enum('status', ['active', 'disabled', 'pending'])->default('pending')->index();
            $table->timestamp('last_check_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['provider', 'base_domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_provider_accounts');
    }
};
