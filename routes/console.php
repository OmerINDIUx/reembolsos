<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reimbursements:send-reminders')->wednesdays()->at('12:01');
Schedule::command('reimbursements:process-batches')->everyMinute();
