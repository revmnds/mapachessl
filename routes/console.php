<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('cleanup:certificates')->hourly();
Schedule::command('notify:summary')->dailyAt('09:00')->timezone(config('services.telegram.timezone'));
