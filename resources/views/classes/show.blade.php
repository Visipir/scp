<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('classes.index') }}" class="p-2 -ml-2 rounded-lg text-zinc-400 hover:text-zinc-900 hover:bg-[#26262A] transition" title="Kembali ke Daftar Kelas">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider">Master Data Akademik</span>
                        <span class="text-zinc-300 dark:text-[#5B5B61]">&bull;</span>
                        <span class="text-xs font-semibold text-[#0093dd] uppercase tracking-wider">Detail Rombel</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0] mt-0.5 flex items-center gap-2 flex-wrap">
                        {{ $class->class_name }}
                        @if($class->grade_level)
                            <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-800 font-mono text-xs font-bold">{{ $class->grade_level }}</span>
                        @endif
                        @if($class->major)
                            <span class="px-2 py-0.5 rounded bg-purple-50 text-purple-800 font-mono text-xs font-bold">{{ $class->major }}</span>
                        @endif
                        <span class="px-2.5 py-0.5 rounded bg-zinc-100 border border-zinc-200 font-mono text-xs text-zinc-700 font-semibold">TP {{ $class->academic_year ?? '2026/2027' }}</span>
                    </h1>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('classes.export', ['class' => $class->id, 'start_date' => $startDate ?? now()->startOfMonth()->toDateString(), 'end_date' => $endDate ?? now()->toDateString()]) }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white border border-zinc-300 hover:bg-[#26262A] text-zinc-700 text-xs font-semibold rounded-md shadow-none transition">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Export CSV
                </a>
                <a href="{{ route('classes.index') }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-zinc-900 hover:bg-black text-white text-xs font-semibold rounded-md shadow-none transition">
                    &larr; Semua Kelas
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{ activeTab: 'students' }">

        {{-- KPI Cards Presensi Kelas --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
            {{-- Total Siswa --}}
            <div class="bg-white border border-zinc-200/80 rounded-lg p-4 shadow-none">
                <div class="text-[10px] font-mono uppercase tracking-wider text-zinc-400 font-bold">Total Siswa</div>
                <div class="text-2xl font-bold font-mono text-zinc-900 mt-1">{{ $students->count() }}</div>
                <div class="text-[11px] text-zinc-500 mt-0.5">Siswa Terdaftar</div>
            </div>

            {{-- Hadir --}}
            <div class="bg-white border border-zinc-200/80 rounded-lg p-4 shadow-none">
                <div class="text-[10px] font-mono uppercase tracking-wider text-emerald-600 font-bold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Hadir
                </div>
                <div class="text-2xl font-bold font-mono text-emerald-700 mt-1">{{ $totalHadir }}</div>
                <div class="text-[11px] text-emerald-600/80 font-mono mt-0.5 font-semibold">{{ $attendanceRate }}% Kehadiran</div>
            </div>

            {{-- Sakit --}}
            <div class="bg-white border border-zinc-200/80 rounded-lg p-4 shadow-none">
                <div class="text-[10px] font-mono uppercase tracking-wider text-blue-600 font-bold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Sakit (S)
                </div>
                <div class="text-2xl font-bold font-mono text-blue-700 mt-1">{{ $totalSakit }}</div>
                <div class="text-[11px] text-zinc-500 mt-0.5">Catatan sakit</div>
            </div>

            {{-- Izin --}}
            <div class="bg-white border border-zinc-200/80 rounded-lg p-4 shadow-none">
                <div class="text-[10px] font-mono uppercase tracking-wider text-amber-600 font-bold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Izin (I)
                </div>
                <div class="text-2xl font-bold font-mono text-amber-700 mt-1">{{ $totalIzin }}</div>
                <div class="text-[11px] text-zinc-500 mt-0.5">Izin berhalangan</div>
            </div>

            {{-- Alpa --}}
            <div class="bg-white border border-zinc-200/80 rounded-lg p-4 shadow-none">
                <div class="text-[10px] font-mono uppercase tracking-wider text-rose-600 font-bold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Alpa (A)
                </div>
                <div class="text-2xl font-bold font-mono text-rose-700 mt-1">{{ $totalAlpa }}</div>
                <div class="text-[11px] text-zinc-500 mt-0.5">Tanpa keterangan</div>
            </div>

            {{-- Terlambat --}}
            <div class="bg-white border border-zinc-200/80 rounded-lg p-4 shadow-none">
                <div class="text-[10px] font-mono uppercase tracking-wider text-orange-600 font-bold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span> Terlambat (T)
                </div>
                <div class="text-2xl font-bold font-mono text-orange-700 mt-1">{{ $totalTerlambat }}</div>
                <div class="text-[11px] text-zinc-500 mt-0.5">Hadir terlambat</div>
            </div>
        </div>

        {{-- Filter & Top Bar --}}
        <div class="bg-white border border-zinc-200/80 rounded-lg p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <form method="GET" action="{{ route('classes.show', $class) }}" class="flex flex-col sm:flex-row sm:items-center gap-3 flex-wrap w-full md:w-auto">
                <label class="text-xs font-bold font-mono uppercase tracking-wider text-zinc-500 shrink-0">Filter Rentang Waktu:</label>

                <div class="flex items-center gap-2">
                    <input type="date" name="start_date" value="{{ $startDate }}" placeholder="Dari Tanggal"
                           class="rounded-md border-zinc-300 bg-white px-2.5 py-1.5 text-xs font-mono text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none">
                    <span class="text-zinc-400 text-xs">s/d</span>
                    <input type="date" name="end_date" value="{{ $endDate }}" placeholder="Sampai Tanggal"
                           class="rounded-md border-zinc-300 bg-white px-2.5 py-1.5 text-xs font-mono text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none">
                </div>

                <div class="relative w-full sm:w-56">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama atau NIS..." 
                           class="w-full rounded-md border-zinc-300 bg-white px-3 py-1.5 text-xs text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none">
                </div>

                <button type="submit" class="px-3 py-1.5 bg-zinc-100 hover:bg-[#26262A] text-zinc-800 text-xs font-semibold rounded transition cursor-pointer">
                    Filter
                </button>

                @if(!empty($startDate) || !empty($endDate) || !empty($search))
                    <a href="{{ route('classes.show', $class) }}" class="text-xs font-mono text-zinc-500 hover:text-zinc-900 underline">
                        Reset
                    </a>
                @endif
            </form>

            {{-- Tabs switcher --}}
            <div class="flex items-center p-1 bg-zinc-100 rounded-lg border border-zinc-200 self-start md:self-auto shrink-0">
                <button type="button" @click="activeTab = 'students'" 
                        :class="activeTab === 'students' ? 'bg-white text-zinc-900 shadow-xs font-bold' : 'text-zinc-500 hover:text-zinc-900 font-medium'"
                        class="px-3 py-1.5 text-xs rounded-md transition font-mono">
                    Daftar Siswa ({{ $students->count() }})
                </button>
                <button type="button" @click="activeTab = 'absences'" 
                        :class="activeTab === 'absences' ? 'bg-white text-zinc-900 shadow-xs font-bold' : 'text-zinc-500 hover:text-zinc-900 font-medium'"
                        class="px-3 py-1.5 text-xs rounded-md transition font-mono flex items-center gap-1.5">
                    Riwayat Sakit / Izin / Alpa
                    @if($totalSakit + $totalIzin + $totalAlpa + $totalTerlambat > 0)
                        <span class="px-1.5 py-0.2 rounded-full bg-rose-100 text-rose-700 text-[10px] font-bold">
                            {{ $totalSakit + $totalIzin + $totalAlpa + $totalTerlambat }}
                        </span>
                    @endif
                </button>
            </div>
        </div>

        {{-- TAB 1: Tabel Siswa & Rekap Presensi --}}
        <div x-show="activeTab === 'students'" class="bg-white border border-zinc-200/80 rounded-lg overflow-x-auto shadow-none">
            <table class="min-w-full divide-y divide-zinc-200/80 text-left">
                <thead class="bg-zinc-50 font-mono text-[10px] text-zinc-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 font-bold w-12 text-center">No</th>
                        <th class="px-4 py-3 font-bold">NIS / Username</th>
                        <th class="px-4 py-3 font-bold">Nama Lengkap Siswa</th>
                        <th class="px-3 py-3 font-bold text-center">L/P</th>
                        <th class="px-3 py-3 font-bold text-center text-emerald-700">Hadir</th>
                        <th class="px-3 py-3 font-bold text-center text-blue-700">Sakit</th>
                        <th class="px-3 py-3 font-bold text-center text-amber-700">Izin</th>
                        <th class="px-3 py-3 font-bold text-center text-rose-700">Alpa</th>
                        <th class="px-3 py-3 font-bold text-center text-orange-700">Terlambat</th>
                        <th class="px-4 py-3 font-bold text-right">% Kehadiran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 text-xs">
                    @forelse($students as $idx => $student)
                        @php
                            $stAtt = $groupedAttendances->get($student->id, collect());
                            $h = $stAtt->where('status', 'Hadir')->count();
                            $s = $stAtt->where('status', 'Sakit')->count();
                            $i = $stAtt->where('status', 'Izin')->count();
                            $a = $stAtt->where('status', 'Alpa')->count();
                            $t = $stAtt->where('status', 'Terlambat')->count();
                            $tot = $stAtt->count();
                            $rate = $tot > 0 ? round(($h / $tot) * 100) : 100;
                        @endphp
                        <tr class="hover:bg-[#232327] transition-colors">
                            <td class="px-4 py-3 font-mono text-zinc-400 text-center">
                                {{ $student->attendance_number ?? ($idx + 1) }}
                            </td>
                            <td class="px-4 py-3 font-mono text-zinc-600 font-semibold">
                                {{ $student->username }}
                            </td>
                            <td class="px-4 py-3 font-bold text-zinc-900">
                                {{ $student->fullname }}
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($student->gender === 'L')
                                    <span class="inline-block px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 font-mono text-[10px] font-bold">L</span>
                                @elseif($student->gender === 'P')
                                    <span class="inline-block px-1.5 py-0.5 rounded bg-pink-50 text-pink-700 font-mono text-[10px] font-bold">P</span>
                                @else
                                    <span class="text-zinc-300">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center font-mono font-bold text-emerald-700">
                                {{ $h }}
                            </td>
                            <td class="px-3 py-3 text-center font-mono font-semibold {{ $s > 0 ? 'text-blue-700 bg-blue-50/50 rounded' : 'text-zinc-400' }}">
                                {{ $s }}
                            </td>
                            <td class="px-3 py-3 text-center font-mono font-semibold {{ $i > 0 ? 'text-amber-700 bg-amber-50/50 rounded' : 'text-zinc-400' }}">
                                {{ $i }}
                            </td>
                            <td class="px-3 py-3 text-center font-mono font-bold {{ $a > 0 ? 'text-rose-700 bg-rose-50/60 rounded' : 'text-zinc-400' }}">
                                {{ $a }}
                            </td>
                            <td class="px-3 py-3 text-center font-mono font-semibold {{ $t > 0 ? 'text-orange-700 bg-orange-50/50 rounded' : 'text-zinc-400' }}">
                                {{ $t }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold
                                    {{ $rate >= 90 ? 'bg-emerald-100 text-emerald-800' : ($rate >= 75 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                    {{ $rate }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-5 py-10 text-center font-mono text-xs text-zinc-400">
                                Belum ada data siswa di kelas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- TAB 2: Log Catatan Ketidakhadiran (Sakit, Izin, Alpa, Terlambat) --}}
        <div x-show="activeTab === 'absences'" style="display: none;" class="space-y-4">
            <div class="bg-white border border-zinc-200/80 rounded-lg overflow-x-auto shadow-none">
                <table class="min-w-full divide-y divide-zinc-200/80 text-left">
                    <thead class="bg-zinc-50 font-mono text-[10px] text-zinc-400 uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 font-bold">Tanggal</th>
                            <th class="px-4 py-3 font-bold">NIS</th>
                            <th class="px-4 py-3 font-bold">Nama Siswa</th>
                            <th class="px-4 py-3 font-bold">Status</th>
                            <th class="px-4 py-3 font-bold">Mata Pelajaran / Jurnal</th>
                            <th class="px-4 py-3 font-bold">Alasan / Catatan Guru</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 text-xs">
                        @forelse($recentAbsences as $att)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-[#232327] transition-colors">
                                <td class="px-4 py-3 font-mono text-zinc-600 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($att->date)->isoFormat('D MMM Y') }}
                                </td>
                                <td class="px-4 py-3 font-mono text-zinc-500">
                                    {{ $att->student?->username ?? '-' }}
                                </td>
                                <td class="px-4 py-3 font-bold text-zinc-900">
                                    {{ $att->student?->fullname ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                        {{ $att->status === 'Sakit' ? 'bg-blue-100 text-blue-800' : '' }}
                                        {{ $att->status === 'Izin' ? 'bg-amber-100 text-amber-800' : '' }}
                                        {{ $att->status === 'Alpa' ? 'bg-rose-100 text-rose-800' : '' }}
                                        {{ $att->status === 'Terlambat' ? 'bg-orange-100 text-orange-800' : '' }}
                                    ">
                                        {{ $att->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-zinc-600">
                                    <span class="font-semibold text-zinc-800">{{ $att->journal?->schedule?->subject ?? 'KBM' }}</span>
                                    @if($att->journal?->topic_description)
                                        <span class="text-zinc-400 block text-[11px] truncate max-w-xs">{{ $att->journal->topic_description }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-zinc-600 text-[11px]">
                                    {{ $att->notes ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center font-mono text-xs text-zinc-400">
                                    &check; Nihil catatan ketidakhadiran (Semua siswa hadir penuh pada rentang waktu ini).
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
