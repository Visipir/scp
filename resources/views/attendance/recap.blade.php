<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#F1F1F4] flex items-center gap-2">
                    <svg class="w-6 h-6 text-[#0073B1] dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                    </svg>
                    Rekapitulasi Presensi Bulanan
                </h1>
                <p class="text-xs text-zinc-600 dark:text-[#B4B6C2] mt-1">
                    Akumulasi data kehadiran seluruh siswa dan analisis rombongan belajar per bulan akademik.
                </p>
            </div>
            
            {{-- Form Filter Bulan --}}
            <search>
                <form method="GET" action="{{ route('attendance.recap') }}" class="flex flex-wrap items-center gap-2 shrink-0">
                    <label for="month-select" class="text-xs font-mono text-zinc-700 dark:text-[#B4B6C2]">Filter Bulan:</label>
                    <select id="month-select" name="month" class="min-h-[44px] sm:min-h-[38px] text-xs font-semibold rounded-lg border border-zinc-300 dark:border-[#2E3140] bg-white dark:bg-[#15161D] text-zinc-900 dark:text-[#F1F1F4] py-1.5 px-3 focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1] cursor-pointer">
                        @foreach($attendanceOverview['month']['available'] ?? [] as $mKey => $mLabel)
                            <option value="{{ $mKey }}" {{ $mKey === ($selectedMonth ?? '') ? 'selected' : '' }}>
                                {{ $mLabel }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="min-h-[44px] sm:min-h-[38px] px-3.5 py-1.5 rounded-lg bg-[#0073B1] hover:bg-[#005D8F] text-white text-xs font-semibold transition cursor-pointer">
                        Tampilkan
                    </button>
                </form>
            </search>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Ringkasan Statistik Bulan Terpilih -->
        <div class="bg-white dark:bg-[#1D1D22] border border-zinc-200 dark:border-[#2E3140] rounded-xl p-5 sm:p-6 space-y-6 shadow-xs">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-200 dark:border-[#2E3140] pb-4">
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="font-bold text-base text-zinc-900 dark:text-[#F1F1F4]">Ringkasan Statistik Kehadiran</h2>
                        <span class="px-2.5 py-0.5 rounded text-xs font-mono font-semibold bg-sky-50 dark:bg-[#16222F] border border-sky-200 dark:border-sky-700/60 text-[#0073B1] dark:text-sky-300">
                            {{ $attendanceOverview['month']['name_formatted'] ?? '' }}
                        </span>
                    </div>
                    <p class="text-xs text-zinc-600 dark:text-[#B4B6C2] mt-1">
                        Persentase kehadiran dihitung dari total akumulasi presensi siswa yang telah tercatat di jurnal KBM.
                    </p>
                </div>
                <div class="text-xs font-mono text-zinc-700 dark:text-[#B4B6C2] tabular-nums">
                    Total Tercatat: <strong class="text-zinc-900 dark:text-[#F1F1F4]">{{ number_format($attendanceOverview['month']['total_logged'] ?? 0) }} sesi</strong>
                </div>
            </div>

            {{-- Metric Cards Bulan Terpilih (Kontrak warna konsisten: Hadir=emerald, Izin=sky, Sakit=amber, Alpa=rose) --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
                <div class="bg-zinc-50 dark:bg-[#15161D] border border-zinc-200 dark:border-[#2E3140] rounded-lg p-3.5 flex flex-col justify-between">
                    <div class="text-xs font-mono text-zinc-600 dark:text-[#B4B6C2] uppercase">Hadir Bulan Ini</div>
                    <div class="mt-2 flex items-baseline justify-between tabular-nums">
                        <span class="text-xl sm:text-2xl font-bold font-mono text-emerald-700 dark:text-emerald-400">
                            {{ number_format($attendanceOverview['month']['counts']['Hadir'] ?? 0) }}
                        </span>
                        <span class="text-xs font-mono text-emerald-700 dark:text-emerald-400">sesi</span>
                    </div>
                </div>

                <div class="bg-zinc-50 dark:bg-[#15161D] border border-zinc-200 dark:border-[#2E3140] rounded-lg p-3.5 flex flex-col justify-between">
                    <div class="text-xs font-mono text-zinc-600 dark:text-[#B4B6C2] uppercase">Izin Bulan Ini</div>
                    <div class="mt-2 flex items-baseline justify-between tabular-nums">
                        <span class="text-xl sm:text-2xl font-bold font-mono text-[#0073B1] dark:text-sky-400">
                            {{ number_format($attendanceOverview['month']['counts']['Izin'] ?? 0) }}
                        </span>
                        <span class="text-xs font-mono text-[#0073B1] dark:text-sky-400">sesi</span>
                    </div>
                </div>

                <div class="bg-zinc-50 dark:bg-[#15161D] border border-zinc-200 dark:border-[#2E3140] rounded-lg p-3.5 flex flex-col justify-between">
                    <div class="text-xs font-mono text-zinc-600 dark:text-[#B4B6C2] uppercase">Sakit Bulan Ini</div>
                    <div class="mt-2 flex items-baseline justify-between tabular-nums">
                        <span class="text-xl sm:text-2xl font-bold font-mono text-amber-700 dark:text-amber-400">
                            {{ number_format($attendanceOverview['month']['counts']['Sakit'] ?? 0) }}
                        </span>
                        <span class="text-xs font-mono text-amber-700 dark:text-amber-400">sesi</span>
                    </div>
                </div>

                <div class="bg-zinc-50 dark:bg-[#15161D] border border-zinc-200 dark:border-[#2E3140] rounded-lg p-3.5 flex flex-col justify-between">
                    <div class="text-xs font-mono text-zinc-600 dark:text-[#B4B6C2] uppercase">Alpa Bulan Ini</div>
                    <div class="mt-2 flex items-baseline justify-between tabular-nums">
                        <span class="text-xl sm:text-2xl font-bold font-mono text-rose-700 dark:text-rose-400">
                            {{ number_format($attendanceOverview['month']['counts']['Alpa'] ?? 0) }}
                        </span>
                        <span class="text-xs font-mono text-rose-700 dark:text-rose-400">sesi</span>
                    </div>
                </div>

                <div class="bg-zinc-50 dark:bg-[#15161D] border border-zinc-200 dark:border-[#2E3140] rounded-lg p-3.5 flex flex-col justify-between col-span-2 sm:col-span-1">
                    <div class="text-xs font-mono text-zinc-600 dark:text-[#B4B6C2] uppercase">% Kehadiran</div>
                    <div class="mt-2 flex items-baseline justify-between tabular-nums">
                        <span class="text-xl sm:text-2xl font-bold font-mono text-zinc-900 dark:text-[#F1F1F4]">
                            {{ $attendanceOverview['month']['attendance_rate'] ?? 0 }}%
                        </span>
                        <span class="text-xs font-mono text-zinc-600 dark:text-[#B4B6C2]">
                            efektif
                        </span>
                    </div>
                </div>
            </div>

            {{-- Daftar Siswa Tidak Hadir Hari Ini --}}
            @if(!empty($attendanceOverview['today']['absentees']) && count($attendanceOverview['today']['absentees']) > 0)
                <div x-data="{ openAbsent: true, currentPage: 1, pageSize: 15, totalMatches: {{ count($attendanceOverview['today']['absentees']) }}, get totalPages() { return Math.max(1, Math.ceil(this.totalMatches / this.pageSize)); } }" class="border border-zinc-200 dark:border-[#2E3140] rounded-lg bg-zinc-50 dark:bg-[#15161D] p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-rose-600 dark:bg-rose-400"></span>
                            <h3 class="text-xs font-bold font-mono uppercase tracking-wider text-zinc-800 dark:text-[#F1F1F4]">
                                Siswa Tidak Hadir Hari Ini ({{ count($attendanceOverview['today']['absentees']) }} Siswa)
                            </h3>
                        </div>
                        <button type="button" @click="openAbsent = !openAbsent" class="min-h-[36px] px-2 text-xs font-semibold text-[#0073B1] dark:text-sky-400 hover:underline cursor-pointer" x-text="openAbsent ? 'Sembunyikan' : 'Tampilkan'"></button>
                    </div>

                    <div x-show="openAbsent" class="mt-3 space-y-3">
                        <div class="divide-y divide-zinc-200 dark:divide-[#2E3140]">
                            @foreach($attendanceOverview['today']['absentees'] as $idx => $ab)
                                <div x-show="{{ $idx }} >= (currentPage - 1) * pageSize && {{ $idx }} < currentPage * pageSize" class="py-2 first:pt-0 last:pb-0 flex items-center justify-between gap-2 text-xs">
                                    <div class="min-w-0">
                                        <span class="font-bold text-zinc-900 dark:text-[#F1F1F4]">{{ $ab->student?->fullname ?? 'Siswa' }}</span>
                                        <span class="text-zinc-600 dark:text-[#B4B6C2] text-xs">({{ $ab->student?->schoolClass?->class_name ?? '-' }})</span>
                                        @if(!empty($ab->notes))
                                            <span class="text-zinc-600 dark:text-[#9496A1] text-xs italic ml-1">&mdash; {{ $ab->notes }}</span>
                                        @endif
                                    </div>
                                    <x-status-badge :status="$ab->status" />
                                </div>
                            @endforeach
                        </div>
                        <x-client-pagination total-expr="totalMatches" />
                    </div>
                </div>
            @endif

            {{-- Tabel Rekap per Rombongan Belajar (Kelas) --}}
            <div x-data="{
                searchClass: '',
                pageSize: 15,
                currentPage: 1,
                classesData: [
                    @foreach($attendanceOverview['month']['class_breakdown'] ?? [] as $cls)
                    {
                        id: {{ (int) $cls['class_id'] }},
                        name: '{{ addslashes(strtolower($cls['class_name'])) }}',
                        homeroom: '{{ addslashes(strtolower($cls['homeroom'] ?? '')) }}'
                    },
                    @endforeach
                ],
                init() {
                    this.$watch('searchClass', () => { this.currentPage = 1; });
                },
                get filteredClassIds() {
                    const q = this.searchClass.toLowerCase().trim();
                    return this.classesData
                        .filter(c => !q || c.name.includes(q) || c.homeroom.includes(q))
                        .map(c => c.id);
                },
                get totalMatches() {
                    return this.filteredClassIds.length;
                },
                get totalPages() {
                    return Math.max(1, Math.ceil(this.totalMatches / this.pageSize));
                },
                isClassOnPage(id) {
                    const idx = this.filteredClassIds.indexOf(id);
                    if (idx === -1) return false;
                    const start = (this.currentPage - 1) * this.pageSize;
                    return idx >= start && idx < start + this.pageSize;
                }
            }" class="space-y-3 pt-2">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <h3 class="text-xs font-bold font-mono uppercase tracking-wider text-zinc-700 dark:text-[#B4B6C2]">
                        Rekapitulasi Kehadiran per Rombel Kelas
                    </h3>
                    <div class="w-full sm:w-64">
                        <label for="search-class-recap" class="sr-only">Cari rombel atau nama kelas</label>
                        <input id="search-class-recap" type="search" x-model="searchClass" placeholder="Cari rombel / kelas…" class="w-full min-h-[44px] sm:min-h-[36px] text-xs rounded-lg border border-zinc-300 dark:border-[#2E3140] bg-white dark:bg-[#15161D] text-zinc-900 dark:text-[#F1F1F4] py-1.5 px-3 focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1]">
                    </div>
                </div>

                <div tabindex="0" role="region" aria-label="Rekap Kehadiran per Rombel" class="border border-zinc-200 dark:border-[#2E3140] rounded-lg overflow-x-auto table-scroll-region">
                    <table class="w-full text-left text-xs">
                        <caption class="sr-only">Rekapitulasi Kehadiran Bulanan per Rombongan Belajar</caption>
                        <thead class="bg-zinc-100 dark:bg-[#15161D] text-xs font-mono text-zinc-700 dark:text-[#B4B6C2] uppercase sticky top-0 z-10 border-b border-zinc-200 dark:border-[#2E3140]">
                            <tr>
                                <th scope="col" class="px-3.5 py-2.5">Kelas</th>
                                <th scope="col" class="px-3.5 py-2.5">Wali Kelas</th>
                                <th scope="col" class="px-3.5 py-2.5 text-center">Hadir</th>
                                <th scope="col" class="px-3.5 py-2.5 text-center">Izin</th>
                                <th scope="col" class="px-3.5 py-2.5 text-center">Sakit</th>
                                <th scope="col" class="px-3.5 py-2.5 text-center">Alpa</th>
                                <th scope="col" class="px-3.5 py-2.5 text-center">Persentase</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-[#2E3140] font-mono tabular-nums">
                            @forelse($attendanceOverview['month']['class_breakdown'] ?? [] as $cls)
                                <tr x-show="isClassOnPage({{ (int) $cls['class_id'] }})" class="hover:bg-zinc-50 dark:hover:bg-[#262832] transition-colors">
                                    <td class="px-3.5 py-2.5 font-bold text-zinc-900 dark:text-[#F1F1F4] whitespace-nowrap">
                                        <a href="{{ route('classes.show', $cls['class_id']) }}" class="hover:text-[#0073B1] dark:hover:text-sky-400 transition-colors">
                                            {{ $cls['class_name'] }}
                                        </a>
                                    </td>
                                    <td class="px-3.5 py-2.5 text-zinc-700 dark:text-[#B4B6C2] font-sans">
                                        {{ $cls['homeroom'] ?? '-' }}
                                    </td>
                                    <td class="px-3.5 py-2.5 text-center text-emerald-700 dark:text-emerald-400 font-bold">
                                        {{ number_format($cls['hadir'] ?? 0) }}
                                    </td>
                                    <td class="px-3.5 py-2.5 text-center text-[#0073B1] dark:text-sky-400">
                                        {{ number_format($cls['izin'] ?? 0) }}
                                    </td>
                                    <td class="px-3.5 py-2.5 text-center text-amber-700 dark:text-amber-400">
                                        {{ number_format($cls['sakit'] ?? 0) }}
                                    </td>
                                    <td class="px-3.5 py-2.5 text-center text-rose-700 dark:text-rose-400">
                                        {{ number_format($cls['alpa'] ?? 0) }}
                                    </td>
                                    <td class="px-3.5 py-2.5 text-center whitespace-nowrap">
                                        @php
                                            $rate = $cls['attendance_rate'] ?? 0;
                                            $totalCls = $cls['total'] ?? 0;
                                        @endphp
                                        @if($totalCls === 0)
                                            <span class="px-2 py-0.5 rounded text-xs font-medium text-zinc-600 dark:text-[#9496A1] bg-zinc-100 dark:bg-[#262832]">Belum tercatat</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-xs font-bold
                                                {{ $rate >= 90 ? 'bg-emerald-100 text-emerald-800 dark:bg-[#16281D] dark:text-emerald-300' : '' }}
                                                {{ $rate >= 75 && $rate < 90 ? 'bg-amber-100 text-amber-800 dark:bg-[#282115] dark:text-amber-300' : '' }}
                                                {{ $rate < 75 ? 'bg-rose-100 text-rose-800 dark:bg-[#2D1618] dark:text-rose-300' : '' }}
                                            ">
                                                {{ $rate }}%
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-3.5 py-6 text-center text-zinc-600 dark:text-[#B4B6C2]">
                                        Belum ada data presensi siswa untuk bulan terpilih.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <x-client-pagination total-expr="totalMatches" />
            </div>
        </div>
    </div>
</x-app-layout>
