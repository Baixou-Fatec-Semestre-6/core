<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'Amazon', 'base_url' => 'https://www.amazon.com.br'],
        ] as $storeData) {
            Store::query()->firstOrCreate(
                ['name' => $storeData['name']],
                ['base_url' => $storeData['base_url']],
            );
        }
    }
}
