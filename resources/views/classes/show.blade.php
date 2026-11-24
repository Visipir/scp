<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('classes.index') }}" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[36px] sm:min-w-[36px] p-2 -ml-2 rounded-lg text-zinc-500 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] transition" aria-label="Kembali ke Daftar Kelas" title="Kembali ke Daftar Kelas">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-zinc-600 dark:text-[#9496A1] uppercase tracking-wider">Manajemen Data Akademik</span>
                        <span class="text-zinc-400 dark:text-[#5B5B61]">&bull;</span>
                        <span class="text-xs font-semibold text-[#0073B1] dark:text-[#38BDF8] uppercase tracking-wider">Detail Rombel</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0] mt-0.5 flex items-center gap-2 flex-wrap">
                        {{ $class->class_name }}
                        @if($class->grade_level)
                            <span class="px-2 py-0.5 rounded bg-blue-50 dark:bg-[#16222F] text-blue-800 dark:text-blue-300 font-mono text-xs font-bold">{{ $class->grade_level }}</span>
                        @endif
                        @if($class->major)
                            <span class="px-2 py-0.5 rounded bg-purple-50 dark:bg-[#25182D] text-purple-800 dark:text-purple-300 font-mono text-xs font-bold">{{ $class->major }}</span>
                        @endif
                        <span class="px-2.5 py-0.5 rounded bg-zinc-100 dark:bg-[#232327] border border-zinc-200 dark:border-[#2C2C30] font-mono text-xs text-zinc-700 dark:text-[#EDEDF0] font-semibold">TP {{ $class->academic_year ?? '2026/2027' }}</span>
                    </h1>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                @if(auth()->user()->canExportClassLoginCards($class))
                <a href="{{ route('classes.login-cards', $class) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white dark:bg-[#1E1E22] border border-zinc-300 dark:border-[#2C2C30] hover:bg-zinc-100 dark:hover:bg-[#26262A] text-zinc-700 dark:text-[#EDEDF0] hover:text-zinc-900 dark:hover:text-white text-xs font-semibold rounded-md shadow-none transition">
                    <svg aria-hidden="true" class="w-4 h-4 text-[#0073B1] dark:text-[#38BDF8]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                    </svg>
                    Kartu Login (CSV)
                </a>
                @endif
                <a href="{{ route('classes.export', ['class' => $class->id, 'start_date' => $startDate ?? now()->startOfMonth()->toDateString(), 'end_date' => $endDate ?? now()->toDateString()]) }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white dark:bg-[#1E1E22] border border-zinc-300 dark:border-[#2C2C30] hover:bg-zinc-100 dark:hover:bg-[#26262A] text-zinc-700 dark:text-[#EDEDF0] hover:text-zinc-900 dark:hover:text-white text-xs font-semibold rounded-md shadow-none transition">
                    <svg aria-hidden="true" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Export CSV
                </a>
                <a href="{{ route('classes.index') }}" 
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white dark:bg-[#232327] hover:bg-zinc-100 dark:hover:bg-[#26262A] text-zinc-700 dark:text-[#EDEDF0] hover:text-zinc-900 dark:hover:text-white border border-zinc-200 dark:border-[#2B2B30] text-xs font-semibold rounded-md shadow-2xs transition">
                    &larr; Semua Kelas
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $totalStudentsCount = method_exists($students, 'total') ? $students->total() : $students->count();
        $initialTab = in_array(request('tab'), ['students', 'absences'])
            ? request('tab')
            : (request()->has('absences_page') ? 'absences' : 'students');
    @endphp
    <div class="space-y-6" x-data="{ activeTab: '{{ $initialTab }}', assignModalOpen: false }">

        @if(session('success'))
            <div class="p-4 rounded-lg bg-emerald-50 dark:bg-[#16281D] border border-emerald-300 dark:border-emerald-700/60 text-emerald-800 dark:text-emerald-300 text-xs font-medium">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 rounded-lg bg-rose-50 dark:bg-[#2D1618] border border-rose-300 dark:border-rose-700/60 text-rose-800 dark:text-rose-300 text-xs font-medium">
                {{ session('error') }}
            </div>
        @endif

        {{-- Supervisi: Wali Kelas & Guru BK --}}
        <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2C2C30] rounded-xl p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-sm">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 flex-1">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-[#16222F] text-sky-800 dark:text-sky-300 border border-sky-200 dark:border-sky-700/60 flex items-center justify-center shrink-0">
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-mono tracking-wider text-zinc-400 font-bold block">Wali Kelas</span>
                        <p class="text-sm font-semibold text-zinc-900 dark:text-[#EDEDF0] mt-0.5">
                            {{ $class->homeroomAssignment?->user?->fullname ?? 'Belum ditentukan' }}
                        </p>
                        @if($class->homeroomAssignment?->user?->nip)
                            <p class="text-[11px] font-mono text-zinc-500">NIP: {{ $class->homeroomAssignment->user->nip }}</p>
                        @endif
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-[#25182D] text-purple-800 dark:text-purple-300 border border-purple-200 dark:border-purple-700/60 flex items-center justify-center shrink-0">
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-mono tracking-wider text-zinc-400 font-bold block">Guru Bimbingan Konseling (BK)</span>
                        <p class="text-sm font-semibold text-zinc-900 dark:text-[#EDEDF0] mt-0.5">
                            @if($class->counselorAssignments->isNotEmpty())
                                {{ $class->counselorAssignments->map(fn($ca) => $ca->user?->fullname)->filter()->join(', ') }}
                            @else
                                Belum ditentukan
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            @if(Auth::user()?->hasCurriculumAccess())
                <div>
                    <button type="button" @click="assignModalOpen = true; $nextTick(() => document.getElementById('homeroom_user_id')?.focus())" class="px-3.5 py-2 min-h-[44px] sm:min-h-0 text-xs font-semibold text-[#0073B1] dark:text-[#38BDF8] bg-sky-50 dark:bg-sky-950/40 hover:bg-sky-100 dark:hover:bg-sky-900/50 rounded-lg border border-sky-500/20 transition-colors cursor-pointer">
                        Ubah Penugasan
                    </button>
                </div>
            @endif
        </div>

        @if(Auth::user()?->hasCurriculumAccess())
            <dialog closedby="any" x-effect="assignModalOpen ? ($el.open || $el.showModal()) : ($el.open && $el.close())" @close="assignModalOpen = false" @cancel.prevent="assignModalOpen = false" aria-labelledby="assign-class-modal-title" class="m-auto w-[calc(100%-2rem)] sm:max-w-lg bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2C2C30] rounded-xl p-6 shadow-xl text-left overflow-y-auto">
                    <div class="flex items-start justify-between gap-4 mb-4">
                        <div>
                            <h2 id="assign-class-modal-title" class="text-sm font-bold text-zinc-900 dark:text-[#EDEDF0] mb-1">
                                Ubah Penugasan Guru: {{ $class->class_name }}
                            </h2>
                            <p class="text-xs text-zinc-600 dark:text-[#9496A1]">
                                Pilih guru dari daftar guru aktif untuk ditugaskan sebagai Wali Kelas atau Guru BK.
                            </p>
                        </div>
                        <button type="button" @click="assignModalOpen = false" aria-label="Tutup modal" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[32px] sm:min-w-[32px] rounded-lg text-zinc-500 hover:text-zinc-900 dark:text-[#9496A1] dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-[#26262A]">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form action="{{ route('classes.assignments.update', $class) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="homeroom_user_id" class="block text-xs font-semibold text-zinc-700 dark:text-[#EDEDF0] mb-1">
                                Wali Kelas
                            </label>
                            <select id="homeroom_user_id" name="homeroom_user_id" class="w-full text-xs rounded-lg border-zinc-300 dark:border-[#3A3A3F] bg-zinc-50 dark:bg-[#252529] text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1]">
                                <option value="">-- Belum Ditentukan / Kosongkan --</option>
                                @foreach($teachers as $t)
                                    <option value="{{ $t->id }}" {{ $class->homeroomAssignment?->user_id == $t->id ? 'selected' : '' }}>
                                        {{ $t->fullname }} {{ $t->nip ? "({$t->nip})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="counselor_user_ids" class="block text-xs font-semibold text-zinc-700 dark:text-[#EDEDF0] mb-1">
                                Guru BK (Bimbingan Konseling)
                            </label>
                            @php
                                $assignedCounselorIds = $class->counselorAssignments->pluck('user_id')->all();
                            @endphp
                            <select id="counselor_user_ids" name="counselor_user_ids[]" class="w-full text-xs rounded-lg border-zinc-300 dark:border-[#3A3A3F] bg-zinc-50 dark:bg-[#252529] text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1]">
                                <option value="">-- Belum Ditentukan / Kosongkan --</option>
                                @foreach($teachers as $t)
                                    <option value="{{ $t->id }}" {{ in_array($t->id, $assignedCounselorIds) ? 'selected' : '' }}>
                                        {{ $t->fullname }} {{ $t->nip ? "({$t->nip})" : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-200 dark:border-[#2C2C30]">
                            <button type="button" @click="assignModalOpen = false" class="px-4 py-2 min-h-[44px] sm:min-h-0 text-xs text-zinc-600 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-white bg-zinc-100 dark:bg-[#2C2C30] hover:bg-zinc-200 dark:hover:bg-[#3A3A3F] rounded-lg transition-colors">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 min-h-[44px] sm:min-h-0 text-xs font-semibold text-white bg-[#0073B1] hover:bg-[#005D8F] rounded-lg">
                                Simpan Penugasan
                            </button>
                        </div>
                    </form>
            </dialog>
        @endif

        {{-- KPI Cards Presensi Kelas --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
            {{-- Total Siswa --}}
            <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200/80 dark:border-[#2C2C30] rounded-lg p-4 shadow-none">
                <div class="text-[11px] font-mono uppercase tracking-wider text-zinc-600 dark:text-[#9496A1] font-bold">Total Siswa</div>
                <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-[#EDEDF0] mt-1 tabular-nums">{{ $totalStudentsCount }}</div>
                <div class="text-[11px] text-zinc-600 dark:text-[#9496A1] mt-0.5">Siswa Terdaftar</div>
            </div>

            {{-- Hadir --}}
            <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200/80 dark:border-[#2C2C30] rounded-lg p-4 shadow-none">
                <div class="text-[11px] font-mono uppercase tracking-wider text-emerald-700 dark:text-emerald-400 font-bold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Hadir
                </div>
                <div class="text-2xl font-bold font-mono text-emerald-700 dark:text-emerald-400 mt-1 tabular-nums">{{ $totalHadir }}</div>
                <div class="text-[11px] text-emerald-700 dark:text-emerald-400 font-mono mt-0.5 font-semibold">{{ $attendanceRate }}% Kehadiran</div>
            </div>

            {{-- Sakit --}}
            <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200/80 dark:border-[#2C2C30] rounded-lg p-4 shadow-none">
                <div class="text-[11px] font-mono uppercase tracking-wider text-amber-700 dark:text-amber-400 font-bold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Sakit (S)
                </div>
                <div class="text-2xl font-bold font-mono text-amber-700 dark:text-amber-400 mt-1 tabular-nums">{{ $totalSakit }}</div>
                <div class="text-[11px] text-zinc-600 dark:text-[#9496A1] mt-0.5">Catatan sakit</div>
            </div>

            {{-- Izin --}}
            <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200/80 dark:border-[#2C2C30] rounded-lg p-4 shadow-none">
                <div class="text-[11px] font-mono uppercase tracking-wider text-sky-700 dark:text-sky-400 font-bold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span> Izin (I)
                </div>
                <div class="text-2xl font-bold font-mono text-sky-700 dark:text-sky-400 mt-1 tabular-nums">{{ $totalIzin }}</div>
                <div class="text-[11px] text-zinc-600 dark:text-[#9496A1] mt-0.5">Izin berhalangan</div>
            </div>

            {{-- Alpa --}}
            <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200/80 dark:border-[#2C2C30] rounded-lg p-4 shadow-none">
                <div class="text-[11px] font-mono uppercase tracking-wider text-rose-700 dark:text-rose-400 font-bold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Alpa (A)
                </div>
                <div class="text-2xl font-bold font-mono text-rose-700 dark:text-rose-400 mt-1 tabular-nums">{{ $totalAlpa }}</div>
                <div class="text-[11px] text-zinc-600 dark:text-[#9496A1] mt-0.5">Tanpa keterangan</div>
            </div>

            {{-- Terlambat --}}
            <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200/80 dark:border-[#2C2C30] rounded-lg p-4 shadow-none">
                <div class="text-[11px] font-mono uppercase tracking-wider text-zinc-700 dark:text-zinc-300 font-bold flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-purple-600 dark:bg-purple-400"></span> Terlambat (T)
                </div>
                <div class="text-2xl font-bold font-mono text-zinc-900 dark:text-[#EDEDF0] mt-1 tabular-nums">{{ $totalTerlambat }}</div>
                <div class="text-[11px] text-zinc-600 dark:text-[#9496A1] mt-0.5">Hadir terlambat</div>
            </div>
        </div>

        {{-- Filter & Top Bar --}}
        <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200/80 dark:border-[#2C2C30] rounded-lg p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <form method="GET" action="{{ route('classes.show', $class) }}" class="flex flex-col sm:flex-row sm:items-center gap-3 flex-wrap w-full md:w-auto">
                <span class="text-xs font-bold font-mono uppercase tracking-wider text-zinc-600 dark:text-[#9496A1] shrink-0">Filter Rentang Waktu:</span>

                <div class="flex items-center gap-2">
                    <label for="class_show_start_date" class="sr-only">Dari Tanggal</label>
                    <input id="class_show_start_date" type="date" name="start_date" value="{{ $startDate }}"
                           class="rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-2.5 py-1.5 text-xs font-mono text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1] shadow-none">
                    <span class="text-zinc-500 dark:text-[#9496A1] text-xs">s/d</span>
                    <label for="class_show_end_date" class="sr-only">Sampai Tanggal</label>
                    <input id="class_show_end_date" type="date" name="end_date" value="{{ $endDate }}"
                           class="rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-2.5 py-1.5 text-xs font-mono text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1] shadow-none">
                </div>

                <div class="relative w-full sm:w-56">
                    <label for="class_show_search" class="sr-only">Cari nama atau NIS siswa</label>
                    <input id="class_show_search" type="text" name="search" value="{{ $search }}" placeholder="Cari nama atau NIS…" 
                           class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-1.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1] shadow-none">
                </div>

                <button type="submit" class="px-3 py-1.5 min-h-[44px] sm:min-h-0 bg-zinc-100 dark:bg-[#2C2C30] hover:bg-zinc-200 dark:hover:bg-[#3A3A3F] text-zinc-800 dark:text-[#EDEDF0] text-xs font-semibold rounded transition cursor-pointer">
                    Filter
                </button>

                @if(!empty($startDate) || !empty($endDate) || !empty($search))
                    <a href="{{ route('classes.show', $class) }}" class="text-xs font-mono text-zinc-600 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-[#EDEDF0] underline">
                        Reset
                    </a>
                @endif
            </form>

            {{-- Tabs switcher --}}
            <div class="flex items-center p-1 bg-zinc-100 dark:bg-[#121214] rounded-lg border border-zinc-200 dark:border-[#2B2B30] self-start md:self-auto shrink-0">
                <button type="button" @click="activeTab = 'students'" 
                        :class="activeTab === 'students' ? 'bg-white dark:bg-[#2C2C30] text-zinc-900 dark:text-white shadow-xs font-bold' : 'text-zinc-600 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-white font-medium'"
                        class="px-3 py-1.5 min-h-[44px] sm:min-h-0 text-xs rounded-md transition font-mono">
                    Daftar Siswa ({{ $totalStudentsCount }})
                </button>
                <button type="button" @click="activeTab = 'absences'" 
                        :class="activeTab === 'absences' ? 'bg-white dark:bg-[#2C2C30] text-zinc-900 dark:text-white shadow-xs font-bold' : 'text-zinc-600 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-white font-medium'"
                        class="px-3 py-1.5 min-h-[44px] sm:min-h-0 text-xs rounded-md transition font-mono flex items-center gap-1.5">
                    Riwayat Sakit / Izin / Alpa
                    @if($totalSakit + $totalIzin + $totalAlpa + $totalTerlambat > 0)
                        <span class="px-1.5 py-0.5 rounded-full bg-rose-100 dark:bg-rose-500/20 text-rose-700 dark:text-rose-400 text-[11px] font-bold">
                            {{ $totalSakit + $totalIzin + $totalAlpa + $totalTerlambat }}
                        </span>
                    @endif
                </button>
            </div>
        </div>

        {{-- TAB 1: Tabel Siswa & Rekap Presensi --}}
        <div x-show="activeTab === 'students'" tabindex="0" role="region" aria-label="Daftar Siswa dan Kehadiran Kelas" class="bg-white dark:bg-[#1E1E22] border border-zinc-200/80 dark:border-[#2C2C30] rounded-lg overflow-x-auto table-scroll-region shadow-none">
            <table class="min-w-full divide-y divide-zinc-200/80 dark:divide-[#2C2C30] text-left">
                <caption class="sr-only">Rekapitulasi Kehadiran Siswa di Kelas {{ $class->class_name }}</caption>
                <thead class="bg-zinc-50 dark:bg-[#19191C] font-mono text-[11px] text-zinc-600 dark:text-[#9496A1] uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-bold w-12 text-center">No</th>
                        <th scope="col" class="px-4 py-3 font-bold">NIS / Username</th>
                        <th scope="col" class="px-4 py-3 font-bold">Nama Lengkap Siswa</th>
                        <th scope="col" class="px-3 py-3 font-bold text-center">L/P</th>
                        <th scope="col" class="px-3 py-3 font-bold text-center text-emerald-700 dark:text-emerald-400">Hadir</th>
                        <th scope="col" class="px-3 py-3 font-bold text-center text-amber-700 dark:text-amber-400">Sakit</th>
                        <th scope="col" class="px-3 py-3 font-bold text-center text-sky-700 dark:text-sky-400">Izin</th>
                        <th scope="col" class="px-3 py-3 font-bold text-center text-rose-700 dark:text-rose-400">Alpa</th>
                        <th scope="col" class="px-3 py-3 font-bold text-center text-purple-700 dark:text-purple-400">Terlambat</th>
                        <th scope="col" class="px-4 py-3 font-bold text-right">% Kehadiran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-[#2C2C30] text-xs">
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
                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-[#232327] transition-colors">
                            <td class="px-4 py-3 font-mono text-zinc-400 dark:text-[#5B5B61] text-center">
                                {{ $student->attendance_number ?? ((method_exists($students, 'firstItem') && $students->firstItem() ? $students->firstItem() : 1) + $idx) }}
                            </td>
                            <td class="px-4 py-3 font-mono text-zinc-600 dark:text-[#97979B] font-semibold">
                                {{ $student->username }}
                            </td>
                            <td class="px-4 py-3 font-bold text-zinc-900 dark:text-[#EDEDF0]">
                                {{ $student->fullname }}
                            </td>
                            <td class="px-3 py-3 text-center">
                                @if($student->gender === 'L')
                                    <span class="inline-block px-1.5 py-0.5 rounded bg-blue-50 dark:bg-[#16222F] text-blue-700 dark:text-sky-300 font-mono text-[10px] font-bold">L</span>
                                @elseif($student->gender === 'P')
                                    <span class="inline-block px-1.5 py-0.5 rounded bg-pink-50 dark:bg-[#2D1625] text-pink-700 dark:text-pink-300 font-mono text-[10px] font-bold">P</span>
                                @else
                                    <span class="text-zinc-300 dark:text-[#5B5B61]">-</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center font-mono font-bold text-emerald-700 dark:text-emerald-400">
                                {{ $h }}
                            </td>
                            <td class="px-3 py-3 text-center font-mono font-semibold {{ $s > 0 ? 'text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-[#282115] rounded' : 'text-zinc-500 dark:text-[#9496A1]' }}">
                                {{ $s }}
                            </td>
                            <td class="px-3 py-3 text-center font-mono font-semibold {{ $i > 0 ? 'text-sky-800 dark:text-sky-300 bg-sky-50 dark:bg-[#16222F] rounded' : 'text-zinc-500 dark:text-[#9496A1]' }}">
                                {{ $i }}
                            </td>
                            <td class="px-3 py-3 text-center font-mono font-bold {{ $a > 0 ? 'text-rose-800 dark:text-rose-300 bg-rose-50 dark:bg-[#2D1618] rounded' : 'text-zinc-500 dark:text-[#9496A1]' }}">
                                {{ $a }}
                            </td>
                            <td class="px-3 py-3 text-center font-mono font-semibold {{ $t > 0 ? 'text-purple-800 dark:text-purple-300 bg-purple-50 dark:bg-[#25182D] rounded' : 'text-zinc-500 dark:text-[#9496A1]' }}">
                                {{ $t }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold
                                    {{ $rate >= 90 ? 'bg-emerald-100 dark:bg-[#16281D] text-emerald-800 dark:text-emerald-300' : ($rate >= 75 ? 'bg-amber-100 dark:bg-[#282115] text-amber-800 dark:text-amber-300' : 'bg-rose-100 dark:bg-[#2D1618] text-rose-800 dark:text-rose-300') }}">
                                    {{ $rate }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-5 py-10 text-center font-mono text-xs text-zinc-400 dark:text-[#5B5B61]">
                                Belum ada data siswa di kelas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if(method_exists($students, 'hasPages') && $students->hasPages())
                <div class="p-4 border-t border-zinc-200/80 dark:border-[#2C2C30] text-xs">
                    {{ $students->links() }}
                </div>
            @endif
        </div>

        {{-- TAB 2: Log Catatan Ketidakhadiran (Sakit, Izin, Alpa, Terlambat) --}}
        <div x-show="activeTab === 'absences'" style="display: none;" class="space-y-4">
            <div tabindex="0" role="region" aria-label="Log Ketidakhadiran dan Izin Kelas" class="bg-white dark:bg-[#1E1E22] border border-zinc-200/80 dark:border-[#2C2C30] rounded-lg overflow-x-auto table-scroll-region shadow-none">
                <table class="min-w-full divide-y divide-zinc-200/80 dark:divide-[#2C2C30] text-left">
                    <caption class="sr-only">Catatan Ketidakhadiran dan Izin Siswa Kelas {{ $class->class_name }}</caption>
                    <thead class="bg-zinc-50 dark:bg-[#19191C] font-mono text-[10px] text-zinc-600 dark:text-[#9496A1] uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="px-4 py-3 font-bold">Tanggal</th>
                            <th scope="col" class="px-4 py-3 font-bold">NIS</th>
                            <th scope="col" class="px-4 py-3 font-bold">Nama Siswa</th>
                            <th scope="col" class="px-4 py-3 font-bold">Status</th>
                            <th scope="col" class="px-4 py-3 font-bold">Mata Pelajaran / Jurnal</th>
                            <th scope="col" class="px-4 py-3 font-bold">Alasan / Catatan Guru</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-[#2C2C30] text-xs">
                        @forelse($recentAbsences as $att)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-[#232327] transition-colors">
                                <td class="px-4 py-3 font-mono text-zinc-600 dark:text-[#97979B] whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($att->date)->isoFormat('D MMM Y') }}
                                </td>
                                <td class="px-4 py-3 font-mono text-zinc-500 dark:text-[#97979B]">
                                    {{ $att->student?->username ?? '-' }}
                                </td>
                                <td class="px-4 py-3 font-bold text-zinc-900 dark:text-[#EDEDF0]">
                                    {{ $att->student?->fullname ?? '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider
                                        {{ $att->status === 'Sakit' ? 'bg-amber-100 dark:bg-[#282115] text-amber-800 dark:text-amber-300' : '' }}
                                        {{ $att->status === 'Izin' ? 'bg-sky-100 dark:bg-[#16222F] text-sky-800 dark:text-sky-300' : '' }}
                                        {{ $att->status === 'Alpa' ? 'bg-rose-100 dark:bg-[#2D1618] text-rose-800 dark:text-rose-300' : '' }}
                                        {{ $att->status === 'Terlambat' ? 'bg-purple-100 dark:bg-[#25182D] text-purple-800 dark:text-purple-300' : '' }}
                                    ">
                                        {{ $att->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-[#97979B]">
                                    <span class="font-semibold text-zinc-800 dark:text-[#EDEDF0]">{{ $att->journal?->schedule?->subject ?? 'KBM' }}</span>
                                    @if($att->journal?->topic_description)
                                        <span class="text-zinc-400 dark:text-[#5B5B61] block text-[11px] truncate max-w-xs">{{ $att->journal->topic_description }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-zinc-600 dark:text-[#97979B] text-[11px]">
                                    {{ $att->notes ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center font-mono text-xs text-zinc-400 dark:text-[#5B5B61]">
                                    &check; Nihil catatan ketidakhadiran (Semua siswa hadir penuh pada rentang waktu ini).
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if(method_exists($recentAbsences, 'hasPages') && $recentAbsences->hasPages())
                    <div class="p-4 border-t border-zinc-200/80 dark:border-[#2C2C30] text-xs">
                        {{ $recentAbsences->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>
</x-app-layout>
