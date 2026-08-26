<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PcBuilderConfigurationService
{
    public const SLOTS_KEY = 'pc_builder_slots';
    public const RULES_KEY = 'pc_builder_rules';

    public function defaultSlots(): array
    {
        return [
            ['key'=>'processor','label'=>'Processor','category'=>'processor','category_id'=>null,'sub_category_id'=>null,'required'=>true,'icon'=>'cog','icon_image'=>null],
            ['key'=>'cooler','label'=>'CPU Cooler','category'=>'cpu cooler','category_id'=>null,'sub_category_id'=>null,'required'=>false,'icon'=>'refresh','icon_image'=>null],
            ['key'=>'motherboard','label'=>'Motherboard','category'=>'motherboard','category_id'=>null,'sub_category_id'=>null,'required'=>true,'icon'=>'sitemap','icon_image'=>null],
            ['key'=>'ram','label'=>'Memory (RAM)','category'=>'ram','category_id'=>null,'sub_category_id'=>null,'required'=>true,'icon'=>'list','icon_image'=>null],
            ['key'=>'storage','label'=>'Primary Storage','category'=>'ssd','category_id'=>null,'sub_category_id'=>null,'required'=>true,'icon'=>'hdd-o','icon_image'=>null],
            ['key'=>'graphics','label'=>'Graphics Card','category'=>'graphics card','category_id'=>null,'sub_category_id'=>null,'required'=>false,'icon'=>'picture-o','icon_image'=>null],
            ['key'=>'power','label'=>'Power Supply','category'=>'power supply','category_id'=>null,'sub_category_id'=>null,'required'=>true,'icon'=>'bolt','icon_image'=>null],
            ['key'=>'casing','label'=>'Casing','category'=>'casing','category_id'=>null,'sub_category_id'=>null,'required'=>true,'icon'=>'archive','icon_image'=>null],
            ['key'=>'hdd','label'=>'Additional HDD','category'=>'hdd','category_id'=>null,'sub_category_id'=>null,'required'=>false,'icon'=>'hdd-o','icon_image'=>null],
            ['key'=>'monitor','label'=>'Monitor','category'=>'monitor','category_id'=>null,'sub_category_id'=>null,'required'=>false,'icon'=>'desktop','icon_image'=>null],
        ];
    }

    public function defaultRules(): array
    {
        return [['name'=>'Processor and motherboard socket','left_slot'=>'processor','left_attribute'=>'socket','right_slot'=>'motherboard','right_attribute'=>'socket','message'=>'Processor and motherboard socket values do not match.','enabled'=>true]];
    }

    public function slots(): array
    {
        $stored = DB::table('site_settings')->where('setting_key', self::SLOTS_KEY)->value('setting_value');
        $slots = json_decode((string) $stored, true);
        if (!is_array($slots) || !$slots) return $this->defaultSlots();
        return collect($this->defaultSlots())->map(function ($default) use ($slots) {
            $saved = collect($slots)->firstWhere('key', $default['key']) ?: [];
            return array_merge($default, is_array($saved) ? $saved : []);
        })->values()->all();
    }

    public function rules(): array
    {
        $stored = DB::table('site_settings')->where('setting_key', self::RULES_KEY)->value('setting_value');
        $rules = json_decode((string) $stored, true);
        return is_array($rules) ? array_values($rules) : $this->defaultRules();
    }

    public function save(array $slots, array $rules): void
    {
        foreach ([$this::SLOTS_KEY => $slots, $this::RULES_KEY => $rules] as $key => $value) {
            DB::table('site_settings')->updateOrInsert(['setting_key'=>$key], ['setting_value'=>json_encode($value), 'created_at'=>now(), 'updated_at'=>now()]);
        }
    }
}
