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
        Schema::create('financial_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('meta_data_id')->constrained('financial_meta_data');
            $table->string('report_date');
            $table->decimal('transfers', 10, 2);
            $table->integer('orders_count');
            $table->decimal('average_check', 10, 2);
            $table->decimal('purchase_cost', 10, 2);
            $table->decimal('logistic_cost', 10, 2);
            $table->decimal('logistic_percent', 10, 2);
            $table->decimal('storage_cost', 10, 2);
            $table->decimal('advertising_cost', 10, 2);
            $table->decimal('ddr_percent', 5, 2);
            $table->decimal('fine', 10, 2);
            $table->decimal('credited_to_account', 10, 2);
            $table->decimal('batch_cost', 10, 2);
            $table->decimal('profit', 10, 2);
            $table->decimal('profit_percent', 5, 2);
            $table->decimal('margin_after_expenses', 10, 2);
            $table->integer('returns_count');
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
        Schema::dropIfExists('financial_reports');
    }
};
