<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 — Akses Dibatasi | CRM UCIC</title>
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
        .blob-1 { width: 600px; height: 600px; background: #e11d48; top: -200px; left: -150px; }
        .blob-2 { width: 400px; height: 400px; background: #f59e0b; bottom: -100px; right: -100px; }
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
            padding: 2.5rem 2rem;
            max-width: 540px;
            width: 100%;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 8px 24px rgba(0,0,0,0.06), 0 32px 64px rgba(0,0,0,0.04);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            color: #be123c;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 0.35rem 0.85rem;
            border-radius: 9999px;
            margin-bottom: 1.25rem;
        }
        .error-code {
            font-size: 4rem;
            font-weight: 900;
            line-height: 1;
            letter-spacing: -0.04em;
            background: linear-gradient(135deg, #e11d48 0%, #f43f5e 50%, #fb7185 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.75rem;
        }
        .error-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.75rem;
            letter-spacing: -0.02em;
        }
        .error-desc {
            font-size: 0.875rem;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 0.85rem 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.78rem;
            color: #475569;
            text-align: left;
            line-height: 1.5;
        }
        .btn-group {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            border-radius: 12px;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .btn-primary {
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 1px 2px rgba(37,99,235,0.2);
        }
        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .btn-secondary:hover {
            background: #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="card">
        <div class="badge">
            🔒 403 Forbidden — Akses Dibatasi
        </div>

        <div class="error-code">403</div>
        <h1 class="error-title">Akses Ditolak (Unauthorized)</h1>
        
        <p class="error-desc">
            Akun Anda saat ini tidak memiliki wewenang untuk membuka atau mengelola data prospek ini.
        </p>

        <div class="info-box">
            <strong>Mengapa ini terjadi?</strong><br>
            • Sesuai aturan PRD & SOP CRM, prospek tahap awal (<strong>01 BARU s.d 04 HOT PROSPEK</strong>) bersifat tertutup dan hanya dapat diakses oleh <strong>Sales pemilik lead</strong> serta <strong>Supervisor (SPV)</strong> terkait.<br>
            • Staf <strong>Customer Service (CS)</strong> baru dapat mengakses prospek setelah masuk tahap <strong>05 FORMULIR (Serah Terima/Handover)</strong> atau jika ditugaskan langsung.
        </div>

        <div class="btn-group">
            <a href="javascript:history.back()" class="btn btn-secondary">
                ← Kembali ke Halaman Sebelumnya
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                Ke Dashboard Utama
            </a>
        </div>
    </div>
</body>
</html>
