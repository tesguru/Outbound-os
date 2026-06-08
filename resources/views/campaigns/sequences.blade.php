@extends('layouts.app')

@section('title', 'Manage Sequences')
@section('subtitle', 'Update follow-up sequences for — ' . $campaign->name)

@section('topbar-actions')
    <a href="{{ route('campaigns.show', $campaign->id) }}" class="btn btn-ghost">← Back</a>
@endsection

@section('content')

<div style="max-width:900px;">
<form action="{{ route('campaigns.sequences.update', $campaign->id) }}" method="POST">
@csrf

<div class="card" style="margin-bottom:1rem;">
    <div class="card-title" style="margin-bottom:0.3rem;">Follow-up Sequences</div>
    <div class="card-sub" style="margin-bottom:1.5rem;">
        Add up to 20 follow-up sequences per type — system picks next sequence automatically per recipient
    </div>

    {{-- Info box --}}
    <div style="padding:0.875rem 1rem;background:var(--accent-dim);border:1px solid rgba(74,222,128,0.15);border-radius:8px;margin-bottom:1.5rem;">
        <div style="font-size:0.72rem;color:var(--accent);font-family:'Syne',sans-serif;font-weight:600;margin-bottom:0.4rem;">How sequences work:</div>
        <div style="display:flex;flex-direction:column;gap:0.2rem;">
            <div style="font-size:0.65rem;color:var(--muted);">→ First click "Create Follow-ups" → all recipients get Sequence 1</div>
            <div style="font-size:0.65rem;color:var(--muted);">→ Second click → all recipients get Sequence 2</div>
            <div style="font-size:0.65rem;color:var(--muted);">→ No more sequences → recipients are skipped automatically</div>
            <div style="font-size:0.65rem;color:var(--muted);">→ Replied/Bounced recipients are always skipped</div>
        </div>
    </div>

    <div class="grid-2" style="gap:1.5rem;">

        {{-- Personal Sequences --}}
        <div style="padding:1rem;background:var(--bg);border:1px solid var(--border);border-radius:10px;">
            <div style="font-size:0.72rem;color:var(--blue);font-family:'Syne',sans-serif;font-weight:700;margin-bottom:1rem;">👤 Personal Follow-up Sequences</div>

            <div id="personalSequences" style="display:flex;flex-direction:column;gap:0.5rem;">
                @forelse($campaign->personalFollowupSequences as $seq)
                <div class="sequence-row" data-type="personal">
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <span style="font-size:0.65rem;color:var(--blue);font-family:'Syne',sans-serif;font-weight:700;width:24px;">{{ $seq->sequence }}</span>
                        <select name="personal_followup_sequences[]" class="form-input" style="flex:1;padding:0.4rem 0.5rem;font-size:0.72rem;">
                            <option value="">— Select template —</option>
                            @foreach($personalFollowupTemplates as $t)
                                <option value="{{ $t->id }}" {{ $seq->template_id == $t->id ? 'selected' : '' }}>
                                    {{ $t->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" onclick="removeSequence(this, 'personal')" style="background:none;border:none;color:var(--red);cursor:pointer;font-size:1rem;padding:0.2rem;">✕</button>
                    </div>
                </div>
                @empty
                <div class="sequence-row" data-type="personal">
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <span style="font-size:0.65rem;color:var(--blue);font-family:'Syne',sans-serif;font-weight:700;width:24px;">1</span>
                        <select name="personal_followup_sequences[]" class="form-input" style="flex:1;padding:0.4rem 0.5rem;font-size:0.72rem;">
                            <option value="">— Select template —</option>
                            @foreach($personalFollowupTemplates as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" onclick="removeSequence(this, 'personal')" style="background:none;border:none;color:var(--red);cursor:pointer;font-size:1rem;padding:0.2rem;">✕</button>
                    </div>
                </div>
                @endforelse
            </div>

            <button type="button" class="btn btn-ghost" style="margin-top:0.75rem;padding:0.4rem 0.75rem;font-size:0.65rem;width:100%;" onclick="addSequence('personal')">
                + Add Personal Follow-up
            </button>

            @if($personalFollowupTemplates->isEmpty())
                <div style="font-size:0.65rem;color:var(--yellow);margin-top:0.5rem;">
                    ⚠️ No personal follow-up templates.
                    <a href="{{ route('templates.create') }}" style="color:var(--accent);">Create one →</a>
                </div>
            @endif
        </div>

        {{-- Company Sequences --}}
        <div style="padding:1rem;background:var(--bg);border:1px solid var(--border);border-radius:10px;">
            <div style="font-size:0.72rem;color:var(--yellow);font-family:'Syne',sans-serif;font-weight:700;margin-bottom:1rem;">🏢 Company Follow-up Sequences</div>

            <div id="companySequences" style="display:flex;flex-direction:column;gap:0.5rem;">
                @forelse($campaign->companyFollowupSequences as $seq)
                <div class="sequence-row" data-type="company">
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <span style="font-size:0.65rem;color:var(--yellow);font-family:'Syne',sans-serif;font-weight:700;width:24px;">{{ $seq->sequence }}</span>
                        <select name="company_followup_sequences[]" class="form-input" style="flex:1;padding:0.4rem 0.5rem;font-size:0.72rem;">
                            <option value="">— Select template —</option>
                            @foreach($companyFollowupTemplates as $t)
                                <option value="{{ $t->id }}" {{ $seq->template_id == $t->id ? 'selected' : '' }}>
                                    {{ $t->name }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" onclick="removeSequence(this, 'company')" style="background:none;border:none;color:var(--red);cursor:pointer;font-size:1rem;padding:0.2rem;">✕</button>
                    </div>
                </div>
                @empty
                <div class="sequence-row" data-type="company">
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        <span style="font-size:0.65rem;color:var(--yellow);font-family:'Syne',sans-serif;font-weight:700;width:24px;">1</span>
                        <select name="company_followup_sequences[]" class="form-input" style="flex:1;padding:0.4rem 0.5rem;font-size:0.72rem;">
                            <option value="">— Select template —</option>
                            @foreach($companyFollowupTemplates as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                        <button type="button" onclick="removeSequence(this, 'company')" style="background:none;border:none;color:var(--red);cursor:pointer;font-size:1rem;padding:0.2rem;">✕</button>
                    </div>
                </div>
                @endforelse
            </div>

            <button type="button" class="btn btn-ghost" style="margin-top:0.75rem;padding:0.4rem 0.75rem;font-size:0.65rem;width:100%;" onclick="addSequence('company')">
                + Add Company Follow-up
            </button>

            @if($companyFollowupTemplates->isEmpty())
                <div style="font-size:0.65rem;color:var(--yellow);margin-top:0.5rem;">
                    ⚠️ No company follow-up templates.
                    <a href="{{ route('templates.create') }}" style="color:var(--accent);">Create one →</a>
                </div>
            @endif
        </div>

    </div>
</div>

<div style="display:flex;gap:0.75rem;justify-content:flex-end;">
    <a href="{{ route('campaigns.show', $campaign->id) }}" class="btn btn-ghost">Cancel</a>
    <button type="submit" class="btn btn-primary">Save Sequences →</button>
</div>

</form>
</div>

@endsection

@push('scripts')
<script>
    const personalTemplates = @json($personalFollowupTemplates->values()->map(fn($t) => ['id' => $t->id, 'name' => $t->name]));
    const companyTemplates  = @json($companyFollowupTemplates->values()->map(fn($t) => ['id' => $t->id, 'name' => $t->name]));

    function addSequence(type) {
        const container = document.getElementById(type + 'Sequences');
        const rows      = container.querySelectorAll('.sequence-row');
        const nextSeq   = rows.length + 1;
        const templates = type === 'personal' ? personalTemplates : companyTemplates;
        const color     = type === 'personal' ? 'var(--blue)' : 'var(--yellow)';

        if (nextSeq > 20) {
            alert('Maximum 20 follow-up sequences!');
            return;
        }

        const options = templates.map(t =>
            `<option value="${t.id}">${t.name}</option>`
        ).join('');

        const row = document.createElement('div');
        row.className   = 'sequence-row';
        row.dataset.type = type;
        row.innerHTML = `
            <div style="display:flex;align-items:center;gap:0.5rem;">
                <span style="font-size:0.65rem;color:${color};font-family:'Syne',sans-serif;font-weight:700;width:24px;">${nextSeq}</span>
                <select name="${type}_followup_sequences[]" class="form-input" style="flex:1;padding:0.4rem 0.5rem;font-size:0.72rem;">
                    <option value="">— Select template —</option>
                    ${options}
                </select>
                <button type="button" onclick="removeSequence(this, '${type}')" style="background:none;border:none;color:var(--red);cursor:pointer;font-size:1rem;padding:0.2rem;">✕</button>
            </div>
        `;

        container.appendChild(row);
    }

    function removeSequence(btn, type) {
        const container = document.getElementById(type + 'Sequences');
        if (container.querySelectorAll('.sequence-row').length <= 1) return;
        btn.closest('.sequence-row').remove();
        // Renumber
        container.querySelectorAll('.sequence-row').forEach((r, i) => {
            const span = r.querySelector('span');
            if (span) span.textContent = i + 1;
        });
    }
</script>
@endpush