<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="text-xs font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider">
                    Kurikulum &amp; Kesiswaan
                </div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0] mt-0.5">
                    Hari Tidak Efektif KBM
                </h1>
                <p class="text-xs text-zinc-500 mt-0.5">
                    Tanggal yang ditandai tidak efektif akan membebaskan guru dari kewajiban mengisi jurnal pembelajaran.
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button 
                    type="button" 
                    onclick="window.dispatchEvent(new CustomEvent('toggle-ineffective-modal'))"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-zinc-900 hover:bg-black text-white text-xs font-semibold uppercase tracking-wider rounded-md transition shadow-xs cursor-pointer"
                >
                    <svg class="w-4 h-4 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Tambah Hari Tidak Efektif
                </button>
            </div>
        </div>
    </x-slot>

    <div 
        class="space-y-6 max-w-5xl"
        x-data="{ showModal: false, isRange: false }"
        @toggle-ineffective-modal.window="showModal = true"
    >
        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if(session('error'))
            <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
        @endif

        {{-- STATUS HARI INI CARD --}}
        @if($todayIneffective)
            <div class="bg-amber-50/80 border border-amber-200/80 rounded-lg p-5 flex items-start justify-between gap-4">
                <div class="flex items-start gap-3.5">
                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-amber-500 text-white font-mono text-[10px] font-bold uppercase shrink-0 mt-0.5">
                        HARI INI
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-amber-950">
                            Hari Ini Ditetapkan Sebagai Hari Tidak Efektif KBM
                        </h3>
                        <p class="text-xs text-amber-800 mt-0.5">
                            Keterangan: <span class="font-semibold">{{ $todayIneffective->description }}</span> &bull; 
                            Tahun Ajaran: <span class="font-mono">{{ $todayIneffective->academic_year ?? '-' }}</span>.
                            Guru dibebaskan dari kewajiban pengisian jurnal.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- TABEL DAFTAR HARI TIDAK EFEKTIF --}}
        <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200/80 dark:border-[#2B2B30] rounded-lg overflow-hidden shadow-xs">
            <div class="p-5 border-b border-zinc-200/80 dark:border-[#2B2B30] flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-zinc-50/50 dark:bg-[#19191C]">
                <div>
                    <h2 class="text-sm font-bold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">
                        Daftar Tanggal Tidak Efektif
                    </h2>
                    <p class="text-xs text-zinc-500 mt-0.5">
                        Total {{ $ineffectiveDays->total() }} hari terdaftar dalam kalender akademik sekolah.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-zinc-200/80 dark:border-[#2B2B30] bg-zinc-50 dark:bg-[#232327] text-[11px] font-bold font-mono text-zinc-500 dark:text-[#97979B] uppercase tracking-wider">
                            <th class="py-3 px-4">Tanggal &amp; Hari</th>
                            <th class="py-3 px-4">Keterangan / Agenda</th>
                            <th class="py-3 px-4">Th. Ajaran</th>
                            <th class="py-3 px-4">Ditetapkan Oleh</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200/60 dark:divide-[#2B2B30] text-xs text-zinc-700 dark:text-[#EDEDF0]">
                        @forelse($ineffectiveDays as $day)
                            @php
                                $isToday = $day->date->isToday();
                                $isFuture = $day->date->isFuture();
                            @endphp
                            <tr class="hover:bg-[#232327] transition-colors {{ $isToday ? 'bg-amber-50/30 font-medium' : '' }}">
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-zinc-900 dark:text-[#EDEDF0] font-mono">
                                            {{ $day->date->translatedFormat('l, d M Y') }}
                                        </span>
                                        @if($isToday)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200">
                                                Hari Ini
                                            </span>
                                        @elseif($isFuture)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200">
                                                Mendatang
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-zinc-100 dark:bg-[#232327] text-zinc-500 dark:text-[#97979B]">
                                                Selesai
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="text-zinc-900 dark:text-[#EDEDF0] font-medium">{{ $day->description }}</span>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px] text-zinc-600">
                                    {{ $day->academic_year ?? '-' }}
                                </td>
                                <td class="py-3.5 px-4 text-zinc-500">
                                    {{ $day->creator?->fullname ?? 'Sistem' }}
                                </td>
                                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                    <form 
                                        method="POST" 
                                        action="{{ route('ineffective-days.destroy', $day) }}" 
                                        onsubmit="return confirm('Hapus tanggal {{ $day->date->translatedFormat('d M Y') }} dari daftar hari tidak efektif?')"
                                        class="inline-block"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button 
                                            type="submit" 
                                            class="p-1 text-zinc-400 hover:text-rose-600 rounded hover:bg-rose-50 transition cursor-pointer"
                                            title="Hapus"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-10 text-center text-zinc-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <svg class="w-8 h-8 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                        </svg>
                                        <p class="text-xs font-medium">Belum ada hari tidak efektif yang didaftarkan.</p>
                                        <button 
                                            type="button" 
                                            @click="showModal = true"
                                            class="text-xs text-zinc-900 dark:text-[#EDEDF0] font-semibold underline hover:text-black dark:hover:text-white mt-1"
                                        >
                                            Tambah Hari Tidak Efektif Sekarang
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($ineffectiveDays->hasPages())
                <div class="p-4 border-t border-zinc-200/80 dark:border-[#2B2B30] bg-zinc-50/50 dark:bg-[#19191C]">
                    {{ $ineffectiveDays->links() }}
                </div>
            @endif
        </div>

        {{-- MODAL POPUP TAMBAH HARI TIDAK EFEKTIF --}}
        <div 
            x-show="showModal" 
            x-cloak 
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title" 
            role="dialog" 
            aria-modal="true"
            @keydown.escape.window="showModal = false"
        >
            <!-- Backdrop Blur Overlay -->
            <div 
                x-show="showModal"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-zinc-900/60 backdrop-blur-xs transition-opacity"
                @click="showModal = false"
            ></div>

            <!-- Modal Window -->
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-6">
                <div 
                    x-show="showModal"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                    class="relative transform overflow-hidden rounded-xl bg-white dark:bg-[#1D1D21] text-left shadow-2xl transition-all w-full max-w-lg border border-zinc-200/80 dark:border-[#2B2B30] divide-y divide-zinc-200/80 dark:divide-[#2B2B30]"
                    @click.stop
                >
                    <!-- Header -->
                    <div class="p-5 sm:p-6 flex items-start justify-between gap-4">
                        <div>
                            <h2 id="modal-title" class="text-base font-bold text-zinc-900 dark:text-[#EDEDF0] tracking-tight flex items-center gap-2">
                                <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                                Tetapkan Hari Tidak Efektif
                            </h2>
                            <p class="text-xs text-zinc-500 mt-0.5">
                                Pilih tanggal atau rentang tanggal libur/kegiatan khusus sekolah.
                            </p>
                        </div>
                        <button 
                            type="button" 
                            @click="showModal = false"
                            class="p-1.5 rounded-md text-zinc-400 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] transition cursor-pointer"
                        >
                            <span class="sr-only">Tutup</span>
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <form method="POST" action="{{ route('ineffective-days.store') }}" class="p-5 sm:p-6 space-y-4">
                        @csrf

                        {{-- Mode Selector: Tunggal vs Rentang --}}
                        <div>
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-[#EDEDF0] uppercase tracking-wider mb-2">
                                Tipe Tanggal
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <label 
                                    class="flex items-center gap-2.5 p-3 rounded-lg border cursor-pointer transition text-xs font-medium"
                                    :class="!isRange ? 'border-zinc-900 dark:border-[#0093dd] bg-zinc-50 dark:bg-[#19191C] text-zinc-900 dark:text-[#EDEDF0] font-bold' : 'border-zinc-200 dark:border-[#2B2B30] text-zinc-600 dark:text-[#97979B] hover:bg-zinc-100 dark:hover:bg-[#26262A]'"
                                >
                                    <input type="radio" name="mode" :value="false" x-model="isRange" class="text-zinc-900 focus:ring-zinc-900">
                                    <span>Satu Hari Saja</span>
                                </label>
                                <label 
                                    class="flex items-center gap-2.5 p-3 rounded-lg border cursor-pointer transition text-xs font-medium"
                                    :class="isRange ? 'border-zinc-900 dark:border-[#0093dd] bg-zinc-50 dark:bg-[#19191C] text-zinc-900 dark:text-[#EDEDF0] font-bold' : 'border-zinc-200 dark:border-[#2B2B30] text-zinc-600 dark:text-[#97979B] hover:bg-zinc-100 dark:hover:bg-[#26262A]'"
                                >
                                    <input type="radio" name="mode" :value="true" x-model="isRange" class="text-zinc-900 focus:ring-zinc-900">
                                    <span>Rentang Hari (Libur)</span>
                                </label>
                            </div>
                        </div>

                        {{-- Tanggal Mulai & Akhir --}}
                        <div class="grid gap-3" :class="isRange ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1'">
                            <div>
                                <x-input-label for="start_date" x-text="isRange ? 'Tanggal Mulai' : 'Tanggal'" />
                                <input 
                                    id="start_date" 
                                    name="start_date" 
                                    type="date" 
                                    class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-2 text-sm font-mono text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none transition" 
                                    value="{{ old('start_date', now()->format('Y-m-d')) }}" 
                                    required 
                                />
                                <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
                            </div>

                            <div x-show="isRange" x-cloak>
                                <x-input-label for="end_date" value="Tanggal Selesai" />
                                <input 
                                    id="end_date" 
                                    name="end_date" 
                                    type="date" 
                                    class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-2 text-sm font-mono text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none transition" 
                                    value="{{ old('end_date') }}" 
                                />
                                <x-input-error :messages="$errors->get('end_date')" class="mt-1" />
                            </div>
                        </div>

                        {{-- Keterangan / Agenda --}}
                        <div>
                            <x-input-label for="description" value="Keterangan / Alasan Tidak Efektif" />
                            <input 
                                id="description" 
                                name="description" 
                                type="text" 
                                placeholder="Contoh: Class Meeting Semester Ganjil, Libur Nasional Maulid Nabi" 
                                class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-2 text-sm text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none transition" 
                                value="{{ old('description') }}" 
                                required 
                            />
                            <x-input-error :messages="$errors->get('description')" class="mt-1" />
                        </div>

                        {{-- Tahun Ajaran --}}
                        <div>
                            <x-input-label for="academic_year" value="Tahun Ajaran" />
                            <input 
                                id="academic_year" 
                                name="academic_year" 
                                type="text" 
                                class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-zinc-50 dark:bg-[#19191C] px-3 py-2 text-sm font-mono text-zinc-700 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none transition" 
                                value="{{ old('academic_year', '2026/2027') }}" 
                            />
                            <x-input-error :messages="$errors->get('academic_year')" class="mt-1" />
                        </div>

                        <div class="flex items-center justify-between gap-4 pt-3 border-t border-zinc-200/80 dark:border-[#2B2B30]">
                            <button 
                                type="button" 
                                @click="showModal = false"
                                class="px-4 py-2 border border-zinc-200 dark:border-[#2B2B30] rounded-md text-xs font-semibold text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] transition cursor-pointer"
                            >
                                Batal
                            </button>
                            <button 
                                type="submit"
                                class="px-5 py-2 bg-zinc-900 hover:bg-black text-white text-xs font-semibold uppercase tracking-wider rounded-md transition shadow-none cursor-pointer"
                            >
                                Simpan Hari Tidak Efektif
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
