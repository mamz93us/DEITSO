<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_remote_access', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('asset_id')->constrained()->cascadeOnDelete();

            $table->enum('type', [
                'teamviewer', 'anydesk', 'rdp', 'vnc', 'ssh', 'rustdesk', 'other',
            ])->index();
            $table->string('identifier'); // TeamViewer ID / hostname / IP
            $table->string('username')->nullable();
            // Encrypted via Eloquent's 'encrypted' cast on the model. Stored as TEXT.
            $table->text('password_encrypted')->nullable();
            $table->unsignedSmallInteger('port')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_remote_access');
    }
};
