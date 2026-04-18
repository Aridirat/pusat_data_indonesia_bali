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
        Schema::create('metadata', function (Blueprint $table) {

            $table->integer('metadata_id')->autoIncrement();

            $table->string('nama', 255);
            $table->string('alias', 255)->nullable();

            $table->text('konsep');
            $table->text('definisi');

            $table->string('klasifikasi', 100);

            $table->text('asumsi')->nullable();

            $table->string('metodologi', 100);
            $table->text('penjelasan_metodologi');

            $table->string('tipe_data', 50);
            $table->string('satuan_data', 50);

            $table->string('tahun_mulai_data', 50);
            $table->string('frekuensi_penerbitan', 50);
            $table->Integer('tahun_pertama_rilis')->nullable();
            $table->unsignedTinyInteger('bulan_pertama_rilis')->nullable();
            $table->unsignedTinyInteger('tanggal_rilis')->nullable();

            $table->integer('produsen_id');
            
            $table->string('tag', 255);
            
            $table->integer('flag_desimal')->default(0);           

            $table->integer('tipe_group')->default(0);
            $table->integer('group_by')->nullable();
            $table->integer('status')->default(1);
            
            $table->dateTime('date_inputed');

            $table->integer('user_id');

            $table->foreign('user_id')
                ->references('user_id')
                ->on('user')
                ->onDelete('restrict')
                ->onUpdate('restrict');

            $table->foreign('produsen_id')
                ->references('produsen_id')
                ->on('produsen_data')
                ->onDelete('restrict')
                ->onUpdate('restrict');

            $table->foreign('group_by')
                ->references('metadata_id')
                ->on('metadata')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metadata');
    }
};