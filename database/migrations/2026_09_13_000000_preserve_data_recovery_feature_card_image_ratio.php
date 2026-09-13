<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('homepage_feature_cards')) {
            return;
        }

        // The Data Recovery artwork is an A4 landscape image. Containing it
        // keeps the complete design visible inside the orange card background.
        DB::table('homepage_feature_cards')
            ->where('name', 'Data Recovery')
            ->where('card_type', 'IMAGE')
            ->whereNotNull('image_path')
            ->where('image_path', '<>', '')
            ->update([
                'image_fit' => 'CONTAIN',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('homepage_feature_cards')) {
            return;
        }

        DB::table('homepage_feature_cards')
            ->where('name', 'Data Recovery')
            ->where('card_type', 'IMAGE')
            ->whereNotNull('image_path')
            ->where('image_path', '<>', '')
            ->update([
                'image_fit' => 'COVER',
                'updated_at' => now(),
            ]);
    }
};
