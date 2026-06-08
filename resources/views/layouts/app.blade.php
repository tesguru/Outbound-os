<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OutboundOS — @yield('title')</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg: #0a0a0a;
            --surface: #111111;
            --surface2: #161616;
            --border: #1e1e1e;
            --border-hover: #2e2e2e;
            --text: #f0f0f0;
            --muted: #555555;
            --muted2: #333333;
            --accent: #4ade80;
            --accent-dim: rgba(74, 222, 128, 0.08);
            --accent-glow: rgba(74, 222, 128, 0.15);
            --red: #ef4444;
            --red-dim: rgba(239, 68, 68, 0.08);
            --yellow: #facc15;
            --yellow-dim: rgba(250, 204, 21, 0.08);
            --blue: #60a5fa;
            --blue-dim: rgba(96, 165, 250, 0.08);
            --sidebar-width: 240px;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Mono', monospace;
            min-height: 100vh;
            display: flex;
        }

        /* ============================================================
           SIDEBAR
        ============================================================ */
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 200;
            transition: transform 0.3s ease;
        }

        .sidebar-brand {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .sidebar-brand-icon {
            width: 36px;
            height: 36px;
            background: var(--accent-dim);
            border: 1px solid rgba(74, 222, 128, 0.2);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .sidebar-brand-text h2 {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 0.95rem;
            color: var(--text);
            letter-spacing: -0.02em;
        }

        .sidebar-brand-text span {
            font-size: 0.6rem;
            color: var(--muted);
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .sidebar-nav {
            flex: 1;
            padding: 1rem 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            overflow-y: auto;
        }

        .nav-label {
            font-size: 0.58rem;
            color: var(--muted);
            letter-spacing: 0.15em;
            text-transform: uppercase;
            padding: 0.75rem 0.75rem 0.4rem;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.6rem 0.75rem;
            border-radius: 8px;
            text-decoration: none;
            color: var(--muted);
            font-size: 0.78rem;
            letter-spacing: 0.01em;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }

        .nav-item:hover {
            color: var(--text);
            background: var(--surface2);
            border-color: var(--border);
        }

        .nav-item.active {
            color: var(--accent);
            background: var(--accent-dim);
            border-color: rgba(74, 222, 128, 0.15);
        }

        .nav-item .icon { font-size: 0.9rem; width: 18px; text-align: center; flex-shrink: 0; }

        .sidebar-footer {
            padding: 1rem 0.75rem;
            border-top: 1px solid var(--border);
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.6rem 0.75rem;
            border-radius: 8px;
            background: var(--surface2);
            border: 1px solid var(--border);
            margin-bottom: 0.5rem;
        }

        .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--border-hover);
            flex-shrink: 0;
        }

        .user-avatar-placeholder {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--accent-dim);
            border: 1px solid rgba(74, 222, 128, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            color: var(--accent);
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            flex-shrink: 0;
        }

        .user-info { flex: 1; min-width: 0; }
        .user-name {
            font-size: 0.72rem;
            color: var(--text);
            font-family: 'Syne', sans-serif;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-email {
            font-size: 0.6rem;
            color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .btn-logout {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.55rem;
            background: transparent;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--muted);
            font-family: 'DM Mono', monospace;
            font-size: 0.72rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn-logout:hover {
            color: var(--red);
            border-color: rgba(239, 68, 68, 0.3);
            background: var(--red-dim);
        }

        /* ============================================================
           OVERLAY (mobile sidebar backdrop)
        ============================================================ */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 199;
            backdrop-filter: blur(2px);
        }

        /* ============================================================
           MAIN CONTENT
        ============================================================ */
        .main {
            margin-left: var(--sidebar-width);
            flex: 1;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        /* ============================================================
           TOPBAR
        ============================================================ */
        .topbar {
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--surface);
            position: sticky;
            top: 0;
            z-index: 50;
            gap: 1rem;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            min-width: 0;
            flex: 1;
        }

        /* Hamburger button — hidden on desktop */
        .menu-toggle {
            display: none;
            background: none;
            border: 1px solid var(--border-hover);
            border-radius: 7px;
            color: var(--muted);
            cursor: pointer;
            padding: 0.45rem 0.55rem;
            transition: all 0.15s ease;
            flex-shrink: 0;
            line-height: 1;
            font-size: 1rem;
        }

        .menu-toggle:hover {
            color: var(--text);
            background: var(--surface2);
        }

        .topbar-titles { min-width: 0; }

        .topbar-title {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: -0.02em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .topbar-sub {
            font-size: 0.65rem;
            color: var(--muted);
            margin-top: 0.1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-shrink: 0;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        /* ============================================================
           PAGE CONTENT
        ============================================================ */
        .page-content {
            padding: 2rem;
            flex: 1;
        }

        /* ============================================================
           ALERTS
        ============================================================ */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.75rem;
            margin-bottom: 1.5rem;
            border: 1px solid;
            animation: fadeDown 0.3s ease both;
        }

        @keyframes fadeDown {
            from { opacity: 0; transform: translateY(-8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success { background: var(--accent-dim); border-color: rgba(74,222,128,0.2); color: var(--accent); }
        .alert-error   { background: var(--red-dim);    border-color: rgba(239,68,68,0.2);  color: var(--red); }

        /* ============================================================
           BUTTONS
        ============================================================ */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            font-family: 'Syne', sans-serif;
            font-weight: 600;
            font-size: 0.78rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            border: 1px solid;
            letter-spacing: 0.01em;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #000;
        }

        .btn-primary:hover {
            background: #22c55e;
            border-color: #22c55e;
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(74, 222, 128, 0.2);
        }

        .btn-ghost {
            background: transparent;
            border-color: var(--border-hover);
            color: var(--muted);
        }

        .btn-ghost:hover {
            color: var(--text);
            border-color: var(--border-hover);
            background: var(--surface2);
        }

        .btn-danger {
            background: transparent;
            border-color: rgba(239,68,68,0.3);
            color: var(--red);
        }

        .btn-danger:hover {
            background: var(--red-dim);
            transform: translateY(-1px);
        }

        /* ============================================================
           CARDS
        ============================================================ */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .card-title {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .card-sub {
            font-size: 0.68rem;
            color: var(--muted);
            margin-top: 0.2rem;
        }

        /* ============================================================
           BADGES
        ============================================================ */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.62rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border: 1px solid;
            white-space: nowrap;
        }

        .badge-green  { background: var(--accent-dim);  border-color: rgba(74,222,128,0.2);  color: var(--accent); }
        .badge-red    { background: var(--red-dim);     border-color: rgba(239,68,68,0.2);   color: var(--red); }
        .badge-yellow { background: var(--yellow-dim);  border-color: rgba(250,204,21,0.2);  color: var(--yellow); }
        .badge-blue   { background: var(--blue-dim);    border-color: rgba(96,165,250,0.2);  color: var(--blue); }
        .badge-gray   { background: var(--muted2);      border-color: var(--border-hover);   color: var(--muted); }

        /* ============================================================
           TABLE
        ============================================================ */
        .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; min-width: 500px; }
        th {
            text-align: left;
            font-size: 0.62rem;
            color: var(--muted);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 0.65rem 1rem;
            border-bottom: 1px solid var(--border);
            font-weight: 500;
            white-space: nowrap;
        }
        td {
            padding: 0.85rem 1rem;
            font-size: 0.75rem;
            border-bottom: 1px solid var(--border);
            color: var(--text);
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: var(--surface2); }

        /* ============================================================
           EMPTY STATE
        ============================================================ */
        .empty { text-align: center; padding: 4rem 2rem; color: var(--muted); }
        .empty-icon { font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.3; }
        .empty h3 { font-family: 'Syne', sans-serif; font-size: 0.9rem; color: var(--text); margin-bottom: 0.5rem; }
        .empty p  { font-size: 0.72rem; }

        /* ============================================================
           GRID HELPERS
        ============================================================ */
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }

        /* ============================================================
           STAT CARD
        ============================================================ */
        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
        }
        .stat-label { font-size: 0.65rem; color: var(--muted); letter-spacing: 0.1em; text-transform: uppercase; }
        .stat-value { font-family: 'Syne', sans-serif; font-weight: 800; font-size: 1.8rem; color: var(--text); margin: 0.3rem 0; letter-spacing: -0.03em; }
        .stat-value span { color: var(--accent); }
        .stat-desc { font-size: 0.65rem; color: var(--muted); }

        /* ============================================================
           FORM ELEMENTS
        ============================================================ */
        .form-group  { margin-bottom: 1rem; }
        .form-label  { display: block; font-size: 0.68rem; color: var(--muted); letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 0.4rem; }
        .form-input  {
            width: 100%;
            padding: 0.65rem 0.875rem;
            background: var(--bg);
            border: 1px solid var(--border-hover);
            border-radius: 8px;
            color: var(--text);
            font-family: 'DM Mono', monospace;
            font-size: 0.78rem;
            transition: border-color 0.15s ease;
            outline: none;
        }
        .form-input:focus { border-color: var(--accent); }

        /* ============================================================
           MODAL
        ============================================================ */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            z-index: 300;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
            padding: 1rem;
        }
        .modal-overlay.open { display: flex; }
        .modal {
            background: var(--surface);
            border: 1px solid var(--border-hover);
            border-radius: 16px;
            padding: 2rem;
            width: 100%;
            max-width: 440px;
            animation: fadeUp 0.2s ease both;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-title { font-family: 'Syne', sans-serif; font-weight: 700; font-size: 1rem; margin-bottom: 0.3rem; }
        .modal-sub   { font-size: 0.72rem; color: var(--muted); margin-bottom: 1.5rem; }
        .modal-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            font-size: 1.2rem;
            line-height: 1;
            transition: color 0.15s;
        }
        .modal-close:hover { color: var(--text); }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ============================================================
           UTILITY
        ============================================================ */
        .flex            { display: flex; }
        .items-center    { align-items: center; }
        .justify-between { justify-content: space-between; }
        .gap-2  { gap: 0.5rem; }
        .gap-3  { gap: 0.75rem; }
        .mt-1   { margin-top: 0.25rem; }
        .mt-2   { margin-top: 0.5rem; }
        .mt-3   { margin-top: 0.75rem; }
        .mt-4   { margin-top: 1rem; }
        .mb-4   { margin-bottom: 1rem; }
        .w-full { width: 100%; }

        /* ============================================================
           RESPONSIVE — Tablet (≤1024px)
        ============================================================ */
        @media (max-width: 1024px) {
            :root { --sidebar-width: 200px; }

            .topbar  { padding: 1rem 1.5rem; }
            .page-content { padding: 1.5rem; }
        }

        /* ============================================================
           RESPONSIVE — Mobile (≤768px)
           Sidebar becomes a drawer, topbar gets a hamburger
        ============================================================ */
        @media (max-width: 768px) {
            /* Sidebar slides off-screen by default */
            .sidebar {
                transform: translateX(-100%);
                width: 260px;
                box-shadow: 4px 0 24px rgba(0,0,0,0.4);
            }

            /* When open */
            .sidebar.open {
                transform: translateX(0);
            }

            .sidebar-overlay.open {
                display: block;
            }

            /* Main takes full width */
            .main {
                margin-left: 0;
            }

            /* Show hamburger */
            .menu-toggle {
                display: flex;
            }

            /* Topbar adjustments */
            .topbar {
                padding: 0.875rem 1rem;
            }

            .topbar-title { font-size: 0.88rem; }
            .topbar-sub   { font-size: 0.6rem; }

            /* Stack topbar actions on very small screens */
            .topbar-actions {
                gap: 0.35rem;
            }

            .topbar-actions .btn {
                padding: 0.45rem 0.75rem;
                font-size: 0.7rem;
            }

            /* Page content */
            .page-content { padding: 1rem; }

            /* Grids collapse */
            .grid-2,
            .grid-3,
            .grid-4 { grid-template-columns: 1fr; }

            /* Cards */
            .card { padding: 1rem; }

            /* Stat grid — 2 col on mobile */
            .stat-grid-4 { grid-template-columns: repeat(2, 1fr) !important; }

            /* Modal full-width */
            .modal {
                padding: 1.5rem;
                border-radius: 12px;
                max-height: 85vh;
            }
        }

        /* ============================================================
           RESPONSIVE — Small mobile (≤480px)
        ============================================================ */
        @media (max-width: 480px) {
            .topbar-actions .btn {
                padding: 0.4rem 0.6rem;
                font-size: 0.65rem;
            }

            /* Hide button labels, keep icons on tiny screens */
            .btn-label-hide { display: none; }

            .stat-value { font-size: 1.4rem; }

            .page-content { padding: 0.875rem; }

            .card { padding: 0.875rem; }

            table { min-width: 400px; }
        }
    </style>
    @stack('styles')
</head>
<body>

{{-- Mobile sidebar overlay --}}
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

{{-- Sidebar --}}
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-brand-icon">⚡</div>
        <div class="sidebar-brand-text">
            <h2>OutboundOS</h2>
            <span>Outbound Engine</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-label">Main</div>
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" onclick="closeSidebar()">
            <span class="icon">▦</span> Dashboard
        </a>
        <a href="{{ route('campaigns.index') }}" class="nav-item {{ request()->routeIs('campaigns.*') ? 'active' : '' }}" onclick="closeSidebar()">
            <span class="icon">◎</span> Campaigns
        </a>

        <div class="nav-label">Setup</div>
        <a href="{{ route('gmail-accounts.index') }}" class="nav-item {{ request()->routeIs('gmail-accounts.*') ? 'active' : '' }}" onclick="closeSidebar()">
            <span class="icon">✉</span> Gmail Accounts
        </a>
        <a href="{{ route('templates.index') }}" class="nav-item {{ request()->routeIs('templates.*') ? 'active' : '' }}" onclick="closeSidebar()">
            <span class="icon">❐</span> Templates
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            @if(Auth::user()->avatar)
                <img src="{{ Auth::user()->avatar }}" alt="avatar" class="user-avatar">
            @else
                <div class="user-avatar-placeholder">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
            @endif
            <div class="user-info">
                <div class="user-name">{{ Auth::user()->name }}</div>
                <div class="user-email">{{ Auth::user()->email }}</div>
            </div>
        </div>
        <a href="{{ route('logout') }}" class="btn-logout">⇠ Logout</a>
    </div>
</aside>

{{-- Main --}}
<main class="main">
    <div class="topbar">
        <div class="topbar-left">
            {{-- Hamburger: only visible on mobile --}}
            <button class="menu-toggle" id="menuToggle" onclick="openSidebar()" aria-label="Open menu">
                ☰
            </button>
            <div class="topbar-titles">
                <div class="topbar-title">@yield('title')</div>
                <div class="topbar-sub">@yield('subtitle')</div>
            </div>
        </div>
        <div class="topbar-actions">
            @yield('topbar-actions')
        </div>
    </div>

    <div class="page-content">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        @yield('content')
    </div>
</main>

<script>
    function openSidebar() {
        document.getElementById('sidebar').classList.add('open');
        document.getElementById('sidebarOverlay').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('open');
        document.body.style.overflow = '';
    }

    // Close sidebar on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeSidebar();
    });
</script>

@stack('scripts')
</body>
</html>