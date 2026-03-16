<?php

use Illuminate\Support\Facades\Route;
use Modules\CallCenter\Http\Controllers\CallCenterController;

Route::middleware('auth')->group(function () {
    Route::get('/contact-center', [CallCenterController::class, 'index'])->name('contact-center.index');
    Route::get('/contact-center/settings', [CallCenterController::class, 'settings'])->name('contact-center.settings');

    Route::post('/contact-center/queues', [CallCenterController::class, 'storeQueue'])->name('contact-center.queues.store');
    Route::put('/contact-center/queues/{queue}', [CallCenterController::class, 'updateQueue'])->name('contact-center.queues.update');
    Route::delete('/contact-center/queues/{queue}', [CallCenterController::class, 'destroyQueue'])->name('contact-center.queues.destroy');

    Route::post('/contact-center/agents', [CallCenterController::class, 'storeAgent'])->name('contact-center.agents.store');
    Route::put('/contact-center/agents/{agent}', [CallCenterController::class, 'updateAgent'])->name('contact-center.agents.update');
    Route::delete('/contact-center/agents/{agent}', [CallCenterController::class, 'destroyAgent'])->name('contact-center.agents.destroy');
    Route::post('/contact-center/agents/{agent}/pause', [CallCenterController::class, 'pauseAgentStatus'])->name('contact-center.agents.pause');
    Route::post('/contact-center/agents/{agent}/resume', [CallCenterController::class, 'resumeAgentStatus'])->name('contact-center.agents.resume');

    Route::post('/contact-center/users', [CallCenterController::class, 'storeUser'])->name('contact-center.user.store');
    Route::post('/contact-center/pause-reasons', [CallCenterController::class, 'storePauseReason'])->name('contact-center.pause-reasons.store');
    Route::post('/contact-center/callbacks', [CallCenterController::class, 'storeCallback'])->name('contact-center.callbacks.store');
    Route::put('/contact-center/callbacks/{callback}', [CallCenterController::class, 'updateCallback'])->name('contact-center.callbacks.update');
    Route::post('/contact-center/monitoring', [CallCenterController::class, 'startMonitoring'])->name('contact-center.monitoring.store');
});
