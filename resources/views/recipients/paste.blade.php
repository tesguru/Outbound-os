@extends('layouts.app')

@section('title', 'Add Recipients')
@section('subtitle', 'Paste emails for — ' . $campaign->name)

@section('topbar-actions')
    <a href="{{ route('campaigns.show', $campaign->id) }}" class="btn btn-ghost">← Back</a>
@endsection

@section('content')

<div style="max-width:700px;">

    {{-- Campaign Info Bar --}}
    <div style="display:flex;align-items:center;gap:1rem;padding:0.875rem 1.25rem;background:var(--surface);border:1px solid var(--border);border-radius:10px;margin-bottom:1.5rem;">
        <div style="width:36px;height:36px;border-radius:8px;background:var(--accent-dim);border:1px solid rgba(74,222,128,0.2);display:flex;align-items:center;justify-content:center;font-size:1rem;">
            {{ $campaign->template_type === 'personal' ? '👤' : '🏢' }}
        </div>
        <div style="flex:1;">
            <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:0.85rem;">{{ $campaign->name }}</div>
            <div style="font-size:0.65rem;color:var(--muted);">{{ ucfirst($campaign->template_type) }} campaign · {{ $campaign->gmailAccounts->count() }} account(s)</div>
        </div>
        <div style="display:flex;gap:0.5rem;">
            @foreach($campaign->gmailAccounts as $account)
            <div style="padding:0.25rem 0.65rem;background:var(--bg);border:1px solid var(--border);border-radius:20px;font-size:0.62rem;color:var(--muted);">
                {{ $account->email }} <span style="color:var(--accent);">{{ $account->pivot->recipient_limit }} limit</span>
            </div>
            @endforeach
        </div>
    </div>

    <form action="{{ route('campaigns.recipients.analyse', $campaign->id) }}" method="POST">
        @csrf

        {{-- Card 1: Recipient Emails --}}
        <div class="card" style="margin-bottom:1rem;">
            <div class="card-title" style="margin-bottom:0.3rem;">Paste Recipients</div>
            <div class="card-sub" style="margin-bottom:1.5rem;">
                Paste emails separated by newline, comma, or semicolon — duplicates and invalid emails are removed automatically
            </div>

            <div class="form-group">
                <label class="form-label">Email Addresses</label>
                <textarea
                    name="emails"
                    class="form-input"
                    rows="14"
                    placeholder="john@acme.com&#10;sarah@techco.com&#10;mike@startup.io&#10;lisa@agency.com&#10;..."
                    required
                    style="resize:vertical;line-height:1.8;font-size:0.8rem;"
                >{{ old('emails') }}</textarea>
                @error('emails')
                    <div style="color:var(--red);font-size:0.65rem;margin-top:0.3rem;">{{ $message }}</div>
                @enderror
            </div>

            {{-- Live counter --}}
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:0.75rem;padding:0.75rem 1rem;background:var(--bg);border:1px solid var(--border);border-radius:8px;">
                <div style="font-size:0.68rem;color:var(--muted);">
                    Emails detected: <span id="emailCount" style="color:var(--accent);font-family:'Syne',sans-serif;font-weight:700;">0</span>
                </div>
                <div style="font-size:0.65rem;color:var(--muted);">
                    System will auto-clean · deduplicate · fetch names
                </div>
            </div>

            {{-- What happens next info --}}
            <div style="margin-top:1rem;padding:0.875rem 1rem;background:var(--accent-dim);border:1px solid rgba(74,222,128,0.15);border-radius:8px;">
                <div style="font-size:0.72rem;color:var(--accent);font-family:'Syne',sans-serif;font-weight:600;margin-bottom:0.5rem;">What happens next:</div>
                <div style="display:flex;flex-direction:column;gap:0.3rem;">
                    <div style="font-size:0.68rem;color:var(--muted);">1. Invalid emails removed automatically</div>
                    <div style="font-size:0.68rem;color:var(--muted);">2. Duplicates removed automatically</div>
                    <div style="font-size:0.68rem;color:var(--muted);">3. Real names fetched from Gmail contacts</div>
                    <div style="font-size:0.68rem;color:var(--muted);">4. Company names extracted from email domains</div>
                    <div style="font-size:0.68rem;color:var(--muted);">5. You review and confirm before saving</div>
                </div>
            </div>
        </div>

        {{-- Card 2: Campaign Websites (separate from recipients) --}}
        <div class="card" style="margin-bottom:1rem;">
            <div class="card-title" style="margin-bottom:0.3rem;">🔗 Campaign Websites</div>
            <div class="card-sub" style="margin-bottom:1.5rem;">
                Paste website URLs — one per line. These are saved to the campaign (not to individual recipients) so you can open them all quickly from the campaign page.
            </div>

            <div class="form-group">
                <label class="form-label">Website URLs <span style="color:var(--muted);font-weight:400;font-size:0.65rem;">(optional)</span></label>
                <textarea
                    name="websites"
                    class="form-input"
                    rows="10"
                    placeholder="https://facebook.com/acme&#10;https://techco.com&#10;https://startup.io&#10;https://linkedin.com/company/agency&#10;..."
                    style="resize:vertical;line-height:1.8;font-size:0.8rem;"
                >{{ old('websites') }}</textarea>
            </div>

            {{-- URL live counter --}}
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:0.75rem;padding:0.75rem 1rem;background:var(--bg);border:1px solid var(--border);border-radius:8px;">
                <div style="font-size:0.68rem;color:var(--muted);">
                    URLs detected: <span id="urlCount" style="color:var(--accent);font-family:'Syne',sans-serif;font-weight:700;">0</span>
                </div>
                <div style="font-size:0.65rem;color:var(--muted);">
                    Saved to campaign · open all in one click from campaign page
                </div>
            </div>
        </div>

        <div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1rem;">
            <a href="{{ route('campaigns.show', $campaign->id) }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">Analyse Recipients →</button>
        </div>

    </form>
</div>

@endsection

@push('scripts')
<script>
    // Email counter
    const textarea   = document.querySelector('textarea[name="emails"]');
    const counter    = document.getElementById('emailCount');
    const emailRegex = /[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/g;

    function countEmails() {
        const matches = textarea.value.match(emailRegex);
        counter.textContent = matches ? matches.length : 0;
    }

    textarea.addEventListener('input', countEmails);
    countEmails();

    // URL counter
    const urlTextarea = document.querySelector('textarea[name="websites"]');
    const urlCounter  = document.getElementById('urlCount');

    function countUrls() {
        const lines = urlTextarea.value.split('\n').filter(l => l.trim().length > 0);
        urlCounter.textContent = lines.length;
    }

    urlTextarea.addEventListener('input', countUrls);
    countUrls();
</script>
@endpush