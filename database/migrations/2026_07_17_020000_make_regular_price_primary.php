<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeRegularPricePrimary extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('product')) {
            return;
        }

        DB::statement('UPDATE product SET regular_price = CASE WHEN regular_price IS NOT NULL AND regular_price > 0 THEN regular_price ELSE offer_price END');

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE product MODIFY regular_price DECIMAL(12,2) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE product MODIFY offer_price DECIMAL(12,2) NULL DEFAULT NULL');
        }

        DB::statement('UPDATE product SET offer_price = NULL WHERE offer_price IS NULL OR offer_price >= regular_price');
    }

    public function down()
    {
        if (!Schema::hasTable('product')) {
            return;
        }

        DB::statement('UPDATE product SET offer_price = regular_price WHERE offer_price IS NULL');
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE product MODIFY offer_price DECIMAL(12,2) NOT NULL DEFAULT 0');
            DB::statement('ALTER TABLE product MODIFY regular_price DECIMAL(12,2) NULL DEFAULT NULL');
        }
    }
}
