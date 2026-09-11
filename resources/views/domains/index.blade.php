@extends('layouts.app')

@section('title', 'Domain CRM')
@section('subtitle', 'Track domain spend, outbound volume, and sales')

@section('topbar-actions')
    <button class="btn btn-primary" onclick="document.getElementById('addDomainModal').classList.add('open')">
        + Add Domain
    </button>
@endsection

@section('content')

@php
    $currency       = $domains->first()?->currency ?? 'USD';
    $symbol         = $currency === 'USD' ? '$' : ($currency === 'EUR' ? '€' : ($currency === 'GBP' ? '£' : $currency . ' '));
    $totalOutbound  = 0;
    $totalSent      = 0;
    $totalReplies   = 0;
    foreach ($domains as $domain) {
        $totalOutbound += $domain->outboundCount();
        $totalSent     += $domain->sentCount();
        $totalReplies  += $domain->repliedCount();
    }
@endphp

{{-- Summary stats --}}
<div style="display:grid;grid-template-columns:repeat(6,1fr);gap:1rem;margin-bottom:1.5rem;">
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.1rem 1.1rem;text-align:center;">
        <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.5rem;color:var(--text);">{{ $domains->count() }}</div>
        <div style="font-size:0.58rem;color:var(--muted);letter-spacing:0.08em;text-transform:uppercase;margin-top:0.3rem;">Domains</div>
    </div>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.1rem 1.1rem;text-align:center;">
        <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.5rem;color:var(--blue);">{{ $summary['totalSpent'] }}<span style="font-size:0.8rem;">{{ $symbol }}</span></div>
        <div style="font-size:0.58rem;color:var(--muted);letter-spacing:0.08em;text-transform:uppercase;margin-top:0.3rem;">Total Spent</div>
    </div>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.1rem 1.1rem;text-align:center;">
        <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.5rem;color:var(--accent);">{{ $summary['totalSold'] }}<span style="font-size:0.8rem;">{{ $symbol }}</span></div>
        <div style="font-size:0.58rem;color:var(--muted);letter-spacing:0.08em;text-transform:uppercase;margin-top:0.3rem;">Sales Revenue</div>
    </div>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.1rem 1.1rem;text-align:center;">
        <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.5rem;color:{{ $summary['totalProfit'] >= 0 ? 'var(--accent)' : 'var(--red)' }};">{{ $summary['totalProfit'] }}<span style="font-size:0.8rem;">{{ $symbol }}</span></div>
        <div style="font-size:0.58rem;color:var(--muted);letter-spacing:0.08em;text-transform:uppercase;margin-top:0.3rem;">Net Profit</div>
    </div>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.1rem 1.1rem;text-align:center;">
        <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.5rem;color:var(--yellow);">{{ $totalOutbound }}</div>
        <div style="font-size:0.58rem;color:var(--muted);letter-spacing:0.08em;text-transform:uppercase;margin-top:0.3rem;">Outbound Emails</div>
    </div>
    <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:1.1rem 1.1rem;text-align:center;">
        <div style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.5rem;color:var(--accent);">{{ $summary['soldCount'] }}</div>
        <div style="font-size:0.58rem;color:var(--muted);letter-spacing:0.08em;text-transform:uppercase;margin-top:0.3rem;">Domains Sold</div>
    </div>
</div>

{{-- Add Domain + Discovered --}}
<div class="grid-2" style="gap:1rem;margin-bottom:1.5rem;">

    {{-- Add Domain card (inline, no modal needed) --}}
    <div class="card">
        <div class="card-title" style="margin-bottom:0.3rem;">✚ Add Domain to CRM</div>
        <div class="card-sub" style="margin-bottom:1rem;">Log what you paid and upgrade it as it moves toward SOLD</div>

        <form action="{{ route('domains.store') }}" method="POST" id="addDomainFormInline">
            @csrf
            <div class="grid-2" style="gap:0.75rem;">
                <div class="form-group">
                    <label class="form-label">Domain *</label>
                    <input type="text" name="domain" class="form-input" placeholder="acme.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Price Paid</label>
                    <input type="number" name="price" class="form-input" placeholder="0.00" step="0.01" min="0">
                </div>
            </div>
            <div class="grid-2" style="gap:0.75rem;">
                <div class="form-group">
                    <label class="form-label">Registrar</label>
                    <input type="text" name="registrar" class="form-input" placeholder="GoDaddy / Namecheap">
                </div>
                <div class="form-group">
                    <label class="form-label">Renews At</label>
                    <input type="date" name="renews_at" class="form-input">
                </div>
            </div>
            <div class="form-group" style="margin-bottom:0.25rem;">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-input" rows="2" placeholder="Value estimate, buyer leads, strategy..."></textarea>
            </div>
            <div style="display:flex;justify-content:flex-end;margin-top:0.75rem;">
                <button type="submit" class="btn btn-primary">Add to CRM →</button>
            </div>
        </form>
    </div>

    {{-- Discovered campaign domains --}}
    <div class="card">
        <div class="card-title" style="margin-bottom:0.3rem;">🔎 Discovered Domains</div>
        <div class="card-sub" style="margin-bottom:1rem;">Domains you've built campaigns for but haven't added to the CRM — add them in one click</div>

        @if($campaignDomains->isEmpty())
            <div style="padding:1.5rem;text-align:center;font-size:0.75rem;color:var(--muted);background:var(--bg);border:1px solid var(--border);border-radius:8px;">
                Nothing found — every domain you've pitched is already in your CRM, or you haven't created campaigns yet.
            </div>
        @else
            <div style="display:flex;flex-direction:column;gap:0.5rem;">
                @foreach($campaignDomains as $domainName)
                @php $stats = $campaignDomainStats[$domainName]; @endphp
                <div style="display:flex;align-items:center;gap:0.75rem;padding:0.7rem 0.875rem;background:var(--bg);border:1px solid var(--border);border-radius:8px;">
                    <div style="width:32px;height:32px;border-radius:8px;background:var(--accent-dim);border:1px solid rgba(74,222,128,0.2);display:flex;align-items:center;justify-content:center;font-size:0.85rem;">🌐</div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:0.78rem;font-family:'Syne',sans-serif;font-weight:600;">{{ $domainName }}</div>
                        <div style="font-size:0.62rem;color:var(--muted);">
                            {{ $stats['outbound'] }} outbound · {{ $stats['sent'] }} sent · {{ $stats['replied'] }} replied
                        </div>
                    </div>
                    <form action="{{ route('domains.create-from-campaign') }}" method="POST">
                        @csrf
                        <input type="hidden" name="domain" value="{{ $domainName }}">
                        <button type="submit" class="btn btn-ghost" style="padding:0.35rem 0.7rem;font-size:0.62rem;">+ Add</button>
                    </form>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Domains table --}}
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Domain Portfolio</div>
            <div class="card-sub">Outbound counts pull from your campaigns automatically</div>
        </div>
        <span class="badge badge-green">{{ $domains->count() }} domains</span>
    </div>

    @if($domains->isEmpty())
        <div class="empty">
            <div class="empty-icon">🌐</div>
            <h3>No domains in your CRM</h3>
            <p>Add a domain above, or add the ones you've already been pitching</p>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>Cost</th>
                        <th>Outbound</th>
                        <th>Sent</th>
                        <th>Replies</th>
                        <th>Leads</th>
                        <th>Status</th>
                        <th style="width:240px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($domains as $domain)
                    <tr style="{{ $domain->status === 'sold' ? 'opacity:0.75;' : '' }}">
                        <td>
                            <div style="font-size:0.78rem;font-family:'Syne',sans-serif;font-weight:600;">{{ $domain->domain }}</div>
                            @if($domain->registrar)
                                <div style="font-size:0.6rem;color:var(--muted);">{{ $domain->registrar }}</div>
                            @endif
                        </td>
                        <td style="font-size:0.75rem;">{{ $domain->price }} {{ $domain->currency }}</td>
                        <td style="font-size:0.75rem;color:var(--yellow);font-family:'Syne',sans-serif;font-weight:700;">{{ $domain->outboundCount() }}</td>
                        <td style="font-size:0.75rem;color:var(--blue);">{{ $domain->sentCount() }}</td>
                        <td style="font-size:0.75rem;color:var(--accent);">{{ $domain->repliedCount() }}</td>
                        <td style="font-size:0.75rem;">{{ $domain->leads()->count() }}</td>
                        <td>
                            @php
                                $statusMap = [
                                    'available'   => ['badge-gray',   'Available'],
                                    'outbounding' => ['badge-yellow', 'Outbounding'],
                                    'sold'        => ['badge-green',  'Sold'],
                                ];
                                $s = $statusMap[$domain->status] ?? ['badge-gray', $domain->status];
                            @endphp
                            <span class="badge {{ $s[0] }}">{{ $s[1] }}</span>
                            @if($domain->status === 'sold' && $domain->sold_price)
                                <div style="font-size:0.62rem;color:var(--accent);margin-top:0.25rem;">{{ $domain->sold_price }} {{ $domain->currency }} sold</div>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:0.4rem;flex-wrap:wrap;">
                                @if($domain->status !== 'sold')
                                <button class="btn btn-primary" style="padding:0.3rem 0.7rem;font-size:0.62rem;" onclick="openSold({{ $domain->id }}, {{ $domain->price }})">🎉 Mark Sold</button>
                                @else
                                <form action="{{ route('domains.toggle-sold', $domain->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost" style="padding:0.3rem 0.7rem;font-size:0.62rem;">↩️ Re-open</button>
                                </form>
                                @endif
                                <button class="btn btn-ghost" style="padding:0.3rem 0.6rem;font-size:0.62rem;" onclick="openEditDomain({{ $domain->id }})">✎</button>
                                <form action="{{ route('domains.destroy', $domain->id) }}" method="POST" onsubmit="return confirm('Remove this domain from CRM?')">
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

{{-- Mark Sold Modal --}}
<div class="modal-overlay" id="soldModal">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('soldModal').classList.remove('open')">✕</button>
        <div class="modal-title">🎉 Domain Sold!</div>
        <div class="modal-sub">Record the sale to close the loop</div>

        <form method="POST" id="soldForm">
            @csrf
            <div class="form-group">
                <label class="form-label">Sold Price</label>
                <input type="number" step="0.01" min="0" name="sold_price" class="form-input" id="soldPriceInput" placeholder="e.g. 5000">
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Sold Date</label>
                <input type="date" name="sold_at" class="form-input" id="soldDateInput" value="{{ now()->toDateString() }}">
            </div>
            <div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1.25rem;">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('soldModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary">Mark Sold →</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Domain Modal --}}
<div class="modal-overlay" id="editDomainModal">
    <div class="modal">
        <button class="modal-close" onclick="document.getElementById('editDomainModal').classList.remove('open')">✕</button>
        <div class="modal-title">✎ Edit Domain</div>
        <div class="modal-sub">Update cost, registrar, and renewal</div>

        <form method="POST" id="editDomainForm">
            @csrf
            @method('PUT')
            <div class="form-group">
                <label class="form-label">Domain *</label>
                <input type="text" name="domain" id="ed_domain" class="form-input" required>
            </div>
            <div class="grid-2" style="gap:0.75rem;">
                <div class="form-group">
                    <label class="form-label">Price Paid</label>
                    <input type="number" step="0.01" min="0" name="price" id="ed_price" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency" id="ed_currency" class="form-input" maxlength="4">
                </div>
            </div>
            <div class="grid-2" style="gap:0.75rem;">
                <div class="form-group">
                    <label class="form-label">Registrar</label>
                    <input type="text" name="registrar" id="ed_registrar" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Renews At</label>
                    <input type="date" name="renews_at" id="ed_renews_at" class="form-input">
                </div>
            </div>
            <div class="grid-2" style="gap:0.75rem;">
                <div class="form-group">
                    <label class="form-label">Registered At</label>
                    <input type="date" name="registered_at" id="ed_registered_at" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Sold Price</label>
                    <input type="number" step="0.01" min="0" name="sold_price" id="ed_sold_price" class="form-input">
                </div>
            </div>
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Status</label>
                <select name="status" id="ed_status" class="form-input">
                    <option value="available">Available</option>
                    <option value="outbounding">Outbounding</option>
                    <option value="sold">Sold</option>
                </select>
            </div>
            <div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1.25rem;">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('editDomainModal').classList.remove('open')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes →</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Open sold confirmation modal
    function openSold(id, defaultPrice) {
        document.getElementById('soldPriceInput').value = defaultPrice || '';
        document.getElementById('soldDateInput').value  = '{{ now()->toDateString() }}';
        document.getElementById('soldForm').action      = '{{ route("domains.toggle-sold", ":id") }}'.replace(':id', id);
        document.getElementById('soldModal').classList.add('open');
    }

    // Edit domain modal hydration
    const editDomains = @json($domains);
    function openEditDomain(id) {
        const domain = editDomains.find(d => d.id === id);
        if (!domain) return;

        document.getElementById('ed_domain').value        = domain.domain;
        document.getElementById('ed_price').value         = domain.price;
        document.getElementById('ed_currency').value      = domain.currency || 'USD';
        document.getElementById('ed_registrar').value     = domain.registrar || '';
        document.getElementById('ed_renews_at').value     = domain.renews_at || '';
        document.getElementById('ed_registered_at').value = domain.registered_at || '';
        document.getElementById('ed_sold_price').value    = domain.sold_price || '';
        document.getElementById('ed_status').value        = domain.status || 'outbounding';
        document.getElementById('editDomainForm').action  = '{{ route("domains.update", ":id") }}'.replace(':id', id);

        document.getElementById('editDomainModal').classList.add('open');
    }

    // Highlight domain from leads page link (?d=domain)
    const urlParams = new URLSearchParams(window.location.search);
    const d = urlParams.get('d');
    if (d) {
        document.querySelectorAll('tbody tr').forEach(row => {
            if (row.textContent.toLowerCase().includes(d.toLowerCase())) {
                row.style.outline = '1px solid var(--accent)';
                row.scrollIntoView({ block: 'center', behavior: 'smooth' });
                setTimeout(() => { row.style.outline = ''; }, 3000);
            }
        });
    }
</script>
@endpush