<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Form Lapangan CRM UCIC' }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CSS (Vite / Compiled) -->
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        fontFamily: {
                            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        }
                    }
                }
            }
        </script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endif

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }
        /* Mobile safe-area padding for notch/home indicators */
        .safe-pb {
            padding-bottom: env(safe-area-inset-bottom, 16px);
        }
    </style>
</head>
<body class="h-full text-slate-800 antialiased flex flex-col bg-slate-50 selection:bg-blue-600 selection:text-white">

    <!-- Mobile Top Navigation Header -->
    <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-200/80 px-4 py-3 shadow-xs">
        <div class="flex items-center justify-between max-w-lg mx-auto">
            <div class="flex items-center gap-3">
                <a href="javascript:history.back()" class="p-1.5 -ml-1 text-slate-600 hover:text-slate-900 active:scale-95 transition rounded-lg">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="flex items-center gap-2">
                    <img src="/images/cic-logo.png" alt="UCIC" class="h-6 w-auto object-contain" onerror="this.src='/images/logo.png'">
                    <div>
                        <h1 class="text-sm font-bold text-slate-900 leading-tight">{{ $pageHeader ?? 'Form Lapangan' }}</h1>
                        <p class="text-[11px] text-slate-500 font-medium">CRM Marketing & Sales UCIC</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                    Mobile App
                </span>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 max-w-lg mx-auto w-full px-4 py-5 safe-pb">
        @if(session('success'))
            <div class="mb-4 p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start gap-2.5 text-xs text-emerald-800">
                <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="mb-4 p-3.5 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-800">
                <p class="font-bold mb-1">Periksa kembali isian form:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{ $slot }}
    </main>

    <script>
        // Flutter WebView JavaScript bridge listener/dispatcher
        window.sendToFlutter = function(data) {
            if (window.FlutterChannel && window.FlutterChannel.postMessage) {
                window.FlutterChannel.postMessage(JSON.stringify(data));
            } else if (window.flutter_inappwebview) {
                window.flutter_inappwebview.callHandler('onFormData', data);
            }
        };
    </script>
</body>
</html>
