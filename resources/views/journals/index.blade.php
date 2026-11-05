<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Jurnal & Presensi" subtitle="Daftar catatan KBM dan dokumentasi presensi kelas">
            <x-slot:actions>
                @if(auth()->user()->role_id === 2)
                    <a href="{{ route('journals.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#0093dd] hover:bg-[#0082c4] active:bg-[#006fa7] text-white text-xs font-semibold rounded-lg transition shadow-none cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Isi Jurnal Baru
                    </a>
                @endif
            </x-slot:actions>
        </x-page-header>
    </x-slot>

    <div class="space-y-3 max-w-4xl">

        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif

        <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl divide-y divide-zinc-200 dark:divide-[#2B2B30] overflow-hidden">
            @forelse($journals as $journal)
                <a href="{{ route('journals.show', $journal) }}" class="p-4 flex items-center justify-between hover:bg-zinc-50 dark:hover:bg-[#232327] transition-colors group">
                    <div class="space-y-1 min-w-0 pr-4">
                        <div class="flex items-center gap-2 flex-wrap text-xs">
                            <span class="px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-[#232327] border border-zinc-200 dark:border-[#2B2B30] font-mono font-bold text-[11px] text-zinc-800 dark:text-[#EDEDF0]">
                                {{ $journal->schedule->schoolClass->class_name }}
                            </span>
                            <span class="font-bold text-zinc-900 dark:text-[#EDEDF0]">
                                {{ $journal->schedule->subject ?? 'Mata Pelajaran' }}
                            </span>
                            @if(auth()->user()->role_id !== 2)
                                <span class="text-zinc-400 font-mono text-[11px]">&bull; {{ $journal->schedule->teacher->fullname }}</span>
                            @endif
                        </div>
                        <p class="text-xs text-zinc-400 line-clamp-1">
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
                                    <span class="text-amber-400 font-semibold">Presensi: {{ $absentStd->count() }} Tidak Hadir ({{ $absentStd->pluck('status')->unique()->join(', ') }})</span>
                                @else
                                    <span class="text-emerald-400">Presensi: Semua Hadir (Nihil)</span>
                                @endif
                            @endif
                        </p>
                    </div>
                    <div class="text-right shrink-0 flex items-center gap-3 font-mono text-xs text-zinc-400">
                        <span>{{ $journal->created_at->diffForHumans() }}</span>
                    </div>
                </a>
            @empty
                <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-10 text-center">
                    <p class="text-xs font-medium text-zinc-600 dark:text-[#97979B]">Belum ada rekaman jurnal KBM yang tersimpan untuk filter saat ini.</p>
                    @if(Auth::user()->role_id === 2)
                        <div class="mt-3">
                            <a href="{{ route('journals.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-[#0093dd] hover:bg-[#0082c4] rounded-lg transition">
                                Isi Jurnal Baru
                            </a>
                        </div>
                    @endif
                </div>
            @endforelse
        </div>

        <div class="pt-2 font-mono text-xs">
            {{ $journals->links() }}
        </div>

    </div>
</x-app-layout>