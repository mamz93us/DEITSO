<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Per-user notification channel preferences (in-app always on).
            $table->boolean('notify_via_mail')->default(true)->after('timezone');
            $table->boolean('notify_via_whatsapp')->default(false)->after('notify_via_mail');
            $table->string('whatsapp_phone', 32)->nullable()->after('notify_via_whatsapp');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notify_via_mail', 'notify_via_whatsapp', 'whatsapp_phone']);
        });
    }
};
