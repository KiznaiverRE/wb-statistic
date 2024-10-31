<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seller_articles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('article')->unique(); // уникальное числовое поле для артикулов
            $table->unsignedBigInteger('header_id')->nullable();
            $table->foreign('header_id')->references('id')->on('headers')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seller_articles');
    }
};
