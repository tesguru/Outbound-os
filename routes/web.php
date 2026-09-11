<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\GmailAccountController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\DomainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ============================================================
// PUBLIC ROUTES
// ============================================================
Route::get('/', function () {
    return view('login');
})->name('login');

Route::get('/auth/google', [GoogleController::class, 'redirect'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
Route::get('/auth/logout', [GoogleController::class, 'logout'])->name('logout');

// ============================================================
// PROTECTED ROUTES
// ============================================================
Route::middleware('auth')->group(function () {

    // Dashboard (view computes its own stats so it works with any cached route)
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::post('/dashboard/target', function (Request $request) {
        $request->validate(['weekly_email_target' => 'required|integer|min:1|max:1000']);
        Auth::user()->update(['weekly_email_target' => $request->integer('weekly_email_target')]);
        return back()->with('success', '✅ Weekly target updated!');
    })->name('dashboard.target');

    // Add Gmail Account
    Route::get('/auth/google/add-account', [GoogleController::class, 'redirectAccount'])->name('google.add-account');

    // Gmail Accounts
    Route::get('/gmail-accounts', [GmailAccountController::class, 'index'])->name('gmail-accounts.index');
    Route::delete('/gmail-accounts/{id}', [GmailAccountController::class, 'destroy'])->name('gmail-accounts.destroy');
    Route::post('/gmail-accounts/{id}/toggle', [GmailAccountController::class, 'toggleActive'])->name('gmail-accounts.toggle');
    Route::post('/gmail-accounts/{id}/limit', [GmailAccountController::class, 'updateLimit'])->name('gmail-accounts.limit');

    // Templates
    Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/create', [TemplateController::class, 'create'])->name('templates.create');
    Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
    Route::get('/templates/{id}/edit', [TemplateController::class, 'edit'])->name('templates.edit');
    Route::put('/templates/{id}', [TemplateController::class, 'update'])->name('templates.update');
    Route::delete('/templates/{id}', [TemplateController::class, 'destroy'])->name('templates.destroy');

    // Leads (master lead list)
    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
    Route::post('/leads/bulk', [LeadController::class, 'bulkStore'])->name('leads.bulk-store');
    Route::put('/leads/{id}', [LeadController::class, 'update'])->name('leads.update');
    Route::patch('/leads/{id}/status', [LeadController::class, 'status'])->name('leads.status');
    Route::delete('/leads/{id}', [LeadController::class, 'destroy'])->name('leads.destroy');

    // Domains (Domain CRM)
    Route::get('/domains', [DomainController::class, 'index'])->name('domains.index');
    Route::post('/domains', [DomainController::class, 'store'])->name('domains.store');
    Route::post('/domains/create-from-campaign', [DomainController::class, 'createFromCampaign'])->name('domains.create-from-campaign');
    Route::post('/domains/{id}/toggle-sold', [DomainController::class, 'toggleSold'])->name('domains.toggle-sold');
    Route::put('/domains/{id}', [DomainController::class, 'update'])->name('domains.update');
    Route::delete('/domains/{id}', [DomainController::class, 'destroy'])->name('domains.destroy');

    // Campaigns
    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
    Route::get('/campaigns/create', [CampaignController::class, 'create'])->name('campaigns.create');
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::get('/campaigns/{id}', [CampaignController::class, 'show'])->name('campaigns.show');
    Route::delete('/campaigns/{id}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');
Route::get('/campaigns/{id}/sequences', [CampaignController::class, 'manageSequences'])->name('campaigns.sequences');
Route::post('/campaigns/{id}/sequences', [CampaignController::class, 'updateSequences'])->name('campaigns.sequences.update');

Route::post('/campaigns/{id}/recipients/drafts-batch', [RecipientController::class, 'createDraftsBatch'])->name('campaigns.recipients.drafts-batch');
Route::post('/campaigns/{id}/recipients/followups-batch', [RecipientController::class, 'createFollowupsBatch'])->name('campaigns.recipients.followups-batch');
    // Recipients
    Route::get('/campaigns/{id}/recipients/paste', [RecipientController::class, 'paste'])->name('campaigns.recipients.paste');
    Route::post('/campaigns/{id}/recipients/analyse', [RecipientController::class, 'analyse'])->name('campaigns.recipients.analyse');
    Route::post('/campaigns/{id}/recipients/confirm', [RecipientController::class, 'confirm'])->name('campaigns.recipients.confirm');
    Route::post('/campaigns/{id}/recipients/drafts', [RecipientController::class, 'createDrafts'])->name('campaigns.recipients.drafts');
    Route::post('/campaigns/{id}/recipients/mark-sent', [RecipientController::class, 'markSent'])->name('campaigns.recipients.mark-sent');
    Route::post('/campaigns/{id}/recipients/mark-replied', [RecipientController::class, 'markReplied'])->name('campaigns.recipients.mark-replied');
    Route::post('/campaigns/{id}/recipients/followups', [RecipientController::class, 'createFollowups'])->name('campaigns.recipients.followups');

});