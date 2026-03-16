<?php

use Illuminate\Support\Facades\Route;
use Modules\CallCenter\Http\Controllers\Api\CallCenterApiController;

Route::prefix('contact-center')->group(function () {
    Route::get('/queues', [CallCenterApiController::class, 'queues']);
    Route::get('/queues/{queue}', [CallCenterApiController::class, 'showQueue']);
    Route::get('/agents', [CallCenterApiController::class, 'agents']);
    Route::get('/agents/{agent}', [CallCenterApiController::class, 'showAgent']);
    Route::get('/wallboard', [CallCenterApiController::class, 'wallboard']);
});
