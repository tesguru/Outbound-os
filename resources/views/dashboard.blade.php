@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Overview of your outbound activity')

@section('topbar-actions')
    <a href="{{ route('campaigns.create') }}" class="btn btn-primary">+ New Campaign</a>
@endsection

@php
    $user = Auth::user();

    // ── Core outbound stats ─────────────────────────────────────────
    $campaignsCount = $user->campaigns()->count();
    $totalRecipients = \App\Models\Recipient::whereHas('campaign', fn($q) => $q->where('user_id', $user->id))->count();
    $draftsCreated   = \App\Models\Recipient::whereHas('campaign', fn($q) => $q->where('user_id', $user->id))->where('status', 'draft_created')->count();
    $sent            = \App\Models\Recipient::whereHas('campaign', fn($q) => $q->where('user_id', $user->id))->whereIn('status', ['sent', 'replied'])->count();
    $replied         = \App\Models\Recipient::whereHas('campaign', fn($q) => $q->where('user_id', $user->id))->where('status', 'replied')->count();

    // ── CRM stats (guarded so the dashboard still renders before migrations) ──
    $hasCrm = \Illuminate\Support\Facades\Schema::hasTable('leads') && \Illuminate\Support\Facades\Schema::hasTable('domains');
    if ($hasCrm) {
        $totalLeads   = $user->leads()->count();
        $soldLeads    = $user->leads()->where('status', 'sold')->count();
        $totalDomains = $user->domains()->count();
        $soldDomains  = $user->domains()->where('status', 'sold')->count();
        $domainSpent  = (float) $user->domains()->sum('price');
        $saleRevenue  = (float) $user->domains()->where('status', 'sold')->sum('sold_price');
    } else {
        $totalLeads = $soldLeads = $totalDomains = $soldDomains = 0;
        $domainSpent = $saleRevenue = 0;
    }

    // ── Consistency — day streak based on days you sent/replied ─────
    $activityDays = \App\Models\Recipient::whereHas('campaign', fn($q) => $q->where('user_id', $user->id))
        ->whereIn('status', ['sent', 'replied'])
        ->whereNotNull('updated_at')
        ->pluck('updated_at')
        ->map(fn($d) => $d->toDateString())
        ->unique();

    $streak = 0;
    $cursor = now()->toDateString();
    while ($activityDays->contains($cursor)) {
        $streak++;
        $cursor = \Carbon\Carbon::parse($cursor)->subDay()->toDateString();
    }

    $sentThisWeek = \App\Models\Recipient::whereHas('campaign', fn($q) => $q->where('user_id', $user->id))
        ->whereIn('status', ['sent', 'replied'])
        ->where('updated_at', '>=', now()->startOfWeek())
        ->count();

    $todaySent = \App\Models\Recipient::whereHas('campaign', fn($q) => $q->where('user_id', $user->id))
        ->whereIn('status', ['sent', 'replied'])
        ->whereDate('updated_at', today())
        ->count();

    $weeklyTarget = (int) ($user->weekly_email_target ?? 50);
    $weekPct = (int) min(100, round($sentThisWeek / max(1, $weeklyTarget) * 100));

    // ── Level / rank based on lifetime emails sent ───────────────────
    $levels = [
        ['min' => 0,    'title' => 'Rookie Sender',    'icon' => '🌱'],
        ['min' => 25,   'title' => 'Getting Momentum', 'icon' => '⚡'],
        ['min' => 100,  'title' => 'Outbound Machine', 'icon' => '🤖'],
        ['min' => 250,  'title' => 'Consistent King',  'icon' => '👑'],
        ['min' => 500,  'title' => 'Send Machine',     'icon' => '🚀'],
        ['min' => 1000, 'title' => 'Outbound Legend',  'icon' => '🏆'],
    ];
    $levelIndex = 0;
    foreach ($levels as $i => $lvl) {
        if ($sent >= $lvl['min']) { $levelIndex = $i; }
    }
    $level = $levels[$levelIndex];
    $nextLevel = $levels[$levelIndex + 1] ?? null;
    $toNext = $nextLevel ? $nextLevel['min'] - $sent : 0;

    // ── Achievements ─────────────────────────────────────────────────
    $achievements = [
        ['icon' => '🌱', 'label' => 'First 10 Emails',      'unlocked' => $sent >= 10],
        ['icon' => '💯', 'label' => '100 Emails Sent',      'unlocked' => $sent >= 100],
        ['icon' => '💬', 'label' => 'First Reply',          'unlocked' => $replied >= 1],
        ['icon' => '🔥', 'label' => '10 Conversations',     'unlocked' => $replied >= 10],
        ['icon' => '🏆', 'label' => 'First Domain Sold',    'unlocked' => $soldDomains >= 1],
        ['icon' => '💰', 'label' => '5 Domains Sold',       'unlocked' => $soldDomains >= 5],
        ['icon' => '🎯', 'label' => '3-Day Streak',         'unlocked' => $streak >= 3],
        ['icon' => '📅', 'label' => '7-Day Streak',         'unlocked' => $streak >= 7],
        ['icon' => '✔️', 'label' => 'Weekly Target Hit',    'unlocked' => $sentThisWeek >= $weeklyTarget],
    ];
    $unlockedCount = collect($achievements)->where('unlocked', true)->count();

    // ── Daily dose of I've-got-this ──────────────────────────────────
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

    $recentReplies = \App\Models\Recipient::whereHas('campaign', fn($q) => $q->where('user_id', $user->id))
        ->where('status', 'replied')
        ->orderBy('updated_at', 'desc')
        ->take(5)
        ->get();
@endphp

@section('content')

{{-- Motivation banner --}}
<div style="background:linear-gradient(135deg, rgba(74,222,128,0.12), transparent);border:1px solid rgba(74,222,128,0.25);border-radius:14px;padding:1.5rem 2rem;margin-bottom:1.5rem;">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1.5rem;flex-wrap:wrap;">
        <div style="flex:1;min-width:240px;">
            <div style="font-size:0.62rem;color:var(--accent);letter-spacing:0.15em;text-transform:uppercase;margin-bottom:0.4rem;">🔥 Keep Sending — Never Give Up</div>
            <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:1.1rem;color:var(--text);">{{ $quote }}</div>
            <div style="font-size:0.68rem;color:var(--muted);margin-top:0.5rem;">
                Rank: <b style="color:var(--accent);">{{ $level['icon'] }} {{ $level['title'] }}</b>
                @if($nextLevel)
                    · <b style="color:var(--blue);">{{ $toNext }}</b> more emails to {{ $nextLevel['title'] }}
                @else
                    · 🏆 Maximum level reached — you are an Outbound Legend!
                @endif
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:2rem;">
            <div style="text-align:center;">
                <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:2rem;color:var(--accent);">🔥 {{ $streak }}</div>
                <div style="font-size:0.6rem;color:var(--muted);letter-spacing:0.08em;text-transform:uppercase;margin-top:0.2rem;">Day Streak</div>
            </div>
            <div style="text-align:center;">
                <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:2rem;color:var(--blue);">{{ $sentThisWeek }}</div>
                <div style="font-size:0.6rem;color:var(--muted);letter-spacing:0.08em;text-transform:uppercase;margin-top:0.2rem;">Sent This Week</div>
            </div>
            <div style="text-align:center;">
                <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:2rem;color:var(--text);">{{ $todaySent }}</div>
                <div style="font-size:0.6rem;color:var(--muted);letter-spacing:0.08em;text-transform:uppercase;margin-top:0.2rem;">Sent Today</div>
            </div>
        </div>
    </div>

    {{-- Weekly goal progress + editable target --}}
    <div style="display:flex;align-items:center;gap:0.75rem;margin-top:1.25rem;flex-wrap:wrap;">
        <div style="flex:1;min-width:220px;">
            <div style="display:flex;justify-content:space-between;font-size:0.62rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.3rem;">
                <span>🎯 Weekly Goal — {{ $sentThisWeek }} / {{ $weeklyTarget }} emails</span>
                <span style="color:var(--accent);">{{ $weekPct }}%</span>
            </div>
            <div style="height:8px;border-radius:999px;background:var(--muted2);border:1px solid var(--border-hover);overflow:hidden;">
                <div style="height:100%;width:{{ $weekPct }}%;border-radius:999px;background:linear-gradient(90deg,var(--accent),var(--blue));transition:width .4s;"></div>
            </div>
        </div>
        @if(\Illuminate\Support\Facades\Route::has('dashboard.target'))
        <form method="POST" action="{{ route('dashboard.target') }}" style="display:flex;gap:0.4rem;align-items:center;">
            @csrf
            <input type="number" name="weekly_email_target" value="{{ $weeklyTarget }}" min="1" max="1000"
                   style="width:64px;background:transparent;border:1px solid var(--border-hover);border-radius:6px;padding:0.3rem 0.4rem;color:var(--text);font-size:0.68rem;text-align:center;">
            <button type="submit" class="btn btn-ghost" style="font-size:0.62rem;padding:0.35rem 0.6rem;">Set Goal</button>
        </form>
        @endif
    </div>
    @if($sentThisWeek >= $weeklyTarget)
        <div style="font-size:0.68rem;color:var(--accent);margin-top:0.6rem;">🎉 Weekly goal crushed — {{ $unlockedCount }}/{{ count($achievements) }} achievements unlocked.</div>
    @else
        <div style="font-size:0.68rem;color:var(--muted);margin-top:0.6rem;">{{ $weeklyTarget - $sentThisWeek }} more this week to hit your goal · {{ $unlockedCount }}/{{ count($achievements) }} achievements unlocked.</div>
    @endif
</div>

{{-- Achievements --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:0.75rem;margin-bottom:1.5rem;">
    @foreach($achievements as $a)
    <div style="display:flex;align-items:center;gap:0.6rem;padding:0.7rem 0.9rem;border:1px solid {{ $a['unlocked'] ? 'rgba(74,222,128,0.3)' : 'var(--border)' }};background:{{ $a['unlocked'] ? 'var(--accent-dim)' : 'var(--muted2)' }};border-radius:10px;opacity:{{ $a['unlocked'] ? 1 : 0.5 }};">
        <span style="font-size:1.15rem;">{{ $a['unlocked'] ? $a['icon'] : '🔒' }}</span>
        <div>
            <div style="font-size:0.68rem;font-family:'Syne',sans-serif;font-weight:600;color:{{ $a['unlocked'] ? 'var(--accent)' : 'var(--muted)' }};">{{ $a['label'] }}</div>
            <div style="font-size:0.55rem;color:var(--muted);letter-spacing:0.08em;">{{ $a['unlocked'] ? 'UNLOCKED' : 'LOCKED' }}</div>
        </div>
    </div>
    @endforeach
</div>

<div class="grid-4 mb-4">
    <div class="stat-card">
        <div class="stat-label">Total Campaigns</div>
        <div class="stat-value"><span>{{ $campaignsCount }}</span></div>
        <div class="stat-desc">All time</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Recipients</div>
        <div class="stat-value"><span>{{ $totalRecipients }}</span></div>
        <div class="stat-desc">Across all campaigns</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Emails Sent</div>
        <div class="stat-value"><span>{{ $sent }}</span></div>
        <div class="stat-desc">{{ $draftsCreated }} drafts still ready to send</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Replies Received</div>
        <div class="stat-value"><span style="color:var(--accent);">{{ $replied }}</span></div>
        <div class="stat-desc">Momentum is real</div>
    </div>
</div>

{{-- Domain + Leads summary --}}
<div class="grid-4 mb-4">
    <div class="stat-card">
        <div class="stat-label">🌐 Domains</div>
        <div class="stat-value"><span>{{ $totalDomains }}</span></div>
        <div class="stat-desc">{{ $soldDomains }} sold · <a href="{{ route('domains.index') }}" style="color:var(--accent);">CRM →</a></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Domain Spend</div>
        <div class="stat-value"><span>{{ $domainSpent }}</span></div>
        <div class="stat-desc">Paid to acquire domains</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Sales Revenue</div>
        <div class="stat-value"><span style="color:var(--accent);">{{ $saleRevenue }}</span></div>
        <div class="stat-desc">From sold domains</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">☾ Saved Leads</div>
        <div class="stat-value"><span>{{ $totalLeads }}</span></div>
        <div class="stat-desc">{{ $soldLeads }} sold · <a href="{{ route('leads.index') }}" style="color:var(--accent);">Leads Saver →</a></div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Recent Campaigns</div>
                <div class="card-sub">Your latest outbound campaigns</div>
            </div>
            <a href="{{ route('campaigns.index') }}" class="btn btn-ghost">View All</a>
        </div>
        @if($campaignsCount === 0)
            <div class="empty">
                <div class="empty-icon">◎</div>
                <h3>No campaigns yet</h3>
                <p>Create your first campaign to get started</p>
            </div>
        @else
            <div style="display:flex;flex-direction:column;">
                @foreach($user->campaigns()->orderBy('created_at','desc')->take(5)->get() as $campaign)
                <a href="{{ route('campaigns.show', $campaign->id) }}" style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem 0;border-bottom:1px solid var(--border);text-decoration:none;color:var(--text);">
                    <div style="width:30px;height:30px;border-radius:8px;background:var(--accent-dim);border:1px solid rgba(74,222,128,0.2);display:flex;align-items:center;justify-content:center;font-size:0.8rem;">{{ $campaign->template_type === 'personal' ? '👤' : '🏢' }}</div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:0.78rem;font-family:'Syne',sans-serif;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $campaign->name }}</div>
                        <div style="font-size:0.62rem;color:var(--muted);">🌐 {{ $campaign->domain }} · {{ $campaign->recipients()->count() }} recipients</div>
                    </div>
                    <span style="font-size:0.65rem;color:var(--accent);">{{ $campaign->created_at->diffForHumans() }}</span>
                </a>
                @endforeach
            </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">⚡ Recent Replies & Wins</div>
                <div class="card-sub">Proof that sending works — keep going</div>
            </div>
            <a href="{{ route('leads.index') }}" class="btn btn-ghost">Leads Saver</a>
        </div>
        @if($recentReplies->isEmpty())
            <div class="empty">
                <div class="empty-icon">✉</div>
                <h3>No replies yet</h3>
                <p>Every sender started at zero. Your next reply is out there.</p>
            </div>
        @else
            <div style="display:flex;flex-direction:column;">
                @foreach($recentReplies as $r)
                <div style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem 0;border-bottom:1px solid var(--border);">
                    <div style="width:30px;height:30px;border-radius:50%;background:var(--accent-dim);border:1px solid rgba(74,222,128,0.2);display:flex;align-items:center;justify-content:center;font-size:0.8rem;">✨</div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:0.78rem;font-family:'Syne',sans-serif;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $r->email }}</div>
                        <div style="font-size:0.62rem;color:var(--muted);">{{ $r->company_name ?? '—' }} · {{ $r->updated_at->diffForHumans() }}</div>
                    </div>
                    <span class="badge badge-green">Replied</span>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="grid-2" style="margin-top:1rem;">
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">🌐 Domain Snapshot</div>
                <div class="card-sub">Where your portfolio stands</div>
            </div>
            <a href="{{ route('domains.index') }}" class="btn btn-ghost">Domain CRM</a>
        </div>
        @if(!$hasCrm || $totalDomains === 0)
            <div class="empty">
                <div class="empty-icon">🌐</div>
                <h3>No domains tracked</h3>
                <p>Add your domains to track spend & mark them sold</p>
            </div>
        @else
            <div style="display:flex;flex-direction:column;">
                @foreach($user->domains()->orderBy('created_at','desc')->take(6)->get() as $domain)
                <div style="display:flex;align-items:center;gap:0.75rem;padding:0.7rem 0;border-bottom:1px solid var(--border);">
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:0.78rem;font-family:'Syne',sans-serif;font-weight:600;">{{ $domain->domain }}</div>
                        <div style="font-size:0.62rem;color:var(--muted);">Cost {{ $domain->price }} {{ $domain->currency }} · {{ $domain->outboundCount() }} outbound</div>
                    </div>
                    <span class="badge {{ $domain->status === 'sold' ? 'badge-green' : ($domain->status === 'outbounding' ? 'badge-yellow' : 'badge-gray') }}">
                        {{ ucfirst($domain->status) }}
                    </span>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">☾ Leads Snapshot</div>
                <div class="card-sub">Your saved lead pipeline</div>
            </div>
            <a href="{{ route('leads.index') }}" class="btn btn-ghost">Leads Saver</a>
        </div>
        @if(!$hasCrm || $totalLeads === 0)
            <div class="empty">
                <div class="empty-icon">☾</div>
                <h3>No saved leads</h3>
                <p>Leads you save on campaign pages appear here automatically</p>
            </div>
        @else
            <div style="display:flex;flex-direction:column;">
                @foreach($user->leads()->orderBy('updated_at','desc')->take(6)->get() as $lead)
                <div style="display:flex;align-items:center;gap:0.75rem;padding:0.7rem 0;border-bottom:1px solid var(--border);">
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:0.78rem;font-family:'Syne',sans-serif;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $lead->email }}</div>
                        <div style="font-size:0.62rem;color:var(--muted);">{{ $lead->company_name ?? '—' }} · {{ $lead->domain ?? 'no domain' }}</div>
                    </div>
                    <span class="badge {{ $lead->status === 'sold' ? 'badge-green' : ($lead->status === 'replied' ? 'badge-yellow' : 'badge-gray') }}">
                        {{ ucfirst($lead->status) }}
                    </span>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@endsection