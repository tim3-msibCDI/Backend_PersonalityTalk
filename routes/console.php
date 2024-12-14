<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Schedule::command('update:transaction-status')->everyMinute();
Schedule::command('update:consultation-status')->everyMinute();
