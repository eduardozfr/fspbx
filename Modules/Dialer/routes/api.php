<?php

use Illuminate\Support\Facades\Route;
use Modules\Dialer\Http\Controllers\Api\DialerApiController;

Route::prefix('dialer')->group(function () {
    Route::get('/campaigns', [DialerApiController::class, 'campaigns']);
    Route::get('/campaigns/{campaign}', [DialerApiController::class, 'showCampaign']);
    Route::post('/campaigns', [DialerApiController::class, 'storeCampaign']);
    Route::put('/campaigns/{campaign}', [DialerApiController::class, 'updateCampaign']);
    Route::delete('/campaigns/{campaign}', [DialerApiController::class, 'destroyCampaign']);

    Route::get('/leads', [DialerApiController::class, 'leads']);
    Route::get('/leads/{lead}', [DialerApiController::class, 'showLead']);
    Route::post('/leads', [DialerApiController::class, 'storeLead']);
    Route::put('/leads/{lead}', [DialerApiController::class, 'updateLead']);
    Route::delete('/leads/{lead}', [DialerApiController::class, 'destroyLead']);
});
