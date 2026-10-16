<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="text-[10px] font-bold font-mono text-zinc-400 uppercase tracking-widest">Presensi</div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 mt-0.5">
                    Rekap Kehadiran
                </h1>
            </div>
            <a href="{{ route('leave-requests.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-zinc-300 text-xs font-semibold uppercase tracking-wider text-zinc-700 rounded-md hover:bg-zinc-50 transition">
                Izin &amp; Sakit
            </a>
        </div>
    </x-slot>

    <div class="space-y-4 sm:space-y-6 max-w-3xl">

        {{-- Filter: child (parent only) + month --}}
        <form method="GET" action="{{ route('student.attendance.index') }}" class="bg-white border border-zinc-200/80 rounded-lg p-4 sm:p-5 flex flex-col sm:flex-row sm:items-end gap-3">
            @if($isParent)
                <div class="flex-1">
                    <x-input-label for="student_id" value="Pilih Siswa" />
                    <select id="student_id" name="student_id" class="w-full rounded-md border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition">
                        @foreach($children as $child)
                            <option value="{{ $child->id }}" @selected($student->id === $child->id)>{{ $child->fullname }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="flex-1">
                <x-input-label for="month" value="Bulan" />
                <input id="month" name="month" type="month" value="{{ $month }}" class="w-full rounded-md border-zinc-300 bg-white px-3 py-2 text-sm font-mono text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition" />
            </div>
            <button type="submit" class="px-4 py-2 bg-zinc-900 hover:bg-black text-white text-xs font-semibold uppercase tracking-wider rounded-md transition">
                Terapkan
            </button>
        </form>

        {{-- Summary chips --}}
        @php
            $chips = [
                'Hadir'     => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                'Sakit'     => 'bg-purple-50 text-purple-800 border-purple-200',
                'Izin'      => 'bg-blue-50 text-blue-800 border-blue-200',
                'Alpa'      => 'bg-rose-50 text-rose-800 border-rose-200',
                'Terlambat' => 'bg-orange-50 text-orange-800 border-orange-200',
            ];
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
            @foreach($chips as $label => $classes)
                <div class="rounded-lg border {{ $classes }} px-3 py-2.5 text-center">
                    <div class="text-xl font-black font-mono">{{ $summary[$label] ?? 0 }}</div>
                    <div class="text-[10px] font-bold uppercase tracking-wider mt-0.5">{{ $label }}</div>
                </div>
            @endforeach
        </div>

        {{-- Records --}}
        <div class="space-y-2">
            @forelse($records as $record)
                @php $schedule = $record->journal?->schedule; @endphp
                <div class="bg-white border border-zinc-200/80 rounded-lg p-4 flex items-start justify-between gap-3">
                    <div class="min-w-0 space-y-1">
                        <div class="font-mono text-xs text-zinc-500">
                            {{ \Carbon\Carbon::parse($record->date)->translatedFormat('l, d F Y') }}
                        </div>
                        <div class="font-bold text-sm text-zinc-900 truncate">
                            {{ $schedule?->subject ?? 'KBM' }}
                        </div>
                        <div class="font-mono text-[11px] text-zinc-500">
                            {{ $schedule?->period_label ?? '-' }}
                            @if($schedule)
                                &bull; {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}&ndash;{{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                            @endif
                        </div>
                        @if($record->notes)
                            <p class="text-xs text-zinc-600 leading-relaxed">{{ $record->notes }}</p>
                        @endif
                    </div>
                    <div class="shrink-0">
                        <x-status-badge :status="$record->status" />
                    </div>
                </div>
            @empty
                <div class="bg-white border border-zinc-200/80 rounded-lg p-12 text-center">
                    <p class="text-xs font-mono text-zinc-400 uppercase tracking-wider">Belum ada data presensi pada bulan ini.</p>
                </div>
            @endforelse
        </div>

    </div>
</x-app-layout>
