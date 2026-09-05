<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            'Rooftop' => 6,
            'Terrasse' => 8,
            'Intérieur' => 5,
            'Bar' => 4,
        ];

        foreach ($zones as $zoneName => $tableCount) {
            $zone = Zone::firstOrCreate(['name' => $zoneName], ['is_active' => true]);

            for ($i = 1; $i <= $tableCount; $i++) {
                RestaurantTable::firstOrCreate(
                    ['zone_id' => $zone->id, 'name' => strtoupper(substr($zoneName, 0, 1)).$i],
                    ['capacity' => fake()->randomElement([2, 2, 4, 4, 6, 8]), 'status' => 'available']
                );
            }
        }

        $categories = [
            'Entrées' => 'food',
            'Tajines & Plats' => 'food',
            'Grillades' => 'food',
            'Desserts' => 'food',
            'Cocktails' => 'drink',
            'Mocktails' => 'drink',
            'Boissons chaudes' => 'drink',
            'Softs & Eaux' => 'drink',
        ];

        $categoryModels = [];
        foreach ($categories as $name => $type) {
            $categoryModels[$name] = Category::firstOrCreate(['name' => $name], ['type' => $type, 'is_active' => true]);
        }

        $products = [
            ['Entrées', 'Briouates au fromage', 45],
            ['Entrées', 'Salade Dune', 60],
            ['Tajines & Plats', 'Tajine Poulet Citron Confit', 130],
            ['Tajines & Plats', 'Tajine Agneau Pruneaux', 160],
            ['Grillades', 'Brochettes de Bœuf', 150],
            ['Grillades', 'Côte de Bœuf (partage)', 320],
            ['Desserts', 'Pastilla au Lait', 55],
            ['Desserts', 'Fondant au Chocolat', 60],
            ['Cocktails', 'Dune Sunset', 110],
            ['Cocktails', 'Mojito Passion', 100],
            ['Mocktails', 'Virgin Mojito', 70],
            ['Boissons chaudes', 'Thé à la Menthe', 35],
            ['Softs & Eaux', 'Eau minérale 50cl', 25],
            ['Softs & Eaux', 'Jus d\'orange frais', 45],
        ];

        foreach ($products as $index => [$categoryName, $name, $price]) {
            Product::firstOrCreate(
                ['sku' => 'DUNE-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'category_id' => $categoryModels[$categoryName]->id,
                    'name' => $name,
                    'price' => $price,
                    'tax_rate' => 20,
                    'is_active' => true,
                ]
            );
        }

        $customers = [
            ['Youssef El Amrani', '0661234567', 'youssef.elamrani@example.com'],
            ['Salma Bennis', '0662345678', 'salma.bennis@example.com'],
            ['Karim Idrissi', '0663456789', null],
        ];

        foreach ($customers as [$name, $phone, $email]) {
            Customer::firstOrCreate(['name' => $name], ['phone' => $phone, 'email' => $email]);
        }
    }
}
