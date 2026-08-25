<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Lightweight keep-alive target for uptime monitors (e.g. UptimeRobot) on
// Render's free plan, which spins the service down after ~15 min idle.
// No auth, no DB query — just proves the process is up.
Route::get('/ping', function () {
    return response()->json(['status' => 'ok', 'time' => now()->toISOString()]);
});
