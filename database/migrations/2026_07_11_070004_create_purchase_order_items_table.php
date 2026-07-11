<?php

use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignIdFor(PurchaseOrder::class)->constrained();
            $table->foreignIdFor(Product::class)->constrained();
            $table->string('product_name_snapshot', 255);
            $table->bigInteger('unit_price_snapshot')->default(0);
            $table->integer('quantity')->default(0);
            $table->bigInteger('subtotal')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
