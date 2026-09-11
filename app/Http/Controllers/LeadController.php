<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Services\GmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
{
    // ============================================================
    // INDEX
    // ============================================================
    public function index(Request $request)
    {
        $leads = Lead::where('user_id', Auth::id())
                     ->with('sourceCampaign')
                     ->orderBy('updated_at', 'desc')
                     ->get();

        $status = $request->input('status', 'saved');

        return view('leads.index', compact('leads', 'status'));
    }

    // ============================================================
    // STORE (single)
    // ============================================================
    public function store(Request $request)
    {
        $request->validate([
            'email'        => 'required|email',
            'first_name'   => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'domain'       => 'nullable|string|max:255',
            'website'      => 'nullable|url',
            'status'       => 'nullable|in:saved,contacted,replied,sold,lost',
            'notes'        => 'nullable|string',
        ]);

        $email = strtolower(trim($request->email));

        $lead = Lead::firstOrNew([
            'user_id' => Auth::id(),
            'email'   => $email,
        ]);

        $lead->fill([
            'first_name'   => $request->first_name ?: $lead->first_name,
            'company_name' => $request->company_name ?: $lead->company_name,
            'domain'       => $request->domain ?: $lead->domain,
            'website'      => $request->website ?: $lead->website,
            'status'       => $request->status ?? $lead->status ?? 'saved',
            'notes'        => $request->notes ?: $lead->notes,
        ]);
        $lead->save();

        return redirect()->route('leads.index')
                         ->with('success', '✅ Lead saved!');
    }

    // ============================================================
    // BULK STORE — from paste page autosave / save button
    // ============================================================
    public function bulkStore(Request $request)
    {
        $request->validate(['emails' => 'required|string']);

        $raw    = $request->input('emails');
        $emails = preg_split('/[\s,;]+/', $raw);
        $emails = array_map('trim', $emails);
        $emails = array_filter($emails, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL));
        $emails = array_values(array_unique(array_map('strtolower', $emails)));

        if (empty($emails)) {
            return response()->json(['success' => false, 'created' => 0, 'updated' => 0, 'total' => 0]);
        }

        $domain     = $request->input('domain');
        $campaignId = $request->input('campaign_id');

        $created = 0;
        $updated = 0;

        foreach ($emails as $email) {
            $extracted = GmailService::extractNamesFromEmail($email);

            $lead = Lead::where('user_id', Auth::id())
                        ->where('email', $email)
                        ->first();

            if ($lead) {
                if (!$lead->company_name) $lead->company_name = $extracted['company_name'];
                if (!$lead->first_name)   $lead->first_name   = $extracted['first_name'];
                if ($domain && !$lead->domain) $lead->domain = strtolower(trim($domain));
                if ($lead->status === 'lost') $lead->status = 'saved';
                $lead->save();
                $updated++;
            } else {
                Lead::create([
                    'user_id'           => Auth::id(),
                    'source_campaign_id' => $campaignId,
                    'email'              => $email,
                    'first_name'         => $extracted['first_name'],
                    'company_name'       => $extracted['company_name'],
                    'domain'             => $domain ? strtolower(trim($domain)) : null,
                    'status'             => 'saved',
                ]);
                $created++;
            }
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'updated' => $updated,
            'total'   => count($emails),
        ]);
    }

    // ============================================================
    // UPDATE
    // ============================================================
    public function update(Request $request, $id)
    {
        $lead = Lead::where('id', $id)
                    ->where('user_id', Auth::id())
                    ->firstOrFail();

        $request->validate([
            'email'        => 'required|email',
            'first_name'   => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'domain'       => 'nullable|string|max:255',
            'website'      => 'nullable|url',
            'status'       => 'nullable|in:saved,contacted,replied,sold,lost',
            'notes'        => 'nullable|string',
        ]);

        $lead->update([
            'email'        => strtolower(trim($request->email)),
            'first_name'   => $request->first_name,
            'company_name' => $request->company_name,
            'domain'       => $request->domain ? strtolower(trim($request->domain)) : null,
            'website'      => $request->website,
            'status'       => $request->status,
            'notes'        => $request->notes,
        ]);

        return redirect()->route('leads.index')
                         ->with('success', '✅ Lead updated!');
    }

    // ============================================================
    // QUICK STATUS PATCH
    // ============================================================
    public function status(Request $request, $id)
    {
        $lead = Lead::where('id', $id)
                    ->where('user_id', Auth::id())
                    ->firstOrFail();

        $request->validate(['status' => 'required|in:saved,contacted,replied,sold,lost']);

        $lead->update(['status' => $request->status]);

        return redirect()->route('leads.index')
                         ->with('success', '✅ Lead status updated!');
    }

    // ============================================================
    // DESTROY
    // ============================================================
    public function destroy($id)
    {
        $lead = Lead::where('id', $id)
                    ->where('user_id', Auth::id())
                    ->firstOrFail();

        $lead->delete();

        return redirect()->route('leads.index')
                         ->with('success', '✅ Lead removed!');
    }
}