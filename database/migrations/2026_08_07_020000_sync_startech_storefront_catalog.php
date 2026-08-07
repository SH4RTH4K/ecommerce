<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncStartechStorefrontCatalog extends Migration
{
    public function up()
    {
        if (! Schema::hasColumn('sub_category', 'display_order')) {
            Schema::table('sub_category', function (Blueprint $table) {
                $table->unsignedSmallInteger('display_order')->default(0)->after('sub_category_name');
            });
        }

        $now = date('Y-m-d H:i:s');
        $categories = [
            ['names' => ['Desktops', 'Desktop'], 'name' => 'Desktop', 'description' => 'Desktop computers and custom PC systems.', 'icon' => 'fa-desktop', 'order' => 1],
            ['names' => ['Laptop & Notebook', 'Laptop'], 'name' => 'Laptop', 'description' => 'Laptops, notebooks, and portable computers.', 'icon' => 'fa-laptop', 'order' => 2],
            ['names' => ['Components', 'Component'], 'name' => 'Component', 'description' => 'Computer components and upgrade parts.', 'icon' => 'fa-cogs', 'order' => 3],
            ['names' => ['Monitor'], 'name' => 'Monitor', 'description' => 'Computer monitors and display accessories.', 'icon' => 'fa-desktop', 'order' => 4],
            ['names' => ['Power'], 'name' => 'Power', 'description' => 'UPS, power stations, stabilizers, and power protection.', 'icon' => 'fa-bolt', 'order' => 5],
            ['names' => ['Mobile', 'Phone'], 'name' => 'Phone', 'description' => 'Mobile phones and related devices.', 'icon' => 'fa-mobile', 'order' => 6],
            ['names' => ['Tablet PC', 'Tablet'], 'name' => 'Tablet', 'description' => 'Tablets and tablet accessories.', 'icon' => 'fa-tablet', 'order' => 7],
            ['names' => ['Office Equipment'], 'name' => 'Office Equipment', 'description' => 'Office equipment and business solutions.', 'icon' => 'fa-briefcase', 'order' => 8],
            ['names' => ['Camera'], 'name' => 'Camera', 'description' => 'Digital cameras and photography equipment.', 'icon' => 'fa-camera', 'order' => 9],
            ['names' => ['Security Camera', 'Security'], 'name' => 'Security', 'description' => 'Security cameras and surveillance equipment.', 'icon' => 'fa-shield', 'order' => 10],
            ['names' => ['Networking'], 'name' => 'Networking', 'description' => 'Networking products and connectivity equipment.', 'icon' => 'fa-sitemap', 'order' => 11],
            ['names' => ['Software'], 'name' => 'Software', 'description' => 'Software, licenses, and digital tools.', 'icon' => 'fa-code', 'order' => 12],
            ['names' => ['Server & Networking', 'Server & Storage'], 'name' => 'Server & Storage', 'description' => 'Servers, storage, and workstation solutions.', 'icon' => 'fa-hdd-o', 'order' => 13],
            ['names' => ['Accessories'], 'name' => 'Accessories', 'description' => 'Computer and mobile accessories.', 'icon' => 'fa-plug', 'order' => 14],
            ['names' => ['Gadget'], 'name' => 'Gadget', 'description' => 'Smart gadgets and everyday technology.', 'icon' => 'fa-clock-o', 'order' => 15],
            ['names' => ['Gaming'], 'name' => 'Gaming', 'description' => 'Gaming hardware, peripherals, and consoles.', 'icon' => 'fa-gamepad', 'order' => 16],
            ['names' => ['TV'], 'name' => 'TV', 'description' => 'Televisions and home entertainment.', 'icon' => 'fa-television', 'order' => 17],
            ['names' => ['Appliance'], 'name' => 'Appliance', 'description' => 'Home and kitchen appliances.', 'icon' => 'fa-home', 'order' => 18],
            ['names' => ['Air Conditioner'], 'name' => 'Air Conditioner', 'description' => 'Air conditioners and cooling solutions.', 'icon' => 'fa-snowflake-o', 'order' => 90],
            ['names' => ['Air Cooler'], 'name' => 'Air Cooler', 'description' => 'Air coolers and fans.', 'icon' => 'fa-refresh', 'order' => 91],
            ['names' => ['Air Purifier'], 'name' => 'Air Purifier', 'description' => 'Air purifiers and indoor air care.', 'icon' => 'fa-leaf', 'order' => 92],
            ['names' => ['Access Control'], 'name' => 'Access Control', 'description' => 'Access control and attendance products.', 'icon' => 'fa-lock', 'order' => 93],
            ['names' => ['ROUTER', 'Router'], 'name' => 'ROUTER', 'description' => 'Routers and home networking devices.', 'icon' => 'fa-signal', 'order' => 94],
        ];

        foreach ($categories as $definition) {
            $existing = DB::table('category')
                ->whereIn('category_name', $definition['names'])
                ->orderBy('category_id')
                ->first();

            $data = [
                'category_name' => $definition['name'],
                'publication_status' => 1,
                'icon_class' => $definition['icon'],
                'is_featured' => 1,
                'display_order' => $definition['order'],
                'updated_at' => $now,
            ];

            if ($existing) {
                DB::table('category')->where('category_id', $existing->category_id)->update($data);
            } else {
                DB::table('category')->insert(array_merge($data, [
                    'category_description' => $definition['description'],
                    'created_at' => $now,
                ]));
            }
        }

        $categoryIds = DB::table('category')
            ->whereIn('category_name', ['Desktop', 'Laptop', 'Component', 'Monitor', 'Power', 'Accessories', 'Gadget', 'Appliance'])
            ->pluck('category_id', 'category_name');

        $subCategories = [
            'Desktop' => [
                ['AI PC', [], 10], ['Desktop Offer', [], 20], ['Star PC', [], 30], ['Gaming PC', [], 40],
                ['Brand PC', [], 50], ['All-in-One PC', ['All In One PC'], 60], ['Portable Mini PC', [], 70],
                ['Apple Mac Mini', [], 80], ['Apple iMac', [], 90], ['Apple Mac Studio', [], 100], ['Apple Mac Pro', [], 110],
            ],
            'Laptop' => [
                ['All Laptop', ['Laptop'], 10], ['Gaming Laptop', [], 20], ['Premium Ultrabook', ['Ultrabook'], 30],
                ['Laptop Bag', [], 40], ['Laptop Accessories', [], 50], ['Laptop Finder', [], 60],
            ],
            'Component' => [
                ['Processor', [], 10], ['CPU Cooler', [], 20], ['Motherboard', [], 30], ['Graphics Card', [], 40],
                ['RAM (Desktop)', ['RAM'], 50], ['RAM (Laptop)', ['Laptop RAM'], 60], ['Power Supply', [], 70],
                ['Hard Disk Drive', [], 80], ['Portable Hard Disk Drive', [], 90], ['SSD', [], 100], ['Portable SSD', [], 110],
                ['Casing', [], 120], ['Casing Cooler', [], 130], ['Optical Disk Drive', ['Optical HDD'], 140],
                ['Vertical GPU Holder', [], 150], ['Water / Liquid Cooling', [], 160],
            ],
            'Monitor' => [
                ['Gaming Monitor', [], 10], ['Curved Monitor', [], 20], ['Touch Monitor', [], 30],
                ['4K Monitor', [], 40], ['Portable Monitor', [], 50], ['Monitor Arm', [], 60],
            ],
            'Power' => [
                ['UPS', [], 10], ['Online UPS', [], 20], ['Mini UPS', [], 30], ['Portable Power Station', [], 40],
                ['IPS', [], 50], ['UPS Battery', [], 60], ['Voltage Stabilizer', [], 70], ['Inverter', [], 80], ['Solar Panel', [], 90],
            ],
            'Accessories' => [
                ['Keyboard', ['Keyboards'], 10], ['Mouse', [], 20], ['Headphone', [], 30],
                ['Speaker & Home Theater', ['Speaker And Home Theater'], 40], ['Bluetooth Speakers', ['Bluetooth Speaker'], 50],
                ['Microphone', [], 60], ['Pen Drive', [], 70], ['Mouse Pad', [], 80], ['Webcam', [], 90],
            ],
            'Gadget' => [
                ['Smart Watch', [], 10], ['Earphone', [], 20], ['Earbuds', [], 30], ['Neckband', [], 40], ['Power Bank', [], 50],
            ],
            'Appliance' => [
                ['AC', [], 10], ['Air Cooler', [], 20], ['Air Curtain', [], 30], ['Fan', [], 40], ['Air Fryer', [], 50],
                ['Oven', [], 60], ['Cooker', [], 70], ['Induction Cooker', [], 80], ['Toaster', [], 90],
                ['Blender & Grinder', [], 100], ['Juicer', [], 110], ['Electric Kettle', [], 120], ['Coffee Maker', [], 130],
                ['Fridge', [], 140], ['Washing Machine', [], 150], ['Vacuum Cleaner', [], 160], ['Geyser', [], 170],
                ['Room Heater', [], 180], ['Air Purifier', [], 190], ['Dehumidifier', [], 200], ['Iron', [], 210], ['Sewing Machine', [], 220],
            ],
        ];

        foreach ($subCategories as $categoryName => $definitions) {
            $categoryId = $categoryIds->get($categoryName);
            if (! $categoryId) continue;

            foreach ($definitions as [$name, $aliases, $order]) {
                $lookupNames = array_values(array_unique(array_merge([$name], $aliases)));
                $existing = DB::table('sub_category')
                    ->where('category_id', $categoryId)
                    ->where(function ($query) use ($lookupNames) {
                        foreach ($lookupNames as $index => $lookupName) {
                            $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                            $query->{$method}('LOWER(sub_category_name) = ?', [strtolower($lookupName)]);
                        }
                    })
                    ->orderBy('sub_category_id')
                    ->first();

                $data = [
                    'sub_category_name' => $name,
                    'publication_status' => 1,
                    'display_order' => $order,
                    'deleted_at' => null,
                    'updated_at' => $now,
                ];

                if ($existing) {
                    DB::table('sub_category')->where('sub_category_id', $existing->sub_category_id)->update($data);
                } else {
                    DB::table('sub_category')->insert(array_merge($data, [
                        'category_id' => $categoryId,
                        'created_at' => $now,
                    ]));
                }
            }
        }
    }

    public function down()
    {
        // This migration synchronizes catalog labels and is intentionally kept
        // non-destructive on rollback so existing product/category links remain safe.
    }
}
