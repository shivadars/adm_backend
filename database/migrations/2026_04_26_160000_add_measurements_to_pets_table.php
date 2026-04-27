<?php

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
        Schema::table('pets', function (Blueprint $table) {
            $table->string('size')->nullable()->after('status');
            $table->decimal('neck_length', 5, 2)->nullable()->after('size');
            $table->decimal('chest_length', 5, 2)->nullable()->after('neck_length');
            $table->decimal('back_length', 5, 2)->nullable()->after('chest_length');
            $table->decimal('top_to_toe_height', 5, 2)->nullable()->after('back_length');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pets', function (Blueprint $table) {
            $table->dropColumn([
                'size',
                'neck_length',
                'chest_length',
                'back_length',
                'top_to_toe_height',
            ]);
        });
    }
};
