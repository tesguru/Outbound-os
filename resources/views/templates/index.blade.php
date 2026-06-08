@extends('layouts.app')

@section('title', 'Templates')
@section('subtitle', 'Manage your personal and company templates')

@section('topbar-actions')
    <a href="{{ route('templates.create') }}" class="btn btn-primary">+ New Template</a>
@endsection

@section('content')

@if($templates->isEmpty())
    <div class="card">
        <div class="empty">
            <div class="empty-icon">❐</div>
            <h3>No templates yet</h3>
            <p>Create your first template to start sending outbound emails</p>
            <br>
            <a href="{{ route('templates.create') }}" class="btn btn-primary">+ Create Template</a>
        </div>
    </div>
@else

    {{-- Personal Initial --}}
    @php
        $personalInitial  = $templates->where('type','personal')->where('category','initial');
        $personalFollowup = $templates->where('type','personal')->where('category','followup');
        $companyInitial   = $templates->where('type','company')->where('category','initial');
        $companyFollowup  = $templates->where('type','company')->where('category','followup');
    @endphp

    <div style="display:flex;flex-direction:column;gap:1.5rem;">

        @foreach([
            ['label'=>'👤 Personal — Initial','color'=>'badge-blue','items'=>$personalInitial],
            ['label'=>'👤 Personal — Follow-up','color'=>'badge-blue','items'=>$personalFollowup],
            ['label'=>'🏢 Company — Initial','color'=>'badge-yellow','items'=>$companyInitial],
            ['label'=>'🏢 Company — Follow-up','color'=>'badge-yellow','items'=>$companyFollowup],
        ] as $group)
            @if($group['items']->count())
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">{{ $group['label'] }}</div>
                        <div class="card-sub">{{ $group['items']->count() }} template{{ $group['items']->count() > 1 ? 's' : '' }}</div>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Subject</th>
                                <th>Has Price</th>
                                <th>Placeholders</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group['items'] as $template)
                            <tr>
                                <td>
                                    <div style="font-family:'Syne',sans-serif;font-weight:600;font-size:0.8rem;">{{ $template->name }}</div>
                                    <div style="font-size:0.62rem;color:var(--muted);margin-top:0.1rem;">Created {{ $template->created_at->diffForHumans() }}</div>
                                </td>
                                <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:0.75rem;">
                                    {{ $template->subject }}
                                </td>
                                <td>
                                    @if($template->has_price)
                                        <span class="badge badge-green">Yes</span>
                                    @else
                                        <span class="badge badge-gray">No</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex;gap:0.3rem;flex-wrap:wrap;">
                                        @if(str_contains($template->body, '{{first_name}}'))
                                            <span class="badge badge-blue">{{first_name}}</span>
                                        @endif
                                        @if(str_contains($template->body, '{{company_name}}'))
                                            <span class="badge badge-yellow">{{company_name}}</span>
                                        @endif
                                        @if(str_contains($template->body, '{{price}}'))
                                            <span class="badge badge-green">{{price}}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <a href="{{ route('templates.edit', $template->id) }}" class="btn btn-ghost" style="padding:0.4rem 0.75rem;font-size:0.68rem;">Edit</a>
                                        <form action="{{ route('templates.destroy', $template->id) }}" method="POST" onsubmit="return confirm('Delete this template?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" style="padding:0.4rem 0.75rem;font-size:0.68rem;">Delete</button>
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
        @endforeach

    </div>
@endif

@endsection