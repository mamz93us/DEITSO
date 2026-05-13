<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * License-specific columns on the assets table. Used only when the asset's
 * category has tracking_mode='license'. NULL on other tracking modes.
 *
 * license_key_encrypted is encrypted at rest via the model's 'encrypted' cast.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->text('license_key_encrypted')->nullable()->after('serial_number');
            $table->unsignedInteger('seats_total')->nullable()->after('quantity');
            $table->date('expiry_date')->nullable()->after('warranty_until');
            $table->boolean('renewable')->default(false)->after('expiry_date');
            $table->boolean('auto_renewal')->default(false)->after('renewable');

            $table->index(['organization_id', 'expiry_date']);
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'expiry_date']);
            $table->dropColumn(['license_key_encrypted', 'seats_total', 'expiry_date', 'renewable', 'auto_renewal']);
        });
    }
};
