<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('orders:auto-complete')->hourly();

Schedule::command('payments:reconcile --limit=50 --interval=6')
    ->everyTenMinutes()
    ->withoutOverlapping();
