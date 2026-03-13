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
        Schema::create('time', function (Blueprint $table) {
            $table->integer('time_id')->autoIncrement();
            $table->integer('decade');     
            $table->integer('year');       
            $table->integer('quarter');    
            $table->integer('month');      
            $table->integer('day');        
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time');
    }
};