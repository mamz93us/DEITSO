<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_servers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('hostname');
            $table->string('username');
            $table->text('api_token_encrypted');
            $table->unsignedSmallInteger('port')->default(2087);
            $table->enum('status', ['active', 'disabled', 'failed'])->default('active')->index();
            $table->timestamp('last_connection_check_at')->nullable();
            $table->string('last_connection_status')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_servers');
    }
};
