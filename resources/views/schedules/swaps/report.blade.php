<x-app-layout>
    <x-slot name="header">
        <x-page-header 
            title="Laporan Tukar Jadwal" 
            subtitle="Audit trail, rekapitulasi, dan monitoring pertukaran jadwal mengajar guru"
        >
            <a 
                href="{{ route('schedule-swaps.export-csv', request()->query()) }}" 
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-zinc-700 dark:text-zinc-200 bg-white dark:bg-[#1E1E22] rounded-lg border border-zinc-200 dark:border-[#2B2B30] hover:bg-zinc-50 dark:hover:bg-[#26262A] transition"
            >
                <svg class="w-3.5 h-3.5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 12L12 16.5m0 0L16.5 12M12 16.5V3" />
                </svg>
                <span>Ekspor CSV</span>
            </a>
            <a 
                href="{{ route('schedule-swaps.print', request()->query()) }}" 
                target="_blank"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-zinc-700 dark:text-zinc-200 bg-white dark:bg-[#1E1E22] rounded-lg border border-zinc-200 dark:border-[#2B2B30] hover:bg-zinc-50 dark:hover:bg-[#26262A] transition"
            >
                <svg class="w-3.5 h-3.5 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.656h10.5z" />
                </svg>
                <span>Cetak / PDF</span>
            </a>
            <a 
                href="{{ route('schedules.index') }}" 
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white bg-zinc-100 dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 transition"
            >
                &larr; Kembali ke Jadwal Pelajaran
            </a>
        </x-page-header>
    </x-slot>

    <div 
        class="space-y-6" 
        x-data="{
            activeTab: '{{ $activeTab ?? 'swaps' }}',
            drawerOpen: false,
            activeSwap: null,
            openDrawer(swap) {
                this.activeSwap = swap;
                this.drawerOpen = true;
            },
            closeDrawer() {
                this.drawerOpen = false;
                this.activeSwap = null;
            }
        }"
    >
        {{-- 1. Summary Strip (4 Equal Columns) --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-4 sm:p-5 shadow-xs">
                <div class="flex items-center justify-between text-zinc-500 dark:text-zinc-400">
                    <span class="text-xs font-semibold uppercase tracking-wider">Total Pengajuan</span>
                    <div class="w-8 h-8 rounded-lg bg-sky-50 dark:bg-[#16222F] text-sky-800 dark:text-sky-300 border border-sky-200 dark:border-sky-700/60 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-bold font-mono text-zinc-900 dark:text-white">
                    {{ $summary['total'] }}
                </div>
                <div class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1">
                    Seluruh permohonan periode ini
                </div>
            </div>

            <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-4 sm:p-5 shadow-xs">
                <div class="flex items-center justify-between text-zinc-500 dark:text-zinc-400">
                    <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Disetujui</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 dark:bg-[#16281D] text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700/60 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-bold font-mono text-emerald-600 dark:text-emerald-400">
                    {{ $summary['approved'] }}
                </div>
                <div class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1">
                    Jadwal efektif beralih ke guru pengganti
                </div>
            </div>

            <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-4 sm:p-5 shadow-xs">
                <div class="flex items-center justify-between text-zinc-500 dark:text-zinc-400">
                    <span class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-400">Menunggu</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-[#282115] text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-700/60 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-bold font-mono text-amber-600 dark:text-amber-400">
                    {{ $summary['pending'] }}
                </div>
                <div class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1">
                    Menunggu persetujuan guru target
                </div>
            </div>

            <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-4 sm:p-5 shadow-xs">
                <div class="flex items-center justify-between text-zinc-500 dark:text-zinc-400">
                    <span class="text-xs font-semibold uppercase tracking-wider text-rose-700 dark:text-rose-400">Ditolak / Batal / Expired</span>
                    <div class="w-8 h-8 rounded-lg bg-rose-50 dark:bg-[#2D1618] text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-700/60 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                </div>
                <div class="mt-2 text-2xl font-bold font-mono text-rose-600 dark:text-rose-400">
                    {{ $summary['rejected_cancelled_expired'] }}
                </div>
                <div class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1">
                    Tidak terlaksana atau kedaluwarsa
                </div>
            </div>
        </div>

        {{-- 2. Navigation Tabs --}}
        <div class="flex items-center border-b border-zinc-200 dark:border-[#2B2B30] gap-4">
            <button 
                type="button" 
                @click="activeTab = 'swaps'"
                :class="activeTab === 'swaps' ? 'border-[#0093dd] text-[#0093dd] dark:text-[#0093dd]' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'"
                class="py-3 px-1 border-b-2 font-medium text-xs sm:text-sm transition flex items-center gap-2"
            >
                <span>Daftar Tukar Jadwal</span>
                <span class="px-1.5 py-0.5 text-[10px] font-mono rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                    {{ $swaps->total() }}
                </span>
            </button>
            <button 
                type="button" 
                @click="activeTab = 'teachers'"
                :class="activeTab === 'teachers' ? 'border-[#0093dd] text-[#0093dd] dark:text-[#0093dd]' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'"
                class="py-3 px-1 border-b-2 font-medium text-xs sm:text-sm transition flex items-center gap-2"
            >
                <span>Rekap per Guru</span>
                <span class="px-1.5 py-0.5 text-[10px] font-mono rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                    {{ $teacherRecap->count() }}
                </span>
            </button>
            <button 
                type="button" 
                @click="activeTab = 'logs'"
                :class="activeTab === 'logs' ? 'border-[#0093dd] text-[#0093dd] dark:text-[#0093dd]' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'"
                class="py-3 px-1 border-b-2 font-medium text-xs sm:text-sm transition flex items-center gap-2"
            >
                <span>Log Aktivitas</span>
                <span class="px-1.5 py-0.5 text-[10px] font-mono rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                    {{ $activityLogs->total() }}
                </span>
            </button>
        </div>

        {{-- 3. Filter Toolbar --}}
        <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-4 shadow-xs">
            <form method="GET" action="{{ route('schedule-swaps.index') }}" class="space-y-3">
                <input type="hidden" name="tab" :value="activeTab">

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
                    {{-- Start Date --}}
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Mulai Tanggal</label>
                        <input 
                            type="date" 
                            name="start_date" 
                            value="{{ $activeFilters['start_date'] }}"
                            class="w-full text-xs rounded-lg border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800/80 text-zinc-900 dark:text-white px-2.5 py-1.5 focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd]"
                        >
                    </div>

                    {{-- End Date --}}
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Sampai Tanggal</label>
                        <input 
                            type="date" 
                            name="end_date" 
                            value="{{ $activeFilters['end_date'] }}"
                            class="w-full text-xs rounded-lg border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800/80 text-zinc-900 dark:text-white px-2.5 py-1.5 focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd]"
                        >
                    </div>

                    {{-- Status --}}
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Status</label>
                        <select 
                            name="status" 
                            class="w-full text-xs rounded-lg border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800/80 text-zinc-900 dark:text-white px-2.5 py-1.5 focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd]"
                        >
                            <option value="all" {{ ($activeFilters['status'] ?? 'all') === 'all' ? 'selected' : '' }}>Semua Status</option>
                            <option value="approved" {{ ($activeFilters['status'] ?? '') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                            <option value="pending" {{ ($activeFilters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Menunggu</option>
                            <option value="rejected" {{ ($activeFilters['status'] ?? '') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                            <option value="cancelled" {{ ($activeFilters['status'] ?? '') === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                            <option value="expired" {{ ($activeFilters['status'] ?? '') === 'expired' ? 'selected' : '' }}>Kedaluwarsa</option>
                        </select>
                    </div>

                    {{-- Teacher --}}
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Guru</label>
                        <select 
                            name="teacher_id" 
                            class="w-full text-xs rounded-lg border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800/80 text-zinc-900 dark:text-white px-2.5 py-1.5 focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd]"
                        >
                            <option value="">Semua Guru</option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ (string)($activeFilters['teacher_id'] ?? '') === (string)$t->id ? 'selected' : '' }}>
                                    {{ $t->fullname }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Class --}}
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Kelas</label>
                        <select 
                            name="class_id" 
                            class="w-full text-xs rounded-lg border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800/80 text-zinc-900 dark:text-white px-2.5 py-1.5 focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd]"
                        >
                            <option value="">Semua Kelas</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ (string)($activeFilters['class_id'] ?? '') === (string)$c->id ? 'selected' : '' }}>
                                    {{ $c->class_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Putaran / Cycle --}}
                    <div>
                        <label class="block text-[11px] font-semibold text-zinc-600 dark:text-zinc-400 mb-1">Putaran</label>
                        <select 
                            name="cycle" 
                            class="w-full text-xs rounded-lg border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800/80 text-zinc-900 dark:text-white px-2.5 py-1.5 focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd]"
                        >
                            <option value="all" {{ ($activeFilters['cycle'] ?? '') === 'all' ? 'selected' : '' }}>Semua Putaran</option>
                            <option value="Putaran 1" {{ ($activeFilters['cycle'] ?? '') === 'Putaran 1' ? 'selected' : '' }}>Putaran 1</option>
                            <option value="Putaran 2" {{ ($activeFilters['cycle'] ?? '') === 'Putaran 2' ? 'selected' : '' }}>Putaran 2</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                    <div class="relative flex-1">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ $activeFilters['search'] ?? '' }}"
                            placeholder="Cari guru, mata pelajaran, atau catatan..." 
                            class="w-full text-xs rounded-lg border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800/80 text-zinc-900 dark:text-white px-3 py-1.5 pl-8 focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd]"
                        />
                        <svg class="w-3.5 h-3.5 text-zinc-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                    </div>

                    <div class="flex items-center gap-2">
                        <a 
                            href="{{ route('schedule-swaps.index') }}" 
                            class="px-3 py-1.5 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white bg-zinc-100 dark:bg-zinc-800 rounded-lg transition text-center"
                        >
                            Reset
                        </a>
                        <button 
                            type="submit" 
                            class="px-4 py-1.5 text-xs font-semibold text-white bg-[#0093dd] hover:bg-[#0082c4] rounded-lg transition text-center shadow-xs"
                        >
                            Terapkan Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- TAB 1: DAFTAR TUKAR JADWAL --}}
        <div x-show="activeTab === 'swaps'" class="space-y-4">
            <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-zinc-600 dark:text-zinc-400 border-b border-zinc-200 dark:border-[#2B2B30] font-semibold uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="py-3 px-4">Tanggal &amp; Putaran</th>
                                <th class="py-3 px-4">Guru Pemohon &amp; Jadwal</th>
                                <th class="py-3 px-4">Guru Target &amp; Jadwal</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Jurnal Terisi</th>
                                <th class="py-3 px-4">Diajukan</th>
                                <th class="py-3 px-4">Diputuskan</th>
                                <th class="py-3 px-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse($swaps as $swap)
                                @php
                                    $statusBadgeClass = match($swap->status) {
                                        'approved'  => 'bg-emerald-50 text-emerald-800 border-emerald-300 dark:bg-[#16281D] dark:text-emerald-300 dark:border-emerald-700/60',
                                        'pending'   => 'bg-amber-50 text-amber-800 border-amber-300 dark:bg-[#282115] dark:text-amber-300 dark:border-amber-700/60',
                                        'rejected'  => 'bg-rose-50 text-rose-800 border-rose-300 dark:bg-[#2D1618] dark:text-rose-300 dark:border-rose-700/60',
                                        'cancelled' => 'bg-zinc-100 text-zinc-700 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700',
                                        'expired'   => 'bg-zinc-100 text-zinc-500 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-500 dark:border-zinc-700',
                                        default     => 'bg-zinc-100 dark:bg-[#232327] text-zinc-700 dark:text-[#EDEDF0] border border-zinc-200 dark:border-[#2B2B30]',
                                    };
                                    $statusLabel = match($swap->status) {
                                        'approved'  => 'Disetujui',
                                        'pending'   => 'Menunggu',
                                        'rejected'  => 'Ditolak',
                                        'cancelled' => 'Dibatalkan',
                                        'expired'   => 'Kedaluwarsa',
                                        default     => ucfirst($swap->status),
                                    };
                                    $journalFilled = $swap->isJournalFilled();
                                @endphp
                                <tr 
                                    class="hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40 transition cursor-pointer"
                                    @click="openDrawer({{ json_encode((new \App\Http\Resources\Api\V1\ScheduleSwapResource($swap))->resolve()) }})"
                                >
                                    <td class="py-3 px-4 align-top whitespace-nowrap">
                                        <div class="font-bold text-zinc-900 dark:text-white">
                                            {{ $swap->swap_date?->translatedFormat('d M Y') }}
                                        </div>
                                        <div class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                            {{ $swap->swap_date?->translatedFormat('l') }} &bull; {{ $swap->cycle }}
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
                                                {{ substr($swap->requesterSchedule->start_time, 0, 5) }} - {{ substr($swap->requesterSchedule->end_time, 0, 5) }}
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
                                                {{ substr($swap->targetSchedule->start_time, 0, 5) }} - {{ substr($swap->targetSchedule->end_time, 0, 5) }}
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
                                    </td>
                                    <td class="py-3 px-4 align-top whitespace-nowrap">
                                        @if($swap->status === 'approved')
                                            @if($journalFilled)
                                                <a 
                                                    href="{{ route('journals.index', ['date' => $swap->swap_date?->format('Y-m-d')]) }}" 
                                                    @click.stop
                                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800 hover:underline"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                                                    </svg>
                                                    <span>Sudah Diisi</span>
                                                </a>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800">
                                                    <span>Belum Diisi</span>
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-zinc-400">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 align-top whitespace-nowrap font-mono text-[11px] text-zinc-500 dark:text-zinc-400">
                                        {{ $swap->created_at?->timezone('Asia/Jakarta')->format('d/m H:i') }}
                                    </td>
                                    <td class="py-3 px-4 align-top whitespace-nowrap font-mono text-[11px] text-zinc-500 dark:text-zinc-400">
                                        {{ $swap->responded_at ? $swap->responded_at->timezone('Asia/Jakarta')->format('d/m H:i') : '-' }}
                                    </td>
                                    <td class="py-3 px-4 align-top text-right whitespace-nowrap">
                                        <button 
                                            type="button" 
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white bg-zinc-100 dark:bg-zinc-800 rounded-md transition"
                                            @click.stop="openDrawer({{ json_encode((new \App\Http\Resources\Api\V1\ScheduleSwapResource($swap))->resolve()) }})"
                                        >
                                            Detail &rarr;
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-12 text-center text-zinc-400 dark:text-zinc-500">
                                        <svg class="w-8 h-8 mx-auto text-zinc-300 dark:text-zinc-600 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                        </svg>
                                        Tidak ada data pertukaran jadwal yang sesuai dengan filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($swaps->hasPages())
                    <div class="p-4 border-t border-zinc-200 dark:border-[#2B2B30]">
                        {{ $swaps->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- TAB 2: REKAP PER GURU --}}
        <div x-show="activeTab === 'teachers'" class="space-y-4">
            <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl overflow-hidden shadow-xs">
                <div class="p-4 border-b border-zinc-200 dark:border-[#2B2B30] flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-zinc-900 dark:text-white">Rekapitulasi Pertukaran Jadwal per Guru</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Total aktivitas pengajuan dan penerimaan pertukaran jadwal periode ini</p>
                    </div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400 font-mono">
                        {{ $teacherRecap->count() }} Guru Terdaftar
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-zinc-600 dark:text-zinc-400 border-b border-zinc-200 dark:border-[#2B2B30] font-semibold uppercase tracking-wider text-[11px]">
                            <tr>
                                <th class="py-3 px-4 w-12 text-center">Rank</th>
                                <th class="py-3 px-4">Nama Guru</th>
                                <th class="py-3 px-4 text-center">Diajukan</th>
                                <th class="py-3 px-4 text-center">Diterima</th>
                                <th class="py-3 px-4 text-center">Disetujui</th>
                                <th class="py-3 px-4 text-center">Ditolak</th>
                                <th class="py-3 px-4 text-center">Total Aktivitas</th>
                                <th class="py-3 px-4">Tukar Terakhir</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse($teacherRecap as $index => $recap)
                                <tr class="{{ $recap['is_top_5'] ? 'bg-amber-50/20 dark:bg-amber-950/10' : '' }} hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40 transition">
                                    <td class="py-3 px-4 text-center font-mono font-bold">
                                        @if($recap['is_top_5'])
                                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 text-[10px]">
                                                #{{ $index + 1 }}
                                            </span>
                                        @else
                                            <span class="text-zinc-400">#{{ $index + 1 }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                            <span>{{ $recap['teacher']->fullname }}</span>
                                            @if($recap['is_top_5'])
                                                <span class="px-1.5 py-0.2 text-[9px] font-bold rounded bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50">
                                                    Top 5 Aktif
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-zinc-500 dark:text-zinc-400 font-mono">
                                            NIP / User: {{ $recap['teacher']->username }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono font-semibold text-zinc-700 dark:text-zinc-300">
                                        {{ $recap['requested_count'] }}
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono font-semibold text-zinc-700 dark:text-zinc-300">
                                        {{ $recap['received_count'] }}
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                        {{ $recap['approved_count'] }}
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono font-semibold text-rose-600 dark:text-rose-400">
                                        {{ $recap['rejected_count'] }}
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono font-bold text-zinc-900 dark:text-white">
                                        {{ $recap['total_swaps'] }}
                                    </td>
                                    <td class="py-3 px-4 font-mono text-[11px] text-zinc-500 dark:text-zinc-400">
                                        {{ $recap['last_swap_date'] ? \Carbon\Carbon::parse($recap['last_swap_date'])->translatedFormat('d M Y') : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-zinc-400 dark:text-zinc-500">
                                        Tidak ada data guru.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- TAB 3: LOG AKTIVITAS (AUDIT TRAIL) --}}
        <div x-show="activeTab === 'logs'" class="space-y-4">
            <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl overflow-hidden shadow-xs">
                <div class="p-4 border-b border-zinc-200 dark:border-[#2B2B30]">
                    <h2 class="text-sm font-bold text-zinc-900 dark:text-white">Audit Trail &amp; Log Perubahan Status</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Catatan kronologis immutable setiap event pertukaran jadwal beserta alasan dan snapshot data</p>
                </div>

                <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse($activityLogs as $log)
                        @php
                            $eventBadgeClass = match($log->event) {
                                'requested'        => 'bg-sky-50 text-sky-800 border-sky-300 dark:bg-[#16222F] dark:text-sky-300 dark:border-sky-700/60',
                                'approved'         => 'bg-emerald-50 text-emerald-800 border-emerald-300 dark:bg-[#16281D] dark:text-emerald-300 dark:border-emerald-700/60',
                                'rejected'         => 'bg-rose-50 text-rose-800 border-rose-300 dark:bg-[#2D1618] dark:text-rose-300 dark:border-rose-700/60',
                                'cancelled'        => 'bg-zinc-100 text-zinc-700 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:border-zinc-700',
                                'expired'          => 'bg-amber-50 text-amber-800 border-amber-300 dark:bg-[#282115] dark:text-amber-300 dark:border-amber-700/60',
                                'conflict_blocked' => 'bg-purple-50 text-purple-800 border-purple-300 dark:bg-[#25182D] dark:text-purple-300 dark:border-purple-700/60',
                                default            => 'bg-zinc-100 dark:bg-[#232327] text-zinc-700 dark:text-[#EDEDF0] border border-zinc-200 dark:border-[#2B2B30]',
                            };
                            $eventLabel = match($log->event) {
                                'requested'        => 'Permintaan Diajukan',
                                'approved'         => 'Disetujui',
                                'rejected'         => 'Ditolak',
                                'cancelled'        => 'Dibatalkan',
                                'expired'          => 'Kedaluwarsa Otomatis',
                                'conflict_blocked' => 'Gagal: Bentrok Terdeteksi',
                                default            => ucfirst($log->event),
                            };
                        @endphp
                        <div class="p-4 hover:bg-zinc-50/60 dark:hover:bg-zinc-800/40 transition">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border {{ $eventBadgeClass }}">
                                        {{ $eventLabel }}
                                    </span>
                                    <span class="font-bold text-xs text-zinc-900 dark:text-white">
                                        Swap #{{ $log->swap_id }}
                                    </span>
                                    <span class="text-zinc-400 dark:text-zinc-500">&bull;</span>
                                    <span class="text-xs text-zinc-600 dark:text-zinc-300">
                                        {{ $log->actor?->fullname ?? 'Sistem Otomatis' }} ({{ ucfirst($log->actor_role) }})
                                    </span>
                                </div>
                                <div class="font-mono text-[11px] text-zinc-500 dark:text-zinc-400">
                                    {{ $log->created_at?->timezone('Asia/Jakarta')->translatedFormat('d M Y H:i:s') }} WIB
                                </div>
                            </div>

                            @if($log->message)
                                <div class="mt-2 text-xs text-zinc-700 dark:text-zinc-300 bg-zinc-50 dark:bg-zinc-800/50 p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700/60 font-mono">
                                    {{ $log->message }}
                                </div>
                            @endif

                            @if($log->snapshot)
                                <div class="mt-2 text-[11px] text-zinc-500 dark:text-zinc-400 flex flex-wrap items-center gap-x-4 gap-y-1">
                                    <span>Tanggal: <strong class="text-zinc-700 dark:text-zinc-300">{{ $log->snapshot['swap_date'] ?? '-' }}</strong></span>
                                    <span>Putaran: <strong class="text-zinc-700 dark:text-zinc-300">{{ $log->snapshot['cycle'] ?? '-' }}</strong></span>
                                    <span>Pemohon: <strong class="text-zinc-700 dark:text-zinc-300">{{ $log->snapshot['requester']['name'] ?? '-' }}</strong></span>
                                    <span>Target: <strong class="text-zinc-700 dark:text-zinc-300">{{ $log->snapshot['target']['name'] ?? '-' }}</strong></span>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="py-12 text-center text-zinc-400 dark:text-zinc-500">
                            Belum ada riwayat log aktivitas pertukaran jadwal.
                        </div>
                    @endforelse
                </div>

                @if($activityLogs->hasPages())
                    <div class="p-4 border-t border-zinc-200 dark:border-[#2B2B30]">
                        {{ $activityLogs->links() }}
                    </div>
                @endif
            </div>
        </div>

        {{-- DETAIL DRAWER (SLIDE-OVER) --}}
        <div 
            x-show="drawerOpen" 
            style="display: none;"
            class="fixed inset-0 z-50 overflow-hidden" 
            aria-labelledby="slide-over-title" 
            role="dialog" 
            aria-modal="true"
        >
            <div class="absolute inset-0 overflow-hidden">
                {{-- Backdrop --}}
                <div 
                    x-show="drawerOpen"
                    x-transition:enter="ease-in-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in-out duration-300"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="absolute inset-0 bg-zinc-950/60 backdrop-blur-xs transition-opacity" 
                    @click="closeDrawer()"
                ></div>

                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div 
                        x-show="drawerOpen"
                        x-transition:enter="transform transition ease-in-out duration-300"
                        x-transition:enter-start="translate-x-full"
                        x-transition:enter-end="translate-x-0"
                        x-transition:leave="transform transition ease-in-out duration-300"
                        x-transition:leave-start="translate-x-0"
                        x-transition:leave-end="translate-x-full"
                        class="pointer-events-auto w-screen max-w-md bg-white dark:bg-[#1E1E22] border-l border-zinc-200 dark:border-[#2B2B30] shadow-2xl flex flex-col"
                    >
                        {{-- Drawer Header --}}
                        <div class="p-5 border-b border-zinc-200 dark:border-[#2B2B30] flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                    <span>Detail Pertukaran Jadwal</span>
                                    <span class="text-xs font-mono px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300" x-text="activeSwap ? '#' + activeSwap.id : ''"></span>
                                </h2>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5" x-text="activeSwap ? activeSwap.swap_date + ' • ' + activeSwap.cycle : ''"></p>
                            </div>
                            <button 
                                type="button" 
                                @click="closeDrawer()"
                                class="p-1 rounded-lg text-zinc-400 hover:text-zinc-600 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-800 transition"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Drawer Body --}}
                        <div class="flex-1 overflow-y-auto p-5 space-y-5 text-xs">
                            <template x-if="activeSwap">
                                <div class="space-y-5">
                                    {{-- Status Banner --}}
                                    <div class="p-3 rounded-lg border flex items-center justify-between"
                                        :class="{
                                            'bg-emerald-50 dark:bg-[#16281D] border-emerald-300 dark:border-emerald-700/60 text-emerald-800 dark:text-emerald-300': activeSwap.status === 'approved',
                                            'bg-amber-50 dark:bg-[#282115] border-amber-300 dark:border-amber-700/60 text-amber-800 dark:text-amber-300': activeSwap.status === 'pending',
                                            'bg-rose-50 dark:bg-[#2D1618] border-rose-300 dark:border-rose-700/60 text-rose-800 dark:text-rose-300': activeSwap.status === 'rejected',
                                            'bg-zinc-100 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 text-zinc-800 dark:text-zinc-300': activeSwap.status === 'cancelled' || activeSwap.status === 'expired'
                                        }"
                                    >
                                        <div class="flex items-center gap-2 font-bold uppercase tracking-wider text-[11px]">
                                            <span>Status:</span>
                                            <span x-text="activeSwap.status === 'approved' ? 'Disetujui' : (activeSwap.status === 'pending' ? 'Menunggu' : (activeSwap.status === 'rejected' ? 'Ditolak' : (activeSwap.status === 'cancelled' ? 'Dibatalkan' : 'Kedaluwarsa')))"></span>
                                        </div>
                                        <div class="font-mono text-[11px]" x-text="activeSwap.responded_at ? 'Respon: ' + activeSwap.responded_at.substring(0, 10) : ''"></div>
                                    </div>

                                    {{-- Jurnal Pengganti Info --}}
                                    <div class="p-3.5 bg-zinc-50 dark:bg-zinc-800/40 rounded-xl border border-zinc-200 dark:border-zinc-700/60 space-y-2">
                                        <div class="font-semibold text-zinc-900 dark:text-white flex items-center justify-between">
                                            <span>Pengisian Jurnal KBM (Guru Pengganti)</span>
                                            <span 
                                                class="px-2 py-0.5 rounded text-[10px] font-bold"
                                                :class="activeSwap.journal_filled ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'"
                                                x-text="activeSwap.journal_filled ? 'Sudah Diisi' : 'Belum Diisi'"
                                            ></span>
                                        </div>
                                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                            Ketika pertukaran disetujui, guru pengganti berkewajiban mengisi jurnal KBM untuk jam yang diambil.
                                        </p>
                                    </div>

                                    {{-- Visual Snapshot --}}
                                    <div class="space-y-3">
                                        <div class="font-bold text-zinc-900 dark:text-white uppercase tracking-wider text-[11px]">
                                            Rincian Jadwal yang Ditukar
                                        </div>

                                        {{-- Requester Side --}}
                                        <div class="p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60">
                                            <div class="text-[10px] font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">
                                                Guru Pemohon
                                            </div>
                                            <div class="font-bold text-zinc-900 dark:text-white mt-0.5" x-text="activeSwap.requester_name"></div>
                                            <template x-if="activeSwap.requester_schedule">
                                                <div class="mt-1 text-zinc-600 dark:text-zinc-300 space-y-0.5">
                                                    <div><strong x-text="activeSwap.requester_schedule.subject"></strong> &bull; <span x-text="activeSwap.requester_schedule.class_name"></span></div>
                                                    <div class="font-mono text-[11px] text-zinc-500">
                                                        <span x-text="activeSwap.requester_schedule.day_of_week"></span>, 
                                                        <span x-text="activeSwap.requester_schedule.start_time + ' - ' + activeSwap.requester_schedule.end_time"></span>
                                                        (<span x-text="activeSwap.requester_schedule.room_name || 'Ruang -'"></span>)
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                        <div class="flex justify-center text-zinc-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                                            </svg>
                                        </div>

                                        {{-- Target Side --}}
                                        <div class="p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60">
                                            <div class="text-[10px] font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">
                                                Guru Pengganti (Target)
                                            </div>
                                            <div class="font-bold text-zinc-900 dark:text-white mt-0.5" x-text="activeSwap.target_name"></div>
                                            <template x-if="activeSwap.target_schedule">
                                                <div class="mt-1 text-zinc-600 dark:text-zinc-300 space-y-0.5">
                                                    <div><strong x-text="activeSwap.target_schedule.subject"></strong> &bull; <span x-text="activeSwap.target_schedule.class_name"></span></div>
                                                    <div class="font-mono text-[11px] text-zinc-500">
                                                        <span x-text="activeSwap.target_schedule.day_of_week"></span>, 
                                                        <span x-text="activeSwap.target_schedule.start_time + ' - ' + activeSwap.target_schedule.end_time"></span>
                                                        (<span x-text="activeSwap.target_schedule.room_name || 'Ruang -'"></span>)
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Note --}}
                                    <div class="p-3 bg-zinc-50 dark:bg-zinc-800/40 rounded-lg border border-zinc-200 dark:border-zinc-700/60">
                                        <div class="text-[10px] font-semibold text-zinc-400 uppercase tracking-wider">Catatan Pemohon:</div>
                                        <div class="mt-1 text-zinc-700 dark:text-zinc-300 italic" x-text="activeSwap.note || 'Tidak ada catatan.'"></div>
                                    </div>

                                    {{-- Timeline of Events --}}
                                    <template x-if="activeSwap.logs && activeSwap.logs.length > 0">
                                        <div class="space-y-3 pt-2">
                                            <div class="font-bold text-zinc-900 dark:text-white uppercase tracking-wider text-[11px]">
                                                Audit Trail Kejadian
                                            </div>

                                            <div class="relative pl-6 space-y-4 before:content-[''] before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-zinc-200 dark:before:bg-zinc-700">
                                                <template x-for="log in activeSwap.logs" :key="log.id">
                                                    <div class="relative">
                                                        <div class="absolute -left-6 top-1 w-2.5 h-2.5 rounded-full border-2 border-white dark:border-[#1E1E22]"
                                                            :class="{
                                                                'bg-[#0093dd]': log.event === 'requested',
                                                                'bg-emerald-500': log.event === 'approved',
                                                                'bg-rose-500': log.event === 'rejected',
                                                                'bg-zinc-500': log.event === 'cancelled' || log.event === 'expired',
                                                                'bg-purple-500': log.event === 'conflict_blocked'
                                                            }"
                                                        ></div>
                                                        <div class="font-semibold text-zinc-900 dark:text-white" x-text="log.event === 'requested' ? 'Permintaan Diajukan' : (log.event === 'approved' ? 'Disetujui' : (log.event === 'rejected' ? 'Ditolak' : (log.event === 'cancelled' ? 'Dibatalkan' : (log.event === 'conflict_blocked' ? 'Bentrok Terdeteksi' : log.event))))"></div>
                                                        <div class="text-[11px] text-zinc-500 font-mono" x-text="log.created_at ? log.created_at.substring(0, 16).replace('T', ' ') : ''"></div>
                                                        <div class="text-[11px] text-zinc-600 dark:text-zinc-400 mt-0.5" x-text="log.actor_name ? log.actor_name + ' (' + log.actor_role + ')' : 'Sistem'"></div>
                                                        <template x-if="log.message">
                                                            <div class="mt-1 text-[11px] text-zinc-700 dark:text-zinc-300 font-mono bg-zinc-100 dark:bg-zinc-800 p-1.5 rounded" x-text="log.message"></div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
