<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductListing;
use App\Models\PriceHistory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Logitech MX Master 3 Sem Fio',
                'image_url' => 'https://m.media-amazon.com/images/I/61ni3t1ryQL._AC_SL1500_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B07S395RWD',
                        'current_price' => 646.46,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'Monitor Gamer LG UltraGear 27" Full HD 144Hz',
                'image_url' => 'https://m.media-amazon.com/images/W/BW_MEDIAX_AVIF_MEASUREMENT_1306696-T2/images/I/51TxRQnL7AL._AC_SX450_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B0G3144L7C',
                        'current_price' => 701.10,
                        'currency' => 'BRL',
                    ],
                ],
            ],
        ];

        foreach ($products as $productData) {
            $listings = $productData['listings'];
            unset($productData['listings']);

            $product = Product::create($productData);

            foreach ($listings as $listingData) {
                $listing = ProductListing::create([
                    'product_id' => $product->id,
                    ...$listingData,
                ]);

                // Create 3 price history entries for each listing
                $basePrice = $listingData['current_price'];
                $priceVariations = [
                    $basePrice * 1.10, // 10% higher
                    $basePrice * 1.05, // 5% higher
                    $basePrice,        // current price
                ];

                foreach ($priceVariations as $index => $price) {
                    PriceHistory::create([
                        'listing_id' => $listing->id,
                        'price' => round($price, 2),
                        'currency' => $listingData['currency'],
                        'scraped_at' => now()->subDays(3 - $index),
                    ]);
                }
            }
        }
    }
}
