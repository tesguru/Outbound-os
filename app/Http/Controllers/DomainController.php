<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DomainController extends Controller
{
    // ============================================================
    // INDEX — Domain CRM
    // ============================================================
    public function index()
    {
        $domains = Domain::where('user_id', Auth::id())
                         ->orderBy('created_at', 'desc')
                         ->get();

        // Domains the user has pitched in campaigns but hasn't added to the CRM yet.
        $campaignDomains = Campaign::where('user_id', Auth::id())
                                   ->whereNotNull('domain')
                                   ->get(['domain'])
                                   ->pluck('domain')
                                   ->map(fn($d) => strtolower(trim($d)))
                                   ->unique()
                                   ->values()
                                   ->reject(fn($d) => $domains->contains(fn($domain) => strtolower($domain->domain) === $d))
                                   ->values();

        $campaignDomainStats = [];
        foreach ($campaignDomains as $domainName) {
            $campaignIds = Campaign::where('user_id', Auth::id())
                                   ->whereRaw('LOWER(domain) = ?', [$domainName])
                                   ->pluck('id');

            $campaignDomainStats[$domainName] = [
                'outbound' => \App\Models\Recipient::whereIn('campaign_id', $campaignIds)->count(),
                'sent'     => \App\Models\Recipient::whereIn('campaign_id', $campaignIds)->whereIn('status', ['sent', 'replied'])->count(),
                'replied'  => \App\Models\Recipient::whereIn('campaign_id', $campaignIds)->where('status', 'replied')->count(),
            ];
        }

        // Summary stats
        $totalSpent  = (float) $domains->sum('price');
        $totalSold   = $domains->where('status', 'sold')->sum('sold_price');
        $totalProfit = $domains->where('status', 'sold')->sum(fn($d) => (float) $d->sold_price - (float) $d->price);
        $outbounding = $domains->where('status', '!=', 'sold')->count();
        $soldCount   = $domains->where('status', 'sold')->count();

        $summary = compact('totalSpent', 'totalSold', 'totalProfit', 'outbounding', 'soldCount');

        return view('domains.index', compact('domains', 'campaignDomains', 'campaignDomainStats', 'summary'));
    }

    // ============================================================
    // STORE
    // ============================================================
    public function store(Request $request)
    {
        $request->validate([
            'domain'      => 'required|string|max:255',
            'price'       => 'nullable|numeric|min:0',
            'currency'    => 'nullable|string|max:4',
            'registrar'   => 'nullable|string|max:255',
            'registered_at' => 'nullable|date',
            'renews_at'   => 'nullable|date',
            'status'      => 'nullable|in:available,outbounding,sold',
            'sold_price'  => 'nullable|numeric|min:0',
            'sold_at'     => 'nullable|date',
            'notes'       => 'nullable|string',
        ]);

        $domain = strtolower(trim($request->domain));

        $existing = Domain::where('user_id', Auth::id())
                          ->whereRaw('LOWER(domain) = ?', [$domain])
                          ->first();

        $data = [
            'price'         => $request->price ?? 0,
            'currency'      => $request->currency ?: 'USD',
            'registrar'     => $request->registrar,
            'registered_at' => $request->registered_at,
            'renews_at'     => $request->renews_at,
            'status'        => $request->status ?? 'outbounding',
            'sold_price'    => $request->sold_price,
            'sold_at'       => $request->sold_at,
            'notes'         => $request->notes,
        ];

        if ($existing) {
            $existing->update($data);
            $msg = '✅ Domain updated!';
        } else {
            Domain::create(array_merge(['user_id' => Auth::id(), 'domain' => $domain], $data));
            $msg = '✅ Domain added to CRM!';
        }

        // If marked sold, update its leads too
        if (($request->status ?? 'outbounding') === 'sold') {
            \App\Models\Lead::where('user_id', Auth::id())
                            ->where('domain', strtolower(trim($request->domain)))
                            ->where('status', '!=', 'sold')
                            ->update(['status' => 'sold']);
        }

        return redirect()->route('domains.index')->with('success', $msg);
    }

    // ============================================================
    // CREATE FROM CAMPAIGN (quick add discovered domain)
    // ============================================================
    public function createFromCampaign(Request $request)
    {
        $request->validate(['domain' => 'required|string|max:255']);

        $domain = strtolower(trim($request->domain));

        Domain::firstOrCreate(
            ['user_id' => Auth::id(), 'domain' => $domain],
            [
                'price'    => 0,
                'currency' => 'USD',
                'status'   => 'outbounding',
            ]
        );

        return redirect()->route('domains.index')
                         ->with('success', "✅ {$domain} added to Domain CRM!");
    }

    // ============================================================
    // TOGGLE SOLD
    // ============================================================
    public function toggleSold(Request $request, $id)
    {
        $domain = Domain::where('id', $id)
                        ->where('user_id', Auth::id())
                        ->firstOrFail();

        $request->validate([
            'sold_price' => 'nullable|numeric|min:0',
            'sold_at'    => 'nullable|date',
        ]);

        if ($domain->status === 'sold') {
            $domain->update([
                'status'     => 'outbounding',
                'sold_price' => null,
                'sold_at'    => null,
            ]);

            // Un-sold leads attached to this domain
            \App\Models\Lead::where('user_id', Auth::id())
                            ->where('domain', $domain->domain)
                            ->where('status', 'sold')
                            ->update(['status' => 'replied']);

            $msg = "↩️ {$domain->domain} marked as NOT sold.";
        } else {
            $domain->update([
                'status'     => 'sold',
                'sold_price' => $request->sold_price ?: $domain->price,
                'sold_at'    => $request->sold_at ?: now()->toDateString(),
            ]);

            // Mark linked leads as sold
            \App\Models\Lead::where('user_id', Auth::id())
                            ->where('domain', $domain->domain)
                            ->where('status', '!=', 'sold')
                            ->update(['status' => 'sold']);

            $msg = "🎉 {$domain->domain} marked as SOLD!";
        }

        return redirect()->route('domains.index')->with('success', $msg);
    }

    // ============================================================
    // UPDATE
    // ============================================================
    public function update(Request $request, $id)
    {
        $domain = Domain::where('id', $id)
                        ->where('user_id', Auth::id())
                        ->firstOrFail();

        $request->validate([
            'domain'      => 'required|string|max:255',
            'price'       => 'nullable|numeric|min:0',
            'currency'    => 'nullable|string|max:4',
            'registrar'   => 'nullable|string|max:255',
            'registered_at' => 'nullable|date',
            'renews_at'   => 'nullable|date',
            'status'      => 'nullable|in:available,outbounding,sold',
            'sold_price'  => 'nullable|numeric|min:0',
            'sold_at'     => 'nullable|date',
            'notes'       => 'nullable|string',
        ]);

        $domain->update([
            'domain'        => strtolower(trim($request->domain)),
            'price'         => $request->price ?? 0,
            'currency'      => $request->currency ?: 'USD',
            'registrar'     => $request->registrar,
            'registered_at' => $request->registered_at,
            'renews_at'     => $request->renews_at,
            'status'        => $request->status ?? 'outbounding',
            'sold_price'    => $request->sold_price,
            'sold_at'       => $request->sold_at,
            'notes'         => $request->notes,
        ]);

        return redirect()->route('domains.index')
                         ->with('success', '✅ Domain updated!');
    }

    // ============================================================
    // DESTROY
    // ============================================================
    public function destroy($id)
    {
        $domain = Domain::where('id', $id)
                        ->where('user_id', Auth::id())
                        ->firstOrFail();

        $domain->delete();

        return redirect()->route('domains.index')
                         ->with('success', '✅ Domain removed!');
    }
}