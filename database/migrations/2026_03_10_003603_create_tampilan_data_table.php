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
        Schema::create('tampilan_data', function (Blueprint $table) {
            $table->integer('tampilan_id');
            $table->integer('id');
            $table->primary(['tampilan_id', 'id']);
            $table->foreign('tampilan_id')
                  ->references('tampilan_id')->on('tampilan')
                  ->onDelete('cascade');
            $table->foreign('id')
                  ->references('id')->on('data')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tampilan_data');
    }
};