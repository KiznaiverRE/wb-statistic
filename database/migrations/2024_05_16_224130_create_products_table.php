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
        Schema::create('products', function (Blueprint $table) {
            $table->id(); // bigint unsigned
            $table->unsignedBigInteger('sellers_article')->nullable();
            $table->unsignedBigInteger('wb_article')->nullable();
            $table->string('title')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->onDelete('set null');
            $table->unsignedBigInteger('header_id')->nullable();
            $table->foreign('header_id')->references('id')->on('headers')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Add user_id with foreign key constraint
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
        // Сначала удаляем внешний ключ и столбец category_id
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        // Затем удаляем саму таблицу products
        Schema::dropIfExists('products');
    }
};
