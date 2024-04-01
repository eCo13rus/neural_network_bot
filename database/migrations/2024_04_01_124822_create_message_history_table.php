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
        Schema::create('message_history', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('neural_history_network_id')->nullable();
            $table->text('message_text')->collation('utf8mb4_unicode_ci');
            $table->tinyInteger('is_from_user')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')
                  ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('neural_history_network_id')->references('id')->on('neural_networks')
                  ->onDelete('cascade')->onUpdate('cascade');

            $table->index('user_id', 'fk_message_history_user_id_idx');
            $table->index('neural_history_network_id', 'fk_neural_history_network_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('message_history');
    }
};
