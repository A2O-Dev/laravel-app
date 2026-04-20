<?php

use App\Models\Order;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('orderable');
            $table->string('stripe_payment_intent_id')->nullable()->unique()->index();
            $table->unsignedInteger('amount');
            $table->string('currency', 3)->default('usd');
            $table->enum('status', [Order::PENDING, Order::PROCESSING, Order::PAID, Order::FAILED, Order::REFUNDED])->default(Order::PENDING);
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('orders');
    }
};
