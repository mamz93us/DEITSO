<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivityLogTable extends Migration
{
    public function up()
    {
        Schema::connection(config('activitylog.database_connection'))->create(config('activitylog.table_name'), function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable();
            $table->text('description');
            // ULID-friendly morphs (subject_id / causer_id stored as char(26)).
            $table->string('subject_type')->nullable();
            $table->char('subject_id', 26)->nullable();
            $table->index(['subject_id', 'subject_type'], 'subject');
            $table->string('causer_type')->nullable();
            $table->char('causer_id', 26)->nullable();
            $table->index(['causer_id', 'causer_type'], 'causer');
            $table->json('properties')->nullable();
            $table->timestamps();
            $table->index('log_name');
        });
    }

    public function down()
    {
        Schema::connection(config('activitylog.database_connection'))->dropIfExists(config('activitylog.table_name'));
    }
}
