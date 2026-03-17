<?php

use Illuminate\Support\Facades\Route;
use Modules\Dialer\Http\Controllers\DialerController;

Route::middleware('auth')->group(function () {
    Route::get('/dialer', [DialerController::class, 'index'])->name('dialer.index');
    Route::post('/dialer/campaigns', [DialerController::class, 'storeCampaign'])->name('dialer.campaigns.store');
    Route::put('/dialer/campaigns/{campaign}', [DialerController::class, 'updateCampaign'])->name('dialer.campaigns.update');
    Route::delete('/dialer/campaigns/{campaign}', [DialerController::class, 'destroyCampaign'])->name('dialer.campaigns.destroy');

    Route::post('/dialer/leads', [DialerController::class, 'storeLead'])->name('dialer.leads.store');
    Route::delete('/dialer/leads/{lead}', [DialerController::class, 'destroyLead'])->name('dialer.leads.destroy');
    Route::post('/dialer/dispositions', [DialerController::class, 'storeDisposition'])->name('dialer.dispositions.store');
    Route::post('/dialer/attempts/{attempt}/disposition', [DialerController::class, 'dispositionAttempt'])->name('dialer.attempts.disposition');
    Route::post('/dialer/dnc', [DialerController::class, 'storeDncEntry'])->name('dialer.dnc.store');
    Route::delete('/dialer/dnc/{entry}', [DialerController::class, 'destroyDncEntry'])->name('dialer.dnc.destroy');
    Route::post('/dialer/state-rules', [DialerController::class, 'storeStateRule'])->name('dialer.state-rules.store');
    Route::put('/dialer/state-rules/{stateRule}', [DialerController::class, 'updateStateRule'])->name('dialer.state-rules.update');
    Route::post('/dialer/compliance-profiles', [DialerController::class, 'storeComplianceProfile'])->name('dialer.compliance-profiles.store');
    Route::put('/dialer/compliance-profiles/{profile}', [DialerController::class, 'updateComplianceProfile'])->name('dialer.compliance-profiles.update');
    Route::delete('/dialer/compliance-profiles/{profile}', [DialerController::class, 'destroyComplianceProfile'])->name('dialer.compliance-profiles.destroy');
    Route::post('/dialer/imports', [DialerController::class, 'importLeads'])->name('dialer.imports.store');

    Route::get('/dialer/campaigns/{campaign}/preview', [DialerController::class, 'previewNextLead'])->name('dialer.campaigns.preview');
    Route::post('/dialer/campaigns/{campaign}/dial', [DialerController::class, 'dialLead'])->name('dialer.campaigns.dial');
    Route::post('/dialer/campaigns/{campaign}/run', [DialerController::class, 'runCampaign'])->name('dialer.campaigns.run');
});
