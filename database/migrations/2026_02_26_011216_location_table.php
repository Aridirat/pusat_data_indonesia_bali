<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('location', function (Blueprint $table) {
            $table->integer('location_id')->autoIncrement();

            $table->string('kode_provinsi',10);
            $table->string('kode_kabupaten',10);
            $table->string('kode_kecamatan',10);
            $table->string('kode_desa',10);

            $table->string('provinsi', 100);
            $table->string('kabupaten', 100);
            $table->string('kecamatan', 100);
            $table->string('desa', 100);

            $table->string('banjar', 100)->nullable();
            $table->string('rt', 10)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location');
    }
};