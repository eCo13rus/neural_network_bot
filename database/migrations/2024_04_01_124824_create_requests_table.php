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
        Schema::create('requests', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('neural_network_id')->nullable();
            $table->string('status', 45)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->foreign('neural_network_id', 'fk_neural_network_id')
                  ->references('id')->on('neural_networks')
                  ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id', 'fk_requests_user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade')->onUpdate('cascade');

            $table->index('neural_network_id', 'fk_neural_network_id_idx');
            $table->index('user_id', 'fk_requests_user_id_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('requests');
    }
};
