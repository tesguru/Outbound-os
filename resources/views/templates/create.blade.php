@extends('layouts.app')

@section('title', 'Create Template')
@section('subtitle', 'Build a new email template with personalization')

@section('topbar-actions')
    <a href="{{ route('templates.index') }}" class="btn btn-ghost">← Back</a>
@endsection

@section('content')

<div style="max-width:780px;">
    <form action="{{ route('templates.store') }}" method="POST">
        @csrf

        <div class="card" style="margin-bottom:1rem;">
            <div class="card-title" style="margin-bottom:0.3rem;">Template Details</div>
            <div class="card-sub" style="margin-bottom:1.5rem;">Set the type and category for this template</div>

            <div class="grid-2" style="gap:1rem;margin-bottom:1rem;">
                <div class="form-group">
                    <label class="form-label">Template Name</label>
                    <input type="text" name="name" class="form-input" placeholder="e.g. Cold Outreach v1" value="{{ old('name') }}" required>
                    @error('name')<div style="color:var(--red);font-size:0.65rem;margin-top:0.3rem;">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Template Type</label>
                    <select name="type" class="form-input" required id="typeSelect">
                        <option value="">— Select type —</option>
                        <option value="personal" {{ old('type')=='personal' ? 'selected' : '' }}>👤 Personal (uses first name)</option>
                        <option value="company" {{ old('type')=='company' ? 'selected' : '' }}>🏢 Company (uses company name)</option>
                    </select>
                    @error('type')<div style="color:var(--red);font-size:0.65rem;margin-top:0.3rem;">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Category</label>
                <div style="display:flex;gap:0.75rem;">
                    <label style="flex:1;cursor:pointer;">
                        <input type="radio" name="category" value="initial" {{ old('category','initial')=='initial' ? 'checked' : '' }} style="display:none;" class="cat-radio">
                        <div class="cat-card {{ old('category','initial')=='initial' ? 'cat-active' : '' }}" data-value="initial">
                            <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:0.85rem;">📧 Initial Email</div>
                            <div style="font-size:0.68rem;color:var(--muted);margin-top:0.2rem;">First contact with recipient</div>
                        </div>
                    </label>
                    <label style="flex:1;cursor:pointer;">
                        <input type="radio" name="category" value="followup" {{ old('category')=='followup' ? 'checked' : '' }} style="display:none;" class="cat-radio">
                        <div class="cat-card {{ old('category')=='followup' ? 'cat-active' : '' }}" data-value="followup">
                            <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:0.85rem;">🔁 Follow-up</div>
                            <div style="font-size:0.68rem;color:var(--muted);margin-top:0.2rem;">Thread reply to initial email</div>
                        </div>
                    </label>
                </div>
                @error('category')<div style="color:var(--red);font-size:0.65rem;margin-top:0.3rem;">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="card" style="margin-bottom:1rem;">
            <div class="card-title" style="margin-bottom:0.3rem;">Email Content</div>
            <div class="card-sub" style="margin-bottom:1.5rem;">Use placeholders to personalize your emails</div>

            {{-- Placeholder chips --}}
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1rem;padding:0.75rem;background:var(--bg);border:1px solid var(--border);border-radius:8px;">
                <span style="font-size:0.62rem;color:var(--muted);letter-spacing:0.08em;text-transform:uppercase;align-self:center;margin-right:0.25rem;">Insert:</span>
<button type="button" class="placeholder-btn" data-placeholder="@{{first_name}}">@{{first_name}}</button>
<button type="button" class="placeholder-btn" data-placeholder="@{{company_name}}">@{{company_name}}</button>
<button type="button" class="placeholder-btn" data-placeholder="@{{domain}}">@{{domain}}</button>
<button type="button" class="placeholder-btn" data-placeholder="@{{price}}">@{{price}}</button>
            </div>

            <div class="form-group">
                <label class="form-label">Subject Line</label>
                <input type="text" name="subject" id="subjectInput" class="form-input" placeholder="e.g. Quick question for @{{first_name}}" value="{{ old('subject') }}" required>
                @error('subject')<div style="color:var(--red);font-size:0.65rem;margin-top:0.3rem;">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Email Body</label>
                <textarea name="body" id="bodyInput" class="form-input" rows="12" placeholder="Hi @{{first_name}},&#10;&#10;Write your email here..." required style="resize:vertical;line-height:1.6;">{{ old('body') }}</textarea>
                @error('body')<div style="color:var(--red);font-size:0.65rem;margin-top:0.3rem;">{{ $message }}</div>@enderror
            </div>
        </div>

        <div style="display:flex;gap:0.75rem;justify-content:flex-end;">
            <a href="{{ route('templates.index') }}" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Template →</button>
        </div>

    </form>
</div>

@endsection

@push('styles')
<style>
.cat-card {
    padding: 0.875rem 1rem;
    border: 1px solid var(--border-hover);
    border-radius: 10px;
    background: var(--bg);
    transition: all 0.15s ease;
}
.cat-card:hover { border-color: var(--accent); }
.cat-card.cat-active {
    border-color: var(--accent);
    background: var(--accent-dim);
}
.placeholder-btn {
    padding: 0.3rem 0.65rem;
    background: var(--surface2);
    border: 1px solid var(--border-hover);
    border-radius: 6px;
    color: var(--accent);
    font-family: 'DM Mono', monospace;
    font-size: 0.7rem;
    cursor: pointer;
    transition: all 0.15s ease;
}
.placeholder-btn:hover {
    background: var(--accent-dim);
    border-color: var(--accent);
}
</style>
@endpush

@push('scripts')
<script>
    // Category radio cards
    document.querySelectorAll('.cat-radio').forEach(radio => {
        radio.addEventListener('change', () => {
            document.querySelectorAll('.cat-card').forEach(c => c.classList.remove('cat-active'));
            radio.nextElementSibling.classList.add('cat-active');
        });
    });

    document.querySelectorAll('label').forEach(label => {
        label.addEventListener('click', () => {
            const radio = label.querySelector('.cat-radio');
            if (radio) {
                document.querySelectorAll('.cat-card').forEach(c => c.classList.remove('cat-active'));
                label.querySelector('.cat-card').classList.add('cat-active');
            }
        });
    });

    // Placeholder insert
    let lastFocused = document.getElementById('bodyInput');

    document.getElementById('subjectInput').addEventListener('focus', function() { lastFocused = this; });
    document.getElementById('bodyInput').addEventListener('focus', function() { lastFocused = this; });

    document.querySelectorAll('.placeholder-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const placeholder = btn.dataset.placeholder;
            const el = lastFocused;
            const start = el.selectionStart;
            const end = el.selectionEnd;
            const val = el.value;
            el.value = val.substring(0, start) + placeholder + val.substring(end);
            el.selectionStart = el.selectionEnd = start + placeholder.length;
            el.focus();
        });
    });
</script>
@endpush