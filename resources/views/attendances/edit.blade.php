<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="text-xs font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider">Presensi Siswa</div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0] mt-0.5">
                    Penyesuaian Presensi Kelas
                </h1>
            </div>
            <a href="{{ route('journals.show', $journal) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-zinc-300 text-xs font-semibold uppercase tracking-wider text-zinc-700 rounded-md hover:bg-[#26262A] transition">
                &larr; Batal &amp; Kembali
            </a>
        </div>
    </x-slot>

    <div class="space-y-6 max-w-3xl">

        <div class="bg-zinc-50 border border-zinc-200 rounded-lg p-4 font-mono text-xs text-zinc-600 flex flex-wrap items-center justify-between gap-2">
            <div>
                <span class="font-bold text-zinc-900">{{ $journal->schedule->schoolClass->class_name }}</span>
                <span class="text-zinc-400 mx-2">&bull;</span>
                <span>{{ $journal->date->translatedFormat('l, d F Y') }}</span>
            </div>
            <div class="text-zinc-500 font-medium">
                {{ $journal->schedule->subject }}
            </div>
        </div>

        <div class="p-3.5 bg-amber-50/80 border border-amber-200/80 rounded-lg text-xs text-amber-900 flex items-start gap-2.5">
            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            <div class="leading-relaxed">
                <strong>Siswa Izin Keluar di Tengah Pelajaran:</strong> Jika siswa semula hadir lalu izin meninggalkan kelas (misal: sakit ke UKS, dipanggil BK, dispensasi kegiatan, atau pulang darurat), ubah statusnya menjadi <strong>Izin</strong> atau <strong>Sakit</strong> dan tuliskan alasannya pada kolom catatan.
            </div>
        </div>

        <div class="flex items-center justify-between gap-2 text-xs">
            <span class="text-zinc-600">
                Default: <strong class="text-emerald-700">Hadir</strong>. Ubah hanya bagi siswa yang sakit, izin, alpa, atau izin keluar kelas.
            </span>
            <button 
                type="button" 
                onclick="window.dispatchEvent(new CustomEvent('reset-all-hadir'))" 
                class="px-2.5 py-1 bg-white border border-zinc-300 hover:bg-[#26262A] text-[11px] font-semibold text-zinc-700 rounded transition cursor-pointer"
            >
                Set Semua Hadir
            </button>
        </div>

        <form method="POST" action="{{ route('attendances.update', $journal) }}" class="space-y-6">
            @csrf @method('PUT')

            <div class="bg-white border border-zinc-200 rounded-lg overflow-hidden divide-y divide-zinc-100 shadow-sm">
                @foreach($journal->attendances as $i => $attendance)
                    <div x-data="{ 
                            currentStatus: '{{ $attendance->status }}', 
                            noteText: '{{ addslashes($attendance->notes ?? '') }}' 
                         }" 
                         @reset-all-hadir.window="currentStatus = 'Hadir'; noteText = ''"
                         class="p-4 transition space-y-2.5"
                         :class="currentStatus !== 'Hadir' ? 'bg-amber-50/40' : 'hover:bg-[#232327]'">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-zinc-400 text-[11px] w-6">{{ $i + 1 }}.</span>
                                <div>
                                    <span class="text-xs font-bold text-zinc-900 block">{{ $attendance->student->fullname }}</span>
                                    <span class="font-mono text-[10px] text-zinc-400">NIS: {{ $attendance->student->username }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <input type="hidden" name="attendances[{{ $i }}][id]" value="{{ $attendance->id }}">
                                @foreach(['Hadir','Izin','Sakit','Alpa','Terlambat'] as $status)
                                    <label class="cursor-pointer shrink-0">
                                        <input type="radio" name="attendances[{{ $i }}][status]" value="{{ $status }}" x-model="currentStatus" class="peer sr-only" @checked($attendance->status === $status)>
                                        <span class="inline-block px-2.5 py-1 text-[11px] font-mono font-bold uppercase tracking-wider rounded border transition peer-checked:ring-1 peer-checked:ring-zinc-900
                                            {{ match($status) {
                                                'Hadir' => 'peer-checked:bg-emerald-50 peer-checked:text-emerald-800 peer-checked:border-emerald-300',
                                                'Izin'  => 'peer-checked:bg-blue-50 peer-checked:text-blue-800 peer-checked:border-blue-300',
                                                'Sakit' => 'peer-checked:bg-purple-50 peer-checked:text-purple-800 peer-checked:border-purple-300',
                                                'Alpa'  => 'peer-checked:bg-rose-50 peer-checked:text-rose-800 peer-checked:border-rose-300',
                                                'Terlambat' => 'peer-checked:bg-orange-50 peer-checked:text-orange-800 peer-checked:border-orange-300',
                                            } }} border-zinc-200 text-zinc-500 hover:border-zinc-400">
                                            {{ $status }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Expanded Note Section when not Hadir --}}
                        <div x-show="currentStatus !== 'Hadir'" x-cloak class="pt-2 border-t border-zinc-200/60 space-y-1.5 sm:pl-8">
                            <div class="flex flex-wrap items-center justify-between gap-1">
                                <span class="text-[10px] font-bold font-mono text-zinc-500 uppercase tracking-wider">
                                    Alasan Keluar Kelas / Catatan Ketidakhadiran:
                                </span>
                                <div class="flex flex-wrap items-center gap-1 text-[10px]">
                                    <span class="text-zinc-400 font-mono">Pilih Cepat:</span>
                                    <button type="button" @click="currentStatus = 'Sakit'; noteText = 'Izin keluar kelas: sakit ke UKS'" class="px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 transition font-mono">
                                        + Ke UKS (Sakit)
                                    </button>
                                    <button type="button" @click="currentStatus = 'Izin'; noteText = 'Izin keluar kelas: dipanggil Guru BK'" class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 transition font-mono">
                                        + Dipanggil BK
                                    </button>
                                    <button type="button" @click="currentStatus = 'Izin'; noteText = 'Izin keluar kelas: dispensasi tugas/lomba sekolah'" class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 transition font-mono">
                                        + Dispensasi
                                    </button>
                                    <button type="button" @click="currentStatus = 'Izin'; noteText = 'Izin keluar kelas: pulang lebih awal (dijemput)'" class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 transition font-mono">
                                        + Pulang Cepat
                                    </button>
                                    <button type="button" @click="currentStatus = 'Alpa'; noteText = 'Meninggalkan kelas tanpa izin di tengah KBM'" class="px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition font-mono">
                                        + Tanpa Izin
                                    </button>
                                </div>
                            </div>
                            <input type="text" name="attendances[{{ $i }}][notes]" x-model="noteText" placeholder="Tuliskan catatan kenapa siswa keluar kelas (misal: sakit ke UKS jam ke-3)..." class="w-full border border-zinc-300 rounded-md px-3 py-1.5 text-xs text-zinc-900 placeholder-zinc-400 focus:ring-1 focus:ring-zinc-900 focus:border-zinc-900">
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="px-4 py-2 bg-zinc-900 hover:bg-black text-white text-xs font-semibold rounded-md transition shadow-none">
                    Simpan Perubahan Presensi
                </button>
                <a href="{{ route('journals.show', $journal) }}" class="text-xs font-semibold text-zinc-500 hover:text-zinc-900">
                    Batal
                </a>
            </div>
        </form>

    </div>
</x-app-layout>
