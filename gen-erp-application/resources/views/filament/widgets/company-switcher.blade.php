@php
    $companies = auth()->user()->companies()->wherePivot('is_active', true)->where('companies.is_active', true)->get();
    $active    = \App\Services\CompanyContext::active();
    $branch    = \App\Services\BranchContext::active();
@endphp

<div class="generp-switcher" x-data="{ open: false }" style="position:relative;">
    <div @click="open = !open" style="
        margin: 12px 12px 8px;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 8px; padding: 9px 12px;
        display: flex; align-items: center; gap: 10px;
        cursor: pointer; transition: background 0.15s;
    " @mouseenter="$el.style.background='rgba(255,255,255,0.07)'"
       @mouseleave="$el.style.background='rgba(255,255,255,0.04)'">
        <div style="
            width:26px; height:26px; border-radius:6px;
            background: linear-gradient(135deg, #1B4F72, #2563eb);
            display:flex; align-items:center; justify-content:center;
            font-size:10px; font-weight:700; color:white; flex-shrink:0;
        ">{{ strtoupper(substr($active->name, 0, 2)) }}</div>
        <div style="flex:1; min-width:0; transition:opacity 0.2s;" x-show="$store.sidebar ? $store.sidebar.isOpen : true">
            <div style="font-size:12px; font-weight:600; color:#e6edf3; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                {{ $active->name }}
            </div>
            <div style="font-size:10px; color:#8b949e;">
                {{ $branch?->name ?? 'All Branches' }}
            </div>
        </div>
        <span style="color:#4d5562; font-size:10px;" x-show="$store.sidebar ? $store.sidebar.isOpen : true">⌄</span>
    </div>

    <!-- Dropdown -->
    <div x-show="open" x-transition @click.outside="open = false" style="
        position:absolute; left:12px; right:12px; top:100%; margin-top:4px;
        background:#161b22; border:1px solid rgba(255,255,255,0.1);
        border-radius:10px; padding:6px; z-index:50;
        box-shadow: 0 8px 24px rgba(0,0,0,0.5);
    ">
        <div style="font-size:10px; color:rgba(139,148,158,0.6); padding:4px 8px 6px; font-family:'JetBrains Mono',monospace; letter-spacing:0.5px; text-transform:uppercase;">
            Companies
        </div>
        @foreach($companies as $company)
        <form method="POST" action="{{ route('company.switch', $company->id) }}">
            @csrf
            <button type="submit" style="
                display:flex; align-items:center; gap:10px; width:100%;
                padding:8px 10px; border-radius:7px; border:none; cursor:pointer;
                background:{{ $company->id === $active->id ? 'rgba(34,197,94,0.12)' : 'transparent' }};
                color:{{ $company->id === $active->id ? '#22c55e' : '#c9d1d9' }};
                font-size:12px; font-weight:500; text-align:left;
                font-family:'Instrument Sans',sans-serif;
                transition: background 0.1s;
            ">
                <div style="
                    width:22px; height:22px; border-radius:5px; flex-shrink:0;
                    background: linear-gradient(135deg, #1B4F72, #2563eb);
                    display:flex; align-items:center; justify-content:center;
                    font-size:9px; font-weight:700; color:white;
                ">{{ strtoupper(substr($company->name, 0, 2)) }}</div>
                <span style="flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $company->name }}</span>
                @if($company->id === $active->id)
                    <span style="font-size:11px; color:#22c55e;">✓</span>
                @endif
            </button>
        </form>
        @endforeach
    </div>
</div>
