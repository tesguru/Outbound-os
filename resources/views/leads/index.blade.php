@extends('layouts.app')

@section('title', 'Leads Saver')
@section('subtitle', 'Your master list of saved leads — import them into any campaign')

@section('topbar-actions')
    <button class="btn btn-primary" onclick="document.getElementById('addLeadModal').classList.add('open')">
        + Add Lead
    </button>
@endsection

@section('content')

@php
    $total  = $leads->count();
    $allTotal = \App\Models\Lead::where('user_id', Auth::id())->count();
    $saved  = $leads->where('status', 'saved')->count();
    $replied = $leads->where('status', 'replied')->count();
    $sold   = $leads->where('status', 'sold')->count();
    $lost   = $leads->where('status', 'lost')->count();
    $contacted = $leads->where('status', 'contacted')->count();
@endphp

{{-- Summary --}}
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;margin-bottom:1.5rem;">
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.1rem 1.25rem;text-align:center;">
        <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.7rem;color:var(--text);">{{ $total }}</div>
        <div style="font-size:0.6rem;color:var(--muted);letter-spacing:0.08em;text-transform:uppercase;margin-top:0.3rem;">Total Leads</div>
    </div>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.1rem 1.25rem;text-align:center;">
        <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.7rem;color:var(--blue);">{{ $saved }}</div>
        <div style="font-size:0.6rem;color:var(--muted);letter-spacing:0.08em;text-transform:uppercase;margin-top:0.3rem;">Saved</div>
    </div>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.1rem 1.25rem;text-align:center;">
        <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.7rem;color:var(--yellow);">{{ $contacted }}</div>
        <div style="font-size:0.6rem;color:var(--muted);letter-spacing:0.08em;text-transform:uppercase;margin-top:0.3rem;">Contacted</div>
    </div>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.1rem 1.25rem;text-align:center;">
        <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.7rem;color:var(--accent);">{{ $replied }}</div>
        <div style="font-size:0.6rem;color:var(--muted);letter-spacing:0.08em;text-transform:uppercase;margin-top:0.3rem;">Replied</div>
    </div>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.1rem 1.25rem;text-align:center;">
        <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.7rem;color:var(--accent);">{{ $sold }}</div>
        <div style="font-size:0.6rem;color:var(--muted);letter-spacing:0.08em;text-transform:uppercase;margin-top:0.3rem;">Sold</div>
    </div>
</div>

{{-- Bulk add (paste) --}}
<div class="card" style="margin-bottom:1.5rem;">
    <div class="card-title" style="margin-bottom:0.3rem;">Save Leads (Bulk Paste)</div>
    <div class="card-sub" style="margin-bottom:1rem;">Paste emails separated by newline, comma, or semicolon — they get saved instantly. Leads save automatically 30s after you stop typing too.</div>

    <form action="{{ route('leads.bulk-store') }}" method="POST" id="bulkLeadForm">
        @csrf
        <input type="hidden" name="domain" value="{{ old('domain') }}">
        <div class="form-group" style="margin-bottom:0.75rem;">
            <label class="form-label">List Name <span style="font-weight:400;font-size:0.65rem;color:var(--muted);">(optional — gives this batch a name, e.g. "leads for example.com", so you can import the whole list later)</span></label>
            <input type="text" name="list_name" class="form-input" placeholder="leads for example.com" style="max-width:360px;">
        </div>
        <div class="form-group" style="margin-bottom:0.5rem;">
            <textarea
                name="emails"
                class="form-input"
                rows="6"
                placeholder="john@acme.com&#10;sarah@techco.com&#10;mike@startup.io&#10;..."
                style="resize:vertical;line-height:1.8;font-size:0.8rem;"
            >{{ old('emails') }}</textarea>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;">
            <div style="font-size:0.68rem;color:var(--muted);">
                Emails detected: <span id="leadCount" style="color:var(--accent);font-family:'Syne',sans-serif;font-weight:700;">0</span>
                <span style="color:var(--muted2);margin-left:0.75rem;">· auto-saves after 30s of inactivity</span>
            </div>
            <button type="submit" class="btn btn-primary">💾 Save Leads →</button>
        </div>
    </form>
</div>

{{-- Leads table --}}
@php $unlisted = $leads->count(); @endphp
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">All Saved Leads @if($activeList) — {{ $activeList }} @endif</div>
            <div class="card-sub">Click ✎ to edit a lead — name a list and import it into any campaign</div>
        </div>
        <span class="badge badge-green">{{ $leads->count() }} leads</span>
    </div>

    {{-- List filter --}}
    @if($lists->isNotEmpty() || $activeList)
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;padding:0 1.25rem 1rem;">
        <a href="{{ route('leads.index') }}" class="btn btn-ghost" style="padding:0.35rem 0.75rem;font-size:0.62rem;{{ !$activeList ? 'background:var(--accent-dim);border-color:rgba(74,222,128,0.3);color:var(--accent);' : '' }}">
            All ({{ $allTotal }})
        </a>
        @foreach($lists as $l)
        <a href="{{ route('leads.index', ['list' => $l->list_name]) }}" class="btn btn-ghost" style="padding:0.35rem 0.75rem;font-size:0.62rem;{{ $activeList === $l->list_name ? 'background:var(--accent-dim);border-color:rgba(74,222,128,0.3);color:var(--accent);' : '' }}">
            ☾ {{ $l->list_name }} ({{ $l->total }})
        </a>
        @endforeach
    </div>
    @endif

    @if($leads->isEmpty())
        <div class="empty">
            <div class="empty-icon">☾</div>
            <h3>No saved leads yet</h3>
            <p>Paste leads above — give the batch a List Name so you can import it into any campaign later</p>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>First Name</th>
                        <th>Company</th>
                        <th>List</th>
                        <th>Website</th>
                        <th>Status</th>
                        <th style="width:190px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leads as $lead)
                    <tr>
                        <td style="font-size:0.75rem;">{{ $lead->email }}</td>
                        <td>{{ $lead->first_name ?? '—' }}</td>
                        <td>{{ $lead->company_name ?? '—' }}</td>
                        <td>
                            @if($lead->list_name)
                                <a href="{{ route('leads.index', ['list' => $lead->list_name]) }}" style="color:var(--blue);font-size:0.72rem;">☾ {{ $lead->list_name }}</a>
                            @else
                                <span style="color:var(--muted);">—</span>
                            @endif
                        </td>
                        <td>
                            @if($lead->website)
                                <a href="{{ $lead->website }}" target="_blank" rel="noopener" style="color:var(--accent);font-size:0.72rem;">🌐 link</a>
                            @else
                                <span style="color:var(--muted);">—</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $statusMap = [
                                    'saved'     => ['badge-gray',   'Saved'],
                                    'contacted' => ['badge-blue',   'Contacted'],
                                    'replied'   => ['badge-yellow', 'Replied'],
                                    'sold'      => ['badge-green',  'Sold'],
                                    'lost'      => ['badge-red',    'Lost'],
                                ];
                                $s = $statusMap[$lead->status] ?? ['badge-gray', $lead->status];
                            @endphp
                            <span class="badge {{ $s[0] }}">{{ $s[1] }}</span>
                        </td>
                        <td style="font-size:0.65rem;color:var(--muted);">
                            {{ $lead->sourceCampaign?->name ?? 'Manual / Autosave' }}
                        </td>
                        <td>
                            <div style="display:flex;gap:0.4rem;align-items:center;">
                                <button class="btn btn-ghost" style="padding:0.3rem 0.6rem;font-size:0.62rem;" onclick="openEditLead({{ $lead->id }})">✎ Edit</button>
                                <form action="{{ route('leads.destroy', $lead->id) }}" method="POST" onsubmit="return confirm('Remove this lead?')" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" style="padding:0.3rem 0.6rem;font-size:0.62rem;">✕</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Add Lead Modal --}}
<div class="modal-overlay" id="addLeadModal">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('addLeadModal').classList.remove('open')">✕</button>
        <div class="modal-title">+ Add Lead</div>
        <div class="modal-sub">Save a single lead to your master list</div>

        <form action="{{ route('leads.store') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-input" required placeholder="john@acme.com">
            </div>
            <div class="form-group">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Company Name</label>
                <input type="text" name="company_name" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Related Domain (optional)</label>
                <input type="text" name="domain" class="form-input" placeholder="acme.com">
            </div>
            <div class="form-group">
                <label class="form-label">List Name (optional)</label>
                <input type="text" name="list_name" class="form-input" placeholder="leads for example.com">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Website Link (optional)</label>
                <input type="url" name="website" class="form-input" placeholder="https://acme.com/about">
            </div>
            <div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1.25rem;">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addLeadModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Lead →</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Lead Modal --}}
<div class="modal-overlay" id="editLeadModal">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('editLeadModal').classList.remove('open')">✕</button>
        <div class="modal-title">✎ Edit Lead</div>
        <div class="modal-sub">Update lead details</div>

        <form method="POST" id="editLeadForm">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" id="edit_email" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" id="edit_first_name" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Company Name</label>
                <input type="text" name="company_name" id="edit_company_name" class="form-input">
            </div>
            <div class="form-group">
                <label class="form-label">Related Domain</label>
                <input type="text" name="domain" id="edit_domain" class="form-input" placeholder="acme.com">
            </div>
            <div class="form-group">
                <label class="form-label">List Name</label>
                <input type="text" name="list_name" id="edit_list_name" class="form-input" placeholder="leads for example.com">
            </div>
            <div class="form-group">
                <label class="form-label">Website Link</label>
                <input type="url" name="website" id="edit_website" class="form-input" placeholder="https://acme.com/about">
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" id="edit_status" class="form-input">
                    <option value="saved">Saved</option>
                    <option value="contacted">Contacted</option>
                    <option value="replied">Replied</option>
                    <option value="sold">Sold</option>
                    <option value="lost">Lost</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Notes</label>
                <textarea name="notes" id="edit_notes" class="form-input" rows="3"></textarea>
            </div>
            <div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1.25rem;">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('editLeadModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes →</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Bulk lead email counter
    const bulkTextarea = document.querySelector('#bulkLeadForm textarea[name="emails"]');
    const bulkListName = document.querySelector('#bulkLeadForm input[name="list_name"]');
    const leadCountEl  = document.getElementById('leadCount');
    const emailRegex   = /[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/g;

    function countLeads() {
        const matches = bulkTextarea.value.match(emailRegex);
        leadCountEl.textContent = matches ? matches.length : 0;
    }

    if (bulkTextarea) {
        bulkTextarea.addEventListener('input', countLeads);
        countLeads();

        // Auto-save after 30s of inactivity
        let saveTimer = null;
        const AUTOSAVE_DELAY = 30000;

        async function autoSaveLeads() {
            const emails = bulkTextarea.value;
            const count  = emails.match(emailRegex)?.length ?? 0;
            if (!count) return;

            try {
                const res = await fetch('{{ route("leads.bulk-store") }}', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept':       'application/json',
                    },
                    body: JSON.stringify({ emails, list_name: bulkListName ? bulkListName.value : '' })
                });
                const data = await res.json();
                if (data.success) {
                    showSavePill(`💾 Auto-saved ${data.created} new + ${data.updated} updated leads`);
                }
            } catch (e) {
                // silent — user still has the Save button
            }
        }

        bulkTextarea.addEventListener('input', () => {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(autoSaveLeads, AUTOSAVE_DELAY);
        });

        if (bulkListName) {
            bulkListName.addEventListener('input', () => { clearTimeout(saveTimer); saveTimer = setTimeout(autoSaveLeads, AUTOSAVE_DELAY); });
        }
    }

    function showSavePill(msg) {
        let pill = document.getElementById('savePill');
        if (!pill) {
            pill = document.createElement('div');
            pill.id = 'savePill';
            pill.style.cssText = 'position:fixed;bottom:2rem;left:50%;transform:translateX(-50%);z-index:1000;padding:0.75rem 1.25rem;background:var(--surface);border:1px solid rgba(74,222,128,0.3);border-radius:10px;font-size:0.72rem;color:var(--accent);box-shadow:0 8px 24px rgba(0,0,0,0.5);animation:fadeUp 0.3s ease both;';
            document.body.appendChild(pill);
        }
        pill.textContent = msg;
        pill.style.display = 'block';
        clearTimeout(pill._t);
        pill._t = setTimeout(() => { pill.style.display = 'none'; }, 3000);
    }

    // Edit lead modal — data hydrated from the row (no extra server call)
    const editLeads = @json($leads);
    function openEditLead(id) {
        const lead = editLeads.find(l => l.id === id);
        if (!lead) return;

        document.getElementById('edit_email').value        = lead.email;
        document.getElementById('edit_first_name').value   = lead.first_name || '';
        document.getElementById('edit_company_name').value = lead.company_name || '';
        document.getElementById('edit_domain').value       = lead.domain || '';
        document.getElementById('edit_list_name').value    = lead.list_name || '';
        document.getElementById('edit_website').value      = lead.website || '';
        document.getElementById('edit_status').value       = lead.status || 'saved';
        document.getElementById('edit_notes').value        = lead.notes || '';
        document.getElementById('editLeadForm').action     = '{{ route("leads.update", ":id") }}'.replace(':id', id);

        document.getElementById('editLeadModal').classList.add('open');
    }
</script>
@endpush