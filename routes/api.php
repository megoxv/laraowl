<?php

use App\Http\Controllers\Api\IngestController;
use Illuminate\Support\Facades\Route;

Route::post('/ingest', IngestController::class)
    ->middleware('laraowl.token');

Route::post('/records', IngestController::class)
    ->middleware('laraowl.token');
