<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="text-xs font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider">Presensi</div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0] mt-0.5">
                    Rekap Kehadiran
                </h1>
            </div>
            <a href="{{ route('leave-requests.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent border border-zinc-200 dark:border-[#2B2B30] text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-[#EDEDF0] rounded-lg hover:bg-zinc-100 dark:hover:bg-[#26262A] transition">
                Izin &amp; Sakit
            </a>
        </div>
    </x-slot>

    <div class="space-y-4 sm:space-y-6 max-w-3xl">

        {{-- Filter: child (parent only) + month --}}
        <form method="GET" action="{{ route('student.attendance.index') }}" class="bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-end gap-3">
            @if($isParent)
                <div class="flex-1">
                    <x-input-label for="student_id" value="Pilih Siswa" />
                    <select id="student_id" name="student_id" class="w-full rounded-lg border border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-2 text-sm text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none transition">
                        @foreach($children as $child)
                            <option value="{{ $child->id }}" @selected($student->id === $child->id)>{{ $child->fullname }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="flex-1">
                <x-input-label for="month" value="Bulan" />
                <input id="month" name="month" type="month" value="{{ $month }}" class="w-full rounded-lg border border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-2 text-sm font-mono text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none transition" />
            </div>
            <button type="submit" class="px-4 py-2 bg-[#0093dd] hover:bg-[#0082c4] text-white text-xs font-semibold uppercase tracking-wider rounded-lg transition cursor-pointer">
                Terapkan
            </button>
        </form>

        {{-- Summary chips --}}
        @php
            $chips = [
                'Hadir'     => 'bg-emerald-50 text-emerald-800 border-emerald-300 dark:bg-[#16281D] dark:text-emerald-300 dark:border-emerald-700/60',
                'Sakit'     => 'bg-amber-50 text-amber-800 border-amber-300 dark:bg-[#282115] dark:text-amber-300 dark:border-amber-700/60',
                'Izin'      => 'bg-sky-50 text-sky-800 border-sky-300 dark:bg-[#16222F] dark:text-sky-300 dark:border-sky-700/60',
                'Alpa'      => 'bg-rose-50 text-rose-800 border-rose-300 dark:bg-[#2D1618] dark:text-rose-300 dark:border-rose-700/60',
                'Terlambat' => 'bg-purple-50 text-purple-800 border-purple-300 dark:bg-[#25182D] dark:text-purple-300 dark:border-purple-700/60',
            ];
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
            @foreach($chips as $label => $classes)
                <div class="rounded-xl border {{ $classes }} px-3 py-2.5 text-center">
                    <div class="text-xl font-black font-mono">{{ $summary[$label] ?? 0 }}</div>
                    <div class="text-[10px] font-bold uppercase tracking-wider mt-0.5 font-mono">{{ $label }}</div>
                </div>
            @endforeach
        </div>

        {{-- Records --}}
        <div class="space-y-2">
            @forelse($records as $record)
                @php $schedule = $record->journal?->schedule; @endphp
                <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-4 flex items-start justify-between gap-3">
                    <div class="min-w-0 space-y-1">
                        <div class="font-mono text-xs text-zinc-500 dark:text-[#97979B]">
                            {{ \Carbon\Carbon::parse($record->date)->translatedFormat('l, d F Y') }}
                        </div>
                        <div class="font-bold text-sm text-zinc-900 dark:text-[#EDEDF0] truncate">
                            {{ $schedule?->subject ?? 'KBM' }}
                        </div>
                        <div class="font-mono text-[11px] text-zinc-500 dark:text-[#97979B]">
                            {{ $schedule?->period_label ?? '-' }}
                            @if($schedule)
                                &bull; {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}&ndash;{{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                            @endif
                        </div>
                        @if($record->notes)
                            <p class="text-xs text-zinc-500 dark:text-[#97979B] leading-relaxed">{{ $record->notes }}</p>
                        @endif
                    </div>
                    <div class="shrink-0">
                        <x-status-badge :status="$record->status" />
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-12 text-center">
                    <p class="text-xs font-medium text-zinc-600 dark:text-[#97979B]">Belum ada data presensi pada bulan ini. Data kehadiran dicatat dan disinkronkan langsung dari pengisian jurnal guru pengajar di kelas.</p>
                </div>
            @endforelse
        </div>

    </div>
</x-app-layout>
