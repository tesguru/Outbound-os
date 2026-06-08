<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignFollowupSequence;
use App\Models\CampaignWebsite;
use App\Models\GmailAccount;
use App\Models\Template;
use App\Models\Recipient;
use App\Services\GmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CampaignController extends Controller
{
    // ============================================================
    // INDEX
    // ============================================================
    public function index()
    {
        $campaigns = Campaign::where('user_id', Auth::id())
                             ->withCount('recipients')
                             ->with(['initialTemplate', 'gmailAccounts'])
                             ->orderBy('created_at', 'desc')
                             ->get();

        return view('campaigns.index', compact('campaigns'));
    }

    // ============================================================
    // CREATE
    // ============================================================
    public function create()
    {
        $gmailAccounts = GmailAccount::where('user_id', Auth::id())
                                     ->where('is_active', true)
                                     ->get();

        return view('campaigns.create', compact('gmailAccounts'));
    }

    // ============================================================
    // STORE
    // ============================================================
    public function store(Request $request)
    {
        $request->validate([
            'name'               => 'required|string|max:255',
            'domain'             => 'required|string|max:255',
            'gmail_accounts'     => 'required|array|min:1',
            'gmail_accounts.*'   => 'exists:gmail_accounts,id',
            'recipient_limits'   => 'required|array',
            'recipient_limits.*' => 'required|integer|min:1',
        ]);

        // Create the campaign
        $campaign = Campaign::create([
            'user_id'                      => Auth::id(),
            'name'                         => $request->name,
            'domain'                       => strtolower(trim($request->domain)),
            'template_type'                => null,
            'initial_template_id'          => null,
            'personal_initial_template_id' => null,
            'company_initial_template_id'  => null,
            'gmail_label_id'               => null,
            'gmail_label_name'             => null,
            'status'                       => 'active',
        ]);

        // Attach all Gmail accounts with their limits
        foreach ($request->gmail_accounts as $index => $accountId) {
            $campaign->gmailAccounts()->attach($accountId, [
                'recipient_limit' => $request->recipient_limits[$index] ?? 50,
            ]);
        }

        // ── Create Gmail label on EVERY account ──────────────────
        // Each Gmail account needs its own copy of the label.
        // We save the label ID/name from the first account as the
        // campaign reference (used for display), but all accounts
        // get the label so drafts/threads can be labelled correctly.
        $savedLabelId   = null;
        $savedLabelName = null;

        foreach ($request->gmail_accounts as $index => $accountId) {
            try {
                $gmailAccount = GmailAccount::find($accountId);

                if (!$gmailAccount) {
                    Log::warning('Campaign store: Gmail account not found', ['account_id' => $accountId]);
                    continue;
                }

                $gmailService = new GmailService($gmailAccount);
                $label        = $gmailService->getOrCreateLabel($request->name);

                if ($label['success']) {
                    Log::info('Campaign store: label created/found', [
                        'account'    => $gmailAccount->email,
                        'label_id'   => $label['label_id'],
                        'label_name' => $label['label_name'],
                    ]);

                    // Save from first account as campaign reference
                    if ($index === 0) {
                        $savedLabelId   = $label['label_id'];
                        $savedLabelName = $label['label_name'];
                    }
                } else {
                    Log::warning('Campaign store: label creation failed', [
                        'account' => $gmailAccount->email,
                        'label'   => $label,
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('Campaign store: exception creating label', [
                    'account_id' => $accountId,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        // Save label reference from first account
        if ($savedLabelId) {
            $campaign->update([
                'gmail_label_id'   => $savedLabelId,
                'gmail_label_name' => $savedLabelName,
            ]);
        }

        return redirect()->route('campaigns.recipients.paste', $campaign->id)
                         ->with('success', '✅ Campaign created! Now paste your recipients.');
    }

    // ============================================================
    // SHOW
    // ============================================================
    public function show($id)
    {
        $campaign = Campaign::where('id', $id)
                            ->where('user_id', Auth::id())
                            ->with([
                                'personalInitialTemplate',
                                'companyInitialTemplate',
                                'personalFollowupSequences.template',
                                'companyFollowupSequences.template',
                                'followupSequences.template',
                                'gmailAccounts.recipients' => function ($q) use ($id) {
                                    $q->where('campaign_id', $id);
                                },
                                'websites',
                            ])
                            ->firstOrFail();

        $statusCounts = [
            'pending'       => $campaign->recipients()->where('status', 'pending')->count(),
            'draft_created' => $campaign->recipients()->where('status', 'draft_created')->count(),
            'sent'          => $campaign->recipients()->where('status', 'sent')->count(),
            'replied'       => $campaign->recipients()->where('status', 'replied')->count(),
            'bounced'       => $campaign->recipients()->where('status', 'bounced')->count(),
        ];

        return view('campaigns.show', compact('campaign', 'statusCounts'));
    }

    // ============================================================
    // MANAGE SEQUENCES
    // ============================================================
    public function manageSequences($id)
    {
        $campaign = Campaign::where('id', $id)
                            ->where('user_id', Auth::id())
                            ->with([
                                'personalFollowupSequences.template',
                                'companyFollowupSequences.template',
                            ])
                            ->firstOrFail();

        $templates = Template::where('user_id', Auth::id())
                             ->where('category', 'followup')
                             ->get();

        $personalFollowupTemplates = $templates->where('type', 'personal');
        $companyFollowupTemplates  = $templates->where('type', 'company');

        return view('campaigns.sequences', compact(
            'campaign',
            'personalFollowupTemplates',
            'companyFollowupTemplates'
        ));
    }

    // ============================================================
    // UPDATE SEQUENCES
    // ============================================================
    public function updateSequences(Request $request, $id)
    {
        $campaign = Campaign::where('id', $id)
                            ->where('user_id', Auth::id())
                            ->firstOrFail();

        // Clear all existing sequences for this campaign
        CampaignFollowupSequence::where('campaign_id', $id)->delete();

        // Save personal sequences
        foreach ($request->input('personal_followup_sequences', []) as $seq => $templateId) {
            if ($templateId) {
                CampaignFollowupSequence::create([
                    'campaign_id' => $id,
                    'template_id' => $templateId,
                    'type'        => 'personal',
                    'sequence'    => $seq + 1,
                ]);
            }
        }

        // Save company sequences
        foreach ($request->input('company_followup_sequences', []) as $seq => $templateId) {
            if ($templateId) {
                CampaignFollowupSequence::create([
                    'campaign_id' => $id,
                    'template_id' => $templateId,
                    'type'        => 'company',
                    'sequence'    => $seq + 1,
                ]);
            }
        }

        return redirect()->route('campaigns.show', $id)
                         ->with('success', '✅ Follow-up sequences updated!');
    }

    // ============================================================
    // DESTROY
    // ============================================================
    public function destroy($id)
    {
        $campaign = Campaign::where('id', $id)
                            ->where('user_id', Auth::id())
                            ->firstOrFail();

        $campaign->delete();

        return redirect()->route('campaigns.index')
                         ->with('success', '✅ Campaign deleted!');
    }
}