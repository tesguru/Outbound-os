@extends('layouts.app')

@section('title', 'Leads Saver')
@section('subtitle', 'A notepad per lead list — type and it saves itself')

@section('content')

<div style="max-width:1024px;">

    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:1rem;flex-wrap:wrap;">
        <div style="font-size:0.78rem;color:var(--muted);">
            <b style="color:var(--accent);">{{ $totalLeads }}</b> leads saved · each notepad auto-saves a few seconds after you stop typing — import any list into a campaign later
        </div>
        <button class="btn btn-primary" onclick="newNotepad()" style="white-space:nowrap;">+ New Notepad</button>
    </div>

    <div style="display:grid;grid-template-columns:270px 1fr;gap:1rem;align-items:start;">

        {{-- Notepad list --}}
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:0.75rem;display:flex;flex-direction:column;gap:0.35rem;max-height:640px;overflow-y:auto;">
            <div style="font-size:0.6rem;color:var(--muted);letter-spacing:0.12em;text-transform:uppercase;padding:0.25rem 0.5rem;">Notepads</div>
            @forelse($lists as $l)
            <button data-name="{{ $l->list_name }}" onclick="openNotepad(this)"
                    style="text-align:left;padding:0.6rem 0.75rem;border:1px solid {{ $activeList === $l->list_name ? 'rgba(74,222,128,0.3)' : 'var(--border)' }};background:{{ $activeList === $l->list_name ? 'var(--accent-dim)' : 'transparent' }};border-radius:8px;cursor:pointer;display:flex;flex-direction:column;gap:0.15rem;color:var(--text);">
                <span style="font-size:0.75rem;font-family:'Syne',sans-serif;">☾ {{ $l->list_name }}</span>
                <span style="font-size:0.62rem;color:var(--muted);">{{ $l->total }} emails</span>
            </button>
            @empty
            <div style="padding:1rem 0.5rem;font-size:0.7rem;color:var(--muted);text-align:center;line-height:1.6;">
                No notepads yet.<br>Click <b style="color:var(--accent);">+ New Notepad</b> and start typing.
            </div>
            @endforelse
        </div>

        {{-- Editor --}}
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;">
            <div style="display:flex;align-items:center;gap:0.75rem;padding:0.75rem 1rem;border-bottom:1px solid var(--border);flex-wrap:wrap;">
                <input id="notepadName" type="text" value="{{ $activeList ?? '' }}"
                       placeholder="Notepad name — e.g. leads for floridaattorney.com"
                       style="flex:1;min-width:200px;background:transparent;border:1px solid var(--border-hover);border-radius:8px;padding:0.45rem 0.7rem;color:var(--text);font-size:0.8rem;font-family:'Syne',sans-serif;font-weight:600;outline:none;">
                <span id="notepadCount" style="font-size:0.65rem;color:var(--muted);white-space:nowrap;">{{ $activeList && $notepadLeads ? $notepadLeads->count() : 0 }} emails</span>
                <span id="saveStatus" style="font-size:0.65rem;color:var(--muted);white-space:nowrap;"></span>
            </div>
            <textarea id="notepadArea"
                      placeholder="Paste emails here — one per line. They save automatically 3 seconds after you stop typing."
                      style="width:100%;min-height:560px;padding:1rem 1.15rem;background:transparent;border:0;resize:vertical;color:var(--text);font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:0.85rem;line-height:1.9;outline:none;">@if($activeList && $notepadLeads){{ $notepadLeads->implode("\n") }}@endif</textarea>
        </div>
    </div>

    <div style="margin-top:1rem;font-size:0.68rem;color:var(--muted);">
        💡 Notepads sync with the <b style="color:var(--accent);">☾ Import Saved Leads</b> button on any campaign's paste page — pick this list and all its emails (and any saved website links) get pre-filled.
    </div>
</div>

@endsection

@push('scripts')
<script>
    const CSRF_TOKEN = '{{ csrf_token() }}';
    const notepads   = @json($notepads);
    const emailRegex = /[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/g;

    let currentList = @json($activeList);
    let saveTimer   = null;

    const area      = document.getElementById('notepadArea');
    const nameInput = document.getElementById('notepadName');
    const countEl   = document.getElementById('notepadCount');
    const statusEl  = document.getElementById('saveStatus');

    function emailCount() {
        const m = (area.value || '').match(emailRegex);
        return m ? m.length : 0;
    }

    function syncCount() {
        countEl.textContent = emailCount() + ' emails';
    }

    function setStatus(text, color) {
        statusEl.textContent = text;
        statusEl.style.color = color || 'var(--muted)';
    }

    function openNotepad(btn) {
        const name = btn.dataset.name;
        const found = notepads.find(n => n.name === name);
        currentList = name;
        nameInput.value = name;
        area.value = found ? found.emails : '';
        syncCount();
        setStatus('');
        clearTimeout(saveTimer);
    }

    function newNotepad() {
        const name = prompt('Name this notepad (e.g. leads for floridaattorney.com):');
        if (!name || !name.trim()) return;
        currentList = name.trim();
        nameInput.value = currentList;
        area.value = '';
        syncCount();
        setStatus('New notepad — type and it will save itself', 'var(--blue)');
        area.focus();
    }

    async function saveNotepad() {
        if (!currentList) return;
        setStatus('Saving…', 'var(--blue)');
        try {
            const res = await fetch('{{ route("leads.sync") }}', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                body: JSON.stringify({ list_name: currentList, emails: area.value }),
            });
            const data = await res.json();
            if (data.success) {
                const existing = notepads.find(n => n.name === currentList);
                if (existing) existing.emails = area.value;
                else notepads.push({ name: currentList, emails: area.value });
                syncCount();
                const parts = [];
                if (data.added  > 0) parts.push('+' + data.added);
                if (data.removed > 0) parts.push('−' + data.removed);
                setStatus(parts.length ? '✓ saved (' + parts.join(', ') + ')' : '✓ saved', 'var(--accent)');
            } else {
                setStatus('⚠ could not save', 'var(--red)');
            }
        } catch (e) {
            setStatus('⚠ offline — will retry', 'var(--red)');
        }
    }

    area.addEventListener('input', () => {
        syncCount();
        setStatus('Saving in 3s…', 'var(--muted)');
        clearTimeout(saveTimer);
        saveTimer = setTimeout(saveNotepad, 3000);
    });

    nameInput.addEventListener('change', renameNotepad);

    async function renameNotepad() {
        const newName = nameInput.value.trim();
        if (!newName) { nameInput.value = currentList; setStatus(''); return; }
        if (newName === currentList) { setStatus(''); return; }
        const oldName = currentList;
        try {
            await fetch('{{ route("leads.rename") }}', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
                body: JSON.stringify({ old_name: oldName, list_name: newName }),
            });
        } catch (e) {}
        currentList = newName;
        const idx = notepads.findIndex(n => n.name === oldName);
        if (idx >= 0) notepads[idx].name = newName;
        setStatus('✓ renamed', 'var(--accent)');
    }

    syncCount();
</script>
@endpush