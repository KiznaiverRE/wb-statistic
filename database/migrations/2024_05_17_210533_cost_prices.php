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
        Schema::create('cost_prices', function (Blueprint $table) {
            $table->id(); // bigint unsigned
            $table->unsignedBigInteger('product_id')->constrained('products')->onDelete('cascade'); // bigint unsigned
            $table->integer('date')->unsigned(); // поле для хранения даты в виде числа
            $table->decimal('price', 8, 2); // поле для хранения цены
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
        //
    }
};
