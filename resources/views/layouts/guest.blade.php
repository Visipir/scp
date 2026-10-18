<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full dark bg-[#19191C] text-[#EDEDF0] antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SCP - Login') }}</title>

    <!-- Typography: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            letter-spacing: -0.011em; 
        }
        .console-glow-bg {
            background-image: 
                radial-gradient(circle at 10% 90%, rgba(253, 54, 110, 0.08) 0%, rgba(253, 54, 110, 0.01) 45%, transparent 70%),
                radial-gradient(circle at 90% 10%, rgba(107, 43, 60, 0.10) 0%, transparent 50%);
        }
        @media (prefers-reduced-motion: reduce) {
            .console-glow-bg {
                transition: none !important;
                animation: none !important;
            }
        }
    </style>
</head>
<body class="h-full bg-[#19191C] text-[#EDEDF0] selection:bg-[#FD366E] selection:text-white relative overflow-x-hidden min-h-screen flex flex-col justify-between console-glow-bg">
    
    <!-- Top Minimalist Brand Header -->
    <header class="w-full px-6 py-6 flex items-center justify-between z-10">
        <a href="/" class="flex items-center gap-2.5 group">
            <span class="font-black text-xl tracking-tight text-white font-sans">
                SCP<span class="text-[#FD366E]">.</span>
            </span>
            <div class="h-3.5 w-px bg-[#2B2B30]"></div>
            <span class="text-xs text-[#97979B] font-medium">School Class Progress</span>
        </a>
    </header>

    <!-- Main Central Card Area -->
    <main class="flex-1 flex flex-col justify-center items-center py-8 px-4 sm:px-6 lg:px-8 z-10">
        <div class="w-full sm:max-w-md px-7 py-8 sm:p-9 bg-[#1D1D21] border border-[#2B2B30] rounded-2xl shadow-none">
            {{ $slot }}
        </div>
    </main>

    <!-- Bottom Minimalist Footer -->
    <footer class="w-full py-5 px-6 text-center text-xs font-mono text-[#5B5B61] z-10 border-t border-[#2B2B30]/50">
        <span>School Class Progress &copy; {{ date('Y') }}</span>
    </footer>

</body>
</html>
