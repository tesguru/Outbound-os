@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Overview of your outbound activity')

@section('topbar-actions')
    <a href="{{ route('campaigns.create') }}" class="btn btn-primary">+ New Campaign</a>
@endsection

@section('content')

{{-- Motivation banner --}}
<div style="background:linear-gradient(135deg, rgba(74,222,128,0.12), transparent);border:1px solid rgba(74,222,128,0.25);border-radius:14px;padding:1.5rem 2rem;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1.5rem;flex-wrap:wrap;">
    <div style="flex:1;min-width:240px;">
        <div style="font-size:0.62rem;color:var(--accent);letter-spacing:0.15em;text-transform:uppercase;margin-bottom:0.4rem;">🔥 Keep Sending — Never Give Up</div>
        <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:1.1rem;color:var(--text);">{{ $quote }}</div>
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
    </div>
</div>

<div class="grid-4 mb-4">
    <div class="stat-card">
        <div class="stat-label">Total Campaigns</div>
        <div class="stat-value"><span>{{ Auth::user()->campaigns()->count() }}</span></div>
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
        @if(Auth::user()->campaigns()->count() === 0)
            <div class="empty">
                <div class="empty-icon">◎</div>
                <h3>No campaigns yet</h3>
                <p>Create your first campaign to get started</p>
            </div>
        @else
            <div style="display:flex;flex-direction:column;">
                @foreach(Auth::user()->campaigns()->orderBy('created_at','desc')->take(5)->get() as $campaign)
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
        @if(Auth::user()->domains()->count() === 0)
            <div class="empty">
                <div class="empty-icon">🌐</div>
                <h3>No domains tracked</h3>
                <p>Add your domains to track spend & mark them sold</p>
            </div>
        @else
            <div style="display:flex;flex-direction:column;">
                @foreach(Auth::user()->domains()->orderBy('created_at','desc')->take(6)->get() as $domain)
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
        @if(Auth::user()->leads()->count() === 0)
            <div class="empty">
                <div class="empty-icon">☾</div>
                <h3>No saved leads</h3>
                <p>Leads you save on campaign pages appear here automatically</p>
            </div>
        @else
            <div style="display:flex;flex-direction:column;">
                @foreach(Auth::user()->leads()->orderBy('updated_at','desc')->take(6)->get() as $lead)
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