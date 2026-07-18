<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product', function (Blueprint $table) {
            $table->string('sub_category')->nullable()->change();
            $table->string('product_model')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('product', function (Blueprint $table) {
            $table->string('sub_category')->nullable(false)->change();
            $table->string('product_model')->nullable(false)->change();
        });
    }
};
