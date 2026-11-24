<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Dashboard" subtitle="Ringkasan aktivitas akademik dan informasi presensi sekolah" />
    </x-slot>

    <div class="space-y-6 dashboard-touch-scope">
        @if(in_array((int) $user->role_id, [1, 5], true))
            @php
                $maxVal = max(1, max(
                    $attendanceStats['Hadir'] ?? 0,
                    $attendanceStats['Izin'] ?? 0,
                    $attendanceStats['Sakit'] ?? 0,
                    $attendanceStats['Alpa'] ?? 0,
                    $attendanceStats['Terlambat'] ?? 0
                ));
            @endphp
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                {{-- Statistik Presensi Harian --}}
                <section aria-labelledby="stat-today-heading" class="cv-auto bg-white dark:bg-[#1D1D22] border border-zinc-200 dark:border-[#2E3140] rounded-xl p-5 shadow-xs">
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <h2 id="stat-today-heading" class="font-bold text-sm text-zinc-900 dark:text-[#F1F1F4]">Statistik Presensi Hari Ini</h2>
                            <span class="text-xs font-mono text-zinc-600 dark:text-[#B4B6C2] tabular-nums">
                                Total: {{ number_format(array_sum($attendanceStats ?? [])) }} sesi
                            </span>
                        </div>

                        {{-- Single Authentic Bar Visualization --}}
                        <div class="mt-6 pt-2 pb-1 relative">
                            <div
                                role="img"
                                aria-label="Statistik presensi hari ini: {{ $attendanceStats['Hadir'] ?? 0 }} Hadir, {{ $attendanceStats['Izin'] ?? 0 }} Izin, {{ $attendanceStats['Sakit'] ?? 0 }} Sakit, {{ $attendanceStats['Alpa'] ?? 0 }} Alpa, {{ $attendanceStats['Terlambat'] ?? 0 }} Terlambat"
                                class="grid grid-cols-5 gap-3 sm:gap-5 items-end h-36 px-2 relative z-10 border-b border-zinc-200 dark:border-[#2E3140] pb-2"
                            >
                                @php
                                    $categories = [
                                        ['key' => 'Hadir',     'label' => 'HADIR', 'val' => $attendanceStats['Hadir'] ?? 0,     'bar' => 'bg-emerald-600 dark:bg-emerald-500'],
                                        ['key' => 'Izin',      'label' => 'IZIN',  'val' => $attendanceStats['Izin'] ?? 0,      'bar' => 'bg-[#0073B1] dark:bg-sky-500'],
                                        ['key' => 'Sakit',     'label' => 'SAKIT', 'val' => $attendanceStats['Sakit'] ?? 0,     'bar' => 'bg-amber-600 dark:bg-amber-500'],
                                        ['key' => 'Alpa',      'label' => 'ALPA',  'val' => $attendanceStats['Alpa'] ?? 0,      'bar' => 'bg-rose-600 dark:bg-rose-500'],
                                        ['key' => 'Terlambat', 'label' => 'TELAT', 'val' => $attendanceStats['Terlambat'] ?? 0, 'bar' => 'bg-purple-600 dark:bg-purple-500'],
                                    ];
                                @endphp

                                @foreach($categories as $cat)
                                    @php
                                        $hPercent = ($cat['val'] > 0) ? min(100, max(10, round(($cat['val'] / $maxVal) * 100))) : 4;
                                    @endphp
                                    <div aria-hidden="true" class="flex flex-col items-center h-full justify-end group">
                                        <div class="text-xs font-mono font-bold text-zinc-900 dark:text-[#F1F1F4] mb-1.5 tabular-nums">
                                            {{ $cat['val'] }}
                                        </div>
                                        <div class="flex items-end justify-center h-24 w-full max-w-[32px]">
                                            <div
                                                class="w-4 sm:w-5 {{ $cat['bar'] }} rounded-t-sm transition-opacity duration-150 group-hover:opacity-90"
                                                style="height: {{ $hPercent }}%;"
                                            ></div>
                                        </div>
                                        <div class="text-[11px] font-mono font-semibold text-zinc-600 dark:text-[#B4B6C2] mt-2">
                                            {{ $cat['label'] }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Ringkasan Ketidakhadiran Hari Ini (Native Disclosure agar dapat dicari Find-in-page) --}}
                    @if(isset($attendanceOverview['today']['absentees']) && $attendanceOverview['today']['absentees']->isNotEmpty())
                        <details class="group mt-4 pt-3 border-t border-zinc-200 dark:border-[#2E3140]">
                            <summary class="flex items-center justify-between gap-2 flex-wrap cursor-pointer list-none min-h-[36px] py-1 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)] rounded">
                                <span class="text-xs text-zinc-600 dark:text-[#B4B6C2] tabular-nums">
                                    <span class="font-semibold text-zinc-900 dark:text-[#F1F1F4]">Hari Ini:</span>
                                    <span class="text-[#0073B1] dark:text-sky-400 font-semibold">{{ $attendanceStats['Izin'] ?? 0 }} Izin</span>,
                                    <span class="text-amber-700 dark:text-amber-300 font-semibold">{{ $attendanceStats['Sakit'] ?? 0 }} Sakit</span>,
                                    <span class="text-rose-700 dark:text-rose-400 font-semibold">{{ $attendanceStats['Alpa'] ?? 0 }} Alpa</span>
                                </span>
                                <span class="inline-flex items-center gap-1 text-xs text-[#0073B1] dark:text-sky-400 group-hover:underline font-semibold">
                                    <span class="group-open:hidden">Lihat Siswa ({{ $attendanceOverview['today']['absentees']->count() }})</span>
                                    <span class="hidden group-open:inline">Tutup Daftar</span>
                                    <svg class="w-3.5 h-3.5 transition-transform group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                                </span>
                            </summary>

                            <div x-data="{ currentPage: 1, pageSize: 15, totalMatches: {{ $attendanceOverview['today']['absentees']->count() }}, get totalPages() { return Math.max(1, Math.ceil(this.totalMatches / this.pageSize)); } }" class="mt-3 space-y-2">
                                <ul role="list" class="border border-zinc-200 dark:border-[#2E3140] rounded-lg overflow-hidden divide-y divide-zinc-200 dark:divide-[#2E3140]">
                                    @foreach($attendanceOverview['today']['absentees'] as $idx => $absent)
                                        <li x-show="{{ $idx }} >= (currentPage - 1) * pageSize && {{ $idx }} < currentPage * pageSize" class="p-2.5 text-xs flex items-center justify-between gap-2 hover:bg-zinc-50 dark:hover:bg-[#262832] transition-colors">
                                            <div class="min-w-0 pr-2">
                                                <div class="font-bold text-zinc-900 dark:text-[#F1F1F4] flex items-center gap-2 flex-wrap">
                                                    <span class="break-words">{{ $absent->student?->fullname ?? 'Siswa' }}</span>
                                                    <span class="text-[11px] font-mono px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-[#232327] text-zinc-700 dark:text-[#B4B6C2] shrink-0">
                                                        {{ $absent->student?->schoolClass?->class_name ?? '-' }}
                                                    </span>
                                                </div>
                                                <div class="text-xs text-zinc-600 dark:text-[#9496A1] mt-0.5 break-words">
                                                    {{ $absent->notes ?: 'Tidak ada keterangan tambahan' }}
                                                </div>
                                            </div>
                                            <div class="shrink-0">
                                                <x-status-badge :status="$absent->status" />
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                                <x-client-pagination total-expr="totalMatches" />
                            </div>
                        </details>
                    @else
                        <div class="mt-4 pt-3 border-t border-zinc-200 dark:border-[#2E3140] flex items-center justify-between gap-2 flex-wrap">
                            <div class="text-xs text-zinc-600 dark:text-[#B4B6C2] tabular-nums">
                                <span class="font-semibold text-zinc-900 dark:text-[#F1F1F4]">Hari Ini:</span>
                                <span class="text-[#0073B1] dark:text-sky-400 font-semibold">{{ $attendanceStats['Izin'] ?? 0 }} Izin</span>,
                                <span class="text-amber-700 dark:text-amber-300 font-semibold">{{ $attendanceStats['Sakit'] ?? 0 }} Sakit</span>,
                                <span class="text-rose-700 dark:text-rose-400 font-semibold">{{ $attendanceStats['Alpa'] ?? 0 }} Alpa</span>
                            </div>
                            <span class="text-xs text-zinc-600 dark:text-[#9496A1] font-mono">Nihil ketidakhadiran</span>
                        </div>
                    @endif
                </section>

                {{-- Widget Ringkasan Rekap Presensi Bulanan (Sejajar dengan Statistik Hari Ini agar tinggi seimbang) --}}
                <section aria-labelledby="monthly-recap-heading" class="cv-auto bg-white dark:bg-[#1D1D22] border border-zinc-200 dark:border-[#2E3140] rounded-xl p-5 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-200 dark:border-[#2E3140] pb-3.5">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 id="monthly-recap-heading" class="font-bold text-sm text-zinc-900 dark:text-[#F1F1F4]">Rekapitulasi Presensi Bulanan</h2>
                                <span class="px-2 py-0.5 rounded text-xs font-mono font-semibold bg-sky-50 dark:bg-[#16222F] border border-sky-200 dark:border-sky-700/60 text-[#0073B1] dark:text-sky-300">
                                    {{ $attendanceOverview['month']['name_formatted'] ?? '' }}
                                </span>
                            </div>
                            <p class="text-xs text-zinc-600 dark:text-[#B4B6C2] mt-1">
                                Akumulasi kehadiran siswa per bulan akademik.
                            </p>
                        </div>

                        <a href="{{ route('attendance.recap') }}" class="inline-flex items-center justify-center gap-1.5 min-h-[36px] px-3.5 py-1.5 bg-[#0073B1] hover:bg-[#005D8F] text-white text-xs font-semibold rounded-lg transition shadow-xs shrink-0">
                            <span>Buka Halaman Rekapitulasi Presensi</span>
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-4">
                        <div class="bg-zinc-50 dark:bg-[#15161D] border border-zinc-200 dark:border-[#2E3140] rounded-lg p-3">
                            <div class="text-xs font-mono text-zinc-600 dark:text-[#B4B6C2] uppercase">Hadir</div>
                            <div class="mt-1.5 flex items-baseline justify-between gap-1 tabular-nums">
                                <span class="text-lg sm:text-xl font-bold font-mono text-emerald-700 dark:text-emerald-400">
                                    {{ number_format($attendanceOverview['month']['counts']['Hadir'] ?? 0) }}
                                </span>
                                <span class="text-xs font-mono text-emerald-700 dark:text-emerald-400">sesi</span>
                            </div>
                        </div>

                        <div class="bg-zinc-50 dark:bg-[#15161D] border border-zinc-200 dark:border-[#2E3140] rounded-lg p-3">
                            <div class="text-xs font-mono text-zinc-600 dark:text-[#B4B6C2] uppercase">Izin</div>
                            <div class="mt-1.5 flex items-baseline justify-between gap-1 tabular-nums">
                                <span class="text-lg sm:text-xl font-bold font-mono text-[#0073B1] dark:text-sky-400">
                                    {{ number_format($attendanceOverview['month']['counts']['Izin'] ?? 0) }}
                                </span>
                                <span class="text-xs font-mono text-[#0073B1] dark:text-sky-400">sesi</span>
                            </div>
                        </div>

                        <div class="bg-zinc-50 dark:bg-[#15161D] border border-zinc-200 dark:border-[#2E3140] rounded-lg p-3">
                            <div class="text-xs font-mono text-zinc-600 dark:text-[#B4B6C2] uppercase">Sakit</div>
                            <div class="mt-1.5 flex items-baseline justify-between gap-1 tabular-nums">
                                <span class="text-lg sm:text-xl font-bold font-mono text-amber-700 dark:text-amber-400">
                                    {{ number_format($attendanceOverview['month']['counts']['Sakit'] ?? 0) }}
                                </span>
                                <span class="text-xs font-mono text-amber-700 dark:text-amber-400">sesi</span>
                            </div>
                        </div>

                        <div class="bg-zinc-50 dark:bg-[#15161D] border border-zinc-200 dark:border-[#2E3140] rounded-lg p-3">
                            <div class="text-xs font-mono text-zinc-600 dark:text-[#B4B6C2] uppercase">Alpa</div>
                            <div class="mt-1.5 flex items-baseline justify-between gap-1 tabular-nums">
                                <span class="text-lg sm:text-xl font-bold font-mono text-rose-700 dark:text-rose-400">
                                    {{ number_format($attendanceOverview['month']['counts']['Alpa'] ?? 0) }}
                                </span>
                                <span class="text-xs font-mono text-rose-700 dark:text-rose-400">sesi</span>
                            </div>
                        </div>

                        <div class="bg-zinc-50 dark:bg-[#15161D] border border-zinc-200 dark:border-[#2E3140] rounded-lg p-3 col-span-2">
                            <div class="text-xs font-mono text-zinc-600 dark:text-[#B4B6C2] uppercase">% Kehadiran</div>
                            <div class="mt-1.5 flex items-baseline justify-between gap-2 tabular-nums">
                                <span class="text-lg sm:text-xl font-bold font-mono text-zinc-900 dark:text-[#F1F1F4]">
                                    {{ $attendanceOverview['month']['attendance_rate'] ?? 0 }}%
                                </span>
                                <span class="text-xs font-mono text-zinc-600 dark:text-[#B4B6C2]">
                                    {{ number_format($attendanceOverview['month']['total_logged'] ?? 0) }} total sesi
                                </span>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            {{-- Guru Belum Mengisi Jurnal (Lebar Penuh agar daftar panjang tidak menyisakan ruang kosong di sebelah) --}}
            <section aria-labelledby="unfilled-journals-heading" x-data="{ currentPage: 1, pageSize: 15, totalMatches: {{ isset($unfilledSchedules) ? $unfilledSchedules->count() : 0 }}, get totalPages() { return Math.max(1, Math.ceil(this.totalMatches / this.pageSize)); } }" class="cv-auto bg-white dark:bg-[#1D1D22] border border-zinc-200 dark:border-[#2E3140] rounded-xl p-5 shadow-xs">
                <div class="flex flex-wrap justify-between items-center gap-2 pb-3 border-b border-zinc-200 dark:border-[#2E3140]">
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h2 id="unfilled-journals-heading" class="font-bold text-sm text-zinc-900 dark:text-[#F1F1F4]">Guru Belum Mengisi Jurnal</h2>
                        <span class="text-xs font-mono text-sky-900 dark:text-sky-200 bg-sky-50 dark:bg-[#16222F] px-2.5 py-0.5 rounded-md border border-sky-200 dark:border-sky-700/60 font-semibold tabular-nums shrink-0">
                            {{ $unfilledSchedules->count() ?? 0 }} Kelas Tertunda
                        </span>
                    </div>
                    <a href="{{ route('journals.index') }}" class="inline-flex items-center min-h-[36px] text-xs text-[#0073B1] dark:text-sky-400 hover:underline font-sans font-semibold shrink-0">Lihat Semua &rarr;</a>
                </div>

                @if(isset($unfilledSchedules) && $unfilledSchedules->isNotEmpty())
                    <ul role="list" class="divide-y divide-zinc-200 dark:divide-[#2E3140] mt-2">
                        @foreach($unfilledSchedules as $idx => $sched)
                            <li
                                x-show="{{ $idx }} >= (currentPage - 1) * pageSize && {{ $idx }} < currentPage * pageSize"
                                class="py-3 px-2 grid grid-cols-1 md:grid-cols-[minmax(0,1.2fr)_minmax(0,1.6fr)_auto] items-start md:items-center gap-2 md:gap-4 hover:bg-zinc-50 dark:hover:bg-[#262832] transition-colors"
                            >
                                <div class="font-semibold text-xs text-zinc-900 dark:text-[#F1F1F4] min-w-0 break-words">
                                    {{ $sched->teacher->fullname ?? '-' }}
                                </div>
                                <div class="text-zinc-600 dark:text-[#9496A1] font-mono text-xs min-w-0 break-words">
                                    Kelas {{ $sched->schoolClass->class_name ?? '-' }} &bull; {{ $sched->subject ?? 'KBM' }}
                                </div>
                                <div class="flex items-center md:justify-end gap-2 shrink-0 flex-wrap">
                                    @if(($sched->alert_status ?? '') === 'segera_berakhir')
                                        <span class="inline-flex items-center justify-center min-w-[7.5rem] text-[11px] font-mono text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-[#282115] px-2.5 py-0.5 rounded border border-amber-200 dark:border-amber-700/60 font-semibold">
                                            Segera Berakhir
                                        </span>
                                    @else
                                        <span class="inline-flex items-center justify-center min-w-[7.5rem] text-[11px] font-mono text-rose-800 dark:text-rose-300 bg-rose-50 dark:bg-[#2D1618] px-2.5 py-0.5 rounded border border-rose-200 dark:border-rose-700/60 font-semibold">
                                            Terlambat
                                        </span>
                                    @endif
                                    <span class="inline-flex items-center justify-center min-w-[6.75rem] font-mono text-xs text-sky-900 dark:text-sky-200 bg-sky-50 dark:bg-[#16222F] px-2.5 py-0.5 rounded border border-sky-200 dark:border-sky-700/60 tabular-nums">
                                        {{ \Carbon\Carbon::parse($sched->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($sched->end_time)->format('H:i') }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="text-xs text-zinc-600 dark:text-[#9496A1] font-mono text-center py-6 space-y-2 mt-2">
                        @if(!($todayHasSchedules ?? true))
                            <div>Tidak ada jadwal hari ini.</div>
                            <div>
                                <a href="{{ route('schedules.index') }}" class="inline-flex items-center min-h-[36px] text-[#0073B1] dark:text-sky-400 hover:underline font-sans font-semibold">Lihat Jadwal Hari Lain &rarr;</a>
                            </div>
                        @else
                            <div>Semua kelas yang telah selesai sudah mengisi jurnal.</div>
                        @endif
                    </div>
                @endif

                <div class="mt-3">
                    <x-client-pagination total-expr="totalMatches" />
                </div>
            </section>
        @endif

        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if(session('error'))
            <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
        @endif
        @if(session('leave_success'))
            <x-alert-banner type="success">{{ session('leave_success') }}</x-alert-banner>
        @endif

        {{-- Welcome Profile Card --}}
        <div class="bg-white dark:bg-[#1D1D22] border border-zinc-200 dark:border-[#2E3140] rounded-xl p-6 sm:p-7 shadow-xs">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <div class="inline-flex items-center px-2.5 py-1 rounded-md bg-zinc-100 dark:bg-[#15161D] border border-zinc-200 dark:border-[#2E3140] text-xs font-semibold text-zinc-800 dark:text-[#F1F1F4]">
                        {{ $user->role->role_name ?? 'Pengguna' }}
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-[#F1F1F4]">
                        {{ $user->fullname }}
                    </h2>
                    <p class="text-xs sm:text-sm text-zinc-600 dark:text-[#B4B6C2] max-w-2xl leading-relaxed">
                        Platform monitoring jadwal pelajaran, rombongan belajar, dan presensi siswa SMK Negeri 1 Bantul.
                    </p>
                </div>

                @if($config && $config->is_schedule_locked)
                    <div class="flex flex-row md:flex-col gap-3 shrink-0">
                        <div class="p-3 bg-sky-50 dark:bg-[#16222F] border border-sky-200 dark:border-sky-700/60 rounded-lg min-w-[140px]">
                            <div class="text-xs font-mono text-sky-900 dark:text-sky-300 uppercase tracking-wider">Akses Jadwal</div>
                            <div class="font-bold font-mono text-sm mt-0.5 text-sky-900 dark:text-sky-200 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-[#0073B1] dark:bg-sky-400"></span>
                                Terkunci
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Action Cards --}}
        @php
            $quickActions = match ((int) $user->role_id) {
                5 => [
                    ['route' => 'users.index',                    'title' => 'Manajemen Pengguna',         'desc' => 'Kelola akun guru, kesiswaan, siswa, dan atur ulang kata sandi pengguna.',           'cta' => 'Kelola Pengguna'],
                    ['route' => 'waka.settings',                  'title' => 'Pengaturan Sistem & AI',     'desc' => 'Atur siklus putaran, kunci jadwal, aturan jurnal, dan kunci API Gemini.',          'cta' => 'Kelola Pengaturan'],
                    ['route' => 'system-configuration.email',     'title' => 'Pengaturan Email (SMTP)',    'desc' => 'Konfigurasi server SMTP untuk pengiriman kode OTP dan pemulihan sandi.',            'cta' => 'Kelola Email'],
                    ['route' => 'classes.promotion',              'title' => 'Kenaikan Kelas & Mutasi',    'desc' => 'Simulasi kenaikan rombel, mutasi siswa, serta penugasan Wali & Guru BK.',          'cta' => 'Buka Simulasi'],
                    ['route' => 'schedules.index',                'title' => 'Master Jadwal & Import',     'desc' => 'Pantau seluruh jadwal sekolah, impor FET, dan kelola daftar ruangan.',             'cta' => 'Buka Jadwal'],
                    ['route' => 'waka.settings.login-background', 'title' => 'Tampilan Latar Login',       'desc' => 'Perbarui gambar latar halaman masuk portal sekolah.',                               'cta' => 'Atur Tampilan'],
                ],
                1 => [
                    ['route' => 'waka.settings',       'title' => 'Pengaturan Kurikulum',      'desc' => 'Atur siklus putaran jadwal aktif, status kunci, dan jam pengisian jurnal.',       'cta' => 'Kelola Kurikulum'],
                    ['route' => 'users.index',         'title' => 'Manajemen Pengguna',        'desc' => 'Kelola akun guru, siswa, kesiswaan, dan atur ulang kata sandi pengguna.',         'cta' => 'Kelola Pengguna'],
                    ['route' => 'classes.index',       'title' => 'Data Kelas & Th. Ajaran',   'desc' => 'Kelola daftar rombongan belajar dan lihat siswa per kelas.',                      'cta' => 'Kelola Kelas'],
                    ['route' => 'schedules.index',     'title' => 'Master Jadwal Sekolah',     'desc' => 'Pantau seluruh alokasi jadwal mengajar, ruangan, dan import jadwal FET.',         'cta' => 'Buka Jadwal'],
                    ['route' => 'journals.index',      'title' => 'Monitoring Jurnal KBM',     'desc' => 'Pengawasan pengisian jurnal guru pengajar dan kepatuhan KBM sekolah.',            'cta' => 'Supervisi Jurnal'],
                    ['route' => 'leave-requests.index','title' => 'Monitoring Izin Siswa',     'desc' => 'Rekapitulasi data ketidakhadiran siswa dan surat keterangan izin/sakit.',         'cta' => 'Rekap Izin'],
                    ['route' => 'attendance.recap',    'title' => 'Rekap Presensi Bulanan',    'desc' => 'Analisis statistik kehadiran siswa per bulan dan rombongan belajar.',             'cta' => 'Lihat Rekap'],
                ],
                3 => [
                    ['route' => 'leave-requests.index','title' => 'Verifikasi Permohonan Izin', 'desc' => 'Proses dan setujui permohonan izin/sakit yang diajukan siswa dan orang tua.',    'cta' => 'Verifikasi Izin'],
                    ['route' => 'classes.index',       'title' => 'Data Kelas & Th. Ajaran',   'desc' => 'Kelola daftar rombel kelas dan saring data berdasarkan periode tahun ajaran.',   'cta' => 'Data Kelas'],
                    ['route' => 'journals.index',      'title' => 'Monitoring Presensi KBM',   'desc' => 'Pantau data kehadiran siswa di kelas yang disinkronkan dari jurnal guru.',        'cta' => 'Lihat Presensi'],
                ],
                2 => [
                    ['route' => 'schedules.index', 'title' => 'Jadwal Mengajar Saya', 'desc' => 'Cek alokasi ruangan, kelas, dan jam pelajaran mingguan Anda.',                   'cta' => 'Buka Jadwal'],
                    ['route' => 'journals.create', 'title' => 'Isi Jurnal Baru',      'desc' => 'Catat materi harian, unggah dokumentasi KBM, dan absensi otomatis.',             'cta' => 'Input Sekarang'],
                    ['route' => 'journals.index',  'title' => 'Riwayat Jurnal',       'desc' => 'Arsip catatan kegiatan belajar mengajar dan kehadiran siswa.',                   'cta' => 'Lihat Arsip'],
                ],
                4 => [
                    ['route' => 'leave-requests.index',  'title' => 'Riwayat Pengajuan Izin',         'desc' => 'Lihat status persetujuan surat keterangan sakit atau izin yang diajukan.',  'cta' => 'Lihat Riwayat'],
                    ['route' => 'leave-requests.create', 'title' => 'Ajukan Permohonan Izin Baru',    'desc' => 'Kirimkan formulir ketidakhadiran disertai bukti surat dokter/keterangan.',  'cta' => 'Ajukan Izin'],
                ],
                default => [],
            };
        @endphp

        @if(count($quickActions) > 0)
            <section aria-labelledby="main-menu-heading" class="cv-auto">
                <h2 id="main-menu-heading" class="text-xs font-bold font-mono uppercase tracking-widest text-zinc-600 dark:text-[#B4B6C2] mb-3">
                    Menu Utama
                </h2>
                <div class="card-grid-aligned grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($quickActions as $act)
                        <a href="{{ route($act['route']) }}" class="card-subgrid-link group rounded-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)]">
                            <article class="card-subgrid bg-white dark:bg-[#1D1D22] border border-zinc-200 dark:border-[#2E3140] rounded-xl p-5 group-hover:border-[#0073B1] dark:group-hover:border-sky-400 group-hover:bg-zinc-50 dark:group-hover:bg-[#262832] transition-colors duration-150 shadow-xs">
                                <div>
                                    <h3 class="font-bold text-sm text-zinc-900 dark:text-[#F1F1F4]">{{ $act['title'] }}</h3>
                                    <p class="text-xs text-zinc-600 dark:text-[#B4B6C2] mt-1.5 leading-relaxed">{{ $act['desc'] }}</p>
                                </div>
                                <footer class="mt-4 pt-3 border-t border-zinc-200 dark:border-[#2E3140] flex items-center justify-between text-xs font-semibold text-zinc-800 dark:text-[#F1F1F4]">
                                    <span>{{ $act['cta'] }}</span>
                                    <span class="font-mono text-[#0073B1] dark:text-sky-400 group-hover:translate-x-1 transition-transform" aria-hidden="true">&rarr;</span>
                                </footer>
                            </article>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
