<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-zinc-900">
                    Riwayat Jurnal Mengajar
                </h1>
                <p class="text-xs text-zinc-500 font-mono mt-0.5">Daftar catatan KBM dan dokumentasi guru</p>
            </div>
            @if(auth()->user()->role_id === 2)
                <a href="{{ route('journals.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-zinc-900 hover:bg-black text-white text-xs font-semibold rounded-md transition shadow-none">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Isi Jurnal Baru
                </a>
            @endif
        </div>
    </x-slot>

    <div class="space-y-3 max-w-4xl">

        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif

        <div class="bg-white border border-zinc-200 rounded-lg divide-y divide-zinc-100 overflow-hidden">
            @forelse($journals as $journal)
                <a href="{{ route('journals.show', $journal) }}" class="p-4 flex items-center justify-between hover:bg-zinc-50/70 transition group">
                    <div class="space-y-1 min-w-0 pr-4">
                        <div class="flex items-center gap-2 flex-wrap text-xs">
                            <span class="px-1.5 py-0.5 rounded bg-zinc-100 font-mono font-bold text-[11px] text-zinc-800">
                                {{ $journal->schedule->schoolClass->class_name }}
                            </span>
                            <span class="font-bold text-zinc-900 group-hover:text-black">
                                {{ $journal->schedule->subject ?? 'Mata Pelajaran' }}
                            </span>
                            @if(auth()->user()->role_id !== 2)
                                <span class="text-zinc-400 font-mono text-[11px]">&bull; {{ $journal->schedule->teacher->fullname }}</span>
                            @endif
                        </div>
                        <p class="text-xs text-zinc-600 line-clamp-1">
                            {{ $journal->topic_description }}
                        </p>
                        @php
                            $totalStd = $journal->attendances->count();
                            $absentStd = $journal->attendances->where('status', '!=', 'Hadir');
                        @endphp
                        <p class="text-[11px] font-mono text-zinc-400 flex flex-wrap items-center gap-1.5 mt-0.5">
                            <span>{{ $journal->date->translatedFormat('l, d F Y') }}</span>
                            <span>&bull;</span>
                            <span>Ruang {{ $journal->schedule->room->room_name ?? '-' }}</span>
                            @if($totalStd > 0)
                                <span>&bull;</span>
                                @if($absentStd->count() > 0)
                                    <span class="text-amber-700 font-semibold">Presensi: {{ $absentStd->count() }} Tidak Hadir ({{ $absentStd->pluck('status')->unique()->join(', ') }})</span>
                                @else
                                    <span class="text-emerald-700">Presensi: Semua Hadir (Nihil)</span>
                                @endif
                            @endif
                        </p>
                    </div>
                    <div class="text-right shrink-0 flex items-center gap-3 font-mono text-xs text-zinc-400">
                        <span>{{ $journal->created_at->diffForHumans() }}</span>
                        <span class="group-hover:text-zinc-900 group-hover:translate-x-0.5 transition">&rarr;</span>
                    </div>
                </a>
            @empty
                <div class="p-10 text-center text-xs font-mono text-zinc-400">
                    Belum ada rekaman jurnal mengajar.
                </div>
            @endforelse
        </div>

        <div class="pt-2 font-mono text-xs">
            {{ $journals->links() }}
        </div>

    </div>
</x-app-layout>