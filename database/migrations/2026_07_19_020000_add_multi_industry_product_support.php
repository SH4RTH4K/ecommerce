<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('product', function (Blueprint $table) {
            $table->string('manufacturer_id')->nullable()->change();
            $table->string('industry_profile', 30)->default('general')->after('product_series_id');
            $table->string('generic_name')->nullable()->after('industry_profile');
            $table->string('strength')->nullable()->after('generic_name');
            $table->string('dosage_form')->nullable()->after('strength');
            $table->boolean('prescription_required')->default(false)->after('dosage_form');
            $table->string('storage_instructions')->nullable()->after('prescription_required');
            $table->text('allergen_information')->nullable()->after('storage_instructions');
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id')->index();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->string('barcode')->nullable()->unique();
            $table->decimal('price_adjustment', 12, 2)->default(0);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_lots', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id')->index();
            $table->string('lot_number');
            $table->date('manufactured_at')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->unsignedInteger('quantity')->default(0);
            $table->string('supplier_reference')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'lot_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_lots');
        Schema::dropIfExists('product_variants');
        Schema::table('product', function (Blueprint $table) {
            $table->dropColumn(['industry_profile','generic_name','strength','dosage_form','prescription_required','storage_instructions','allergen_information']);
            $table->string('manufacturer_id')->nullable(false)->change();
        });
    }
};
