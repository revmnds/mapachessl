<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('cleanup:certificates')->hourly();
