<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_domains', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('mail_server_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['mail_server_id', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_domains');
    }
};
