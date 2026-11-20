<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('orders:auto-complete')->hourly();

Schedule::command('orders:remind-overdue-deliveries --limit=200')
    ->dailyAt('09:00')
    ->withoutOverlapping();

Schedule::command('orders:expire-unpaid --limit=100')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('payments:reconcile --limit=50 --interval=6')
    ->everyTenMinutes()
    ->withoutOverlapping();
