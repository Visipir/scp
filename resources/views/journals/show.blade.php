<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0]">
                    Detail Jurnal KBM
                </h1>
                <p class="text-xs text-zinc-500 font-mono mt-0.5">
                    {{ $journal->schedule->schoolClass->class_name }} &bull; {{ $journal->schedule->subject }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('journals.index') }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-white dark:bg-[#232327] border border-zinc-200 dark:border-[#2B2B30] text-xs font-semibold text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] rounded-md transition">
                    &larr; Kembali
                </a>
                @if(auth()->user()->role_id === 2 && \Carbon\Carbon::parse($journal->date)->isSameDay(now('Asia/Jakarta')))
                    <a href="{{ route('attendances.edit', $journal) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#0093dd] hover:bg-[#0082c4] text-white text-xs font-semibold rounded-md transition shadow-2xs">
                        Edit Presensi
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="space-y-4 max-w-3xl">

        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif

        {{-- Info Ringkas Jurnal --}}
        <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-lg p-5 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-zinc-100 dark:border-[#2B2B30] text-xs">
                <div>
                    <span class="font-bold text-zinc-900 dark:text-[#EDEDF0] text-sm block">
                        {{ $journal->schedule->subject ?? 'Mata Pelajaran' }}
                    </span>
                    <span class="text-zinc-500 text-xs">
                        Kelas: <strong>{{ $journal->schedule->schoolClass->class_name }}</strong> &bull; Guru: {{ $journal->schedule->teacher->fullname }}
                    </span>
                </div>
                <div class="text-right font-mono text-xs text-zinc-500">
                    <div>{{ $journal->date->translatedFormat('l, d F Y') }}</div>
                    <div class="text-[11px] text-zinc-400">{{ $journal->schedule->period_label }} ({{ \Carbon\Carbon::parse($journal->schedule->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($journal->schedule->end_time)->format('H:i') }}) &bull; Ruang {{ $journal->schedule->room->room_name ?? '-' }}</div>
                </div>
            </div>

            <div>
                <h3 class="text-xs font-bold text-zinc-700 dark:text-[#EDEDF0] mb-1">Materi &amp; Uraian Kegiatan</h3>
                <p class="text-xs text-zinc-800 dark:text-[#EDEDF0] leading-relaxed whitespace-pre-line bg-zinc-50 dark:bg-[#232327] p-3 rounded-md border border-zinc-100 dark:border-[#2B2B30]">
                    {{ $journal->topic_description }}
                </p>
            </div>

            @if($journal->photo_path)
                <div>
                    <h3 class="text-xs font-bold text-zinc-700 dark:text-[#EDEDF0] mb-1.5">Foto Dokumentasi KBM</h3>
                    <div class="inline-block p-1 bg-zinc-50 dark:bg-[#232327] border border-zinc-200 dark:border-[#2B2B30] rounded-lg">
                        <img 
                            src="{{ route('journals.photo', $journal) }}" 
                            alt="Dokumentasi KBM" 
                            class="rounded max-h-96 max-w-full w-auto object-contain"
                        />
                    </div>
                </div>
            @endif
        </div>

        {{-- Presensi Siswa: Default Hadir, Keterangan Hanya Sakit/Izin/Alpa --}}
        @php
            $totalStudents = $journal->attendances->count();
            $hadirCount = $journal->attendances->where('status', 'Hadir')->count();
            $sakitCount = $journal->attendances->where('status', 'Sakit')->count();
            $izinCount = $journal->attendances->where('status', 'Izin')->count();
            $alpaCount = $journal->attendances->where('status', 'Alpa')->count();
            $absentAttendances = $journal->attendances->where('status', '!=', 'Hadir');
        @endphp

        <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-lg overflow-hidden">
            <div class="px-5 py-3 border-b border-zinc-100 dark:border-[#2B2B30] flex flex-wrap items-center justify-between gap-2 bg-zinc-50/50 dark:bg-[#19191C]">
                <div>
                    <h3 class="font-bold text-xs text-zinc-900 dark:text-[#EDEDF0]">Keterangan Presensi Siswa</h3>
                    <p class="text-[11px] text-zinc-500 font-mono mt-0.5">
                        Default Hadir &bull; <strong class="text-emerald-700">{{ $hadirCount }}</strong> dari {{ $totalStudents }} Siswa
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-1.5 text-[11px] font-mono font-medium">
                    <span class="px-2 py-0.5 rounded bg-emerald-50 dark:bg-[#16281D] text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700/60">
                        Hadir: {{ $hadirCount }}
                    </span>
                    @if($sakitCount > 0)
                        <span class="px-2 py-0.5 rounded bg-purple-50 dark:bg-[#25182D] text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-700/60">
                            Sakit: {{ $sakitCount }}
                        </span>
                    @endif
                    @if($izinCount > 0)
                        <span class="px-2 py-0.5 rounded bg-blue-50 dark:bg-[#16222F] text-blue-700 dark:text-sky-300 border border-blue-200 dark:border-sky-700/60">
                            Izin: {{ $izinCount }}
                        </span>
                    @endif
                    @if($alpaCount > 0)
                        <span class="px-2 py-0.5 rounded bg-rose-50 dark:bg-[#2D1618] text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-700/60">
                            Alpa: {{ $alpaCount }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="p-5">
                @if($absentAttendances->count() > 0)
                    <div class="space-y-3">
                        <div class="text-xs font-bold text-zinc-700 dark:text-[#EDEDF0] flex items-center justify-between">
                            <span>Keterangan Siswa Tidak Hadir / Izin Keluar Kelas ({{ $absentAttendances->count() }} orang):</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            @foreach($absentAttendances as $attendance)
                                <div class="p-3 rounded-lg border border-zinc-200 dark:border-[#2B2B30] bg-zinc-50/80 dark:bg-[#232327] text-xs flex flex-col justify-between gap-2 shadow-sm">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <span class="font-bold text-zinc-900 dark:text-[#EDEDF0] block truncate">{{ $attendance->student->fullname }}</span>
                                            <span class="font-mono text-[10px] text-zinc-400">NIS: {{ $attendance->student->username }}</span>
                                        </div>
                                        <div class="shrink-0">
                                            <x-status-badge :status="$attendance->status" />
                                        </div>
                                    </div>
                                    @if($attendance->notes)
                                        <div class="pt-2 border-t border-zinc-200/60 dark:border-[#2B2B30] flex items-start gap-1.5 text-zinc-700 dark:text-[#EDEDF0] text-[11px] bg-white dark:bg-[#1D1D21] px-2.5 py-1.5 rounded border border-zinc-100 dark:border-[#2B2B30]">
                                            <svg class="w-3.5 h-3.5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
                                            <div class="min-w-0">
                                                <span class="text-[10px] font-bold font-mono text-zinc-500 uppercase tracking-wider block">Alasan / Catatan Keluar Kelas:</span>
                                                <span class="font-medium text-zinc-800 dark:text-[#EDEDF0] break-words">{{ $attendance->notes }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="flex items-center gap-2.5 text-xs text-emerald-800 dark:text-emerald-300 bg-emerald-50 dark:bg-[#16281D] border border-emerald-200/70 dark:border-emerald-700/60 p-3 rounded-md">
                        <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span><strong>Nihil</strong> &mdash; Seluruh {{ $totalStudents }} siswa hadir dalam KBM (tidak ada siswa yang sakit, izin, atau alpa).</span>
                    </div>
                @endif

                @if($totalStudents > 0)
                    <details class="mt-4 pt-3 border-t border-zinc-100 dark:border-[#2B2B30] group">
                        <summary class="text-[11px] font-mono text-zinc-500 hover:text-zinc-800 dark:hover:text-[#EDEDF0] cursor-pointer select-none flex items-center justify-between">
                            <span>Lihat Daftar Seluruh Siswa ({{ $totalStudents }} Siswa)</span>
                            <span class="group-open:rotate-180 transition-transform">&darr;</span>
                        </summary>
                        <div class="mt-3 divide-y divide-zinc-100 dark:divide-[#2B2B30] max-h-60 overflow-y-auto border border-zinc-100 dark:border-[#2B2B30] rounded-md">
                            @foreach($journal->attendances as $index => $attendance)
                                <div class="px-3 py-2 flex flex-col sm:flex-row sm:items-center justify-between text-xs hover:bg-[#232327] gap-1 sm:gap-4">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <span class="text-zinc-400 font-mono text-[11px] w-6 shrink-0">{{ $index + 1 }}.</span>
                                        <span class="text-zinc-800 dark:text-[#EDEDF0] font-medium truncate">{{ $attendance->student->fullname }}</span>
                                        <span class="text-zinc-400 font-mono text-[10px] shrink-0">({{ $attendance->student->username }})</span>
                                    </div>
                                    <div class="flex items-center gap-2.5 shrink-0">
                                        @if($attendance->notes)
                                            <span class="text-[10px] font-mono text-zinc-600 dark:text-[#97979B] bg-zinc-100 dark:bg-[#232327] px-2 py-0.5 rounded border border-zinc-200/70 dark:border-[#2B2B30] max-w-xs truncate" title="{{ $attendance->notes }}">
                                                &bull; {{ $attendance->notes }}
                                            </span>
                                        @endif
                                        <x-status-badge :status="$attendance->status" />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif
            </div>
        </div>

    </div>
</x-app-layout>