<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="text-xs font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider">Kurikulum &bullet; Monitoring</div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0] mt-0.5">
                    Monitoring Tukar Jadwal Guru
                </h1>
            </div>
            <div>
                <a href="{{ route('schedules.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white bg-zinc-100 dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 transition">
                    &larr; Kembali ke Jadwal
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6 max-w-6xl" x-data="{
        statusFilter: 'all',
        searchQuery: '',
        matches(swap) {
            const matchesStatus = this.statusFilter === 'all' || swap.status === this.statusFilter;
            const q = this.searchQuery.toLowerCase().trim();
            if (!q) return matchesStatus;
            const matchesSearch = (swap.requester || '').toLowerCase().includes(q) ||
                                  (swap.target || '').toLowerCase().includes(q) ||
                                  (swap.subject || '').toLowerCase().includes(q) ||
                                  (swap.className || '').toLowerCase().includes(q) ||
                                  (swap.date || '').includes(q);
            return matchesStatus && matchesSearch;
        }
    }">

        @if($isScheduleLocked)
            <div class="p-4 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-xl flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <div class="text-xs text-amber-900 dark:text-amber-200">
                    <span class="font-bold">Jadwal Sedang Dikunci:</span> Guru tidak dapat mengajukan atau menyetujui pertukaran jadwal baru saat jadwal dalam status terkunci oleh Waka Kurikulum.
                </div>
            </div>
        @endif

        {{-- Filter & Search Header --}}
        <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200/80 dark:border-[#2B2B30] rounded-xl p-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
                <button 
                    type="button"
                    @click="statusFilter = 'all'"
                    :class="statusFilter === 'all' ? 'bg-[#0093dd] text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition shrink-0"
                >
                    Semua ({{ $swaps->count() }})
                </button>
                <button 
                    type="button"
                    @click="statusFilter = 'pending'"
                    :class="statusFilter === 'pending' ? 'bg-amber-500 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition shrink-0"
                >
                    Menunggu ({{ $swaps->where('status', 'pending')->count() }})
                </button>
                <button 
                    type="button"
                    @click="statusFilter = 'approved'"
                    :class="statusFilter === 'approved' ? 'bg-emerald-600 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition shrink-0"
                >
                    Disetujui ({{ $swaps->where('status', 'approved')->count() }})
                </button>
                <button 
                    type="button"
                    @click="statusFilter = 'rejected'"
                    :class="statusFilter === 'rejected' ? 'bg-rose-600 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition shrink-0"
                >
                    Ditolak ({{ $swaps->where('status', 'rejected')->count() }})
                </button>
                <button 
                    type="button"
                    @click="statusFilter = 'cancelled'"
                    :class="statusFilter === 'cancelled' ? 'bg-zinc-600 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition shrink-0"
                >
                    Dibatalkan / Kadaluarsa ({{ $swaps->whereIn('status', ['cancelled', 'expired'])->count() }})
                </button>
            </div>

            <div class="relative sm:w-64">
                <input 
                    type="text" 
                    x-model="searchQuery" 
                    placeholder="Cari guru, kelas, mapel..." 
                    class="w-full text-xs rounded-lg border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800/80 text-zinc-900 dark:text-white px-3 py-2 pl-8 focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd]"
                />
                <svg class="w-4 h-4 text-zinc-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
            </div>
        </div>

        {{-- Monitoring Table --}}
        <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200/80 dark:border-[#2B2B30] rounded-xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-zinc-600 dark:text-zinc-400 border-b border-zinc-200/80 dark:border-[#2B2B30] font-semibold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3 px-4">Tanggal & Putaran</th>
                            <th class="py-3 px-4">Guru Pemohon &bullet; Jadwal Asli</th>
                            <th class="py-3 px-4">Guru Target &bullet; Jadwal Pengganti</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200/70 dark:divide-zinc-800">
                        @forelse($swaps as $swap)
                            @php
                                $statusBadgeClass = match($swap->status) {
                                    'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800',
                                    'pending' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800',
                                    'rejected' => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800',
                                    'cancelled' => 'bg-zinc-100 text-zinc-700 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700',
                                    'expired' => 'bg-zinc-100 text-zinc-500 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-500 dark:border-zinc-700',
                                    default => 'bg-zinc-100 text-zinc-700'
                                };
                                $statusLabel = match($swap->status) {
                                    'approved' => 'Disetujui',
                                    'pending' => 'Menunggu',
                                    'rejected' => 'Ditolak',
                                    'cancelled' => 'Dibatalkan',
                                    'expired' => 'Kadaluarsa',
                                    default => ucfirst($swap->status)
                                };
                            @endphp
                            <tr 
                                x-show="matches({
                                    status: '{{ $swap->status }}',
                                    requester: '{{ addslashes($swap->requester?->fullname ?? '') }}',
                                    target: '{{ addslashes($swap->target?->fullname ?? '') }}',
                                    subject: '{{ addslashes($swap->requesterSchedule?->subject ?? '') }}',
                                    className: '{{ addslashes($swap->requesterSchedule?->schoolClass?->class_name ?? '') }}',
                                    date: '{{ $swap->swap_date->format('Y-m-d') }}'
                                })"
                                class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition"
                            >
                                <td class="py-3 px-4 font-mono align-top whitespace-nowrap">
                                    <div class="font-bold text-zinc-900 dark:text-white">
                                        {{ $swap->swap_date->translatedFormat('d M Y') }}
                                    </div>
                                    <div class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                        {{ $swap->swap_date->translatedFormat('l') }} &bull; {{ $swap->cycle }}
                                    </div>
                                    <div class="text-[10px] text-zinc-400 mt-1">
                                        Diajukan: {{ $swap->created_at->format('d/m H:i') }}
                                    </div>
                                </td>
                                <td class="py-3 px-4 align-top">
                                    <div class="font-bold text-zinc-900 dark:text-white">
                                        {{ $swap->requester?->fullname ?? 'Guru #' . $swap->requester_id }}
                                    </div>
                                    @if($swap->requesterSchedule)
                                        <div class="text-zinc-700 dark:text-zinc-300 mt-0.5">
                                            <span class="font-semibold">{{ $swap->requesterSchedule->subject }}</span> &bull; 
                                            {{ $swap->requesterSchedule->schoolClass?->class_name }}
                                        </div>
                                        <div class="text-[11px] font-mono text-zinc-500 dark:text-zinc-400">
                                            {{ \Carbon\Carbon::parse($swap->requesterSchedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($swap->requesterSchedule->end_time)->format('H:i') }}
                                            (Ruang: {{ $swap->requesterSchedule->room?->room_name ?? '-' }})
                                        </div>
                                    @else
                                        <div class="text-xs text-zinc-400 italic">Jadwal tidak ditemukan</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 align-top">
                                    <div class="font-bold text-zinc-900 dark:text-white">
                                        {{ $swap->target?->fullname ?? 'Guru #' . $swap->target_id }}
                                    </div>
                                    @if($swap->targetSchedule)
                                        <div class="text-zinc-700 dark:text-zinc-300 mt-0.5">
                                            <span class="font-semibold">{{ $swap->targetSchedule->subject }}</span> &bull; 
                                            {{ $swap->targetSchedule->schoolClass?->class_name }}
                                        </div>
                                        <div class="text-[11px] font-mono text-zinc-500 dark:text-zinc-400">
                                            {{ \Carbon\Carbon::parse($swap->targetSchedule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($swap->targetSchedule->end_time)->format('H:i') }}
                                            (Ruang: {{ $swap->targetSchedule->room?->room_name ?? '-' }})
                                        </div>
                                    @else
                                        <div class="text-xs text-zinc-400 italic">Jadwal tidak ditemukan</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 align-top whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border {{ $statusBadgeClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                    @if($swap->responded_at)
                                        <div class="text-[10px] text-zinc-400 mt-1 font-mono">
                                            Respon: {{ $swap->responded_at->format('d/m H:i') }}
                                        </div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 align-top max-w-xs text-zinc-600 dark:text-zinc-300">
                                    {{ $swap->note ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-zinc-400 dark:text-zinc-500">
                                    Belum ada data pertukaran jadwal guru.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
