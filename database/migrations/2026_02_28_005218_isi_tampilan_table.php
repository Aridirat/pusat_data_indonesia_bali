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
        Schema::create('isi_tampilan', function (Blueprint $table) {
            $table->integer(column: 'isi_tampilan_id')->autoIncrement();
            $table->integer('tampilan_id');
            $table->foreign('tampilan_id')
                ->references('tampilan_id')
                ->on('tampilan')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->integer('metadata_id');
            $table->foreign('metadata_id')
                ->references('metadata_id')
                ->on('metadata')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('isi_tampilan');
    }
};