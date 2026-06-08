@extends('layouts.app')

@section('title', 'Create Campaign')
@section('subtitle', 'Set up a new outbound campaign')

@section('topbar-actions')
    <a href="{{ route('campaigns.index') }}" class="btn btn-ghost">← Back</a>
@endsection

@section('content')

<div style="max-width:700px;">
<form action="{{ route('campaigns.store') }}" method="POST" id="campaignForm">
@csrf

{{-- Campaign Name --}}
<div class="form-group">
    <label class="form-label">Campaign Name</label>
    <input type="text" name="name" class="form-input" placeholder="e.g. SaaS Outreach Q2" value="{{ old('name') }}" required>
    @error('name')
        <div style="color:var(--red);font-size:0.65rem;margin-top:0.3rem;">{{ $message }}</div>
    @enderror
</div>

<div class="form-group">
    <label class="form-label">Domain You Are Pitching</label>
    <div style="position:relative;">
        <input type="text" name="domain" class="form-input" placeholder="e.g. acme.com" value="{{ old('domain') }}" required style="padding-left:2.5rem;">
        <span style="position:absolute;left:0.875rem;top:50%;transform:translateY(-50%);font-size:0.75rem;color:var(--muted);">🌐</span>
    </div>
    <div style="font-size:0.62rem;color:var(--muted);margin-top:0.3rem;">
        This replaces @{{domain}} in all your templates
    </div>
    @error('domain')
        <div style="color:var(--red);font-size:0.65rem;margin-top:0.3rem;">{{ $message }}</div>
    @enderror
</div>

{{-- Gmail Accounts --}}
<div class="card" style="margin-bottom:1rem;">
    <div class="card-title" style="margin-bottom:0.3rem;">Assign Gmail Accounts</div>
    <div class="card-sub" style="margin-bottom:1.5rem;">Split recipients across accounts and set limits</div>

    @if($gmailAccounts->isEmpty())
        <div style="padding:1rem;background:var(--yellow-dim);border:1px solid rgba(250,204,21,0.2);border-radius:8px;font-size:0.75rem;color:var(--yellow);">
            ⚠️ No active Gmail accounts. <a href="{{ route('google.add-account') }}" style="color:var(--accent);">Connect one first →</a>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:0.75rem;">
            @foreach($gmailAccounts as $index => $account)
            <div style="display:flex;align-items:center;gap:1rem;padding:0.875rem 1rem;background:var(--bg);border:1px solid var(--border);border-radius:10px;">
                <input type="checkbox" name="gmail_accounts[]" value="{{ $account->id }}" id="account_{{ $account->id }}" style="width:16px;height:16px;accent-color:var(--accent);cursor:pointer;">
                <label for="account_{{ $account->id }}" style="display:flex;align-items:center;gap:0.65rem;flex:1;cursor:pointer;">
                    @if($account->avatar)
                        <img src="{{ $account->avatar }}" style="width:28px;height:28px;border-radius:50%;border:1px solid var(--border-hover);">
                    @else
                        <div style="width:28px;height:28px;border-radius:50%;background:var(--accent-dim);display:flex;align-items:center;justify-content:center;font-size:0.7rem;color:var(--accent);font-family:'Syne',sans-serif;font-weight:700;">
                            {{ strtoupper(substr($account->email, 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <div style="font-size:0.78rem;font-family:'Syne',sans-serif;font-weight:600;">{{ $account->name }}</div>
                        <div style="font-size:0.65rem;color:var(--muted);">{{ $account->email }}</div>
                    </div>
                </label>
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <span style="font-size:0.65rem;color:var(--muted);">Recipient limit:</span>
                    <input type="number" name="recipient_limits[]" value="{{ $account->daily_limit }}" min="1" max="500" class="form-input" style="width:80px;padding:0.4rem 0.5rem;font-size:0.75rem;">
                </div>
            </div>
            @endforeach
        </div>
        @error('gmail_accounts')
            <div style="color:var(--red);font-size:0.65rem;margin-top:0.5rem;">{{ $message }}</div>
        @enderror
    @endif
</div>

{{-- Info box --}}
<div style="padding:0.875rem 1rem;background:var(--accent-dim);border:1px solid rgba(74,222,128,0.15);border-radius:8px;margin-bottom:1.5rem;">
    <div style="font-size:0.72rem;color:var(--accent);font-family:'Syne',sans-serif;font-weight:600;margin-bottom:0.4rem;">What happens next:</div>
    <div style="font-size:0.68rem;color:var(--muted);display:flex;flex-direction:column;gap:0.25rem;">
        <div>1. Campaign is created with your Gmail accounts</div>
        <div>2. You paste your recipient emails</div>
        <div>3. System fetches first names and company names</div>
        <div>4. You review the data and decide — Personal or Company</div>
        <div>5. You pick your templates THEN — smart!</div>
    </div>
</div>

<div style="display:flex;gap:0.75rem;justify-content:flex-end;">
    <a href="{{ route('campaigns.index') }}" class="btn btn-ghost">Cancel</a>
    <button type="submit" class="btn btn-primary">Create & Add Recipients →</button>
</div>

</form>
</div>

@endsection