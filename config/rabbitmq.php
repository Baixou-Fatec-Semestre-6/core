<?php

// config/rabbitmq.php

return [
    'host' => env('RABBITMQ_HOST', '127.0.0.1'),
    'port' => env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'exchange' => env('RABBITMQ_EXCHANGE_COMMANDS', 'scrape_commands'),
    'results_queue' => 'core.scrape.results',
];
