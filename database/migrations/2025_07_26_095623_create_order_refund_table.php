<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderRefundTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_refund', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->index('FK_order');
            $table->foreignId('product_id')->index('FK_product_item_variant')->comment('product_item_variant.id');
            $table->string('refund_photo')->nullable();
            $table->text('refund_notes')->nullable();
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
        Schema::dropIfExists('order_refund');
    }
}
