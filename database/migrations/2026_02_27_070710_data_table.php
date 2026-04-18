<?php

use App\Models\rujukan;
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
        Schema::create('data', function (Blueprint $table) {

            // PRIMARY KEY
            $table->integer('id')->autoIncrement();
            
            $table->integer('user_id');

            $table->integer('metadata_id');

            $table->bigInteger('location_id');
            
            $table->integer('rujukan_id');
            
            $table->integer('time_id');

            $table->decimal('number_value', 10, 2)->nullable();

            $table->integer('status')->default(1);

            $table->dateTime('date_inputed');

            $table->foreign('metadata_id')
                ->references('metadata_id')
                ->on('metadata')
                ->onDelete('restrict')
                ->onUpdate('restrict');

            $table->foreign('rujukan_id')
                ->references('rujukan_id')
                ->on('rujukan')
                ->onDelete('restrict')
                ->onUpdate('restrict');
            
            $table->foreign('time_id')
                ->references('time_id')
                ->on('time')
                ->onDelete('restrict')
                ->onUpdate('restrict');

            $table->foreign('location_id')
                ->references('location_id')
                ->on('location')
                ->onDelete('restrict')
                ->onUpdate('restrict');

            $table->foreign('user_id')
                ->references('user_id')
                ->on('user')
                ->onDelete('restrict')
                ->onUpdate('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data');
    }
};