<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_brandings', function (Blueprint $table) {
            $table->foreignUlid('organization_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('primary_color', 16)->default('#0f172a');
            $table->string('secondary_color', 16)->default('#1e293b');
            $table->string('accent_color', 16)->default('#3b82f6');
            $table->text('email_header_html')->nullable();
            $table->text('email_footer_html')->nullable();
            $table->text('pdf_header_html')->nullable();
            $table->text('pdf_footer_html')->nullable();
            $table->json('portal_welcome_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_brandings');
    }
};
