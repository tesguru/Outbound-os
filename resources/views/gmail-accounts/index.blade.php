@extends('layouts.app')

@section('title', 'Gmail Accounts')
@section('subtitle', 'Manage your connected sending accounts')

@section('topbar-actions')
    <a href="{{ route('google.add-account') }}" class="btn btn-primary">+ Connect Account</a>
@endsection

@section('content')

@if($accounts->isEmpty())
    <div class="card">
        <div class="empty">
            <div class="empty-icon">✉</div>
            <h3>No Gmail accounts connected</h3>
            <p>Connect a Gmail account to start sending outbound emails</p>
            <br>
            <a href="{{ route('google.add-account') }}" class="btn btn-primary">+ Connect Gmail Account</a>
        </div>
    </div>
@else
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Daily Limit</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accounts as $account)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                @if($account->avatar)
                                    <img src="{{ $account->avatar }}" style="width:32px;height:32px;border-radius:50%;border:1px solid var(--border-hover);">
                                @else
                                    <div style="width:32px;height:32px;border-radius:50%;background:var(--accent-dim);display:flex;align-items:center;justify-content:center;font-size:0.75rem;color:var(--accent);font-family:'Syne',sans-serif;font-weight:700;">
                                        {{ strtoupper(substr($account->email, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div style="font-size:0.8rem;font-family:'Syne',sans-serif;font-weight:600;">{{ $account->name ?? 'No Name' }}</div>
                                    <div style="font-size:0.68rem;color:var(--muted);">{{ $account->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <form action="{{ route('gmail-accounts.limit', $account->id) }}" method="POST" class="flex gap-2 items-center">
                                @csrf
                                <input type="number" name="daily_limit" value="{{ $account->daily_limit }}" min="1" max="500" class="form-input" style="width:80px;padding:0.4rem 0.5rem;">
                                <button type="submit" class="btn btn-ghost" style="padding:0.4rem 0.75rem;font-size:0.68rem;">Save</button>
                            </form>
                        </td>
                        <td>
                            <span class="badge {{ $account->is_active ? 'badge-green' : 'badge-red' }}">
                                {{ $account->is_active ? 'Active' : 'Paused' }}
                            </span>
                        </td>
                        <td>
                            <div class="flex gap-2">
                                <form action="{{ route('gmail-accounts.toggle', $account->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost" style="padding:0.4rem 0.75rem;font-size:0.68rem;">
                                        {{ $account->is_active ? 'Pause' : 'Activate' }}
                                    </button>
                                </form>
                                <form action="{{ route('gmail-accounts.destroy', $account->id) }}" method="POST" onsubmit="return confirm('Remove this account?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" style="padding:0.4rem 0.75rem;font-size:0.68rem;">Remove</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection