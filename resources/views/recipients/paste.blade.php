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
            <div style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;margin-top:0.75rem;padding:0.75rem 1rem;background:var(--bg);border:1px solid var(--border);border-radius:8px;">
                <div style="font-size:0.68rem;color:var(--muted);">
                    Emails detected: <span id="emailCount" style="color:var(--accent);font-family:'Syne',sans-serif;font-weight:700;">0</span>
                </div>
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <span id="autosaveStatus" style="font-size:0.62rem;color:var(--muted);"></span>
                    <button type="button" class="btn btn-ghost" style="padding:0.35rem 0.75rem;font-size:0.65rem;" onclick="saveLeadsToMaster()">
                        💾 Save to Leads Saver
                    </button>
                    <button type="button" class="btn btn-ghost" style="padding:0.35rem 0.75rem;font-size:0.65rem;" onclick="document.getElementById('importLeadsModal').classList.add('open')">
                        ☾ Import Saved Leads
                    </button>
                </div>
            </div>

            {{-- Autosave result --}}
            <div id="autosaveResult" style="display:none;margin-top:0.6rem;padding:0.6rem 0.875rem;background:var(--accent-dim);border:1px solid rgba(74,222,128,0.2);border-radius:8px;font-size:0.68rem;color:var(--accent);"></div>

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

{{-- Import Saved Leads Modal --}}
<div class="modal-overlay" id="importLeadsModal">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('importLeadsModal').classList.remove('open')">✕</button>
        <div class="modal-title">☾ Import Saved Leads</div>
        <div class="modal-sub">Pick saved leads to add to this campaign — their emails get added to the list below</div>

        <div style="display:flex;gap:0.5rem;margin-bottom:0.75rem;">
            <button type="button" class="btn btn-ghost" style="padding:0.35rem 0.75rem;font-size:0.65rem;" onclick="toggleAllLeads(true)">✅ Select All</button>
            <button type="button" class="btn btn-ghost" style="padding:0.35rem 0.75rem;font-size:0.65rem;" onclick="toggleAllLeads(false)">✕ Deselect</button>
            <span style="font-size:0.65rem;color:var(--muted);align-self:center;margin-left:auto;" id="importSelectedCount">0 selected</span>
        </div>

        <div style="max-height:340px;overflow-y:auto;margin-bottom:1rem;border:1px solid var(--border);border-radius:8px;">
            @forelse($savedLeads as $lead)
            <label style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0.875rem;border-bottom:1px solid var(--border);cursor:pointer;transition:background 0.1s;">
                <input type="checkbox" class="lead-import-checkbox" value="{{ $lead->email }}" style="accent-color:var(--accent);width:15px;height:15px;flex-shrink:0;">
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.75rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $lead->email }}</div>
                    <div style="font-size:0.62rem;color:var(--muted);margin-top:0.1rem;">
                        {{ $lead->first_name ?? '—' }} · {{ $lead->company_name ?? '—' }}
                        @if($lead->domain)
                            · <span style="color:var(--accent);">{{ $lead->domain }}</span>
                        @endif
                    </div>
                </div>
            </label>
            @empty
                <div style="padding:1.5rem;text-align:center;font-size:0.75rem;color:var(--muted);">
                    No saved leads yet. <a href="{{ route('leads.index') }}" style="color:var(--accent);">Save some first →</a>
                </div>
            @endforelse
        </div>

        <div style="display:flex;gap:0.75rem;justify-content:flex-end;">
            <button type="button" class="btn btn-ghost" onclick="document.getElementById('importLeadsModal').classList.remove('open')">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="importSelectedLeads()">Import Selected →</button>
        </div>
    </div>
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
        return matches ? matches.length : 0;
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

    // ============================================================
    // SAVE PASTED LEADS TO MASTER LIST
    // Autosaves 30s after you stop typing + a manual Save button.
    // ============================================================
    const CAMPAIGN_DOMAIN = @json($campaign->domain);
    const CAMPAIGN_ID     = {{ $campaign->id }};
    const CSRF_TOKEN      = '{{ csrf_token() }}';
    const AUTOSAVE_MS     = 30000;
    let   autosaveTimer   = null;
    let   lastSavedRaw    = '';

    function showAutosaveResult(msg) {
        const el = document.getElementById('autosaveResult');
        el.textContent = msg;
        el.style.display = 'block';
        clearTimeout(el._t);
        el._t = setTimeout(() => { el.style.display = 'none'; }, 4000);
    }

    function setAutosaveStatus(text) {
        const el = document.getElementById('autosaveStatus');
        if (el) el.textContent = text;
    }

    async function saveLeadsToMaster() {
        const raw      = textarea.value;
        const detected = countEmails();
        if (!detected) {
            showAutosaveResult('⚠️ No emails detected to save.');
            return;
        }
        if (raw === lastSavedRaw) {
            showAutosaveResult('💾 Already saved — nothing new.');
            return;
        }

        setAutosaveStatus('⏳ Saving…');

        try {
            const res = await fetch('{{ route("leads.bulk-store") }}', {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept':       'application/json',
                },
                body: JSON.stringify({
                    emails:      raw,
                    domain:      CAMPAIGN_DOMAIN,
                    campaign_id: CAMPAIGN_ID,
                }),
            });

            const data = await res.json();

            if (data.success) {
                lastSavedRaw = raw;
                setAutosaveStatus('');
                showAutosaveResult(`💾 Saved ${data.created} new + ${data.updated} existing leads to Leads Saver → <a href="{{ route('leads.index') }}" style="color:var(--accent);text-decoration:underline;">view leads</a>`);
            } else {
                setAutosaveStatus('');
                showAutosaveResult('⚠️ Could not save leads — please try the Analyse button.');
            }
        } catch (e) {
            setAutosaveStatus('');
            showAutosaveResult('⚠️ Network error while saving — your leads are still safe, keep going!');
        }
    }

    // Debounce: autosave after 30s of inactivity
    textarea.addEventListener('input', () => {
        clearTimeout(autosaveTimer);
        setAutosaveStatus('⚡ Auto-saves 30s after you stop typing');
        autosaveTimer = setTimeout(() => {
            setAutosaveStatus('⏳ Auto-saving…');
            saveLeadsToMaster();
        }, AUTOSAVE_MS);
    });

    // ============================================================
    // IMPORT SAVED LEADS
    // ============================================================
    const importCheckboxes = () => document.querySelectorAll('.lead-import-checkbox');

    function updateImportCount() {
        const checked = document.querySelectorAll('.lead-import-checkbox:checked').length;
        document.getElementById('importSelectedCount').textContent = checked + ' selected';
    }

    function toggleAllLeads(check) {
        importCheckboxes().forEach(cb => cb.checked = check);
        updateImportCount();
    }

    document.querySelectorAll('.lead-import-checkbox').forEach(cb => cb.addEventListener('change', updateImportCount));

    function importSelectedLeads() {
        const emails = [...importCheckboxes()].filter(cb => cb.checked).map(cb => cb.value);
        if (!emails.length) return;

        const current = textarea.value.trim();
        const existing = new Set((current.match(emailRegex) || []).map(e => e.toLowerCase()));

        const newEmails = emails.filter(e => !existing.has(e.toLowerCase()));
        if (!newEmails.length) {
            document.getElementById('importLeadsModal').classList.remove('open');
            showAutosaveResult('ℹ️ Those leads are already in the list below.');
            return;
        }

        textarea.value = current ? current + '\n' + newEmails.join('\n') : newEmails.join('\n');
        lastSavedRaw  = '';
        countEmails();

        document.getElementById('importLeadsModal').classList.remove('open');
        showAutosaveResult(`☾ Imported ${newEmails.length} saved lead(s) into this campaign. Click "Analyse Recipients" to continue.`);

        // Restart autosave timer so imported leads get saved with campaign context
        clearTimeout(autosaveTimer);
        autosaveTimer = setTimeout(() => saveLeadsToMaster(), AUTOSAVE_MS);
    }
</script>
@endpush