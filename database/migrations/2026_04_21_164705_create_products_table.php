<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('collection')->nullable();
            $table->string('sub_category')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('tags')->nullable();
            $table->decimal('mrp', 10, 2)->nullable();
            $table->decimal('price', 10, 2); // Selling Price
            $table->text('materials')->nullable(); // Selection list
            $table->text('colors')->nullable(); // Selection list
            $table->string('image')->nullable();
            $table->string('status')->default('active');
            $table->boolean('featured')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
