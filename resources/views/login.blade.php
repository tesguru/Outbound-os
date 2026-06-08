<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Outbound System — Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --bg: #0a0a0a;
            --surface: #111111;
            --border: #1e1e1e;
            --border-hover: #2e2e2e;
            --text: #f0f0f0;
            --muted: #555555;
            --accent: #4ade80;
            --accent-dim: rgba(74, 222, 128, 0.08);
            --accent-glow: rgba(74, 222, 128, 0.15);
            --red: #ef4444;
            --red-dim: rgba(239, 68, 68, 0.08);
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'DM Mono', monospace;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        /* Animated background grid */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(74, 222, 128, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(74, 222, 128, 0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            animation: gridMove 20s linear infinite;
            pointer-events: none;
        }

        @keyframes gridMove {
            0% { transform: translateY(0); }
            100% { transform: translateY(60px); }
        }

        /* Glow orb */
        body::after {
            content: '';
            position: fixed;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(74, 222, 128, 0.06) 0%, transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.5; transform: translate(-50%, -50%) scale(1); }
            50% { opacity: 1; transform: translate(-50%, -50%) scale(1.1); }
        }

        .container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 2rem;
            animation: fadeUp 0.6s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Logo / Brand */
        .brand {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .brand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            border: 1px solid var(--border-hover);
            border-radius: 14px;
            margin-bottom: 1rem;
            background: var(--surface);
            position: relative;
        }

        .brand-icon::before {
            content: '⚡';
            font-size: 1.4rem;
        }

        .brand h1 {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: -0.03em;
            color: var(--text);
        }

        .brand p {
            font-size: 0.72rem;
            color: var(--muted);
            letter-spacing: 0.12em;
            text-transform: uppercase;
            margin-top: 0.3rem;
        }

        /* Card */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(74, 222, 128, 0.3), transparent);
        }

        .card-title {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 0.3rem;
        }

        .card-sub {
            font-size: 0.72rem;
            color: var(--muted);
            margin-bottom: 1.8rem;
            letter-spacing: 0.02em;
        }

        /* Alert */
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-size: 0.75rem;
            margin-bottom: 1.2rem;
            border: 1px solid;
            animation: fadeUp 0.3s ease both;
        }

        .alert-success {
            background: var(--accent-dim);
            border-color: rgba(74, 222, 128, 0.2);
            color: var(--accent);
        }

        .alert-error {
            background: var(--red-dim);
            border-color: rgba(239, 68, 68, 0.2);
            color: var(--red);
        }

        /* Google Button */
        .btn-google {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            width: 100%;
            padding: 0.875rem 1.5rem;
            background: transparent;
            border: 1px solid var(--border-hover);
            border-radius: 10px;
            color: var(--text);
            font-family: 'Syne', sans-serif;
            font-weight: 600;
            font-size: 0.875rem;
            letter-spacing: 0.01em;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-google::before {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--accent-glow);
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .btn-google:hover {
            border-color: var(--accent);
            color: var(--accent);
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(74, 222, 128, 0.1);
        }

        .btn-google:hover::before {
            opacity: 1;
        }

        .btn-google:active {
            transform: translateY(0);
        }

        .btn-google svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            position: relative;
            z-index: 1;
        }

        .btn-google span {
            position: relative;
            z-index: 1;
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.5rem 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .divider span {
            font-size: 0.65rem;
            color: var(--muted);
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        /* Stats row */
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .stat {
            text-align: center;
            padding: 0.75rem 0.5rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .stat-value {
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            color: var(--accent);
            display: block;
        }

        .stat-label {
            font-size: 0.6rem;
            color: var(--muted);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-top: 0.2rem;
            display: block;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.65rem;
            color: var(--muted);
            letter-spacing: 0.05em;
        }

        .footer a {
            color: var(--accent);
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="container">

    <div class="brand">
        <div class="brand-icon"></div>
        <h1>OutboundOS</h1>
        <p>Manual Outbound Engine</p>
    </div>

    <div class="card">

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif

        <div class="card-title">Welcome back</div>
        <div class="card-sub">Sign in to manage your outbound campaigns</div>

        <a href="{{ route('google.redirect') }}" class="btn-google">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            <span>Continue with Google</span>
        </a>

        <div class="divider"><span>what you get</span></div>

        <div class="stats">
            <div class="stat">
                <span class="stat-value">∞</span>
                <span class="stat-label">Accounts</span>
            </div>
            <div class="stat">
                <span class="stat-value">Auto</span>
                <span class="stat-label">Personalize</span>
            </div>
            <div class="stat">
                <span class="stat-value">Live</span>
                <span class="stat-label">Tracking</span>
            </div>
        </div>

    </div>

    <div class="footer">
        Protected by Google OAuth 2.0 &nbsp;·&nbsp; <a href="#">Privacy</a>
    </div>

</div>

</body>
</html>