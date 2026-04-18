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
        Schema::create('rujukan', function (Blueprint $table) {
            $table->integer('rujukan_id')->autoIncrement();
            $table->string('nama_rujukan', 255);
            $table->string('link_rujukan', 255)->nullable();
            $table->string('gambar_rujukan', 255)->nullable();
            $table->integer('produsen_id');
            $table->timestamps();

            $table->foreign('produsen_id')
                ->references('produsen_id')
                ->on('produsen_data')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rujukan');
    }
};