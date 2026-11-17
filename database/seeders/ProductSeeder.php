<?php

namespace Database\Seeders;

use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\ProductListing;
use App\Models\Store;
use Illuminate\Database\Seeder;
use InvalidArgumentException;

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
            [
                'name' => 'Fujifilm Filme Instax Mini com 20 Fotos, 8.6 x 5.4 cm',
                'image_url' => 'https://m.media-amazon.com/images/I/61cpxtAOkFL._AC_SY879_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B0000C73CQ',
                        'current_price' => 124.45,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'CABO HDMI 2.0 4K 3D 19 PINOS - 5 METROS',
                'image_url' => 'https://m.media-amazon.com/images/I/61hOvsR6HKL._AC_SX679_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B0BSH7SQFY',
                        'current_price' => 22.64,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'Mouse Pad Preto',
                'image_url' => 'https://m.media-amazon.com/images/I/31OpUHaGqQL._AC_SX679_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B0DGW54YGZ',
                        'current_price' => 7.85,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'PlayStation®5 Slim Disk - Pacote ASTRO BOT e Gran Turismo 7',
                'image_url' => 'https://m.media-amazon.com/images/I/71IkUBtdDGL._AC_SX679_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B0F8R9NDXC',
                        'current_price' => 4691.91,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'ELG, HS8K20, Cabo HDMI 2.1V, Áudio e Vídeo, 8K, UltraHD, 3D Ready, 2 metros, High Speed Bandwidth 48Gbps, Conectores Banhados a Ouro 24k, Preto',
                'image_url' => 'https://m.media-amazon.com/images/I/510FynkpTqL._AC_SX679_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B07Y2MZ4L6',
                        'current_price' => 54.90,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'Samsung Smart TV 75" Crystal UHD 4K U8100F 2025',
                'image_url' => 'https://m.media-amazon.com/images/I/81QsB0GMcyL._AC_SX679_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B0F5X4NPK2',
                        'current_price' => 4699.00,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'Romantic Crown Mochila Masculina de Viagem para Notebook',
                'image_url' => 'https://m.media-amazon.com/images/I/613o1RrqyAL._AC_SX679_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B0F2N7L7HQ',
                        'current_price' => 108.26,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'Smart TV TCL 55 Polegadas QLED Mini LED 4K A400M WiFi Bluetooth Google TV HDR10+ 144 Hz VRR 55A400M',
                'image_url' => 'https://m.media-amazon.com/images/I/81YQ+AnOI9L._AC_SX679_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B0GYQ2PLW3',
                        'current_price' => 3399.00,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'Amazon Echo Dot Max (Geração mais recente), smart speaker com Alexa, som envolvente e hub de casa inteligente integrado, ideal para sua sala de estar, Cor Grafite, com Acesso Antecipado à Alexa+',
                'image_url' => 'https://m.media-amazon.com/images/I/71J0hF-lw+L._AC_SY879_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B0DKLQT9XR',
                        'current_price' => 719.00,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'Smart TV TCL 43 Polegadas Full HD QLED S5K WiFi Bluetooth Google TV 2 HDMI HDR10 Dolby Audio 43S5K',
                'image_url' => 'https://m.media-amazon.com/images/I/81yyXps2lKL._AC_SX679_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B0FTGK5XK2',
                        'current_price' => 1519.05,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'Nintendo, Console, Nintendo Switch 2, Nacional, Portátil e Doméstico, Bateria de até 9 Horas, Inclui Joy-Con com Suporte e Alças, 1 ano de Garantia - Modelo Nacional de Tomada',
                'image_url' => 'https://m.media-amazon.com/images/I/61oPb9El-SL._AC_SX679_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B0F6D7MQQB',
                        'current_price' => 4128.70,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'Tablet Samsung Galaxy TAB A11+, 128GB, 6GB RAM, WiFi',
                'image_url' => 'https://m.media-amazon.com/images/I/51TU36U5C1L._AC_SX679_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B0G4SLJNFF',
                        'current_price' => 1421.96,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'PlayStation®5 Pro',
                'image_url' => 'https://m.media-amazon.com/images/I/51dfg52K-cL._AC_SX679_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B0DH8J5YGH',
                        'current_price' => 6981.50,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'Havit Headphone Fone de Ouvido H2232d, Gamer, Iluminação RGB, com Microfone, Falante de 50mm, Conector 3.5mm, HAVIT, HV-H2232d, Cabo USB para Iluminação',
                'image_url' => 'https://m.media-amazon.com/images/I/71+giACAGrL._AC_SX679_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B07N78G8GB',
                        'current_price' => 73.59,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'Smartphone Motorola Moto g86 5G - 256GB 24GB (8GB RAM+16GB Ram Boost) Tela 1.5K pOLED, 50MP Sony camera OIS Moto AI, videos em 4K, IP68 + IP69 - Grafite',
                'image_url' => 'https://m.media-amazon.com/images/I/514xNqIfRZL._AC_SX679_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B0FCYP5FB2',
                        'current_price' => 1599.00,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'PHILIPS, Fone de Ouvido com Microfone, TAUE101BK/00, Com fio de 1,2 metros, intra-auricular, Preto',
                'image_url' => 'https://m.media-amazon.com/images/I/51+W7A115SL._AC_SX679_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B084S6BCJN',
                        'current_price' => 26.60,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'TP-Link Tapo C200, Camera de Segurança Wi-Fi 360º, 1080p Full HD, Visão Noturna, Audio de duas vias, Detecção de Movimentos, Trabalha com Alexa, 512G, Armazenamento na Nuvem',
                'image_url' => 'https://m.media-amazon.com/images/I/41WQe9NiU6L._AC_SX679_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B07XLML2YS',
                        'current_price' => 162.37,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'Apple iPhone 14 (128 GB) – Meia-Noite (Seminovo)',
                'image_url' => 'https://m.media-amazon.com/images/I/61cwywLZR-L._AC_SX679_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B0FTKCF3QJ',
                        'current_price' => 2588.98,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'EPSON, Kit de garrafas de tintas originais Epson , Magenta, Amarelo, Ciano, Compacto, T544520-BR',
                'image_url' => 'https://m.media-amazon.com/images/I/71J6JbK1DWL._AC_SX679_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B09JKXGSLG',
                        'current_price' => 139.90,
                        'currency' => 'BRL',
                    ],
                ],
            ],
            [
                'name' => 'Celular Samsung Galaxy A57 5G 128GB, 8GB RAM, Recursos AI (Cinza)',
                'image_url' => 'https://m.media-amazon.com/images/I/51LpX2qhRfL._AC_SX679_.jpg',
                'listings' => [
                    [
                        'store' => 'Amazon',
                        'url' => 'https://www.amazon.com.br/dp/B0GVT1T3PB',
                        'current_price' => 1877.32,
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
                $store = Store::query()->firstOrCreate(
                    ['name' => $listingData['store']],
                    ['base_url' => $this->baseUrl($listingData['url'])],
                );

                $listing = ProductListing::create([
                    'product_id' => $product->id,
                    'store_id' => $store->id,
                    ...$listingData,
                ]);

                $basePrice = $listingData['current_price'];
                $priceVariations = [
                    $basePrice * 1.10,
                    $basePrice * 1.05,
                    $basePrice,
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

    private function baseUrl(string $url): string
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST);

        if (! in_array(strtolower((string) $scheme), ['http', 'https'], true) || ! is_string($host)) {
            throw new InvalidArgumentException('Product listing URL must have an HTTP or HTTPS host.');
        }

        return strtolower($scheme).'://'.$host;
    }
}
