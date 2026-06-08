<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
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
    public function index()
    {
        $campaigns = Campaign::where('user_id', Auth::id())
                             ->withCount('recipients')
                             ->with(['initialTemplate', 'gmailAccounts'])
                             ->orderBy('created_at', 'desc')
                             ->get();
        return view('campaigns.index', compact('campaigns'));
    }

    public function create()
    {
        $gmailAccounts = GmailAccount::where('user_id', Auth::id())
                                     ->where('is_active', true)
                                     ->get();

        return view('campaigns.create', compact('gmailAccounts'));
    }

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

        foreach ($request->gmail_accounts as $index => $accountId) {
            $campaign->gmailAccounts()->attach($accountId, [
                'recipient_limit' => $request->recipient_limits[$index] ?? 50,
            ]);
        }

        // Create Gmail label
        $gmailAccount = GmailAccount::find($request->gmail_accounts[0]);
        $gmailService = new GmailService($gmailAccount);
        $label        = $gmailService->getOrCreateLabel($request->name);

        if ($label['success']) {
            $campaign->update([
                'gmail_label_id'   => $label['label_id'],
                'gmail_label_name' => $label['label_name'],
            ]);
        }

        return redirect()->route('campaigns.recipients.paste', $campaign->id)
                         ->with('success', '✅ Campaign created! Now paste your recipients.');
    }

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
                                'websites', // ← campaign websites
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

    public function manageSequences($id)
    {
        $campaign = Campaign::where('id', $id)
                            ->where('user_id', Auth::id())
                            ->with([
                                'personalFollowupSequences.template',
                                'companyFollowupSequences.template',
                            ])
                            ->firstOrFail();

        $templates = \App\Models\Template::where('user_id', Auth::id())
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

    public function updateSequences(Request $request, $id)
    {
        $campaign = Campaign::where('id', $id)
                            ->where('user_id', Auth::id())
                            ->firstOrFail();

        \App\Models\CampaignFollowupSequence::where('campaign_id', $id)->delete();

        $personalSequences = $request->input('personal_followup_sequences', []);
        foreach ($personalSequences as $seq => $templateId) {
            if ($templateId) {
                \App\Models\CampaignFollowupSequence::create([
                    'campaign_id' => $id,
                    'template_id' => $templateId,
                    'type'        => 'personal',
                    'sequence'    => $seq + 1,
                ]);
            }
        }

        $companySequences = $request->input('company_followup_sequences', []);
        foreach ($companySequences as $seq => $templateId) {
            if ($templateId) {
                \App\Models\CampaignFollowupSequence::create([
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