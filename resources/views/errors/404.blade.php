<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 — Halaman Tidak Ditemukan | CRM UCIC</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/logo-ucic.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo-ucic.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: "Inter", sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        .blob { position: absolute; border-radius: 50%; filter: blur(80px); opacity: 0.08; pointer-events: none; }
        .blob-1 { width: 600px; height: 600px; background: #3b82f6; top: -200px; left: -150px; }
        .blob-2 { width: 400px; height: 400px; background: #6366f1; bottom: -100px; right: -100px; }
        .blob-3 { width: 250px; height: 250px; background: #0ea5e9; top: 50%; left: 60%; }
        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, #cbd5e1 1px, transparent 1px);
            background-size: 28px 28px;
            opacity: 0.35;
            pointer-events: none;
        }
        .card {
            position: relative;
            z-index: 10;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 3rem 2.5rem;
            max-width: 520px;
            width: 100%;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 8px 24px rgba(0,0,0,0.06), 0 32px 64px rgba(0,0,0,0.04);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 0.35rem 0.875rem;
            border-radius: 999px;
            margin-bottom: 1.75rem;
        }
        .badge-dot { width: 6px; height: 6px; background: #3b82f6; border-radius: 50%; animation: pulse 2s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: 0.5; transform: scale(0.75); } }
        .error-code {
            font-size: clamp(5rem, 18vw, 8rem);
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.04em;
            background: linear-gradient(135deg, #1e293b 0%, #3b82f6 50%, #6366f1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            animation: float 4s ease-in-out infinite;
        }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        .icon-ring {
            width: 72px; height: 72px;
            background: linear-gradient(135deg, #eff6ff, #e0e7ff);
            border: 1.5px solid #bfdbfe;
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .icon-ring svg { width: 32px; height: 32px; color: #3b82f6; }
        h1 { font-size: 1.375rem; font-weight: 800; color: #0f172a; letter-spacing: -0.025em; margin-bottom: 0.625rem; }
        p { font-size: 0.875rem; color: #64748b; line-height: 1.6; margin-bottom: 2rem; }
        .divider { border: none; border-top: 1px solid #f1f5f9; margin: 0 0 2rem; }
        .btn-group { display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.25rem; border-radius: 10px; font-size: 0.8125rem; font-weight: 600; text-decoration: none; transition: all 0.15s ease; border: 1px solid transparent; }
        .btn-primary { background: linear-gradient(135deg, #2563eb, #4f46e5); color: #ffffff; box-shadow: 0 2px 8px rgba(37,99,235,0.3); }
        .btn-primary:hover { box-shadow: 0 4px 16px rgba(37,99,235,0.4); transform: translateY(-1px); }
        .btn-secondary { background: #f8fafc; color: #374151; border-color: #e2e8f0; }
        .btn-secondary:hover { background: #f1f5f9; border-color: #cbd5e1; transform: translateY(-1px); }
        .footer-info { margin-top: 2rem; padding-top: 1.25rem; border-top: 1px solid #f1f5f9; font-size: 0.7rem; color: #94a3b8; font-weight: 500; }
        .footer-info span { color: #64748b; font-weight: 600; }
    </style>
</head>
<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="card">
        <div style="display: flex; justify-content: center; margin-bottom: 1.25rem;">
            <img src="{{ asset('images/logo-ucic.png') }}" alt="Logo UCIC" style="height: 48px; width: auto; object-fit: contain;">
        </div>
        <div class="badge"><div class="badge-dot"></div>Error 404</div>
        <div class="error-code">404</div>
        <div class="icon-ring">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
        </div>
        <h1>Halaman Tidak Ditemukan</h1>
        <p>Data atau halaman yang Anda cari tidak tersedia, sudah dihapus,<br>atau Anda tidak memiliki akses untuk melihatnya.</p>
        <hr class="divider">
        <div class="btn-group">
            @auth
                <a href="{{ route("dashboard") }}" class="btn btn-primary">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    Kembali ke Dashboard
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Halaman Sebelumnya
                </a>
            @else
                <a href="{{ route("login") }}" class="btn btn-primary">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                    Login ke CRM
                </a>
            @endauth
        </div>
        <div class="footer-info">CRM Marketing &amp; Sales Inbound &mdash; <span>Universitas Catur Insan Cendekia</span></div>
    </div>
</body>
</html>
