@props(['title' => null])
@php
    $breadcrumbMap = [
        'dashboard'            => 'Dashboard',
        'schedules'            => 'Jadwal Pelajaran',
        'room-schedules'       => 'Jadwal Ruangan',
        'journals'             => 'Jurnal & Presensi',
        'schedule-swaps'       => 'Laporan Tukar Jadwal',
        'leave-requests'       => 'Izin Siswa',
        'academic-calendar'    => 'Kalender Akademik',
        'ineffective-days'     => 'Kalender Akademik',
        'classes'              => 'Data Kelas',
        'users'                => 'Manajemen Pengguna',
        'exams'                => 'Materi & Ujian',
        'waka'                 => 'Pengaturan Kurikulum',
        'settings'             => 'Pengaturan Kurikulum',
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
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle ? $pageTitle . ' - ' : '' }}{{ config('app.name', 'School Class Progress') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">

    <!-- Typography: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">

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
        [x-cloak] {
            display: none !important;
        }
        :root {
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --font-mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, monospace;
        }
        body {
            font-family: var(--font-sans);
            letter-spacing: -0.011em;
        }
        .font-mono {
            font-family: var(--font-mono);
        }
        /* Dark Mode Resilience Rules */
        html.dark div.bg-white:not([class*="dark:bg-"]),
        html.dark section.bg-white:not([class*="dark:bg-"]),
        html.dark a.bg-white:not([class*="dark:bg-"]),
        html.dark select.bg-white,
        html.dark input.bg-white,
        html.dark textarea.bg-white {
            background-color: var(--surface-card) !important;
            border-color: var(--border-hairline) !important;
            color: var(--text-primary) !important;
        }
        html.dark input[type="text"],
        html.dark input[type="search"],
        html.dark input[type="date"],
        html.dark input[type="time"],
        html.dark input[type="month"],
        html.dark select,
        html.dark textarea {
            background-color: var(--bg-page) !important;
            border-color: var(--border-hairline) !important;
            color: var(--text-primary) !important;
        }
    </style>
</head>
<body class="h-full bg-[var(--bg-page)] text-[var(--text-primary)] selection:bg-[#0093dd] selection:text-white transition-colors duration-150" 
      x-data="{ sidebarOpen: false }">
    <div class="min-h-screen flex flex-col lg:flex-row bg-[var(--bg-page)]">
        <!-- Sidebar & Navigation System -->
        @include('layouts.navigation')

        <!-- Main Workspace Area -->
        <div class="flex-1 flex flex-col min-w-0 lg:pl-64 xl:pl-72 transition-all duration-200 bg-[var(--bg-page)]">
            <!-- Desktop Top Utility Bar -->
            @php
                $globalConfig = \App\Models\SystemConfiguration::first();
            @endphp
            <header class="hidden lg:flex h-14 bg-white dark:bg-[#19191C] border-b border-zinc-200 dark:border-[#2B2B30] items-center justify-between px-6 lg:px-8 shrink-0 sticky top-0 z-20 transition-colors duration-150">
                <!-- Left: Minimalist Breadcrumb Trail -->
                <div class="flex items-center gap-2 text-xs">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-5 h-5 object-contain shrink-0">
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
                    <div class="text-[11px] text-zinc-500 dark:text-[#97979B] font-mono">
                        {{ now()->translatedFormat('d M Y') }}
                    </div>

                    <div class="h-4 w-px bg-zinc-200 dark:bg-[#2B2B30]"></div>

                    <!-- Theme Preference Dropdown -->
                    <x-theme-dropdown align="right" />
                </div>
            </header>

            <!-- Page Heading Slot -->
            @isset($header)
                <div class="bg-white dark:bg-[#19191C] border-b border-zinc-200 dark:border-[#2B2B30] px-6 lg:px-8 py-5 transition-colors duration-150 min-w-0">
                    <div class="max-w-7xl mx-auto w-full min-w-0">
                        {{ $header }}
                    </div>
                </div>
            @endisset

            <!-- Page Content -->
            <main class="flex-1 min-w-0 p-4 sm:p-6 lg:p-8 bg-[var(--bg-page)]">
                <div class="max-w-7xl mx-auto w-full min-w-0">
                    {{ $slot }}
                </div>
            </main>

            <!-- Footer -->
            <footer class="border-t border-zinc-200 dark:border-[#2B2B30] py-4 px-6 lg:px-8 text-center sm:text-left text-xs text-zinc-500 dark:text-[#5B5B61] flex flex-col sm:flex-row justify-between items-center gap-2 bg-white dark:bg-[#19191C] transition-colors duration-150">
                <div>School Class Progress &copy; {{ date('Y') }}</div>
                <div class="text-zinc-400 dark:text-[#5B5B61]">Sistem Informasi Presensi &amp; Jurnal Kelas</div>
            </footer>
        </div>
    </div>
</body>
</html>
