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
        Schema::create('user_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('neural_network_text_id')->nullable();
            $table->unsignedInteger('neural_network_image_id')->nullable();
            $table->unsignedInteger('neural_network_tts_id')->nullable();
            $table->integer('context_characters_count')->default(1000);
            $table->integer('max_images_batch')->default(5);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            // Внешние ключи и индексы
            $table->foreign('user_id', 'fk_user_settings_user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('neural_network_text_id', 'fk_user_settings_network_text_idx')
                  ->references('id')->on('neural_networks')
                  ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('neural_network_image_id', 'fk_user_settings_network_image_idx')
                  ->references('id')->on('neural_networks')
                  ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('neural_network_tts_id', 'fk_user_settings_network_tts_id')
                  ->references('id')->on('neural_networks')
                  ->onDelete('cascade')->onUpdate('cascade');

            $table->index('user_id', 'fk_user_id_idx');
            $table->index('neural_network_text_id', 'fk_user_settings_network_text_idx');
            $table->index('neural_network_image_id', 'fk_user_settings_network_image_idx');
            $table->index('neural_network_tts_id', 'fk_user_settings_network_tts_id_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_settings');
    }
};
