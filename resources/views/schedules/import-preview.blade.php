<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-lg text-zinc-900 dark:text-[#EDEDF0] leading-tight">
                    Preview Import Jadwal Pelajaran ({{ $preview['cycle_type'] }})
                </h2>
                <p class="text-xs text-zinc-500 dark:text-[#97979B]">
                    Verifikasi hasil penggabungan blok jam dan deteksi bentrok sebelum disimpan ke sistem.
                </p>
            </div>
            <a href="{{ route('waka.schedules.import.index') }}" class="px-3 py-1.5 text-xs text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-white bg-zinc-100 dark:bg-[#2C2C30] rounded-lg">
                &larr; Batalkan
            </a>
        </div>
    </x-slot>

    <div class="py-6 space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            <div class="p-3 bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2C2C30] rounded-xl text-center">
                <span class="text-[10px] uppercase font-mono text-zinc-500 dark:text-[#97979B]">Total Slot JP Raw</span>
                <p class="text-lg font-bold text-zinc-900 dark:text-[#EDEDF0]">{{ $preview['summary']['total_slots_raw'] }}</p>
            </div>
            <div class="p-3 bg-white dark:bg-[#1E1E22] border border-sky-500/30 rounded-xl text-center">
                <span class="text-[10px] uppercase font-mono text-sky-400">Jadwal Digabung</span>
                <p class="text-lg font-bold text-sky-400">{{ $preview['summary']['aggregated_total'] }}</p>
            </div>
            <div class="p-3 bg-white dark:bg-[#1E1E22] border border-emerald-500/30 rounded-xl text-center">
                <span class="text-[10px] uppercase font-mono text-emerald-400">Siap Disimpan</span>
                <p class="text-lg font-bold text-emerald-400">{{ $preview['summary']['valid_count'] }}</p>
            </div>
            <div class="p-3 bg-white dark:bg-[#1E1E22] border border-rose-500/30 rounded-xl text-center">
                <span class="text-[10px] uppercase font-mono text-rose-400">Bentrok (Dilewati)</span>
                <p class="text-lg font-bold text-rose-400">{{ $preview['summary']['conflict_count'] }}</p>
            </div>
            <div class="p-3 bg-white dark:bg-[#1E1E22] border border-purple-500/30 rounded-xl text-center">
                <span class="text-[10px] uppercase font-mono text-purple-400">Jadwal Berjurnal Dipertahankan</span>
                <p class="text-lg font-bold text-purple-400">{{ $preview['summary']['preserved_with_journals'] }}</p>
            </div>
        </div>

        <!-- Breakdown per Hari -->
        <div class="p-4 bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2C2C30] rounded-xl">
            <span class="text-xs font-semibold text-zinc-700 dark:text-[#EDEDF0] mb-2 block">Sebaran Jadwal per Hari:</span>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                @foreach($preview['summary']['by_day'] as $day => $count)
                    <div class="px-3 py-1.5 rounded-lg bg-zinc-50 dark:bg-[#252529] border border-zinc-200 dark:border-[#323237] flex items-center justify-between">
                        <span class="text-xs font-mono font-medium text-zinc-600 dark:text-[#97979B]">{{ $day }}</span>
                        <span class="text-xs font-mono font-bold text-zinc-900 dark:text-[#EDEDF0]">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        @if(!empty($preview['summary']['unmapped_teachers']))
            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs text-amber-300">
                <p class="font-bold mb-1">Guru Belum Terpetakan di Database:</p>
                <p class="text-amber-300/80">{{ implode(', ', $preview['summary']['unmapped_teachers']) }}</p>
            </div>
        @endif

        <form action="{{ route('waka.schedules.import.commit') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $preview['token'] }}">

            <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2C2C30] rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-zinc-50 dark:bg-[#252529] border-b border-zinc-200 dark:border-[#2C2C30] text-[10px] uppercase tracking-wider font-mono text-zinc-500 dark:text-[#97979B]">
                                <th class="py-2.5 px-3">No</th>
                                <th class="py-2.5 px-3">Hari</th>
                                <th class="py-2.5 px-3">Jam (JP)</th>
                                <th class="py-2.5 px-3">Rentang Waktu</th>
                                <th class="py-2.5 px-3">Mata Pelajaran</th>
                                <th class="py-2.5 px-3">Kelas</th>
                                <th class="py-2.5 px-3">Guru Pengajar</th>
                                <th class="py-2.5 px-3">Ruangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-[#2C2C30]">
                            @forelse($preview['schedules'] as $idx => $s)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-[#26262B]">
                                    <td class="py-2 px-3 font-mono text-zinc-400">{{ $idx + 1 }}</td>
                                    <td class="py-2 px-3 font-semibold">{{ $s['day_of_week'] }}</td>
                                    <td class="py-2 px-3 font-mono">
                                        <span class="px-2 py-0.5 rounded bg-sky-50 dark:bg-sky-950/40 text-[#0093dd] font-bold">
                                            JP {{ $s['start_period'] }}@if($s['start_period'] !== $s['end_period'])–{{ $s['end_period'] }}@endif
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 font-mono text-zinc-500">
                                        {{ substr($s['start_time'], 0, 5) }} – {{ substr($s['end_time'], 0, 5) }}
                                    </td>
                                    <td class="py-2 px-3 font-bold text-zinc-900 dark:text-[#EDEDF0]">{{ $s['subject'] }}</td>
                                    <td class="py-2 px-3 font-medium text-emerald-400">{{ $s['class']['class_name'] }}</td>
                                    <td class="py-2 px-3 text-zinc-700 dark:text-zinc-300">{{ $s['teacher']['fullname'] }}</td>
                                    <td class="py-2 px-3 text-zinc-500">{{ $s['room']['room_name'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-zinc-400 italic">
                                        Tidak ada jadwal valid yang dapat disimpan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-zinc-50 dark:bg-[#252529] border-t border-zinc-200 dark:border-[#2C2C30] flex items-center justify-between">
                    <p class="text-xs text-zinc-500 dark:text-[#97979B]">
                        Menerapkan jadwal ini akan menggantikan jadwal lama {{ $preview['cycle_type'] }} kecuali jadwal yang sudah memiliki jurnal.
                    </p>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('waka.schedules.import.index') }}" class="px-4 py-2 text-xs text-zinc-600 dark:text-[#97979B] hover:text-white bg-zinc-200 dark:bg-[#323237] rounded-lg">
                            Batal
                        </a>
                        <button type="submit" @if(empty($preview['schedules'])) disabled @endif
                                class="px-5 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 rounded-lg shadow-sm transition-colors">
                            Simpan &amp; Terapkan Jadwal
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
