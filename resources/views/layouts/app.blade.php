<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-zinc-50 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SCP - School Class Progress') }}</title>

    <!-- Swiss Minimalist Typography: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

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
    </style>
</head>
<body class="h-full bg-zinc-50/70 text-zinc-900 selection:bg-zinc-900 selection:text-white" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- Modern Swiss Sidebar & Navigation System -->
        @include('layouts.navigation')

        <!-- Main Workspace Area -->
        <div class="flex-1 flex flex-col min-w-0 md:pl-64 xl:pl-72 transition-all duration-200">
            <!-- Desktop Top Utility Bar -->
            @php
                $globalConfig = \App\Models\SystemConfiguration::first();
            @endphp
            <header class="hidden md:flex h-14 bg-white border-b border-zinc-200/80 items-center justify-between px-6 lg:px-8 shrink-0 sticky top-0 z-20">
                <!-- Left: Breadcrumb / System Context -->
                @php
                    $breadcrumbMap = [
                        'dashboard' => 'Dashboard',
                        'schedules' => 'Jadwal Pelajaran KBM',
                        'room-schedules' => 'Jadwal Ruang & Laboratorium',
                        'journals' => 'Jurnal Mengajar & Presensi',
                        'leave-requests' => 'Perizinan Siswa',
                        'academic-calendar' => 'Kalender Akademik',
                        'ineffective-days' => 'Hari Efektif KBM',
                        'classes' => 'Data Rombel & Kelas',
                        'users' => 'Manajemen Pengguna',
                        'exams' => 'Bank Soal & Materi Ujian',
                        'waka' => 'Pengaturan Kurikulum & Sistem',
                        'system-configuration' => 'Pengaturan Sistem',
                        'profile' => 'Profil Saya',
                    ];
                    $currentSegment = request()->segment(1) ?: 'dashboard';
                    $breadcrumbTitle = $breadcrumbMap[$currentSegment] ?? ucwords(str_replace('-', ' ', $currentSegment));
                @endphp
                <div class="flex items-center gap-2.5 text-xs">
                    <span class="font-bold text-zinc-900 tracking-tight">SCP</span>
                    <span class="text-zinc-300">/</span>
                    <span class="text-zinc-600 font-semibold">{{ $breadcrumbTitle }}</span>
                </div>

                <!-- Right: Status Indicators & Utilities -->
                <div class="flex items-center gap-3">
                    @if($globalConfig)
                        <div class="flex items-center gap-2 font-mono text-[11px]">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-zinc-100 text-zinc-800 border border-zinc-200 font-semibold uppercase tracking-wider">
                                <span class="w-1.5 h-1.5 rounded-full bg-zinc-600"></span>
                                {{ $globalConfig->active_cycle }}
                            </span>
                            @if($globalConfig->is_schedule_locked)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-200 font-semibold uppercase tracking-wider">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                    Jadwal Terkunci
                                </span>
                            @endif
                        </div>
                    @endif

                    <div class="h-4 w-px bg-zinc-200"></div>

                    <!-- Date info -->
                    <div class="text-[11px] text-zinc-400 font-mono">
                        {{ now()->translatedFormat('d M Y') }}
                    </div>
                </div>
            </header>

            <!-- Page Heading Slot (Swiss Typographic Header) -->
            @isset($header)
                <div class="bg-white border-b border-zinc-200/80 px-6 lg:px-8 py-5">
                    <div class="max-w-7xl mx-auto">
                        {{ $header }}
                    </div>
                </div>
            @endisset

            <!-- Page Content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <div class="max-w-7xl mx-auto">
                    {{ $slot }}
                </div>
            </main>

            <!-- Subtle Swiss Footer -->
            <footer class="border-t border-zinc-200/60 py-4 px-6 lg:px-8 text-center sm:text-left text-[11px] text-zinc-400 font-mono flex flex-col sm:flex-row justify-between items-center gap-2">
                <div>School Class Progress &copy; {{ date('Y') }} &bull; Swiss Minimalist Architecture</div>
                <div class="text-zinc-400">SMP/SMA Kurikulum Terintegrasi</div>
            </footer>
        </div>
    </div>
</body>
</html>
