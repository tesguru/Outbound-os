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
        $lists = Lead::where('user_id', Auth::id())
                     ->whereNotNull('list_name')
                     ->where('list_name', '!=', '')
                     ->where('status', '!=', 'lost')
                     ->selectRaw('list_name, COUNT(*) as total')
                     ->groupBy('list_name')
                     ->orderBy('list_name')
                     ->get();

        $activeList   = $request->input('list');
        $notepadLeads = null;

        if ($activeList) {
            $notepadLeads = Lead::where('user_id', Auth::id())
                                ->where('list_name', $activeList)
                                ->where('status', '!=', 'lost')
                                ->orderBy('email')
                                ->pluck('email');
        }

        // Hydration for the notepad switcher (name => emails block)
        $notepads = $lists->map(function ($l) {
            return [
                'name'   => $l->list_name,
                'emails' => Lead::where('user_id', Auth::id())
                                ->where('list_name', $l->list_name)
                                ->where('status', '!=', 'lost')
                                ->orderBy('email')
                                ->pluck('email')
                                ->implode("\n"),
            ];
        });

        $totalLeads = Lead::where('user_id', Auth::id())->count();

        return view('leads.index', compact('lists', 'activeList', 'notepadLeads', 'notepads', 'totalLeads'));
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
            'list_name'    => 'nullable|string|max:255',
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
            'list_name'    => $request->list_name ?: $lead->list_name,
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
        $listName   = $request->input('list_name');
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
                if (!$lead->list_name && $listName) $lead->list_name = $listName;
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
                    'list_name'          => $listName ?: null,
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
            'list_name'    => 'nullable|string|max:255',
            'website'      => 'nullable|url',
            'status'       => 'nullable|in:saved,contacted,replied,sold,lost',
            'notes'        => 'nullable|string',
        ]);

        $lead->update([
            'email'        => strtolower(trim($request->email)),
            'first_name'   => $request->first_name,
            'company_name' => $request->company_name,
            'domain'       => $request->domain ? strtolower(trim($request->domain)) : null,
            'list_name'    => $request->list_name ?: null,
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
    // SYNC LIST — the notepad autosave
    // Adds what's typed, removes lines you deleted.
    // ============================================================
    public function syncList(Request $request)
    {
        $request->validate([
            'list_name' => 'required|string|max:255',
            'emails'    => 'nullable|string',
        ]);

        $listName = $request->input('list_name');
        $raw      = $request->input('emails', '');

        $emails = preg_split('/[\s,;]+/', $raw);
        $emails = array_map('trim', $emails);
        $emails = array_filter($emails, fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL));
        $emails = array_values(array_unique(array_map('strtolower', $emails)));

        $existing = Lead::where('user_id', Auth::id())
                        ->where('list_name', $listName)
                        ->get()
                        ->keyBy(fn($l) => strtolower($l->email));

        $added = 0;
        foreach ($emails as $email) {
            $lead = Lead::where('user_id', Auth::id())->where('email', $email)->first();

            if (!$lead) {
                $extracted = GmailService::extractNamesFromEmail($email);
                Lead::create([
                    'user_id'      => Auth::id(),
                    'email'        => $email,
                    'first_name'   => $extracted['first_name'],
                    'company_name' => $extracted['company_name'],
                    'list_name'    => $listName,
                    'status'       => 'saved',
                ]);
                $added++;
            } elseif (in_array($lead->status, ['lost', 'saved', 'contacted'])) {
                if ($lead->list_name !== $listName) $lead->list_name = $listName;
                if ($lead->status === 'lost')       $lead->status   = 'saved';
                $lead->save();
                if (!isset($existing[strtolower($email)])) $added++;
            }
        }

        // Lines you deleted leave this list (replied/sold leads are kept safe)
        $removed  = 0;
        $emailSet = array_flip($emails);
        foreach ($existing as $email => $lead) {
            if (!isset($emailSet[$email]) && in_array($lead->status, ['saved', 'contacted'])) {
                $lead->list_name = null;
                $lead->save();
                $removed++;
            }
        }

        return response()->json([
            'success' => true,
            'added'   => $added,
            'removed' => $removed,
            'total'   => count($emails),
        ]);
    }

    // ============================================================
    // RENAME LIST — rename a notepad
    // ============================================================
    public function renameList(Request $request)
    {
        $request->validate([
            'old_name'  => 'required|string|max:255',
            'list_name' => 'required|string|max:255',
        ]);

        if ($request->old_name !== $request->list_name) {
            Lead::where('user_id', Auth::id())
                ->where('list_name', $request->old_name)
                ->update(['list_name' => $request->list_name]);
        }

        return redirect()->route('leads.index', ['list' => $request->list_name]);
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