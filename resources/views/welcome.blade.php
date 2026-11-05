<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>School Class Progress</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">

    <!-- Typography: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Theme Preference Initialization (Auto Device Detection / Stored) -->
    <script>
        (function() {
            try {
                const savedTheme = localStorage.getItem('theme') || 'system';
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const isDark = savedTheme === 'dark' || (savedTheme === 'system' && prefersDark);
                if (isDark) {
                    document.documentElement.classList.add('dark');
                    document.documentElement.classList.remove('light');
                } else {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.classList.add('light');
                }
            } catch (e) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            letter-spacing: -0.011em; 
        }
    </style>
</head>
<body class="h-full bg-[var(--bg-page)] text-[var(--text-primary)] selection:bg-[#0093dd] selection:text-white relative overflow-x-hidden min-h-screen flex flex-col justify-between transition-colors duration-150">
    
    <!-- Top Navigation -->
    <header class="w-full px-6 py-5 flex justify-between items-center z-10 border-b border-zinc-200/80 dark:border-[#2B2B30]/80 bg-white/70 dark:bg-[#19191C]/70 backdrop-blur-sm sticky top-0">
        <a href="/" class="flex items-center gap-2.5 group">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-8 h-8 object-contain shrink-0">
            <span class="text-base font-bold text-zinc-900 dark:text-white tracking-tight">School Class Progress</span>
        </a>

        <div class="flex items-center gap-3">
            <x-theme-dropdown align="right" />

            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="inline-flex items-center px-4 py-2 bg-[#0093dd] hover:bg-[#0082c4] text-white text-xs font-semibold uppercase tracking-wider rounded-lg transition shadow-none cursor-pointer">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 bg-[#0093dd] hover:bg-[#0082c4] text-white text-xs font-semibold uppercase tracking-wider rounded-lg transition shadow-none cursor-pointer">
                        Masuk Akun
                    </a>
                @endauth
            @endif
        </div>
    </header>

    <!-- Hero Section -->
    <main class="flex-1 flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8 py-16 z-10">
        <div class="text-center max-w-3xl mx-auto">
            
            <h1 class="text-4xl sm:text-6xl font-black tracking-tight text-zinc-900 dark:text-white mb-5 leading-tight font-sans">
                School Class <span class="text-[#0093dd]">Progress</span>
            </h1>
            
            <p class="text-sm sm:text-base text-zinc-600 dark:text-[#97979B] mb-8 max-w-xl mx-auto leading-relaxed">
                Platform digital modern untuk manajemen jadwal KBM, pemantauan presensi siswa, dan supervisi jurnal guru secara real-time.
            </p>

            <div class="flex flex-col sm:flex-row gap-3 justify-center items-center">
                @auth
                    <a href="{{ url('/dashboard') }}" class="inline-flex justify-center items-center w-full sm:w-auto px-6 py-2.5 text-xs font-semibold uppercase tracking-wider text-white bg-[#0093dd] hover:bg-[#0082c4] active:bg-[#006fa7] rounded-lg transition-all shadow-none">
                        Buka Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex justify-center items-center w-full sm:w-auto px-6 py-2.5 text-xs font-semibold uppercase tracking-wider text-white bg-[#0093dd] hover:bg-[#0082c4] active:bg-[#006fa7] rounded-lg transition-all shadow-none">
                        Masuk ke Sistem
                    </a>
                @endauth
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="py-5 px-6 text-center text-xs font-mono text-zinc-400 dark:text-[#5B5B61] z-10 border-t border-zinc-200/80 dark:border-[#2B2B30]/50">
        &copy; {{ date('Y') }} School Class Progress. All rights reserved.
    </footer>

</body>
</html>
