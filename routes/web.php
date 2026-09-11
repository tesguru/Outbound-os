<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\GmailAccountController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\DomainController;
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

    // Dashboard
    Route::get('/dashboard', function () {
        $user = Auth::user();

        $totalRecipients = \App\Models\Recipient::whereHas('campaign', fn($q) => $q->where('user_id', $user->id))->count();
        $draftsCreated   = \App\Models\Recipient::whereHas('campaign', fn($q) => $q->where('user_id', $user->id))->where('status', 'draft_created')->count();
        $sent            = \App\Models\Recipient::whereHas('campaign', fn($q) => $q->where('user_id', $user->id))->whereIn('status', ['sent', 'replied'])->count();
        $replied         = \App\Models\Recipient::whereHas('campaign', fn($q) => $q->where('user_id', $user->id))->where('status', 'replied')->count();

        $totalLeads  = $user->leads()->count();
        $soldLeads   = $user->leads()->where('status', 'sold')->count();
        $totalDomains = $user->domains()->count();
        $soldDomains = $user->domains()->where('status', 'sold')->count();
        $domainSpent = (float) $user->domains()->sum('price');
        $saleRevenue = (float) $user->domains()->where('status', 'sold')->sum('sold_price');

        // Consistency stats — activity days based on when recipients were marked sent/replied
        $activityDays = \App\Models\Recipient::whereHas('campaign', fn($q) => $q->where('user_id', $user->id))
            ->whereIn('status', ['sent', 'replied'])
            ->whereNotNull('updated_at')
            ->pluck('updated_at')
            ->map(fn($d) => $d->toDateString())
            ->unique();

        $streak = 0;
        $cursor = now()->toDateString();
        if ($activityDays->isEmpty()) {
            $streak = 0;
        } else {
            while ($activityDays->contains($cursor)) {
                $streak++;
                $cursor = \Carbon\Carbon::parse($cursor)->subDay()->toDateString();
            }
        }

        $sentThisWeek = \App\Models\Recipient::whereHas('campaign', fn($q) => $q->where('user_id', $user->id))
            ->whereIn('status', ['sent', 'replied'])
            ->where('updated_at', '>=', now()->startOfWeek())
            ->count();

        $recentReplies = \App\Models\Recipient::whereHas('campaign', fn($q) => $q->where('user_id', $user->id))
            ->where('status', 'replied')
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        $quotes = [
            "The domain you're selling is one email away from its new owner.",
            "Every sent email is a swing of the bat. Keep swinging.",
            "Sold domains started as unsent drafts.",
            "Consistency beats intensity — one email a day wins.",
            "Replies are earned by senders who refused to stop.",
            "Nope isn't a failure, it's a filter. Keep sending.",
            "The best sellers in the world are just the ones who sent more.",
            "Your next SOLD could be sitting in your unsent drafts right now.",
        ];
        $quote = $quotes[now()->dayOfYear % count($quotes)];

        $stats = compact(
            'totalRecipients', 'draftsCreated', 'sent', 'replied',
            'totalLeads', 'soldLeads', 'totalDomains', 'soldDomains',
            'domainSpent', 'saleRevenue', 'streak', 'sentThisWeek',
            'recentReplies', 'quote'
        );

        return view('dashboard', $stats);
    })->name('dashboard');

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