<?php

return new class extends \Illuminate\Database\Migrations\Migration
{
    private const IMAGE_PATH = 'asset/front-end/img/feature-cards/feature-card-2-yAme7hi8M1C0fYvvsKSWi4cZ.jpg';

    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('homepage_feature_cards')) {
            return;
        }

        // The live database may still contain the original text-only default.
        // Do not overwrite an image that an administrator has already chosen.
        \Illuminate\Support\Facades\DB::table('homepage_feature_cards')
            ->where('name', 'Fast Nationwide Delivery')
            ->where(function ($query) {
                $query->whereNull('image_path')->orWhere('image_path', '');
            })
            ->update(['image_path' => self::IMAGE_PATH, 'updated_at' => now()]);
    }

    public function down(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('homepage_feature_cards')) {
            return;
        }

        \Illuminate\Support\Facades\DB::table('homepage_feature_cards')
            ->where('name', 'Fast Nationwide Delivery')
            ->where('image_path', self::IMAGE_PATH)
            ->update(['image_path' => null, 'updated_at' => now()]);
    }
};
