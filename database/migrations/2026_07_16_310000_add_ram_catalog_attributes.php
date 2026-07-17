<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddRamCatalogAttributes extends Migration
{
    public function up()
    {
        $attributes = [
            ['name'=>'Memory Type','slug'=>'memory-type','input_type'=>'select','options'=>['DDR3','DDR4','DDR5']],
            ['name'=>'Capacity','slug'=>'ram-capacity','input_type'=>'select','options'=>['4GB','8GB','16GB','32GB','48GB','64GB','96GB','128GB']],
            ['name'=>'Bus Speed','slug'=>'ram-speed','input_type'=>'select','options'=>['1600MHz','2400MHz','2666MHz','3000MHz','3200MHz','3600MHz','4800MHz','5200MHz','5600MHz','6000MHz','6400MHz','7200MHz']],
            ['name'=>'CAS Latency','slug'=>'cas-latency','input_type'=>'text','options'=>[]],
            ['name'=>'Operating Voltage','slug'=>'ram-voltage','input_type'=>'text','options'=>[]],
            ['name'=>'Form Factor','slug'=>'ram-form-factor','input_type'=>'select','options'=>['UDIMM','SO-DIMM']],
            ['name'=>'RGB Lighting','slug'=>'ram-rgb','input_type'=>'select','options'=>['Yes','No']],
            ['name'=>'Heat Spreader','slug'=>'heat-spreader','input_type'=>'select','options'=>['Yes','No']],
        ];

        foreach ($attributes as $order=>$attribute) {
            if (DB::table('catalog_attributes')->where('category_id',34)->where('slug',$attribute['slug'])->exists()) continue;
            DB::table('catalog_attributes')->insert([
                'category_id'=>34,'name'=>$attribute['name'],'slug'=>$attribute['slug'],
                'input_type'=>$attribute['input_type'],'options'=>json_encode($attribute['options']),
                'is_filterable'=>1,'is_comparable'=>1,'display_order'=>($order+1)*10,
                'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down()
    {
        DB::table('catalog_attributes')->where('category_id',34)->whereIn('slug',[
            'memory-type','ram-capacity','ram-speed','cas-latency','ram-voltage',
            'ram-form-factor','ram-rgb','heat-spreader',
        ])->delete();
    }
}
