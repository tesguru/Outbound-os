@extends('layouts.app')

@section('title', 'Dashboard')
@section('subtitle', 'Overview of your outbound activity')

@section('topbar-actions')
    <a href="{{ route('campaigns.create') }}" class="btn btn-primary">+ New Campaign</a>
@endsection

@section('content')

<div class="grid-4 mb-4">
    <div class="stat-card">
        <div class="stat-label">Total Campaigns</div>
        <div class="stat-value"><span>{{ Auth::user()->campaigns()->count() }}</span></div>
        <div class="stat-desc">All time</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Gmail Accounts</div>
        <div class="stat-value"><span>{{ Auth::user()->gmailAccounts()->where('is_active', true)->count() }}</span></div>
        <div class="stat-desc">Active accounts</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Recipients</div>
        <div class="stat-value"><span>0</span></div>
        <div class="stat-desc">Across all campaigns</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Drafts Created</div>
        <div class="stat-value"><span>0</span></div>
        <div class="stat-desc">Ready to send</div>
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
        <div class="empty">
            <div class="empty-icon">◎</div>
            <h3>No campaigns yet</h3>
            <p>Create your first campaign to get started</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Gmail Accounts</div>
                <div class="card-sub">Connected sending accounts</div>
            </div>
            <a href="{{ route('google.add-account') }}" class="btn btn-ghost">+ Add</a>
        </div>
        @forelse(Auth::user()->gmailAccounts as $account)
            <div class="flex items-center gap-3" style="padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                @if($account->avatar)
                    <img src="{{ $account->avatar }}" style="width:28px;height:28px;border-radius:50%;border:1px solid var(--border-hover);">
                @else
                    <div style="width:28px;height:28px;border-radius:50%;background:var(--accent-dim);display:flex;align-items:center;justify-content:center;font-size:0.7rem;color:var(--accent);font-family:'Syne',sans-serif;font-weight:700;">
                        {{ strtoupper(substr($account->email, 0, 1)) }}
                    </div>
                @endif
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.75rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $account->email }}</div>
                    <div style="font-size:0.62rem;color:var(--muted);">Limit: {{ $account->daily_limit }}/day</div>
                </div>
                <span class="badge {{ $account->is_active ? 'badge-green' : 'badge-red' }}">
                    {{ $account->is_active ? 'Active' : 'Paused' }}
                </span>
            </div>
        @empty
            <div class="empty">
                <div class="empty-icon">✉</div>
                <h3>No accounts connected</h3>
                <p>Add a Gmail account to start sending</p>
            </div>
        @endforelse
    </div>
</div>

@endsection