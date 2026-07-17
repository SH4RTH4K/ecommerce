<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddUpsCatalogAttributes extends Migration
{
    public function up()
    {
        $attributes = [
            ['name' => 'UPS Capacity', 'slug' => 'ups-capacity', 'input_type' => 'select', 'options' => ['650VA', '750VA', '800VA', '850VA', '1000VA', '1200VA', '1500VA', '2000VA', '3000VA']],
            ['name' => 'Load Capacity', 'slug' => 'load-capacity', 'input_type' => 'text', 'options' => []],
            ['name' => 'UPS Topology', 'slug' => 'ups-topology', 'input_type' => 'select', 'options' => ['Offline', 'Line Interactive', 'Online']],
            ['name' => 'Battery', 'slug' => 'ups-battery', 'input_type' => 'text', 'options' => []],
            ['name' => 'Input Voltage', 'slug' => 'input-voltage', 'input_type' => 'text', 'options' => []],
            ['name' => 'Output Voltage', 'slug' => 'output-voltage', 'input_type' => 'text', 'options' => []],
            ['name' => 'Backup Time', 'slug' => 'backup-time', 'input_type' => 'text', 'options' => []],
            ['name' => 'Waveform', 'slug' => 'waveform', 'input_type' => 'select', 'options' => ['Simulated Sine Wave', 'Pure Sine Wave']],
        ];

        foreach ($attributes as $order => $attribute) {
            if (DB::table('catalog_attributes')->where('category_id', 33)->where('slug', $attribute['slug'])->exists()) continue;
            DB::table('catalog_attributes')->insert([
                'category_id' => 33,
                'name' => $attribute['name'],
                'slug' => $attribute['slug'],
                'input_type' => $attribute['input_type'],
                'options' => json_encode($attribute['options']),
                'is_filterable' => 1,
                'is_comparable' => 1,
                'display_order' => ($order + 1) * 10,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down()
    {
        DB::table('catalog_attributes')->where('category_id', 33)->whereIn('slug', [
            'ups-capacity', 'load-capacity', 'ups-topology', 'ups-battery',
            'input-voltage', 'output-voltage', 'backup-time', 'waveform',
        ])->delete();
    }
}
