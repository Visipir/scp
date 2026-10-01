<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 antialiased">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SCP - School Class Progress') }}</title>

    <!-- Swiss Minimalist Typography: Inter & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Theme Preference Initialization (Default: Light Theme) -->
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
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
    </style>
</head>
<body class="h-full bg-zinc-50/70 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 selection:bg-zinc-900 dark:selection:bg-zinc-100 selection:text-white dark:selection:text-zinc-950 transition-colors duration-150" 
      x-data="{ 
          sidebarOpen: false,
          darkMode: document.documentElement.classList.contains('dark'),
          toggleDarkMode() {
              this.darkMode = !this.darkMode;
              if (this.darkMode) {
                  document.documentElement.classList.add('dark');
                  localStorage.setItem('theme', 'dark');
              } else {
                  document.documentElement.classList.remove('dark');
                  localStorage.setItem('theme', 'light');
              }
          }
      }">
    <div class="min-h-screen flex flex-col md:flex-row">
        <!-- Modern Swiss Sidebar & Navigation System -->
        @include('layouts.navigation')

        <!-- Main Workspace Area -->
        <div class="flex-1 flex flex-col min-w-0 md:pl-64 xl:pl-72 transition-all duration-200">
            <!-- Desktop Top Utility Bar -->
            @php
                $globalConfig = \App\Models\SystemConfiguration::first();
            @endphp
            <header class="hidden md:flex h-14 bg-white dark:bg-zinc-900 border-b border-zinc-200/80 dark:border-zinc-800 items-center justify-between px-6 lg:px-8 shrink-0 sticky top-0 z-20 transition-colors duration-150">
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
                    <span class="font-bold text-zinc-900 dark:text-white tracking-tight">SCP</span>
                    <span class="text-zinc-300 dark:text-zinc-700">/</span>
                    <span class="text-zinc-600 dark:text-zinc-400 font-semibold">{{ $breadcrumbTitle }}</span>
                </div>

                <!-- Right: Status Indicators & Utilities -->
                <div class="flex items-center gap-3">
                    @if($globalConfig)
                        <div class="flex items-center gap-2 font-mono text-[11px]">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700 font-semibold uppercase tracking-wider">
                                <span class="w-1.5 h-1.5 rounded-full bg-zinc-600 dark:bg-zinc-400"></span>
                                {{ $globalConfig->active_cycle }}
                            </span>
                            @if($globalConfig->is_schedule_locked)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-900 font-semibold uppercase tracking-wider">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-600 dark:bg-rose-400"></span>
                                    Jadwal Terkunci
                                </span>
                            @endif
                        </div>
                    @endif

                    <div class="h-4 w-px bg-zinc-200 dark:bg-zinc-800"></div>

                    <!-- Date info -->
                    <div class="text-[11px] text-zinc-400 dark:text-zinc-500 font-mono">
                        {{ now()->translatedFormat('d M Y') }}
                    </div>

                    <div class="h-4 w-px bg-zinc-200 dark:bg-zinc-800"></div>

                    <!-- Dark Mode Toggle Button (Swiss Minimalist) -->
                    <button 
                        type="button" 
                        @click="toggleDarkMode()" 
                        class="p-1.5 text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-800 transition cursor-pointer"
                        :title="darkMode ? 'Beralih ke Mode Terang (Light Mode)' : 'Beralih ke Mode Gelap (Dark Mode)'"
                        aria-label="Toggle Dark Mode"
                    >
                        <!-- Sun icon when dark -->
                        <svg x-show="darkMode" x-cloak class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                        </svg>
                        <!-- Moon icon when light -->
                        <svg x-show="!darkMode" class="w-4 h-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                        </svg>
                    </button>
                </div>
            </header>

            <!-- Page Heading Slot (Swiss Typographic Header) -->
            @isset($header)
                <div class="bg-white dark:bg-zinc-900 border-b border-zinc-200/80 dark:border-zinc-800 px-6 lg:px-8 py-5 transition-colors duration-150">
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
            <footer class="border-t border-zinc-200/60 dark:border-zinc-800/80 py-4 px-6 lg:px-8 text-center sm:text-left text-[11px] text-zinc-400 dark:text-zinc-500 font-mono flex flex-col sm:flex-row justify-between items-center gap-2 transition-colors duration-150">
                <div>School Class Progress &copy; {{ date('Y') }} &bull; Swiss Minimalist Architecture</div>
                <div class="text-zinc-400 dark:text-zinc-500">SMP/SMA Kurikulum Terintegrasi</div>
            </footer>
        </div>
    </div>
</body>
</html>
