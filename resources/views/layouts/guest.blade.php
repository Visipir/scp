<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-zinc-50 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SCP - Login') }}</title>

    <!-- Swiss Minimalist Typography: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', sans-serif; letter-spacing: -0.011em; }
    </style>
</head>
<body class="h-full bg-zinc-50 text-zinc-900 selection:bg-zinc-900 selection:text-white">
    <div class="min-h-screen flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        
        <!-- Swiss Logo Mark -->
        <div class="mb-8 text-center">
            <a href="/" class="inline-flex items-center gap-3 group">
                <div class="w-10 h-10 bg-zinc-900 text-white flex items-center justify-center font-black text-base rounded tracking-tighter shadow-xs group-hover:bg-black transition">
                    SCP
                </div>
                <div class="text-left">
                    <span class="font-bold text-lg tracking-tight text-zinc-900 block leading-tight">School Class Progress<span class="text-rose-600">.</span></span>
                    <span class="font-mono text-[10px] text-zinc-400 uppercase tracking-widest block">Sistem Kurikulum &amp; KBM</span>
                </div>
            </a>
        </div>

        <!-- Login Card -->
        <div class="w-full sm:max-w-md px-6 py-8 sm:p-8 bg-white border border-zinc-200/80 rounded-lg shadow-xs">
            {{ $slot }}
        </div>

        <div class="mt-8 text-center text-xs font-mono text-zinc-400">
            School Class Progress &bull; Modern Swiss Minimalist
        </div>
        
    </div>
</body>
</html>
