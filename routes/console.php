<?php

use App\Models\Notification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Suppression automatique quotidienne des notifications de plus de 3 jours
Schedule::command('model:prune', [
    '--model' => [Notification::class],
])->daily();
