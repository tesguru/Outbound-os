@extends('layouts.app')

@section('title', 'Campaigns')
@section('subtitle', 'Manage your outbound campaigns')

@section('topbar-actions')
    <a href="{{ route('campaigns.create') }}" class="btn btn-primary">+ New Campaign</a>
@endsection

@section('content')

@if($campaigns->isEmpty())
    <div class="card">
        <div class="empty">
            <div class="empty-icon">◎</div>
            <h3>No campaigns yet</h3>
            <p>Create your first campaign to start sending outbound emails</p>
            <br>
            <a href="{{ route('campaigns.create') }}" class="btn btn-primary">+ Create Campaign</a>
        </div>
    </div>
@else
    <div style="display:flex;flex-direction:column;gap:1rem;">
        @foreach($campaigns as $campaign)
        <div class="card" style="cursor:pointer;" onclick="window.location='{{ route('campaigns.show', $campaign->id) }}'">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:1rem;">
                    <div style="width:42px;height:42px;border-radius:10px;background:var(--accent-dim);border:1px solid rgba(74,222,128,0.2);display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
                        {{ $campaign->template_type === 'personal' ? '👤' : '🏢' }}
                    </div>
                    <div>
                        <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:0.95rem;">{{ $campaign->name }}</div>
                        <div style="font-size:0.68rem;color:var(--muted);margin-top:0.15rem;">
                            {{ ucfirst($campaign->template_type) }} · {{ $campaign->recipients_count }} recipients · Created {{ $campaign->created_at->diffForHumans() }}
                        </div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:0.75rem;">
                    <span class="badge {{ $campaign->status === 'active' ? 'badge-green' : ($campaign->status === 'paused' ? 'badge-yellow' : 'badge-gray') }}">
                        {{ ucfirst($campaign->status) }}
                    </span>
                    <div style="display:flex;gap:0.4rem;" onclick="event.stopPropagation()">
                        <a href="{{ route('campaigns.show', $campaign->id) }}" class="btn btn-ghost" style="padding:0.4rem 0.75rem;font-size:0.68rem;">View</a>
                        <form action="{{ route('campaigns.destroy', $campaign->id) }}" method="POST" onsubmit="return confirm('Delete this campaign?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" style="padding:0.4rem 0.75rem;font-size:0.68rem;">Delete</button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Account pills --}}
            @if($campaign->gmailAccounts->count())
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border);">
                @foreach($campaign->gmailAccounts as $account)
                <div style="display:flex;align-items:center;gap:0.4rem;padding:0.3rem 0.65rem;background:var(--bg);border:1px solid var(--border);border-radius:20px;">
                    <div style="width:6px;height:6px;border-radius:50%;background:var(--accent);"></div>
                    <span style="font-size:0.65rem;color:var(--muted);">{{ $account->email }}</span>
                    <span style="font-size:0.62rem;color:var(--accent);">{{ $account->pivot->recipient_limit }} limit</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach
    </div>
@endif

@endsection