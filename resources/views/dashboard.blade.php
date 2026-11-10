<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Dashboard" subtitle="Ringkasan aktivitas akademik dan informasi presensi sekolah" />
    </x-slot>

    <div class="space-y-6">
        @if($user->role_id === 1)
            @php
                $maxVal = max(1, max(
                    $attendanceStats['Hadir'] ?? 0,
                    $attendanceStats['Izin'] ?? 0,
                    $attendanceStats['Sakit'] ?? 0,
                    $attendanceStats['Alpa'] ?? 0,
                    $attendanceStats['Terlambat'] ?? 0
                ));
            @endphp
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Statistik Presensi Harian -->
                <div class="bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-5 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-bold text-sm text-[#EDEDF0]">Statistik Presensi Hari Ini</h3>
                            </div>
                            <!-- Legend -->
                            <div class="flex items-center gap-3 text-[11px] font-mono text-[#97979B]">
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-xs bg-[#0093dd]"></span>
                                    <span>Aktual</span>
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-xs bg-[#0a3d5e]"></span>
                                    <span>Basis</span>
                                </span>
                            </div>
                        </div>

                        <!-- Paired Bar Visualization -->
                        <div class="mt-6 pt-2 pb-1 relative">
                            <!-- Horizontal hairline grid lines -->
                            <div class="absolute inset-x-0 top-0 border-b border-[#2B2B30]/50"></div>
                            <div class="absolute inset-x-0 top-1/2 border-b border-[#2B2B30]/50"></div>
                            <div class="absolute inset-x-0 bottom-7 border-b border-[#2B2B30]"></div>

                            <div class="grid grid-cols-5 gap-3 sm:gap-6 items-end h-36 px-2 relative z-10">
                                @php
                                    $categories = [
                                        ['key' => 'Hadir', 'label' => 'HADIR', 'val' => $attendanceStats['Hadir'] ?? 0],
                                        ['key' => 'Izin', 'label' => 'IZIN', 'val' => $attendanceStats['Izin'] ?? 0],
                                        ['key' => 'Sakit', 'label' => 'SAKIT', 'val' => $attendanceStats['Sakit'] ?? 0],
                                        ['key' => 'Alpa', 'label' => 'ALPA', 'val' => $attendanceStats['Alpa'] ?? 0],
                                        ['key' => 'Terlambat', 'label' => 'TELAT', 'val' => $attendanceStats['Terlambat'] ?? 0],
                                    ];
                                @endphp

                                @foreach($categories as $cat)
                                    @php
                                        $hPercent = min(100, max(8, round(($cat['val'] / $maxVal) * 100)));
                                        $hSecondary = min(100, max(5, round($hPercent * 0.45)));
                                    @endphp
                                    <div class="flex flex-col items-center h-full justify-end group">
                                        <!-- Count tooltip -->
                                        <div class="text-[11px] font-mono font-bold text-[#EDEDF0] mb-1.5">
                                            {{ $cat['val'] }}
                                        </div>
                                        <!-- Paired Bars Container -->
                                        <div class="flex items-end gap-1.5 h-24 w-full max-w-[36px] justify-center">
                                            <!-- Primary Bar: Royal Blue #0093dd -->
                                            <div 
                                                class="w-2.5 sm:w-3 bg-[#0093dd] rounded-t-xs transition-all duration-300 group-hover:brightness-110" 
                                                style="height: {{ $hPercent }}%;"
                                                title="{{ $cat['key'] }}: {{ $cat['val'] }}"
                                            ></div>
                                            <!-- Secondary Bar: Muted Navy #0a3d5e -->
                                            <div 
                                                class="w-2.5 sm:w-3 bg-[#0a3d5e] rounded-t-xs transition-all duration-300 group-hover:brightness-125" 
                                                style="height: {{ $hSecondary }}%;"
                                                title="Baseline: {{ $cat['key'] }}"
                                            ></div>
                                        </div>
                                        <!-- Category Label -->
                                        <div class="text-[10px] font-mono font-semibold text-[#97979B] mt-2 group-hover:text-[#EDEDF0] transition-colors">
                                            {{ $cat['label'] }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Numeric Summary Eyebrow Chips -->
                    <div class="grid grid-cols-5 gap-2 mt-4 pt-3 border-t border-[#2B2B30]">
                        @foreach($categories as $cat)
                            <div class="text-center">
                                <div class="text-[10px] font-mono text-[#97979B] uppercase">{{ $cat['label'] }}</div>
                                <div class="text-xs font-mono font-bold text-[#EDEDF0] mt-0.5">{{ $cat['val'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Guru Belum Mengisi Jurnal -->
                <div class="bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-5 flex flex-col justify-between">
                    <div>
                        <div class="flex justify-between items-center mb-3">
                            <div>
                                <h3 class="font-bold text-sm text-[#EDEDF0]">Guru Belum Mengisi Jurnal</h3>
                            </div>
                            <span class="text-xs font-mono text-sky-800 dark:text-sky-300 bg-sky-50 dark:bg-[#16222F] px-2.5 py-0.5 rounded-md border border-sky-200 dark:border-sky-700/60 font-semibold">
                                {{ $unfilledSchedules->count() ?? 0 }} Kelas Tertunda
                            </span>
                        </div>
                        <div class="divide-y divide-[#2B2B30] max-h-48 overflow-y-auto mt-2">
                            @forelse($unfilledSchedules ?? [] as $sched)
                                <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between hover:bg-zinc-100 dark:hover:bg-[#232327] px-2 rounded-md transition-colors">
                                    <div>
                                        <div class="font-semibold text-xs text-[#EDEDF0]">{{ $sched->teacher->fullname ?? '-' }}</div>
                                        <div class="text-[#97979B] font-mono text-[11px] mt-0.5">
                                            Kelas {{ $sched->schoolClass->class_name ?? '-' }} &bull; {{ $sched->subject ?? 'KBM' }}
                                        </div>
                                    </div>
                                    <div class="text-right flex items-center gap-2">
                                        @if(($sched->alert_status ?? '') === 'segera_berakhir')
                                            <span class="text-[10px] font-mono text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-[#282115] px-2 py-0.5 rounded border border-amber-200 dark:border-amber-700/60 font-semibold">
                                                Segera Berakhir
                                            </span>
                                        @else
                                            <span class="text-[10px] font-mono text-rose-800 dark:text-rose-300 bg-rose-50 dark:bg-[#2D1618] px-2 py-0.5 rounded border border-rose-200 dark:border-rose-700/60 font-semibold">
                                                Terlambat
                                            </span>
                                        @endif
                                        <span class="font-mono text-[11px] text-sky-800 dark:text-sky-300 bg-sky-50 dark:bg-[#16222F] px-2 py-0.5 rounded border border-sky-200 dark:border-sky-700/60">
                                            {{ \Carbon\Carbon::parse($sched->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($sched->end_time)->format('H:i') }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="text-xs text-[#97979B] font-mono text-center py-8 space-y-2">
                                    @if(!($todayHasSchedules ?? true))
                                        <div>Tidak ada jadwal hari ini.</div>
                                        <div>
                                            <a href="{{ route('schedules.index') }}" class="text-[#0093dd] hover:underline font-sans font-semibold">Lihat Jadwal Hari Lain &rarr;</a>
                                        </div>
                                    @else
                                        <div>Semua kelas yang telah selesai sudah mengisi jurnal.</div>
                                    @endif
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-[#2B2B30] flex items-center justify-end text-xs">
                        <a href="{{ route('journals.index') }}" class="text-[#0093dd] hover:underline font-sans font-semibold">Lihat Semua &rarr;</a>
                    </div>
                </div>

                <!-- Tukar Jadwal Hari Ini -->
                <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-5 flex flex-col justify-between shadow-xs col-span-1 lg:col-span-2">
                    <div>
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-sm text-zinc-900 dark:text-[#EDEDF0]">Tukar Jadwal Hari Ini</h3>
                                @if(!empty($todaySwaps['pending_count']) && $todaySwaps['pending_count'] > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-800 dark:bg-[#282115] dark:text-amber-300 border border-amber-200 dark:border-amber-700/60">
                                        {{ $todaySwaps['pending_count'] }} Butuh Keputusan
                                    </span>
                                @endif
                            </div>
                            <span class="text-xs font-mono text-sky-800 dark:text-sky-300 bg-sky-50 dark:bg-[#16222F] px-2.5 py-0.5 rounded-md border border-sky-200 dark:border-sky-700/60 font-semibold self-start sm:self-auto">
                                {{ $todaySwaps['today_count'] ?? 0 }} Pertukaran Aktif Hari Ini
                            </span>
                        </div>

                        <div class="divide-y divide-zinc-200 dark:divide-[#2B2B30] max-h-48 overflow-y-auto mt-2">
                            @forelse($todaySwaps['approved'] ?? [] as $swap)
                                <div class="py-2.5 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:bg-zinc-50 dark:hover:bg-[#232327] px-2 rounded-md transition-colors">
                                    <div>
                                        <div class="font-semibold text-xs text-zinc-900 dark:text-[#EDEDF0] flex items-center gap-1.5">
                                            <span>{{ $swap->target?->fullname }}</span>
                                            <span class="text-zinc-400">&larr;</span>
                                            <span class="text-zinc-500 dark:text-zinc-400 font-normal">Gantikan {{ $swap->requester?->fullname }}</span>
                                        </div>
                                        <div class="text-zinc-500 dark:text-[#97979B] font-mono text-[11px] mt-0.5">
                                            {{ $swap->requesterSchedule?->subject }} &bull; {{ $swap->requesterSchedule?->schoolClass?->class_name }}
                                            (Ruang: {{ $swap->requesterSchedule?->room?->room_name ?? '-' }})
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 self-start sm:self-auto">
                                        @if($swap->isJournalFilled())
                                            <span class="font-mono text-[10px] text-emerald-800 dark:text-emerald-300 bg-emerald-50 dark:bg-[#16281D] px-2 py-0.5 rounded border border-emerald-200 dark:border-emerald-700/60">
                                                Jurnal Terisi
                                            </span>
                                        @else
                                            <span class="font-mono text-[10px] text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-[#282115] px-2 py-0.5 rounded border border-amber-200 dark:border-amber-700/60">
                                                Belum Jurnal
                                            </span>
                                        @endif
                                        <span class="font-mono text-[11px] text-sky-800 dark:text-sky-300 bg-sky-50 dark:bg-[#16222F] px-2 py-0.5 rounded border border-sky-200 dark:border-sky-700/60">
                                            {{ substr($swap->requesterSchedule?->start_time, 0, 5) }} - {{ substr($swap->requesterSchedule?->end_time, 0, 5) }}
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="text-xs text-zinc-500 dark:text-[#97979B] text-center py-6">
                                    Tidak ada pertukaran jadwal mengajar yang berlaku hari ini.
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-t border-zinc-200 dark:border-[#2B2B30] flex items-center justify-between text-xs">
                        <span class="text-zinc-500 dark:text-zinc-400 text-[11px]">Memantau pelaksanaan jadwal pengganti secara langsung</span>
                        <a href="{{ route('schedule-swaps.index') }}" class="text-[#0093dd] hover:underline font-sans font-semibold">Buka Laporan Tukar Jadwal &rarr;</a>
                    </div>
                </div>
            </div>
        @endif

        {{-- Session Flash Messages --}}
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
        <div class="bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-6 sm:p-7">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="space-y-2">
                    <div class="inline-flex items-center px-2.5 py-1 rounded-md bg-[#232327] border border-[#2B2B30] text-[11px] font-semibold text-[#EDEDF0]">
                        {{ $user->role->role_name ?? 'User' }}
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-[#EDEDF0]">
                        {{ $user->fullname }}
                    </h2>
                    <p class="text-xs sm:text-sm text-[#97979B] max-w-2xl leading-relaxed">
                        Platform monitoring jadwal pelajaran, rombongan belajar, dan presensi siswa.
                    </p>
                </div>

                {{-- Status Pills --}}
                @if($config && $config->is_schedule_locked)
                    <div class="flex flex-row md:flex-col gap-3 shrink-0">
                        <div class="p-3 bg-sky-50 dark:bg-[#16222F] border border-sky-200 dark:border-sky-700/60 rounded-lg min-w-[140px]">
                            <div class="text-[10px] font-mono text-sky-800 dark:text-[#0093dd] uppercase tracking-wider">Akses Jadwal</div>
                            <div class="font-bold font-mono text-sm mt-0.5 text-sky-900 dark:text-sky-200 flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-[#0093dd]"></span>
                                Terkunci
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Action Section Heading --}}
        <div>
            <h3 class="text-xs font-bold font-mono uppercase tracking-widest text-[#97979B] mb-3">
                Menu Utama
            </h3>

            {{-- 1. Quick Actions for WAKA KURIKULUM (role_id === 1: Konfigurasi & Manajemen Full) --}}
            @if($user->role_id === 1)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Konfigurasi Kurikulum & AI -->
                    <a href="{{ route('waka.settings') }}" class="group bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-5 hover:border-[#0093dd]/60 hover:bg-[#232327] transition-all flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-[#232327] border border-[#2B2B30] group-hover:bg-[#0093dd] text-[#97979B] group-hover:text-white flex items-center justify-center transition-colors duration-150 mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/></svg>
                            </div>
                            <h4 class="font-bold text-sm text-[#EDEDF0]">Konfigurasi Sistem &amp; AI</h4>
                            <p class="text-xs text-[#97979B] mt-1 leading-relaxed">Atur konfigurasi sistem, kunci jadwal, dan impor jadwal FET PDF dengan Gemini AI.</p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-[#2B2B30] flex items-center justify-between text-xs font-semibold text-[#EDEDF0]">
                            <span>Kelola Sistem &amp; AI</span>
                            <span class="font-mono text-[#0093dd] group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </a>

                    <!-- Data Kelas & Tahun Ajaran -->
                    <a href="{{ route('classes.index') }}" class="group bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-5 hover:border-[#0093dd]/60 hover:bg-[#232327] transition-all flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-[#232327] border border-[#2B2B30] group-hover:bg-[#0093dd] text-[#97979B] group-hover:text-white flex items-center justify-center transition-colors duration-150 mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
                            </div>
                            <h4 class="font-bold text-sm text-[#EDEDF0]">Data Kelas &amp; Th. Ajaran</h4>
                            <p class="text-xs text-[#97979B] mt-1 leading-relaxed">Kelola daftar rombongan belajar dan tetapkan periode tahun ajaran aktif.</p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-[#2B2B30] flex items-center justify-between text-xs font-semibold text-[#EDEDF0]">
                            <span>Kelola Kelas</span>
                            <span class="font-mono text-[#0093dd] group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </a>

                    <!-- Jadwal Pelajaran -->
                    <a href="{{ route('schedules.index') }}" class="group bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-5 hover:border-[#0093dd]/60 hover:bg-[#232327] transition-all flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-[#232327] border border-[#2B2B30] group-hover:bg-[#0093dd] text-[#97979B] group-hover:text-white flex items-center justify-center transition-colors duration-150 mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                            </div>
                            <h4 class="font-bold text-sm text-[#EDEDF0]">Master Jadwal Sekolah</h4>
                            <p class="text-xs text-[#97979B] mt-1 leading-relaxed">Pantau seluruh alokasi jadwal mengajar, ruangan, dan kelas sekolah.</p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-[#2B2B30] flex items-center justify-between text-xs font-semibold text-[#EDEDF0]">
                            <span>Buka Jadwal</span>
                            <span class="font-mono text-[#0093dd] group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </a>

                    <!-- Monitoring Jurnal -->
                    <a href="{{ route('journals.index') }}" class="group bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-5 hover:border-[#0093dd]/60 hover:bg-[#232327] transition-all flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-[#232327] border border-[#2B2B30] group-hover:bg-[#0093dd] text-[#97979B] group-hover:text-white flex items-center justify-center transition-colors duration-150 mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                            </div>
                            <h4 class="font-bold text-sm text-[#EDEDF0]">Monitoring Jurnal KBM</h4>
                            <p class="text-xs text-[#97979B] mt-1 leading-relaxed">Pengawasan pengisian jurnal guru pengajar dan kepatuhan KBM sekolah.</p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-[#2B2B30] flex items-center justify-between text-xs font-semibold text-[#EDEDF0]">
                            <span>Supervisi Jurnal</span>
                            <span class="font-mono text-[#0093dd] group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </a>

                    <!-- Monitoring Izin Siswa -->
                    <a href="{{ route('leave-requests.index') }}" class="group bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-5 hover:border-[#0093dd]/60 hover:bg-[#232327] transition-all flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-[#232327] border border-[#2B2B30] group-hover:bg-[#0093dd] text-[#97979B] group-hover:text-white flex items-center justify-center transition-colors duration-150 mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
                            </div>
                            <h4 class="font-bold text-sm text-[#EDEDF0]">Monitoring Izin Siswa</h4>
                            <p class="text-xs text-[#97979B] mt-1 leading-relaxed">Rekapitulasi data ketidakhadiran siswa dan surat keterangan izin/sakit.</p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-[#2B2B30] flex items-center justify-between text-xs font-semibold text-[#EDEDF0]">
                            <span>Rekap Izin</span>
                            <span class="font-mono text-[#0093dd] group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </a>
                </div>
            @endif

            {{-- 2. Quick Actions for TIM KESISWAAN / BK (role_id === 3: Verifikasi & Rekap) --}}
            @if($user->role_id === 3)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Verifikasi Izin -->
                    <a href="{{ route('leave-requests.index') }}" class="group bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-5 hover:border-[#0093dd]/60 hover:bg-[#232327] transition-all flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-[#232327] border border-[#2B2B30] group-hover:bg-[#0093dd] text-[#97979B] group-hover:text-white flex items-center justify-center transition-colors duration-150 mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
                            </div>
                            <h4 class="font-bold text-sm text-[#EDEDF0]">Verifikasi Permohonan Izin</h4>
                            <p class="text-xs text-[#97979B] mt-1 leading-relaxed">Proses dan setujui permohonan izin/sakit yang diajukan oleh siswa dan orang tua.</p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-[#2B2B30] flex items-center justify-between text-xs font-semibold text-[#EDEDF0]">
                            <span>Verifikasi Izin</span>
                            <span class="font-mono text-[#0093dd] group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </a>

                    <!-- Data Kelas & Tahun Ajaran -->
                    <a href="{{ route('classes.index') }}" class="group bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-5 hover:border-[#0093dd]/60 hover:bg-[#232327] transition-all flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-[#232327] border border-[#2B2B30] group-hover:bg-[#0093dd] text-[#97979B] group-hover:text-white flex items-center justify-center transition-colors duration-150 mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
                            </div>
                            <h4 class="font-bold text-sm text-[#EDEDF0]">Data Kelas &amp; Th. Ajaran</h4>
                            <p class="text-xs text-[#97979B] mt-1 leading-relaxed">Kelola daftar rombel kelas dan saring data berdasarkan periode tahun ajaran aktif.</p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-[#2B2B30] flex items-center justify-between text-xs font-semibold text-[#EDEDF0]">
                            <span>Data Kelas</span>
                            <span class="font-mono text-[#0093dd] group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </a>

                    <!-- Monitoring Presensi Jurnal KBM -->
                    <a href="{{ route('journals.index') }}" class="group bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-5 hover:border-[#0093dd]/60 hover:bg-[#232327] transition-all flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-[#232327] border border-[#2B2B30] group-hover:bg-[#0093dd] text-[#97979B] group-hover:text-white flex items-center justify-center transition-colors duration-150 mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                            </div>
                            <h4 class="font-bold text-sm text-[#EDEDF0]">Monitoring Presensi KBM</h4>
                            <p class="text-xs text-[#97979B] mt-1 leading-relaxed">Pantau data kehadiran siswa di kelas yang disinkronkan langsung dari jurnal guru.</p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-[#2B2B30] flex items-center justify-between text-xs font-semibold text-[#EDEDF0]">
                            <span>Lihat Presensi</span>
                            <span class="font-mono text-[#0093dd] group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </a>
                </div>
            @endif

            {{-- 3. Quick Actions for GURU (role_id === 2: Jadwal & Jurnal) --}}
            @if($user->role_id === 2)
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <!-- Jadwal Mengajar -->
                    <a href="{{ route('schedules.index') }}" class="group bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-5 hover:border-[#0093dd]/60 hover:bg-[#232327] transition-all flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-[#232327] border border-[#2B2B30] group-hover:bg-[#0093dd] text-[#97979B] group-hover:text-white flex items-center justify-center transition-colors duration-150 mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                            </div>
                            <h4 class="font-bold text-sm text-[#EDEDF0]">Jadwal Mengajar Saya</h4>
                            <p class="text-xs text-[#97979B] mt-1 leading-relaxed">Cek alokasi ruangan, kelas, dan jam pelajaran mingguan Anda.</p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-[#2B2B30] flex items-center justify-between text-xs font-semibold text-[#EDEDF0]">
                            <span>Buka Jadwal</span>
                            <span class="font-mono text-[#0093dd] group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </a>

                    <!-- Isi Jurnal -->
                    <a href="{{ route('journals.create') }}" class="group bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-5 hover:border-[#0093dd]/60 hover:bg-[#232327] transition-all flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-[#232327] border border-[#2B2B30] group-hover:bg-[#0093dd] text-[#97979B] group-hover:text-white flex items-center justify-center transition-colors duration-150 mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                            </div>
                            <h4 class="font-bold text-sm text-[#EDEDF0]">Isi Jurnal Baru</h4>
                            <p class="text-xs text-[#97979B] mt-1 leading-relaxed">Catat materi harian, unggah dokumentasi KBM, dan absensi otomatis.</p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-[#2B2B30] flex items-center justify-between text-xs font-semibold text-[#EDEDF0]">
                            <span>Input Sekarang</span>
                            <span class="font-mono text-[#0093dd] group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </a>

                    <!-- Riwayat Jurnal -->
                    <a href="{{ route('journals.index') }}" class="group bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-5 hover:border-[#0093dd]/60 hover:bg-[#232327] transition-all flex flex-col justify-between">
                        <div>
                            <div class="w-10 h-10 rounded-lg bg-[#232327] border border-[#2B2B30] group-hover:bg-[#0093dd] text-[#97979B] group-hover:text-white flex items-center justify-center transition-colors duration-150 mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                            </div>
                            <h4 class="font-bold text-sm text-[#EDEDF0]">Riwayat Jurnal</h4>
                            <p class="text-xs text-[#97979B] mt-1 leading-relaxed">Arsip catatan kegiatan belajar mengajar dan kehadiran siswa.</p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-[#2B2B30] flex items-center justify-between text-xs font-semibold text-[#EDEDF0]">
                            <span>Lihat Arsip</span>
                            <span class="font-mono text-[#0093dd] group-hover:translate-x-1 transition-transform">&rarr;</span>
                        </div>
                    </a>
                </div>
            @endif

            {{-- 4. Quick Actions for SISWA / ORANG TUA (role_id === 4) --}}
            @if($user->role_id === 4)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="{{ route('leave-requests.index') }}" class="group bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-6 hover:border-[#0093dd]/60 hover:bg-[#232327] transition-all">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-lg bg-[#232327] border border-[#2B2B30] group-hover:bg-[#0093dd] text-[#97979B] group-hover:text-white flex items-center justify-center shrink-0 transition-colors duration-150">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-base text-[#EDEDF0]">Riwayat Pengajuan Izin</h4>
                                <p class="text-xs text-[#97979B] mt-1 leading-relaxed">Lihat status persetujuan surat keterangan sakit atau izin yang telah diajukan.</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('leave-requests.create') }}" class="group bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-6 hover:border-[#0093dd]/60 hover:bg-[#232327] transition-all">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-lg bg-[#232327] border border-[#2B2B30] group-hover:bg-[#0093dd] text-[#97979B] group-hover:text-white flex items-center justify-center shrink-0 transition-colors duration-150">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-base text-[#EDEDF0]">Ajukan Permohonan Izin Baru</h4>
                                <p class="text-xs text-[#97979B] mt-1 leading-relaxed">Kirimkan formulir pemberitahuan ketidakhadiran disertai bukti surat dokter atau dokumen keterangan.</p>
                            </div>
                        </div>
                    </a>
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
