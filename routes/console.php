<?php

use Illuminate\Support\Facades\Schedule;
use App\Services\ScrapeDispatcher;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote');

Schedule::call(fn () => app(ScrapeDispatcher::class)->dispatchDue())
    ->name('scrape-dispatch')
    ->everyFifteenMinutes()
    ->withoutOverlapping();