<?php

use App\Services\ScrapeDispatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote');

$scrapeDispatchEvent = Schedule::call(fn () => app(ScrapeDispatcher::class)->dispatchDue())
    ->name('scrape-dispatch')
    ->everyTwoHours()
    ->withoutOverlapping();

$scrapeDispatchEvent->after(function () use ($scrapeDispatchEvent) {
    Cache::forever('scrape-dispatch:last-run', now()->timestamp);
    Cache::forever('scrape-dispatch:next-run', $scrapeDispatchEvent->nextRunDate()->getTimestamp());
});