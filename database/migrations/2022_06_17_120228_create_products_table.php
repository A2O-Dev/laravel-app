<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->char('name', 100);
            $table->char('code', 20)->unique();
            $table->double('price', unsigned: true);
            $table->text('dimensions')->nullable();
            $table->text('colors')->nullable();
            $table->text('tags')->nullable();
            $table->smallInteger('stock', unsigned: true)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::dropIfExists('products');
    }
};
