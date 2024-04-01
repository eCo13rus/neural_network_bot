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
        Schema::create('transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('request_id')->nullable();
            $table->integer('amount')->nullable();
            $table->string('type', 45)->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->foreign('request_id', 'fk_request_id')
                  ->references('id')->on('requests')
                  ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id', 'fk_transactions_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade')->onUpdate('cascade');

            $table->index('request_id', 'fk_request_id_idx');
            $table->index('user_id', 'fk_transactions_user_id_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
