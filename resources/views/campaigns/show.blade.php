@extends('layouts.app')

@section('title', $campaign->name)
@section('subtitle', ucfirst($campaign->status) . ' campaign · ' . $campaign->recipients()->count() . ' recipients')

@section('topbar-actions')
    <a href="{{ route('campaigns.index') }}" class="btn btn-ghost">← Back</a>
    <a href="{{ route('campaigns.recipients.paste', $campaign->id) }}" class="btn btn-ghost">+ Add Recipients</a>
    <a href="{{ route('campaigns.sequences', $campaign->id) }}" class="btn btn-ghost">⚙️ Sequences</a>

    @if($statusCounts['draft_created'] > 0)
    <button class="btn btn-ghost" onclick="document.getElementById('markSentModal').classList.add('open')">
        ✅ Mark Sent ({{ $statusCounts['draft_created'] }})
    </button>
    @endif

    @if($statusCounts['pending'] > 0)
    <button class="btn btn-primary" onclick="createDrafts()">
        ⚡ Create {{ $statusCounts['pending'] }} Drafts
    </button>
    @endif

    @if($statusCounts['sent'] > 0 && $campaign->followupSequences->count() > 0)
    <button class="btn btn-ghost" onclick="document.getElementById('followupModal').classList.add('open')">
        🔁 Create Follow-ups
    </button>
    @endif
@endsection

@section('content')

{{-- Loading Overlay --}}
<div id="loadingOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.85);z-index:999;align-items:center;justify-content:center;flex-direction:column;gap:1.5rem;">
    <div style="text-align:center;">
        <div style="width:56px;height:56px;border:3px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin 0.8s linear infinite;margin:0 auto 1.5rem;"></div>
        <div id="loadingTitle" style="font-family:'Syne',sans-serif;font-weight:700;font-size:1.1rem;color:var(--text);margin-bottom:0.5rem;"></div>
        <div id="loadingSubtitle" style="font-size:0.75rem;color:var(--muted);">Please wait — do not close this tab</div>
        <div style="display:flex;gap:0.4rem;justify-content:center;margin-top:1.25rem;">
            <div style="width:8px;height:8px;border-radius:50%;background:var(--accent);animation:dotPulse 1.2s ease-in-out infinite;"></div>
            <div style="width:8px;height:8px;border-radius:50%;background:var(--accent);animation:dotPulse 1.2s ease-in-out 0.2s infinite;"></div>
            <div style="width:8px;height:8px;border-radius:50%;background:var(--accent);animation:dotPulse 1.2s ease-in-out 0.4s infinite;"></div>
        </div>
        <div id="loadingTimer" style="margin-top:1rem;font-size:0.68rem;color:var(--muted);display:none;">
            ⏱ This may take a while for large batches — hang tight...
        </div>
    </div>
</div>

{{-- Result Toast --}}
<div id="resultToast" style="display:none;position:fixed;bottom:2rem;right:2rem;z-index:1000;padding:1.25rem 1.5rem;background:var(--surface);border:1px solid var(--border-hover);border-radius:12px;max-width:420px;box-shadow:0 8px 32px rgba(0,0,0,0.5);animation:fadeUp 0.3s ease both;">
    <div id="toastMessage" style="font-size:0.8rem;line-height:1.5;margin-bottom:0.875rem;"></div>
    <button onclick="reloadPage()" style="width:100%;padding:0.6rem;background:var(--accent);border:none;border-radius:8px;color:#000;font-family:'Syne',sans-serif;font-weight:700;font-size:0.78rem;cursor:pointer;letter-spacing:0.01em;">
        OK — Reload Page →
    </button>
</div>

{{-- Status Overview --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem;">
    @foreach([
        ['label'=>'Pending',       'key'=>'pending',       'color'=>'var(--muted)'],
        ['label'=>'Draft Created', 'key'=>'draft_created', 'color'=>'var(--blue)'],
        ['label'=>'Sent',          'key'=>'sent',          'color'=>'var(--yellow)'],
        ['label'=>'Replied',       'key'=>'replied',       'color'=>'var(--accent)'],
    ] as $s)
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.25rem;text-align:center;">
        <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.8rem;color:{{ $s['color'] }};">{{ $statusCounts[$s['key']] }}</div>
        <div style="font-size:0.62rem;color:var(--muted);letter-spacing:0.08em;text-transform:uppercase;margin-top:0.3rem;">{{ $s['label'] }}</div>
    </div>
    @endforeach
</div>

{{-- Campaign Info + Gmail Accounts --}}
<div class="grid-2" style="gap:1rem;margin-bottom:1.5rem;">

    {{-- Campaign Info --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Campaign Info</div>
                <div class="card-sub">Templates and settings</div>
            </div>
        </div>
        <div style="display:flex;flex-direction:column;">

            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.65rem 0;border-bottom:1px solid var(--border);">
                <span style="font-size:0.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;">Gmail Label</span>
                <span style="font-size:0.75rem;color:var(--accent);">{{ $campaign->gmail_label_name ?? '—' }}</span>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.65rem 0;border-bottom:1px solid var(--border);">
                <span style="font-size:0.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;">🌐 Domain</span>
                <span style="font-size:0.75rem;color:var(--accent);">{{ $campaign->domain ?? '—' }}</span>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.65rem 0;border-bottom:1px solid var(--border);">
                <span style="font-size:0.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;">👤 Personal Initial</span>
                <span style="font-size:0.75rem;">{{ $campaign->personalInitialTemplate?->name ?? '—' }}</span>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.65rem 0;border-bottom:1px solid var(--border);">
                <span style="font-size:0.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;">🏢 Company Initial</span>
                <span style="font-size:0.75rem;">{{ $campaign->companyInitialTemplate?->name ?? '—' }}</span>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.65rem 0;border-bottom:1px solid var(--border);">
                <span style="font-size:0.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;">👤 Personal Follow-ups</span>
                <span style="font-size:0.75rem;color:var(--blue);">{{ $campaign->personalFollowupSequences->count() }} sequences</span>
            </div>

            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.65rem 0;">
                <span style="font-size:0.65rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;">🏢 Company Follow-ups</span>
                <span style="font-size:0.75rem;color:var(--yellow);">{{ $campaign->companyFollowupSequences->count() }} sequences</span>
            </div>

        </div>
    </div>

    {{-- Gmail Accounts --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Gmail Accounts</div>
                <div class="card-sub">Sending accounts for this campaign</div>
            </div>
        </div>
        @foreach($campaign->gmailAccounts as $account)
        @php $accountRecipients = $account->recipients->where('campaign_id', $campaign->id); @endphp
        <div style="padding:0.75rem 0;border-bottom:1px solid var(--border);">
            <div style="display:flex;align-items:center;gap:0.65rem;margin-bottom:0.5rem;">
                @if($account->avatar)
                    <img src="{{ $account->avatar }}" style="width:28px;height:28px;border-radius:50%;border:1px solid var(--border-hover);">
                @else
                    <div style="width:28px;height:28px;border-radius:50%;background:var(--accent-dim);border:1px solid rgba(74,222,128,0.2);display:flex;align-items:center;justify-content:center;font-size:0.7rem;color:var(--accent);font-family:'Syne',sans-serif;font-weight:700;">
                        {{ strtoupper(substr($account->email, 0, 1)) }}
                    </div>
                @endif
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.75rem;font-family:'Syne',sans-serif;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $account->email }}</div>
                    <div style="font-size:0.62rem;color:var(--muted);">{{ $accountRecipients->count() }} / {{ $account->pivot->recipient_limit }} recipients</div>
                </div>
            </div>
            @php $pct = $account->pivot->recipient_limit > 0 ? min(100, ($accountRecipients->count() / $account->pivot->recipient_limit) * 100) : 0; @endphp
            <div style="height:3px;background:var(--border);border-radius:2px;overflow:hidden;">
                <div style="height:100%;width:{{ $pct }}%;background:var(--accent);border-radius:2px;"></div>
            </div>
        </div>
        @endforeach
    </div>

</div>

{{-- Recipients by Account --}}
@foreach($campaign->gmailAccounts as $account)
@php $accountRecipients = $account->recipients->where('campaign_id', $campaign->id); @endphp
@if($accountRecipients->count())
<div class="card" style="margin-bottom:1rem;">
    <div class="card-header">
        <div style="display:flex;align-items:center;gap:0.65rem;">
            @if($account->avatar)
                <img src="{{ $account->avatar }}" style="width:28px;height:28px;border-radius:50%;border:1px solid var(--border-hover);">
            @else
                <div style="width:28px;height:28px;border-radius:50%;background:var(--accent-dim);display:flex;align-items:center;justify-content:center;font-size:0.7rem;color:var(--accent);font-family:'Syne',sans-serif;font-weight:700;">
                    {{ strtoupper(substr($account->email, 0, 1)) }}
                </div>
            @endif
            <div>
                <div class="card-title">{{ $account->email }}</div>
                <div class="card-sub">{{ $accountRecipients->count() }} recipients assigned</div>
            </div>
        </div>
        <button class="btn btn-ghost" style="padding:0.4rem 0.75rem;font-size:0.68rem;" onclick="toggleTable('table_{{ $account->id }}')">Toggle ↕</button>
    </div>

    <div id="table_{{ $account->id }}">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Name Used</th>
                        <th>Follow-ups Done</th>
                        <th>Status</th>
                        <th>Thread</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accountRecipients as $recipient)
                    <tr>
                        <td style="font-size:0.75rem;">{{ $recipient->email }}</td>
                        <td>
                            @if($recipient->personalization_type === 'first_name')
                                <span style="font-size:0.72rem;">{{ $recipient->first_name }}</span>
                                <span class="badge badge-blue" style="margin-left:0.3rem;">👤 first name</span>
                            @else
                                <span style="font-size:0.72rem;">{{ $recipient->company_name }} Team</span>
                                <span class="badge badge-yellow" style="margin-left:0.3rem;">🏢 company</span>
                            @endif
                        </td>

                        <td>
                            @php $followupCount = $recipient->followups()->count(); @endphp
                            @if($followupCount > 0)
                                <span style="font-size:0.72rem;color:var(--accent);font-family:'Syne',sans-serif;font-weight:700;">{{ $followupCount }}</span>
                                <span style="font-size:0.65rem;color:var(--muted);"> follow-up{{ $followupCount > 1 ? 's' : '' }}</span>
                            @else
                                <span style="font-size:0.65rem;color:var(--muted);">—</span>
                            @endif
                        </td>

                        <td>
                            @php
                                $statusMap = [
                                    'pending'       => ['badge-gray',   'Pending'],
                                    'draft_created' => ['badge-blue',   'Draft Created'],
                                    'sent'          => ['badge-yellow', 'Sent'],
                                    'replied'       => ['badge-green',  'Replied'],
                                ];
                                $s = $statusMap[$recipient->status] ?? ['badge-gray', $recipient->status];
                            @endphp
                            <span class="badge {{ $s[0] }}">{{ $s[1] }}</span>
                        </td>

                        <td style="font-size:0.65rem;color:var(--muted);">
                            {{ $recipient->thread_id ? substr($recipient->thread_id, 0, 12).'...' : '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endforeach

@if($campaign->recipients()->count() === 0)
<div class="card">
    <div class="empty">
        <div class="empty-icon">📋</div>
        <h3>No recipients yet</h3>
        <p>Paste your recipient emails to get started</p>
        <br>
        <a href="{{ route('campaigns.recipients.paste', $campaign->id) }}" class="btn btn-primary">+ Add Recipients</a>
    </div>
</div>
@endif

{{-- ============================================================ --}}
{{-- CAMPAIGN WEBSITES — completely separate from recipients      --}}
{{-- ============================================================ --}}
@if($campaign->websites->count())
<div class="card" style="margin-top:1.5rem;">
    <div class="card-header">
        <div>
            <div class="card-title">🔗 Campaign Websites</div>
            <div class="card-sub">{{ $campaign->websites->count() }} URL(s) saved to this campaign — click to open individually or blast all at once</div>
        </div>
        <button class="btn btn-primary" style="padding:0.4rem 0.875rem;font-size:0.68rem;" onclick="openAllWebsites()">
            🚀 Open All in Tabs
        </button>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>URL</th>
                    <th style="width:90px;text-align:center;">Open</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campaign->websites as $i => $site)
                <tr>
                    <td style="font-size:0.65rem;color:var(--muted);">{{ $i + 1 }}</td>
                    <td style="font-size:0.75rem;max-width:500px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $site->url }}
                    </td>
                    <td style="text-align:center;">
                        <a href="{{ $site->url }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="btn btn-ghost"
                           style="padding:0.3rem 0.65rem;font-size:0.65rem;">
                            Open ↗
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Follow-up Modal --}}
<div class="modal-overlay" id="followupModal">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('followupModal').classList.remove('open')">✕</button>
        <div class="modal-title">🔁 Create Follow-up Drafts</div>
        <div class="modal-sub">Next sequence for {{ $statusCounts['sent'] ?? 0 }} sent recipients — auto picks personal or company per recipient</div>

        @php
            $needsPrice = $campaign->followupSequences
                ->filter(fn($s) => $s->template?->has_price)
                ->isNotEmpty();
        @endphp

        <div style="padding:0.75rem 1rem;background:var(--bg);border:1px solid var(--border);border-radius:8px;margin-bottom:1rem;">
            <div style="font-size:0.65rem;color:var(--muted);margin-bottom:0.5rem;text-transform:uppercase;letter-spacing:0.08em;">Sequences configured:</div>
            <div style="display:flex;gap:2rem;">
                <div>
                    <div style="font-size:0.65rem;color:var(--blue);margin-bottom:0.35rem;">👤 Personal</div>
                    @forelse($campaign->personalFollowupSequences as $seq)
                        <div style="font-size:0.68rem;color:var(--muted);">{{ $seq->sequence }}. {{ $seq->template->name }}</div>
                    @empty
                        <div style="font-size:0.65rem;color:var(--muted);">None set</div>
                    @endforelse
                </div>
                <div>
                    <div style="font-size:0.65rem;color:var(--yellow);margin-bottom:0.35rem;">🏢 Company</div>
                    @forelse($campaign->companyFollowupSequences as $seq)
                        <div style="font-size:0.68rem;color:var(--muted);">{{ $seq->sequence }}. {{ $seq->template->name }}</div>
                    @empty
                        <div style="font-size:0.65rem;color:var(--muted);">None set</div>
                    @endforelse
                </div>
            </div>
        </div>

        @if($needsPrice)
        <div style="padding:0.75rem 1rem;background:var(--yellow-dim);border:1px solid rgba(250,204,21,0.2);border-radius:8px;margin-bottom:1rem;">
            <div style="font-size:0.72rem;color:var(--yellow);font-family:'Syne',sans-serif;font-weight:600;">💰 Price Required</div>
            <div style="font-size:0.65rem;color:var(--muted);margin-top:0.2rem;">A follow-up template contains @{{price}} — set the price below</div>
        </div>
        <div class="form-group">
            <label class="form-label">Price For This Follow-up</label>
            <input type="number" id="followupPrice" class="form-input" placeholder="e.g. 299" min="0" step="0.01">
        </div>
        @else
            <input type="hidden" id="followupPrice" value="">
            <div style="padding:0.75rem 1rem;background:var(--accent-dim);border:1px solid rgba(74,222,128,0.15);border-radius:8px;margin-bottom:1rem;">
                <div style="font-size:0.72rem;color:var(--accent);">✅ No price needed — ready to create follow-up drafts</div>
            </div>
        @endif

        <div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1.25rem;">
            <button type="button" class="btn btn-ghost" onclick="document.getElementById('followupModal').classList.remove('open')">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="createFollowups()">Create Follow-up Drafts →</button>
        </div>
    </div>
</div>

{{-- Mark Sent Modal --}}
<div class="modal-overlay" id="markSentModal">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('markSentModal').classList.remove('open')">✕</button>
        <div class="modal-title">✅ Mark Drafts as Sent</div>
        <div class="modal-sub">Select recipients you have manually sent from Gmail</div>

        <form action="{{ route('campaigns.recipients.mark-sent', $campaign->id) }}" method="POST">
            @csrf

            <div style="display:flex;gap:0.5rem;margin-bottom:0.75rem;">
                <button type="button" class="btn btn-ghost" style="padding:0.35rem 0.75rem;font-size:0.65rem;" onclick="selectAllSent(true)">✅ Select All</button>
                <button type="button" class="btn btn-ghost" style="padding:0.35rem 0.75rem;font-size:0.65rem;" onclick="selectAllSent(false)">✕ Deselect All</button>
                <span style="font-size:0.65rem;color:var(--muted);align-self:center;margin-left:auto;">
                    {{ $campaign->recipients()->where('status','draft_created')->count() }} drafts ready
                </span>
            </div>

            <div style="max-height:320px;overflow-y:auto;margin-bottom:1rem;border:1px solid var(--border);border-radius:8px;">
                @forelse($campaign->recipients()->where('status','draft_created')->get() as $r)
                <label style="display:flex;align-items:center;gap:0.75rem;padding:0.65rem 0.875rem;border-bottom:1px solid var(--border);cursor:pointer;transition:background 0.1s;">
                    <input type="checkbox" name="recipient_ids[]" value="{{ $r->id }}" class="sent-checkbox" style="accent-color:var(--accent);width:15px;height:15px;flex-shrink:0;">
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:0.75rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $r->email }}</div>
                        <div style="font-size:0.62rem;color:var(--muted);margin-top:0.1rem;">
                            {{ $r->first_name }} · {{ $r->company_name }} ·
                            <span style="color:var(--blue);">{{ $r->gmailAccount->email }}</span>
                        </div>
                    </div>
                </label>
                @empty
                <div style="padding:1.5rem;text-align:center;font-size:0.75rem;color:var(--muted);">No draft recipients found</div>
                @endforelse
            </div>

            <div style="display:flex;gap:0.75rem;justify-content:flex-end;">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('markSentModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary">Mark as Sent →</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('styles')
<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}
@keyframes dotPulse {
    0%, 100% { opacity: 0.3; transform: scale(0.8); }
    50%       { opacity: 1;   transform: scale(1.2); }
}
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
@endpush

@push('scripts')
<script>
const CAMPAIGN_ID       = {{ $campaign->id }};
const CSRF_TOKEN        = '{{ csrf_token() }}';
const CAMPAIGN_WEBSITES = @json($campaign->websites->pluck('url'));
let   timerHandle       = null;

// ============================================================
// LOADING HELPERS
// ============================================================
function showLoading(title) {
    document.getElementById('loadingTitle').textContent     = title;
    document.getElementById('loadingSubtitle').textContent  = 'Please wait — do not close this tab';
    document.getElementById('loadingTimer').style.display   = 'none';
    document.getElementById('loadingOverlay').style.display = 'flex';

    timerHandle = setTimeout(() => {
        document.getElementById('loadingTimer').style.display = 'block';
    }, 5000);
}

function updateLoadingStatus(text) {
    document.getElementById('loadingSubtitle').textContent = text;
}

function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
    if (timerHandle) clearTimeout(timerHandle);
}

function showToast(message) {
    document.getElementById('toastMessage').innerHTML = message;
    document.getElementById('resultToast').style.display = 'block';
}

function reloadPage() {
    window.location.reload();
}

// ============================================================
// OPEN ALL CAMPAIGN WEBSITES
// ============================================================
function openAllWebsites() {
    if (!CAMPAIGN_WEBSITES.length) return;
    if (!confirm(`Open all ${CAMPAIGN_WEBSITES.length} website(s) in new tabs?`)) return;
    CAMPAIGN_WEBSITES.forEach(url => window.open(url, '_blank', 'noopener,noreferrer'));
}

// ============================================================
// CREATE DRAFTS
// ============================================================
async function createDrafts() {
    if (!confirm('Create drafts for all {{ $statusCounts["pending"] }} pending recipients?')) return;

    let totalCreated = 0;
    let totalFailed  = 0;

    showLoading('⚡ Creating Drafts...');

    while (true) {
        let data;

        try {
            const res = await fetch(`/campaigns/${CAMPAIGN_ID}/recipients/drafts-batch`, {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept':       'application/json',
                },
                body: JSON.stringify({}),
            });

            data = await res.json();
        } catch (err) {
            console.error('Network error on drafts batch:', err);
            break;
        }

        if (!data.success) {
            console.error('Server error on drafts batch:', data);
            break;
        }

        totalCreated += data.created || 0;
        totalFailed  += data.failed  || 0;

        updateLoadingStatus(
            `✅ ${totalCreated} done · ❌ ${totalFailed} failed · ⏳ ${data.remaining ?? 0} remaining`
        );

        if (data.done) break;
    }

    hideLoading();

    let msg = `✅ <strong>${totalCreated}</strong> drafts created in Gmail!`;
    if (totalFailed > 0) msg += `<br>⚠️ <strong>${totalFailed}</strong> failed.`;
    showToast(msg);
}

// ============================================================
// CREATE FOLLOW-UPS
// ============================================================
async function createFollowups() {
    const priceEl = document.getElementById('followupPrice');
    const price   = priceEl ? priceEl.value.trim() : '';

    if (priceEl && priceEl.type === 'number' && !price) {
        priceEl.focus();
        priceEl.style.borderColor = 'var(--red)';
        return;
    }

    document.getElementById('followupModal').classList.remove('open');

    let totalCreated = 0;
    let totalSkipped = 0;
    let totalFailed  = 0;
    let totalMaxed   = 0;
    let offset       = 0;

    showLoading('🔁 Creating Follow-up Drafts...');

    while (true) {
        let data;

        try {
            const res = await fetch(`/campaigns/${CAMPAIGN_ID}/recipients/followups-batch`, {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept':       'application/json',
                },
                body: JSON.stringify({ offset, price }),
            });

            data = await res.json();
        } catch (err) {
            console.error('Network error on followups batch:', err);
            break;
        }

        if (!data.success) {
            console.error('Server error on followups batch:', data);
            break;
        }

        totalCreated += data.created || 0;
        totalSkipped += data.skipped || 0;
        totalFailed  += data.failed  || 0;
        totalMaxed   += data.maxed   || 0;
        offset        = data.next_offset;

        updateLoadingStatus(
            `✅ ${totalCreated} done · ⏳ ${data.remaining ?? 0} remaining`
        );

        if (data.done) break;
    }

    hideLoading();

    let msg = `✅ <strong>${totalCreated}</strong> follow-up drafts created!`;
    if (totalMaxed   > 0) msg += `<br>ℹ️ <strong>${totalMaxed}</strong> completed all sequences.`;
    if (totalSkipped > 0) msg += `<br>⚠️ <strong>${totalSkipped}</strong> skipped.`;
    if (totalFailed  > 0) msg += `<br>❌ <strong>${totalFailed}</strong> failed.`;
    showToast(msg);
}

// ============================================================
// HELPERS
// ============================================================
function toggleTable(id) {
    const el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}

function selectAllSent(check) {
    document.querySelectorAll('.sent-checkbox').forEach(cb => cb.checked = check);
}
</script>
@endpush