<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $hpId = DB::table('manufacturer')->where('manufacturer_name', 'HP')->value('manufacturer_id');
            if (! $hpId) return;

            foreach (['OMEN', 'Victus'] as $name) {
                DB::table('product_series')->updateOrInsert(
                    ['manufacturer_id' => $hpId, 'name' => $name],
                    ['is_active' => 1, 'created_at' => now(), 'updated_at' => now()]
                );
                $seriesId = DB::table('product_series')->where('manufacturer_id', $hpId)->where('name', $name)->value('id');
                $oldBrandId = DB::table('manufacturer')->where('manufacturer_name', $name)->value('manufacturer_id');

                if ($oldBrandId) {
                    DB::table('product')->where('manufacturer_id', $oldBrandId)->update(['manufacturer_id' => $hpId, 'product_series_id' => $seriesId]);
                    DB::table('product_series')->where('manufacturer_id', $oldBrandId)->delete();
                    DB::table('manufacturer')->where('manufacturer_id', $oldBrandId)->delete();
                }
            }
        });
    }

    public function down(): void
    {
        // Deliberately non-destructive: migrated product assignments remain valid.
    }
};
