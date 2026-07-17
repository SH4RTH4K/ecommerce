<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDetailsToInventoryLocationsTable extends Migration
{
    public function up()
    {
        Schema::table('inventory_locations', function (Blueprint $table) {
            $table->string('contact_person', 120)->nullable()->after('phone');
            $table->string('email', 150)->nullable()->after('contact_person');
            $table->string('country', 100)->nullable()->after('address');
            $table->string('division', 100)->nullable()->after('country');
            $table->string('city', 100)->nullable()->after('division');
            $table->string('postal_code', 20)->nullable()->after('city');
            $table->decimal('latitude', 10, 7)->nullable()->after('postal_code');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('google_maps_url')->nullable()->after('longitude');
            $table->string('operating_hours', 150)->nullable()->after('email');
            $table->boolean('pickup_available')->default(false)->after('operating_hours');
            $table->boolean('delivery_hub')->default(false)->after('pickup_available');
            $table->text('notes')->nullable()->after('delivery_hub');
        });
    }

    public function down()
    {
        Schema::table('inventory_locations', function (Blueprint $table) {
            $table->dropColumn([
                'contact_person', 'email', 'country', 'division', 'city', 'postal_code',
                'latitude', 'longitude', 'google_maps_url', 'operating_hours',
                'pickup_available', 'delivery_hub', 'notes'
            ]);
        });
    }
}
