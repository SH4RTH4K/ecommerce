<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCouponsTable extends Migration
{
    public function up()
    {
        // This database may have been restored from a backup that contains the
        // tables but not the matching row in Laravel's migrations table.
        if (! Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 60)->unique();
                $table->string('description')->nullable();
                $table->enum('discount_type', ['fixed', 'percent'])->default('fixed');
                $table->decimal('discount_value', 12, 2);
                $table->decimal('minimum_order', 12, 2)->default(0);
                $table->decimal('maximum_discount', 12, 2)->nullable();
                $table->dateTime('starts_at')->nullable();
                $table->dateTime('expires_at')->nullable();
                $table->unsignedInteger('usage_limit')->nullable();
                $table->unsignedInteger('used_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('orders', 'coupon_id')
            || ! Schema::hasColumn('orders', 'coupon_code')
            || ! Schema::hasColumn('orders', 'discount')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! Schema::hasColumn('orders', 'coupon_id')) {
                    $table->unsignedInteger('coupon_id')->nullable()->after('delivery_zone_name');
                }
                if (! Schema::hasColumn('orders', 'coupon_code')) {
                    $table->string('coupon_code', 60)->nullable()->after('coupon_id');
                }
                if (! Schema::hasColumn('orders', 'discount')) {
                    $table->decimal('discount', 12, 2)->default(0)->after('subtotal');
                }
            });
        }
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['coupon_id', 'coupon_code', 'discount']);
        });
        Schema::dropIfExists('coupons');
    }
}
