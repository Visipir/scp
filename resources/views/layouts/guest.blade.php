@php
    $sysConfig = \App\Models\SystemConfiguration::first();
    $bgTimestamp = ($sysConfig && $sysConfig->login_background_updated_at)
        ? $sysConfig->login_background_updated_at->timestamp
        : 1;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'School Class Progress - Login') }}</title>

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
<body class="h-full bg-[var(--bg-page)] text-[var(--text-primary)] selection:bg-[#0093dd] selection:text-white relative overflow-x-hidden min-h-screen transition-colors duration-150">
    
    <div class="min-h-screen w-full lg:grid lg:grid-cols-2">
        
        {{-- KOLOM KIRI: FORM & AUTH CARD (Desktop & Mobile) --}}
        <div class="flex flex-col justify-between min-h-screen p-5 sm:p-8 lg:p-10 xl:p-12 z-10">
            <!-- Top Minimalist Brand Header -->
            <header class="w-full flex items-center justify-between">
                <a href="/" class="flex items-center gap-2.5 group">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo SCP" class="w-8 h-8 object-contain shrink-0">
                    <span class="text-base font-bold text-zinc-900 dark:text-white tracking-tight">School Class Progress</span>
                </a>

                <!-- Theme Preference Dropdown -->
                <x-theme-dropdown align="right" />
            </header>

            <!-- Main Central Card Area -->
            <main class="my-auto py-8 flex flex-col justify-center items-center w-full">
                <div class="w-full sm:max-w-md px-6 py-7 sm:p-8 bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-2xl shadow-xl dark:shadow-none transition-colors duration-150">
                    {{ $slot }}
                </div>
            </main>

            <!-- Bottom Minimalist Footer -->
            <footer class="w-full py-4 text-center text-xs font-mono text-zinc-400 dark:text-[#5B5B61] border-t border-zinc-200/80 dark:border-[#2B2B30]/50">
                <span>School Class Progress &copy; {{ date('Y') }} &bull; SMK Negeri 1 Bawang</span>
            </footer>
        </div>

        {{-- KOLOM KANAN: GAMBAR LATAR BELAKANG PENUH TINGGI (Hanya Desktop >= lg) --}}
        <div class="hidden lg:block relative h-full w-full overflow-hidden bg-gradient-to-br from-[#0c1f38] via-[#0d2a4a] to-[#08182b]">
            <picture class="absolute inset-0 w-full h-full block">
                <source media="(min-width: 1024px)" srcset="{{ route('login.background', ['v' => $bgTimestamp]) }}">
                <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg'/%3E" alt="" aria-hidden="true" class="w-full h-full object-cover">
            </picture>

            {{-- Subtle Gradient Overlay --}}
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-black/10"></div>

            {{-- Decorative Branding Text Overlay --}}
            <div class="absolute bottom-10 left-10 right-10 z-10 text-white pointer-events-none">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-xs font-mono mb-3">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Sistem Informasi Akademik</span>
                </div>
                <h1 class="text-2xl xl:text-3xl font-extrabold tracking-tight">School Class Progress</h1>
                <p class="text-xs xl:text-sm text-white/80 mt-1 max-w-lg leading-relaxed">
                    Platform terpadu untuk monitoring jurnal mengajar guru, absensi siswa real-time, dan manajemen kurikulum sekolah.
                </p>
            </div>
        </div>

    </div>

</body>
</html>
