<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('neural_networks', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug', 45)->nullable();
            $table->string('name', 155)->nullable();
            $table->mediumText('description')->nullable();
            $table->string('type', 50)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index('name', 'fk_name_neural');
        });
    }

    public function down()
    {
        Schema::dropIfExists('neural_networks');
    }
};
