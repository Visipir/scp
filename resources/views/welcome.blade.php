<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#0F1014">
    <meta name="text-scale" content="scale">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <title>{{ config('app.name', 'School Class Progress') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">

    <!-- Typography: Self-hosted Plus Jakarta Sans & JetBrains Mono via Vite -->

    <!-- Theme Preference Initialization (Auto Device Detection / Stored) -->
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        (function() {
            try {
                const savedTheme = localStorage.getItem('theme') || 'system';
                const colorMeta = document.querySelector('meta[name="color-scheme"]');
                if (colorMeta) {
                    colorMeta.content = savedTheme === 'dark' ? 'dark' : (savedTheme === 'light' ? 'light' : 'light dark');
                }
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                const isDark = savedTheme === 'dark' || (savedTheme === 'system' && prefersDark);
                if (isDark) {
                    document.documentElement.classList.add('dark');
                    document.documentElement.classList.remove('light');
                } else {
                    document.documentElement.classList.remove('dark');
                    document.documentElement.classList.add('light');
                }
                const themeMeta = document.querySelector('meta[name="theme-color"]');
                if (themeMeta) {
                    themeMeta.content = isDark ? '#0F1014' : '#F0F2F5';
                }
            } catch (e) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        [x-cloak] { display: none !important; }
        body { 
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            letter-spacing: -0.011em; 
        }
    </style>
</head>
<body class="h-full bg-[var(--bg-page)] text-[var(--text-primary)] selection:bg-[#0073B1] selection:text-white relative overflow-x-hidden min-h-dvh flex flex-col justify-between transition-colors duration-150">
    <a href="#main-content" class="skip-link">Lewati ke konten utama</a>
    <!-- Top Navigation -->
    <header class="w-full px-6 py-5 pt-[max(1.25rem,env(safe-area-inset-top,0px))] flex justify-between items-center z-10 border-b border-zinc-200/80 dark:border-[#2B2B30]/80 bg-white dark:bg-[#19191C] sticky top-0">
        <a href="/" class="flex items-center gap-2.5 group">
            <picture class="shrink-0">
                <source srcset="{{ asset('images/logo.webp') }}" type="image/webp">
                <img src="{{ asset('images/logo.png') }}" alt="" width="32" height="32" class="w-8 h-8 object-contain shrink-0">
            </picture>
            <span translate="no" class="text-base font-bold text-zinc-900 dark:text-white tracking-tight">School Class Progress</span>
        </a>

        <div class="flex items-center gap-3">
            <x-theme-dropdown align="right" />

            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/dashboard') }}" class="inline-flex items-center min-h-[44px] sm:min-h-0 px-4 py-2 bg-[#0073B1] hover:bg-[#005D8F] text-white text-xs font-semibold uppercase tracking-wider rounded-lg transition shadow-none cursor-pointer">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center min-h-[44px] sm:min-h-0 px-4 py-2 bg-[#0073B1] hover:bg-[#005D8F] text-white text-xs font-semibold uppercase tracking-wider rounded-lg transition shadow-none cursor-pointer">
                        Masuk Akun
                    </a>
                @endauth
            @endif
        </div>
    </header>

    <!-- Hero Section -->
    <main id="main-content" tabindex="-1" class="flex-1 flex flex-col justify-center items-center px-4 sm:px-6 lg:px-8 py-16 z-10 focus-visible:outline-none">
        <div class="text-center max-w-3xl mx-auto">
            
            <h1 class="text-4xl sm:text-6xl font-black tracking-tight text-zinc-900 dark:text-white mb-5 leading-tight font-sans">
                School Class <span class="text-[#0073B1] dark:text-[#38BDF8]">Progress</span>
            </h1>
            
            <p class="text-sm sm:text-base text-zinc-600 dark:text-[#B4B6C2] mb-8 max-w-xl mx-auto leading-relaxed">
                Platform digital modern untuk manajemen jadwal KBM, pemantauan presensi siswa, dan supervisi jurnal guru secara real-time.
            </p>

            <div class="flex flex-col sm:flex-row gap-3 justify-center items-center">
                @auth
                    <a href="{{ url('/dashboard') }}" class="inline-flex justify-center items-center min-h-[44px] w-full sm:w-auto px-6 py-2.5 text-xs font-semibold uppercase tracking-wider text-white bg-[#0073B1] hover:bg-[#005D8F] active:bg-[#004A73] rounded-lg transition-colors duration-150 shadow-none">
                        Buka Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex justify-center items-center min-h-[44px] w-full sm:w-auto px-6 py-2.5 text-xs font-semibold uppercase tracking-wider text-white bg-[#0073B1] hover:bg-[#005D8F] active:bg-[#004A73] rounded-lg transition-colors duration-150 shadow-none">
                        Masuk ke Sistem
                    </a>
                @endauth
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="py-5 px-6 pb-[max(1.25rem,env(safe-area-inset-bottom,0px))] text-center text-xs font-mono text-zinc-600 dark:text-[#9496A1] z-10 border-t border-zinc-200/80 dark:border-[#2B2B30]/50">
        &copy; {{ date('Y') }} School Class Progress. All rights reserved.
    </footer>

</body>
</html>
