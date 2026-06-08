@extends('layouts.app')

@section('title', 'Review Recipients')
@section('subtitle', 'Split accounts · assign templates per recipient · confirm')

@section('topbar-actions')
    <a href="{{ route('campaigns.recipients.paste', $campaign->id) }}" class="btn btn-ghost">← Back</a>
@endsection

@section('content')

<form action="{{ route('campaigns.recipients.confirm', $campaign->id) }}" method="POST">
@csrf

{{-- STEP 1: Split Recipients Across Accounts --}}
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-title" style="margin-bottom:0.3rem;">Step 1 — Split Recipients Across Accounts</div>
    <div class="card-sub" style="margin-bottom:1.25rem;">
        Total: <span style="color:var(--accent);font-family:'Syne',sans-serif;font-weight:700;">{{ count($analysed) }} recipients</span> — type how many each account should get
    </div>

    <div style="display:flex;flex-direction:column;gap:0.75rem;" id="accountSplitList">
        @foreach($campaign->gmailAccounts as $account)
        <div style="display:flex;align-items:center;gap:1rem;padding:0.875rem 1rem;background:var(--bg);border:1px solid var(--border);border-radius:10px;">
            <div style="display:flex;align-items:center;gap:0.65rem;flex:1;">
                @if($account->avatar)
                    <img src="{{ $account->avatar }}" style="width:28px;height:28px;border-radius:50%;border:1px solid var(--border-hover);">
                @else
                    <div style="width:28px;height:28px;border-radius:50%;background:var(--accent-dim);border:1px solid rgba(74,222,128,0.2);display:flex;align-items:center;justify-content:center;font-size:0.7rem;color:var(--accent);font-family:'Syne',sans-serif;font-weight:700;">
                        {{ strtoupper(substr($account->email, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <div style="font-size:0.78rem;font-family:'Syne',sans-serif;font-weight:600;">{{ $account->email }}</div>
                    <div style="font-size:0.62rem;color:var(--muted);">Max limit: {{ $account->pivot->recipient_limit }}</div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <span style="font-size:0.65rem;color:var(--muted);">Send to:</span>
                <input
                    type="number"
                    name="account_splits[{{ $account->id }}]"
                    class="form-input split-input"
                    data-account-id="{{ $account->id }}"
                    value="0"
                    min="0"
                    max="{{ $account->pivot->recipient_limit }}"
                    style="width:80px;padding:0.4rem 0.5rem;font-size:0.75rem;"
                >
                <span style="font-size:0.65rem;color:var(--muted);">recipients</span>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Split counter --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:1rem;padding:0.75rem 1rem;background:var(--bg);border:1px solid var(--border);border-radius:8px;">
        <div style="font-size:0.68rem;color:var(--muted);">
            Assigned: <span id="assignedCount" style="color:var(--accent);font-family:'Syne',sans-serif;font-weight:700;">0</span>
            / {{ count($analysed) }}
        </div>
        <div id="splitWarning" style="font-size:0.65rem;color:var(--red);display:none;">
            ⚠️ Numbers don't match total recipients
        </div>
        <button type="button" class="btn btn-ghost" style="padding:0.35rem 0.75rem;font-size:0.65rem;" onclick="autoSplit()">
            Auto Split Evenly
        </button>
    </div>
</div>

{{-- STEP 2: Review Recipients + Assign Template Per Row --}}
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-title" style="margin-bottom:0.3rem;">Step 2 — Review Names & Assign Template Per Recipient</div>
    <div class="card-sub" style="margin-bottom:1.25rem;">
        Check first name and company name — pick which template makes sense for each person
    </div>

    {{-- Quick set all --}}
    <div style="display:flex;gap:0.5rem;margin-bottom:1rem;padding:0.75rem 1rem;background:var(--bg);border:1px solid var(--border);border-radius:8px;align-items:center;">
        <span style="font-size:0.65rem;color:var(--muted);margin-right:0.5rem;">Set all to:</span>
        <button type="button" class="btn btn-ghost" style="padding:0.3rem 0.75rem;font-size:0.65rem;" onclick="setAllTemplates('personal')">
            👤 All Personal
        </button>
        <button type="button" class="btn btn-ghost" style="padding:0.3rem 0.75rem;font-size:0.65rem;" onclick="setAllTemplates('company')">
            🏢 All Company
        </button>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Email</th>
                    <th>First Name</th>
                    <th>Company Name</th>
                    <th>Template To Use</th>
                </tr>
            </thead>
            <tbody>
                @foreach($analysed as $index => $recipient)
                <tr>
                    <td style="color:var(--muted);font-size:0.68rem;">{{ $index + 1 }}</td>

                    {{-- Email --}}
                    <td>
                        <div style="font-size:0.75rem;">{{ $recipient['email'] }}</div>
                        @if($recipient['real_name'])
                            <div style="font-size:0.6rem;color:var(--accent);margin-top:0.1rem;">✓ Found in contacts</div>
                        @else
                            <div style="font-size:0.6rem;color:var(--muted);margin-top:0.1rem;">Extracted from email</div>
                        @endif
                    </td>

                    {{-- First Name --}}
                    <td>
                        <input
                            type="text"
                            name="first_names[]"
                            value="{{ $recipient['first_name'] }}"
                            class="form-input"
                            style="width:110px;padding:0.35rem 0.5rem;font-size:0.72rem;"
                        >
                    </td>

                    {{-- Company Name --}}
                    <td>
                        <input
                            type="text"
                            name="company_names[]"
                            value="{{ $recipient['company_name'] }}"
                            class="form-input"
                            style="width:120px;padding:0.35rem 0.5rem;font-size:0.72rem;"
                        >
                    </td>

                    {{-- Template picker per recipient --}}
                    <td>
                        <div style="display:flex;gap:0.4rem;">
                            <label style="cursor:pointer;">
                                <input type="radio" name="use_types[{{ $index }}]" value="personal" checked style="display:none;" class="template-radio" data-index="{{ $index }}">
                                <div class="template-pill personal-pill active-personal" data-index="{{ $index }}" data-type="personal">
                                    👤 <span class="pill-name">{{ $recipient['first_name'] }}</span>
                                </div>
                            </label>
                            <label style="cursor:pointer;">
                                <input type="radio" name="use_types[{{ $index }}]" value="company" style="display:none;" class="template-radio" data-index="{{ $index }}">
                                <div class="template-pill company-pill" data-index="{{ $index }}" data-type="company">
                                    🏢 <span class="pill-company">{{ $recipient['company_name'] }}</span> Team
                                </div>
                            </label>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


{{-- STEP 3: Pick Templates --}}
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-title" style="margin-bottom:0.3rem;">Step 3 — Select Templates & Follow-up Sequences</div>
    <div class="card-sub" style="margin-bottom:1.25rem;">
        Pick initial templates and build your follow-up sequence — up to 20 follow-ups per type
    </div>

    <div class="grid-2" style="gap:1.5rem;">

        {{-- Personal --}}
        <div style="padding:1rem;background:var(--bg);border:1px solid var(--border);border-radius:10px;">
            <div style="font-size:0.72rem;color:var(--blue);font-family:'Syne',sans-serif;font-weight:700;margin-bottom:0.75rem;">👤 Personal Templates</div>

            <div class="form-group">
                <label class="form-label">Initial Template</label>
                <select name="personal_initial_template_id" class="form-input">
                    <option value="">— Select —</option>
                    @foreach($personalInitialTemplates as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="height:1px;background:var(--border);margin:1rem 0;"></div>

            <div style="font-size:0.65rem;color:var(--muted);letter-spacing:0.08em;text-transform:uppercase;margin-bottom:0.75rem;">Follow-up Sequences</div>

            <div id="personalSequences" style="display:flex;flex-direction:column;gap:0.5rem;">
                <div class="sequence-row" data-type="personal">
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <span style="font-size:0.65rem;color:var(--accent);font-family:'Syne',sans-serif;font-weight:700;width:24px;">1</span>
                        <select name="personal_followup_sequences[]" class="form-input" style="flex:1;padding:0.4rem 0.5rem;font-size:0.72rem;">
                            <option value="">— Select template —</option>
                            @foreach($personalFollowupTemplates as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="remove-seq" style="background:none;border:none;color:var(--red);cursor:pointer;font-size:1rem;padding:0.2rem;">✕</button>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-ghost" style="margin-top:0.75rem;padding:0.4rem 0.75rem;font-size:0.65rem;width:100%;" onclick="addSequence('personal')">
                + Add Personal Follow-up
            </button>

            @if($personalFollowupTemplates->isEmpty())
                <div style="font-size:0.65rem;color:var(--yellow);margin-top:0.5rem;">⚠️ No personal follow-up templates. <a href="{{ route('templates.create') }}" style="color:var(--accent);">Create one →</a></div>
            @endif
        </div>

        {{-- Company --}}
        <div style="padding:1rem;background:var(--bg);border:1px solid var(--border);border-radius:10px;">
            <div style="font-size:0.72rem;color:var(--yellow);font-family:'Syne',sans-serif;font-weight:700;margin-bottom:0.75rem;">🏢 Company Templates</div>

            <div class="form-group">
                <label class="form-label">Initial Template</label>
                <select name="company_initial_template_id" class="form-input">
                    <option value="">— Select —</option>
                    @foreach($companyInitialTemplates as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>

            <div style="height:1px;background:var(--border);margin:1rem 0;"></div>

            <div style="font-size:0.65rem;color:var(--muted);letter-spacing:0.08em;text-transform:uppercase;margin-bottom:0.75rem;">Follow-up Sequences</div>

            <div id="companySequences" style="display:flex;flex-direction:column;gap:0.5rem;">
                <div class="sequence-row" data-type="company">
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <span style="font-size:0.65rem;color:var(--yellow);font-family:'Syne',sans-serif;font-weight:700;width:24px;">1</span>
                        <select name="company_followup_sequences[]" class="form-input" style="flex:1;padding:0.4rem 0.5rem;font-size:0.72rem;">
                            <option value="">— Select template —</option>
                            @foreach($companyFollowupTemplates as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="remove-seq" style="background:none;border:none;color:var(--red);cursor:pointer;font-size:1rem;padding:0.2rem;">✕</button>
                    </div>
                </div>
            </div>

            <button type="button" class="btn btn-ghost" style="margin-top:0.75rem;padding:0.4rem 0.75rem;font-size:0.65rem;width:100%;" onclick="addSequence('company')">
                + Add Company Follow-up
            </button>

            @if($companyFollowupTemplates->isEmpty())
                <div style="font-size:0.65rem;color:var(--yellow);margin-top:0.5rem;">⚠️ No company follow-up templates. <a href="{{ route('templates.create') }}" style="color:var(--accent);">Create one →</a></div>
            @endif
        </div>

    </div>
</div>

<div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1rem;">
    <a href="{{ route('campaigns.recipients.paste', $campaign->id) }}" class="btn btn-ghost">← Re-paste</a>
    <button type="submit" class="btn btn-primary">
        Confirm & Save {{ count($analysed) }} Recipients →
    </button>
</div>

</form>

@endsection

@push('styles')
<style>
.template-pill {
    padding: 0.3rem 0.65rem;
    border-radius: 20px;
    font-size: 0.65rem;
    border: 1px solid var(--border-hover);
    background: var(--bg);
    color: var(--muted);
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}
.template-pill:hover { border-color: var(--accent); }
.active-personal {
    background: var(--blue-dim);
    border-color: rgba(96,165,250,0.3);
    color: var(--blue);
}
.active-company {
    background: var(--yellow-dim);
    border-color: rgba(250,204,21,0.3);
    color: var(--yellow);
}
</style>
@endpush

@push('scripts')
<script>
    const totalRecipients = {{ count($analysed) }};

    // Template pill toggle per row
    document.querySelectorAll('.template-radio').forEach(radio => {
        radio.addEventListener('change', () => {
            const index = radio.dataset.index;
            const type  = radio.value;

            document.querySelectorAll(`.template-pill[data-index="${index}"]`).forEach(pill => {
                pill.classList.remove('active-personal', 'active-company');
            });

            const activePill = document.querySelector(`.template-pill[data-index="${index}"][data-type="${type}"]`);
            if (activePill) {
                activePill.classList.add(type === 'personal' ? 'active-personal' : 'active-company');
            }
        });
    });

    // Clicking pill also triggers radio
    document.querySelectorAll('.template-pill').forEach(pill => {
        pill.addEventListener('click', () => {
            const index = pill.dataset.index;
            const type  = pill.dataset.type;
            const radio = document.querySelector(`.template-radio[data-index="${index}"][value="${type}"]`);
            if (radio) { radio.checked = true; radio.dispatchEvent(new Event('change')); }
        });
    });

    // Set all templates
    function setAllTemplates(type) {
        document.querySelectorAll('.template-radio').forEach(radio => {
            if (radio.value === type) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change'));
            }
        });
    }

    // Split counter
    function updateSplitCount() {
        const inputs  = document.querySelectorAll('.split-input');
        let total = 0;
        inputs.forEach(input => total += parseInt(input.value) || 0);

        document.getElementById('assignedCount').textContent = total;
        const warning = document.getElementById('splitWarning');
        warning.style.display = total !== totalRecipients && total > 0 ? 'block' : 'none';
    }

    document.querySelectorAll('.split-input').forEach(input => {
        input.addEventListener('input', updateSplitCount);
    });

    // Auto split evenly
    function autoSplit() {
        const inputs   = document.querySelectorAll('.split-input');
        const perAccount = Math.floor(totalRecipients / inputs.length);
        let   remainder  = totalRecipients % inputs.length;

        inputs.forEach((input, i) => {
            input.value = perAccount + (i === 0 ? remainder : 0);
        });

        updateSplitCount();
    }

    // Update pill names when first_name or company_name inputs change
    document.querySelectorAll('input[name^="first_names"]').forEach((input, index) => {
        input.addEventListener('input', () => {
            const pill = document.querySelector(`.personal-pill[data-index="${index}"] .pill-name`);
            if (pill) pill.textContent = input.value || 'First Name';
        });
    });

    document.querySelectorAll('input[name^="company_names"]').forEach((input, index) => {
        input.addEventListener('input', () => {
            const pill = document.querySelector(`.company-pill[data-index="${index}"] .pill-company`);
            if (pill) pill.textContent = input.value || 'Company';
        });
    });

    // Sequence builder
const personalTemplates = @json($personalFollowupTemplates->values()->map(fn($t) => ['id' => $t->id, 'name' => $t->name]));
const companyTemplates  = @json($companyFollowupTemplates->values()->map(fn($t) => ['id' => $t->id, 'name' => $t->name]));

function addSequence(type) {
    const container  = document.getElementById(type + 'Sequences');
    const rows       = container.querySelectorAll('.sequence-row');
    const nextSeq    = rows.length + 1;
    const templates  = type === 'personal' ? personalTemplates : companyTemplates;
    const color      = type === 'personal' ? 'var(--blue)' : 'var(--yellow)';
    const maxSeq     = 20;

    if (nextSeq > maxSeq) {
        alert('Maximum 20 follow-up sequences allowed!');
        return;
    }

    const options = templates.map(t =>
        `<option value="${t.id}">${t.name}</option>`
    ).join('');

    const row = document.createElement('div');
    row.className = 'sequence-row';
    row.dataset.type = type;
    row.innerHTML = `
        <div style="display:flex;align-items:center;gap:0.5rem;">
            <span style="font-size:0.65rem;color:${color};font-family:'Syne',sans-serif;font-weight:700;width:24px;">${nextSeq}</span>
            <select name="${type}_followup_sequences[]" class="form-input" style="flex:1;padding:0.4rem 0.5rem;font-size:0.72rem;">
                <option value="">— Select template —</option>
                ${options}
            </select>
            <button type="button" class="remove-seq" style="background:none;border:none;color:var(--red);cursor:pointer;font-size:1rem;padding:0.2rem;" onclick="removeSequence(this, '${type}')">✕</button>
        </div>
    `;

    container.appendChild(row);
}

function removeSequence(btn, type) {
    const row       = btn.closest('.sequence-row');
    const container = document.getElementById(type + 'Sequences');
    row.remove();

    // Renumber sequences
    container.querySelectorAll('.sequence-row').forEach((r, i) => {
        const numSpan = r.querySelector('span');
        if (numSpan) numSpan.textContent = i + 1;
    });
}

// Remove sequence on first row button
document.querySelectorAll('.remove-seq').forEach(btn => {
    btn.addEventListener('click', function() {
        const row      = this.closest('.sequence-row');
        const type     = row.dataset.type;
        const container = document.getElementById(type + 'Sequences');
        if (container.querySelectorAll('.sequence-row').length > 1) {
            row.remove();
            container.querySelectorAll('.sequence-row').forEach((r, i) => {
                const numSpan = r.querySelector('span');
                if (numSpan) numSpan.textContent = i + 1;
            });
        }
    });
});
</script>
@endpush