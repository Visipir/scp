@props(['title' => null])
@php
    $breadcrumbMap = [
        'dashboard'            => 'Dashboard',
        'schedules'            => 'Jadwal Pelajaran',
        'room-schedules'       => 'Jadwal Ruangan',
        'rooms'                => 'Daftar Ruangan',
        'journals'             => 'Jurnal & Presensi',
        'attendance'           => 'Rekap Presensi Bulanan',
        'schedule-swaps'       => 'Laporan Tukar Jadwal',
        'leave-requests'       => 'Izin Siswa',
        'academic-calendar'    => 'Kalender Akademik',
        'ineffective-days'     => 'Kalender Akademik',
        'classes'              => 'Data Kelas',
        'users'                => 'Manajemen Pengguna',
        'exams'                => 'Materi & Ujian',
        'waka'                 => Auth::user()?->role_id === 5 ? 'Pengaturan Sistem' : 'Pengaturan Kurikulum',
        'settings'             => Auth::user()?->role_id === 5 ? 'Pengaturan Sistem' : 'Pengaturan Kurikulum',
        'system-configuration' => 'Pengaturan Email',
        'profile'              => 'Profil',
    ];
    $currentSegment = request()->segment(1) ?: 'dashboard';
    $breadcrumbTitle = $breadcrumbMap[$currentSegment] ?? ucwords(str_replace('-', ' ', $currentSegment));
    $pageTitle = (isset($title) && filled($title)) ? $title : $breadcrumbTitle;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#0F1014">
    <meta name="text-scale" content="scale">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle ? $pageTitle . ' - ' : '' }}{{ config('app.name', 'School Class Progress') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">

    <!-- Scripts & Self-hosted Fonts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

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

    <style nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        [x-cloak] {
            display: none !important;
        }
        body {
            font-family: var(--font-sans);
            letter-spacing: -0.011em;
        }
        .font-mono {
            font-family: var(--font-mono);
        }
    </style>
</head>
<body class="h-full bg-[var(--bg-page)] text-[var(--text-primary)] selection:bg-[#0073B1] selection:text-white transition-colors duration-150" 
      x-data="{ sidebarOpen: false }">
    <a href="#main-content" class="skip-link">Lewati ke konten utama</a>
    <div id="app-announcer" role="status" aria-live="polite" class="sr-only"></div>
    <div class="min-h-dvh flex flex-col lg:flex-row bg-[var(--bg-page)]">
        <!-- Sidebar & Navigation System -->
        @include('layouts.navigation')

        <!-- Main Workspace Area -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-64 xl:pl-72 transition-[padding] duration-200 bg-[var(--bg-page)]">
            <!-- Desktop Top Utility Bar -->
            @php
                $globalConfig = \App\Models\SystemConfiguration::first();
            @endphp
            <header class="hidden lg:flex h-14 bg-white dark:bg-[#19191C] border-b border-zinc-200 dark:border-[#2B2B30] items-center justify-between px-6 lg:px-8 shrink-0 sticky top-0 z-20 transition-colors duration-150">
                <!-- Left: Minimalist Breadcrumb Trail -->
                <div class="flex items-center gap-2 text-xs">
                    <picture class="shrink-0">
                        <source srcset="{{ asset('images/logo.webp') }}" type="image/webp">
                        <img src="{{ asset('images/logo.png') }}" alt="" width="20" height="20" class="w-5 h-5 object-contain shrink-0">
                    </picture>
                    <span class="text-zinc-700 dark:text-[#EDEDF0] font-sans font-semibold text-sm">{{ $breadcrumbTitle }}</span>
                </div>

                <!-- Right: Status Indicators & Utilities -->
                <div class="flex items-center gap-3">
                    @if($globalConfig)
                        <div class="flex items-center gap-2 text-xs">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700 font-sans font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                {{ $globalConfig->active_cycle }}
                            </span>
                            @if($globalConfig->is_schedule_locked)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700 font-sans font-medium">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    Jadwal Terkunci
                                </span>
                            @endif
                        </div>
                    @endif

                    <div class="h-4 w-px bg-zinc-200 dark:bg-[#2B2B30]"></div>

                    <!-- Date info -->
                    <div class="text-xs text-zinc-600 dark:text-[#B4B6C2] font-mono tabular-nums">
                        {{ now()->translatedFormat('d M Y') }}
                    </div>

                    <div class="h-4 w-px bg-zinc-200 dark:bg-[#2B2B30]"></div>

                    <!-- Theme Preference Dropdown -->
                    <x-theme-dropdown align="right" />
                </div>
            </header>

            <!-- Page Heading Slot -->
            @isset($header)
                <div class="bg-white dark:bg-[#19191C] border-b border-zinc-200 dark:border-[#2B2B30] px-4 sm:px-6 lg:px-8 py-5 transition-colors duration-150 min-w-0">
                    <div class="max-w-7xl mx-auto w-full min-w-0">
                        {{ $header }}
                    </div>
                </div>
            @endisset

            <!-- Page Content -->
            <main id="main-content" tabindex="-1" class="flex-1 min-w-0 p-4 sm:p-6 lg:p-8 bg-[var(--bg-page)] focus-visible:outline-none">
                <div class="max-w-7xl mx-auto w-full min-w-0">
                    {{ $slot }}
                </div>
            </main>

            <!-- Footer -->
            <footer class="border-t border-zinc-200 dark:border-[#2B2B30] py-4 px-4 sm:px-6 lg:px-8 pb-[max(1rem,env(safe-area-inset-bottom,0px))] text-center sm:text-left text-xs text-zinc-600 dark:text-[#9496A1] bg-white dark:bg-[#19191C] transition-colors duration-150">
                <div class="max-w-7xl mx-auto w-full min-w-0 flex flex-col sm:flex-row justify-between items-center gap-2">
                    <div>School Class Progress &copy; {{ date('Y') }} &bull; SMK Negeri 1 Bantul</div>
                    <div class="text-zinc-600 dark:text-[#9496A1]">Sistem Informasi Presensi &amp; Jurnal Kelas</div>
                </div>
            </footer>
        </div>
    </div>

    <x-confirm-modal />
</body>
</html>
