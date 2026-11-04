@php
    $pendingLeaveCount = (in_array(Auth::user()->role_id, [1, 3])) 
        ? \App\Models\LeaveRequest::where('status_approval', 'Menunggu')->count() 
        : 0;
    
    $navActive = 'bg-sky-50 text-[#0093dd] dark:bg-[#3A3A3F] dark:text-white font-medium rounded-lg border border-sky-100 dark:border-transparent';
    $navInactive = 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100 dark:text-[#97979B] dark:hover:text-[#EDEDF0] dark:hover:bg-[#26262A] rounded-lg border border-transparent';
    $iconActive = 'text-[#0093dd] dark:text-white';
    $iconInactive = 'text-zinc-400 group-hover:text-zinc-600 dark:text-[#97979B] dark:group-hover:text-[#EDEDF0]';
@endphp

<!-- Navigation Groups -->
<div class="space-y-6">

    <!-- ================= GROUP 1: MAIN (Semua Role) ================= -->
    <div>
        <div class="px-3 mb-2 text-[10px] font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider font-mono">
            MAIN
        </div>
        <div class="space-y-1">
            <a 
                href="{{ route('dashboard') }}" 
                class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('dashboard') ? $navActive : $navInactive }}"
            >
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('dashboard') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
                <span>Dashboard</span>
            </a>
        </div>
    </div>

    <!-- ================= 2. KHUSUS WAKA KURIKULUM (Role 1: Admin & Full Control) ================= -->
    @if(Auth::user()->role_id === 1)
        <!-- Sub-group 1: AKADEMIK -->
        <div>
            <div class="px-3 mb-2 text-[10px] font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider font-mono">
                AKADEMIK
            </div>
            <div class="space-y-1">
                <!-- Jadwal Pelajaran -->
                <a 
                    href="{{ route('schedules.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('schedules.*') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('schedules.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    <span>Jadwal Pelajaran</span>
                </a>

                <!-- Jadwal Ruangan -->
                <a 
                    href="{{ route('room-schedules.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('room-schedules.*') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('room-schedules.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                    <span>Jadwal Ruangan</span>
                </a>

                <!-- Monitoring Jurnal & Presensi -->
                <a 
                    href="{{ route('journals.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('journals.*') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('journals.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                    </svg>
                    <span>Jurnal &amp; Presensi</span>
                </a>
            </div>
        </div>

        <!-- Sub-group 2: KESISWAAN -->
        <div>
            <div class="px-3 mb-2 text-[10px] font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider font-mono">
                KESISWAAN
            </div>
            <div class="space-y-1">
                <!-- Monitoring Izin Siswa -->
                <a 
                    href="{{ route('leave-requests.index') }}" 
                    class="flex items-center justify-between px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('leave-requests.*') ? $navActive : $navInactive }}"
                >
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('leave-requests.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                        <span>Izin Siswa</span>
                    </div>
                    @if($pendingLeaveCount > 0)
                        <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold font-mono rounded-md {{ request()->routeIs('leave-requests.*') ? 'bg-white text-zinc-950' : 'bg-amber-400/20 text-amber-300 border border-amber-400/30' }}">
                            {{ $pendingLeaveCount }}
                        </span>
                    @endif
                </a>

                <!-- Kalender Akademik -->
                <a 
                    href="{{ route('academic-calendar.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('academic-calendar.*') || request()->routeIs('ineffective-days.*') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('academic-calendar.*') || request()->routeIs('ineffective-days.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    <span>Kalender Akademik</span>
                </a>

                <!-- Bank Soal & Materi Ujian -->
                <a 
                    href="{{ route('exams.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('exams.*') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('exams.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                    <span>Materi &amp; Ujian</span>
                </a>
            </div>
        </div>

        <!-- Sub-group 3: PENGATURAN -->
        <div>
            <div class="px-3 mb-2 text-[10px] font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider font-mono">
                PENGATURAN
            </div>
            <div class="space-y-1">
                <!-- Data Kelas -->
                <a 
                    href="{{ route('classes.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('classes.*') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('classes.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                    </svg>
                    <span>Data Kelas</span>
                </a>

                <!-- Manajemen Pengguna -->
                <a 
                    href="{{ route('users.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('users.*') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('users.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    <span>Manajemen Pengguna</span>
                </a>

                <!-- Pengaturan Sistem -->
                <a 
                    href="{{ route('waka.settings') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('waka.*') || (request()->routeIs('system-configuration.*') && !request()->routeIs('system-configuration.email*')) ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('waka.*') || (request()->routeIs('system-configuration.*') && !request()->routeIs('system-configuration.email*')) ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                    </svg>
                    <span>Pengaturan Kurikulum</span>
                </a>

                <!-- Pengaturan Email -->
                <a 
                    href="{{ route('system-configuration.email') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('system-configuration.email*') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('system-configuration.email*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                    </svg>
                    <span>Pengaturan Email</span>
                </a>
            </div>
        </div>
    @endif

    <!-- ================= 3. KHUSUS GURU PENGAJAR (Role 2) ================= -->
    @if(Auth::user()->role_id === 2)
        <!-- Sub-group 1: AKADEMIK -->
        <div>
            <div class="px-3 mb-2 text-[10px] font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider font-mono">
                AKADEMIK
            </div>
            <div class="space-y-1">
                <!-- Isi Jurnal & Presensi KBM (Primary CTA) -->
                <a 
                    href="{{ route('journals.create') }}" 
                    class="flex items-center justify-between px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('journals.create') ? $navActive : $navInactive }}"
                >
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('journals.create') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                        </svg>
                        <span>Isi Jurnal KBM</span>
                    </div>
                    <span class="inline-flex items-center px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded font-mono {{ request()->routeIs('journals.create') ? 'bg-white text-zinc-950' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' }}">
                        + Input
                    </span>
                </a>

                <!-- Riwayat Jurnal -->
                <a 
                    href="{{ route('journals.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('journals.index') || request()->routeIs('journals.show') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('journals.index') || request()->routeIs('journals.show') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                    </svg>
                    <span>Riwayat Jurnal</span>
                </a>
            </div>
        </div>

        <!-- Sub-group 2: JADWAL -->
        <div>
            <div class="px-3 mb-2 text-[10px] font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider font-mono">
                JADWAL
            </div>
            <div class="space-y-1">
                <!-- Jadwal Mengajar -->
                <a 
                    href="{{ route('schedules.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('schedules.*') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('schedules.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    <span>Jadwal Mengajar</span>
                </a>

                <!-- Jadwal Ruangan -->
                <a 
                    href="{{ route('room-schedules.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('room-schedules.*') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('room-schedules.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                    <span>Jadwal Ruangan</span>
                </a>

                <!-- Bank Soal & Materi Ujian -->
                <a 
                    href="{{ route('exams.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('exams.*') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('exams.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                    <span>Materi &amp; Ujian</span>
                </a>
            </div>
        </div>
    @endif

    <!-- ================= 4. KHUSUS TIM KESISWAAN / BK (Role 3) ================= -->
    @if(Auth::user()->role_id === 3)
        <!-- Sub-group 1: KESISWAAN -->
        <div>
            <div class="px-3 mb-2 text-[10px] font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider font-mono">
                KESISWAAN
            </div>
            <div class="space-y-1">
                <!-- Verifikasi Izin Siswa (Primary Duty) -->
                <a 
                    href="{{ route('leave-requests.index') }}" 
                    class="flex items-center justify-between px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('leave-requests.*') ? $navActive : $navInactive }}"
                >
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('leave-requests.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                        <span>Verifikasi Izin</span>
                    </div>
                    @if($pendingLeaveCount > 0)
                        <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold font-mono rounded-md {{ request()->routeIs('leave-requests.*') ? 'bg-white text-zinc-950' : 'bg-amber-400/20 text-amber-300 border border-amber-400/30' }}">
                            {{ $pendingLeaveCount }}
                        </span>
                    @endif
                </a>

                <!-- Monitoring Presensi Siswa / KBM -->
                <a 
                    href="{{ route('journals.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('journals.*') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('journals.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                    </svg>
                    <span>Presensi KBM</span>
                </a>
            </div>
        </div>

        <!-- Sub-group 2: AKADEMIK -->
        <div>
            <div class="px-3 mb-2 text-[10px] font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider font-mono">
                AKADEMIK
            </div>
            <div class="space-y-1">
                <!-- Data Kelas & Siswa -->
                <a 
                    href="{{ route('classes.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('classes.*') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('classes.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                    </svg>
                    <span>Data Kelas</span>
                </a>

                <!-- Jadwal Ruang & Laboratorium -->
                <a 
                    href="{{ route('room-schedules.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('room-schedules.*') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('room-schedules.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                    <span>Jadwal Ruangan</span>
                </a>

                <!-- Kalender Akademik -->
                <a 
                    href="{{ route('academic-calendar.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('academic-calendar.*') || request()->routeIs('ineffective-days.*') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('academic-calendar.*') || request()->routeIs('ineffective-days.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    <span>Kalender Akademik</span>
                </a>

                <!-- Jadwal Asesmen & Ujian -->
                <a 
                    href="{{ route('exams.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('exams.*') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('exams.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                    <span>Materi &amp; Ujian</span>
                </a>

                <!-- Daftar Akun Siswa -->
                <a 
                    href="{{ route('users.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('users.*') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('users.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    <span>Manajemen Pengguna</span>
                </a>
            </div>
        </div>
    @endif

    <!-- ================= 5. KHUSUS SISWA & ORANG TUA (Role 4) ================= -->
    @if(Auth::user()->role_id === 4)
        <!-- Sub-group 1: PORTAL SISWA -->
        <div>
            <div class="px-3 mb-2 text-[10px] font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider font-mono">
                PORTAL SISWA
            </div>
            <div class="space-y-1">
                <!-- Ajukan Izin (Primary Action) -->
                <a 
                    href="{{ route('leave-requests.create') }}" 
                    class="flex items-center justify-between px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('leave-requests.create') ? $navActive : $navInactive }}"
                >
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('leave-requests.create') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Ajukan Izin</span>
                    </div>
                    <span class="inline-flex items-center px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider rounded font-mono {{ request()->routeIs('leave-requests.create') ? 'bg-white text-zinc-950' : 'bg-[#0093dd]/20 text-[#0093dd] border border-[#0093dd]/30' }}">
                        + Form
                    </span>
                </a>

                <!-- Riwayat & Status Izin -->
                <a 
                    href="{{ route('leave-requests.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('leave-requests.index') || request()->routeIs('leave-requests.show') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('leave-requests.index') || request()->routeIs('leave-requests.show') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                    </svg>
                    <span>Riwayat Izin</span>
                </a>

                <!-- Rekap Kehadiran -->
                <a 
                    href="{{ route('student.attendance.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('student.attendance.*') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('student.attendance.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                    </svg>
                    <span>Rekap Kehadiran</span>
                </a>
            </div>
        </div>

        <!-- Sub-group 2: AKADEMIK -->
        <div>
            <div class="px-3 mb-2 text-[10px] font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider font-mono">
                AKADEMIK
            </div>
            <div class="space-y-1">
                <!-- Materi & Ujian -->
                <a 
                    href="{{ route('exams.index') }}" 
                    class="flex items-center gap-3 px-3 py-2 text-xs transition-colors duration-150 {{ request()->routeIs('exams.*') ? $navActive : $navInactive }}"
                >
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('exams.*') ? $iconActive : $iconInactive }}" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                    <span>Materi &amp; Ujian</span>
                </a>
            </div>
        </div>
    @endif

</div>
