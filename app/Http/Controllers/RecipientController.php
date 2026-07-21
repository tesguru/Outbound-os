<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignFollowupSequence;
use App\Models\CampaignWebsite;
use App\Models\Followup;
use App\Models\GmailAccount;
use App\Models\Recipient;
use App\Models\Template;
use App\Services\GmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RecipientController extends Controller
{
    // ============================================================
    // SHOW PASTE PAGE
    // ============================================================
    public function paste($campaignId)
    {
        $campaign = Campaign::where('id', $campaignId)
                            ->where('user_id', Auth::id())
                            ->with('gmailAccounts')
                            ->firstOrFail();

        return view('recipients.paste', compact('campaign'));
    }

    // ============================================================
    // ANALYSE PASTED EMAILS
    // ============================================================
    public function analyse(Request $request, $campaignId)
    {
        $campaign = Campaign::where('id', $campaignId)
                            ->where('user_id', Auth::id())
                            ->with('gmailAccounts')
                            ->firstOrFail();

        $request->validate(['emails' => 'required|string']);

        $raw    = $request->input('emails');
        $emails = preg_split('/[\s,;]+/', $raw);
        $emails = array_map('trim', $emails);
        $emails = array_filter($emails, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL));
        $emails = array_values(array_unique($emails));

        $existing = Recipient::where('campaign_id', $campaignId)
                             ->pluck('email')
                             ->map(fn($e) => strtolower($e))
                             ->toArray();

        $emails = array_values(array_filter($emails, fn($e) => !in_array(strtolower($e), $existing)));

        if (empty($emails)) {
            return redirect()->route('campaigns.recipients.paste', $campaignId)
                             ->with('error', '⚠️ No valid or new emails found after cleaning.');
        }

        $gmailAccount = $campaign->gmailAccounts->first();
        $gmailService = new GmailService($gmailAccount);

        $analysed = [];
        foreach ($emails as $email) {
            $realName  = $gmailService->getContactName($email);
            $extracted = GmailService::extractNamesFromEmail($email);

            $companyName = $extracted['company_name'];
            $firstName   = null;

            if ($realName) {
                $parts     = explode(' ', trim($realName));
                $firstName = ucfirst(strtolower($parts[0]));
            } else {
                $firstName = $extracted['first_name'];
            }

            $analysed[] = [
                'email'        => $email,
                'first_name'   => $firstName,
                'company_name' => $companyName,
                'real_name'    => $realName,
                'use_type'     => $campaign->template_type === 'personal' ? 'first_name' : 'company_name',
            ];
        }

        session(['analysed_recipients_' . $campaignId => $analysed]);

        // ── Save campaign websites (separate from recipients) ──
        $websiteRaw = $request->input('websites', '');
        if (trim($websiteRaw)) {
            $lines = array_filter(array_map('trim', explode("\n", $websiteRaw)));

            CampaignWebsite::where('campaign_id', $campaignId)->delete();

            foreach ($lines as $url) {
                if (!preg_match('#^https?://#i', $url)) {
                    $url = 'https://' . $url;
                }
                CampaignWebsite::create([
                    'campaign_id' => $campaignId,
                    'url'         => $url,
                ]);
            }
        }

        $templates                 = Template::where('user_id', Auth::id())->get();
        $personalInitialTemplates  = $templates->where('type', 'personal')->where('category', 'initial');
        $personalFollowupTemplates = $templates->where('type', 'personal')->where('category', 'followup');
        $companyInitialTemplates   = $templates->where('type', 'company')->where('category', 'initial');
        $companyFollowupTemplates  = $templates->where('type', 'company')->where('category', 'followup');

        return view('recipients.analyse', compact(
            'campaign',
            'analysed',
            'personalInitialTemplates',
            'personalFollowupTemplates',
            'companyInitialTemplates',
            'companyFollowupTemplates'
        ));
    }

    // ============================================================
    // CONFIRM + SAVE RECIPIENTS
    // ============================================================
    public function confirm(Request $request, $campaignId)
    {
        $campaign = Campaign::where('id', $campaignId)
                            ->where('user_id', Auth::id())
                            ->with('gmailAccounts')
                            ->firstOrFail();

        $analysed = session('analysed_recipients_' . $campaignId, []);

        if (empty($analysed)) {
            return redirect()->route('campaigns.recipients.paste', $campaignId)
                             ->with('error', '⚠️ Session expired. Please paste emails again.');
        }

        $request->validate([
            'first_names'                  => 'required|array',
            'company_names'                => 'required|array',
            'use_types'                    => 'required|array',
            'account_splits'               => 'required|array',
            'personal_initial_template_id' => 'nullable|exists:templates,id',
            'company_initial_template_id'  => 'nullable|exists:templates,id',
        ]);

        $campaign->update([
            'personal_initial_template_id' => $request->personal_initial_template_id,
            'company_initial_template_id'  => $request->company_initial_template_id,
        ]);

        // ── Save personal followup sequences ──
        if ($request->has('personal_followup_sequences')) {
            CampaignFollowupSequence::where('campaign_id', $campaignId)
                                    ->where('type', 'personal')
                                    ->delete();

            foreach ($request->personal_followup_sequences as $seq => $templateId) {
                if ($templateId) {
                    CampaignFollowupSequence::create([
                        'campaign_id' => $campaignId,
                        'template_id' => $templateId,
                        'type'        => 'personal',
                        'sequence'    => $seq + 1,
                    ]);
                }
            }
        }

        // ── Save company followup sequences ──
        if ($request->has('company_followup_sequences')) {
            CampaignFollowupSequence::where('campaign_id', $campaignId)
                                    ->where('type', 'company')
                                    ->delete();

            foreach ($request->company_followup_sequences as $seq => $templateId) {
                if ($templateId) {
                    CampaignFollowupSequence::create([
                        'campaign_id' => $campaignId,
                        'template_id' => $templateId,
                        'type'        => 'company',
                        'sequence'    => $seq + 1,
                    ]);
                }
            }
        }

        // ── Assign recipients to accounts based on splits ──
        $accountSplits = $request->account_splits;
        $assignments   = [];
        $index         = 0;

        Log::info('Confirm: account splits received', [
            'splits'         => $accountSplits,
            'total_analysed' => count($analysed),
        ]);

        foreach ($accountSplits as $accountId => $count) {
            for ($i = 0; $i < (int) $count; $i++) {
                if (isset($analysed[$index])) {
                    $assignments[$index] = $accountId;
                    $index++;
                }
            }
        }

        Log::info('Confirm: assignments built', [
            'total_assigned' => count($assignments),
            'total_analysed' => count($analysed),
        ]);

        $saved   = 0;
        $skipped = 0;

        foreach ($analysed as $idx => $recipient) {
            $accountId = $assignments[$idx] ?? null;
            $useType   = $request->use_types[$idx] ?? 'personal';

            if (!$accountId) {
                Log::warning('Confirm: no account assigned for recipient', [
                    'index' => $idx,
                    'email' => $recipient['email'],
                ]);
                $skipped++;
                continue;
            }

            Recipient::create([
                'campaign_id'          => $campaignId,
                'gmail_account_id'     => $accountId,
                'email'                => $recipient['email'],
                'first_name'           => $request->first_names[$idx]   ?? $recipient['first_name'],
                'company_name'         => $request->company_names[$idx] ?? $recipient['company_name'],
                'personalization_type' => $useType === 'personal' ? 'first_name' : 'company_name',
                'status'               => 'pending',
                'is_bounced'           => false,
            ]);

            $saved++;
        }

        session()->forget('analysed_recipients_' . $campaignId);

        Log::info('Confirm: recipients saved', [
            'saved'   => $saved,
            'skipped' => $skipped,
        ]);

        return redirect()->route('campaigns.show', $campaignId)
                         ->with('success', "✅ {$saved} recipients added!" . ($skipped > 0 ? " {$skipped} skipped." : ''));
    }

    // ============================================================
    // CREATE DRAFTS — BATCHED
    // ============================================================
   // ============================================================
// CREATE DRAFTS — BATCHED (small batches + inter-request pacing)
// ============================================================
public function createDraftsBatch(Request $request, $campaignId)
{
    set_time_limit(0);
    ini_set('max_execution_time', 0);

    $campaign = Campaign::where('id', $campaignId)
                        ->where('user_id', Auth::id())
                        ->with(['personalInitialTemplate', 'companyInitialTemplate'])
                        ->firstOrFail();

    // Smaller batch = shorter request = less chance of hitting proxy/server
    // timeouts, and less chance of bursting Gmail's rate limits at once.
    $batchSize = 5;

    $pendingRecipients = Recipient::where('campaign_id', $campaignId)
                                  ->where('status', 'pending')
                                  ->take($batchSize)
                                  ->get();

    if ($pendingRecipients->isEmpty()) {
        return response()->json([
            'success'   => true,
            'done'      => true,
            'created'   => 0,
            'failed'    => 0,
            'remaining' => 0,
        ]);
    }

    $created = 0;
    $failed  = 0;
    $rateLimited = false;

    foreach ($pendingRecipients as $recipient) {
        try {
            $template = $recipient->personalization_type === 'first_name'
                        ? $campaign->personalInitialTemplate
                        : $campaign->companyInitialTemplate;

            if (!$template) {
                Log::warning('Draft batch: no template found', [
                    'recipient'            => $recipient->email,
                    'personalization_type' => $recipient->personalization_type,
                ]);
                $failed++;
                continue;
            }

            $gmailAccount = GmailAccount::find($recipient->gmail_account_id);

            if (!$gmailAccount) {
                Log::warning('Draft batch: Gmail account not found', [
                    'recipient'        => $recipient->email,
                    'gmail_account_id' => $recipient->gmail_account_id,
                ]);
                $failed++;
                continue;
            }

            $gmailService = new GmailService($gmailAccount);

            $subject = str_replace(
                ['{{first_name}}', '{{company_name}}', '{{domain}}'],
                [$recipient->first_name, $recipient->company_name, $campaign->domain],
                $template->subject
            );

            $body = str_replace(
                ['{{first_name}}', '{{company_name}}', '{{domain}}'],
                [$recipient->first_name, $recipient->company_name, $campaign->domain],
                $template->body
            );

            $result = $gmailService->createDraft($recipient->email, $subject, $body);

            if ($result['success']) {
                $recipient->update([
                    'status'     => 'draft_created',
                    'thread_id'  => $result['thread_id'],
                    'message_id' => $result['message_id'],
                ]);

                if ($campaign->gmail_label_name) {
                    try {
                        $label = $gmailService->getOrCreateLabel($campaign->gmail_label_name);
                        if ($label['success']) {
                            $gmailService->addLabelToThread($result['thread_id'], $label['label_id']);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Draft batch: label add failed (non-fatal)', [
                            'recipient' => $recipient->email,
                            'gmail'     => $gmailAccount->email,
                            'error'     => $e->getMessage(),
                        ]);
                    }
                }

                $created++;

            } else {
                // Detect Gmail rate-limit style failures so the frontend can
                // slow itself down instead of hammering harder.
                $errMsg = strtolower(json_encode($result));
                if (str_contains($errMsg, 'rate') || str_contains($errMsg, '429') || str_contains($errMsg, 'quota')) {
                    $rateLimited = true;
                }

                Log::warning('Draft batch: createDraft returned failure', [
                    'recipient' => $recipient->email,
                    'gmail'     => $gmailAccount->email,
                    'result'    => $result,
                ]);
                $failed++;
            }

        } catch (\Exception $e) {
            $errMsg = strtolower($e->getMessage());
            if (str_contains($errMsg, 'rate') || str_contains($errMsg, '429') || str_contains($errMsg, 'quota')) {
                $rateLimited = true;
            }

            Log::error('Draft batch: exception', [
                'recipient' => $recipient->email,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            $failed++;
        }

        // Small pause between individual Gmail API calls within the batch —
        // spreads out 5 calls instead of firing them back-to-back.
        usleep(250000); // 250ms
    }

    $remaining = Recipient::where('campaign_id', $campaignId)
                           ->where('status', 'pending')
                           ->count();

    return response()->json([
        'success'      => true,
        'done'         => $remaining === 0,
        'created'      => $created,
        'failed'       => $failed,
        'remaining'    => $remaining,
        'rate_limited' => $rateLimited,
    ]);
}

// ============================================================
// CREATE FOLLOW-UP DRAFTS — BATCHED
// ============================================================
public function createFollowupsBatch(Request $request, $campaignId)
{
    set_time_limit(0);
    ini_set('max_execution_time', 0);

    $input = json_decode($request->getContent(), true) ?? [];

    $campaign = Campaign::where('id', $campaignId)
                        ->where('user_id', Auth::id())
                        ->with(['followupSequences.template'])
                        ->firstOrFail();

    $price     = $input['price'] ?? null;
    $batchSize = 5;
    $offset    = (int) ($input['offset'] ?? 0);

    $sentRecipients = Recipient::where('campaign_id', $campaignId)
                               ->where('status', 'sent')
                               ->whereNotNull('thread_id')
                               ->skip($offset)
                               ->take($batchSize)
                               ->get();

    $totalSent = Recipient::where('campaign_id', $campaignId)
                           ->where('status', 'sent')
                           ->whereNotNull('thread_id')
                           ->count();

    if ($sentRecipients->isEmpty()) {
        return response()->json([
            'success'   => true,
            'done'      => true,
            'created'   => 0,
            'skipped'   => 0,
            'failed'    => 0,
            'maxed'     => 0,
            'remaining' => 0,
        ]);
    }

    $created = 0;
    $skipped = 0;
    $failed  = 0;
    $maxed   = 0;
    $rateLimited = false;

    foreach ($sentRecipients as $recipient) {
        try {
            $doneCount    = Followup::where('campaign_id', $campaignId)
                                    ->where('recipient_id', $recipient->id)
                                    ->count();
            $nextSequence = $doneCount + 1;
            $sequenceType = $recipient->personalization_type === 'first_name' ? 'personal' : 'company';

            $sequenceEntry = CampaignFollowupSequence::where('campaign_id', $campaignId)
                                                      ->where('type', $sequenceType)
                                                      ->where('sequence', $nextSequence)
                                                      ->with('template')
                                                      ->first();

            if (!$sequenceEntry || !$sequenceEntry->template) {
                $maxed++;
                continue;
            }

            $template = $sequenceEntry->template;

            $gmailAccount = GmailAccount::find($recipient->gmail_account_id);

            if (!$gmailAccount) {
                Log::warning('Followup batch: Gmail account not found', [
                    'recipient'        => $recipient->email,
                    'gmail_account_id' => $recipient->gmail_account_id,
                ]);
                $failed++;
                continue;
            }

            $gmailService = new GmailService($gmailAccount);
            $threadData   = $gmailService->getThreadMessages($recipient->thread_id);

            $subject = 'Re: ' . str_replace(
                ['{{first_name}}', '{{company_name}}', '{{domain}}'],
                [$recipient->first_name, $recipient->company_name, $campaign->domain],
                $template->subject
            );

            $body = str_replace(
                ['{{first_name}}', '{{company_name}}', '{{domain}}', '{{price}}'],
                [$recipient->first_name, $recipient->company_name, $campaign->domain, $price ?? ''],
                $template->body
            );

            $result = $gmailService->createFollowUpDraft(
                $recipient->email,
                $subject,
                $body,
                $recipient->thread_id,
                $threadData['success'] ? $threadData['message_id'] : null,
                $threadData['success'] ? $threadData['references']  : null,
            );

            if ($result['success']) {
                Followup::create([
                    'campaign_id'  => $campaignId,
                    'recipient_id' => $recipient->id,
                    'template_id'  => $template->id,
                    'draft_id'     => $result['draft_id'],
                    'thread_id'    => $result['thread_id'],
                    'price'        => $price ?: null,
                    'sequence'     => $nextSequence,
                    'status'       => 'draft_created',
                ]);

                $created++;

            } else {
                $errMsg = strtolower(json_encode($result));
                if (str_contains($errMsg, 'rate') || str_contains($errMsg, '429') || str_contains($errMsg, 'quota')) {
                    $rateLimited = true;
                }

                Log::warning('Followup batch: createFollowUpDraft returned failure', [
                    'recipient' => $recipient->email,
                    'gmail'     => $gmailAccount->email,
                    'result'    => $result,
                ]);
                $failed++;
            }

        } catch (\Exception $e) {
            $errMsg = strtolower($e->getMessage());
            if (str_contains($errMsg, 'rate') || str_contains($errMsg, '429') || str_contains($errMsg, 'quota')) {
                $rateLimited = true;
            }

            Log::error('Followup batch: exception', [
                'recipient' => $recipient->email,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
            $failed++;
        }

        usleep(250000); // 250ms between recipients
    }

    $nextOffset = $offset + $batchSize;
    $remaining  = max(0, $totalSent - $nextOffset);
    $done       = $nextOffset >= $totalSent;

    return response()->json([
        'success'      => true,
        'done'         => $done,
        'created'      => $created,
        'skipped'      => $skipped,
        'failed'       => $failed,
        'maxed'        => $maxed,
        'next_offset'  => $nextOffset,
        'remaining'    => $remaining,
        'rate_limited' => $rateLimited,
    ]);
}

    // ============================================================
    // MARK RECIPIENTS AS SENT
    // ============================================================
    public function markSent(Request $request, $campaignId)
    {
        $request->validate(['recipient_ids' => 'required|array']);

        Recipient::where('campaign_id', $campaignId)
                 ->whereIn('id', $request->recipient_ids)
                 ->update(['status' => 'sent']);

        return redirect()->route('campaigns.show', $campaignId)
                         ->with('success', '✅ Recipients marked as sent!');
    }

    // ============================================================
    // MARK RECIPIENT AS REPLIED
    // ============================================================
    public function markReplied(Request $request, $campaignId)
    {
        $request->validate(['recipient_id' => 'required|exists:recipients,id']);

        Recipient::where('id', $request->recipient_id)
                 ->where('campaign_id', $campaignId)
                 ->update(['status' => 'replied']);

        return redirect()->route('campaigns.show', $campaignId)
                         ->with('success', '✅ Marked as replied!');
    }
}