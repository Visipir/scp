<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-lg text-zinc-900 dark:text-[#EDEDF0] leading-tight">
                    Import Jadwal Pelajaran (FET Timetable)
                </h2>
                <p class="text-xs text-zinc-500 dark:text-[#97979B]">
                    Unggah ekspor jadwal FET (.xml, .csv, atau .pdf) dengan parser posisi deterministik dan penggabungan blok jam.
                </p>
            </div>
            <a href="{{ route('schedules.index') }}" class="px-3 py-1.5 text-xs text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-white bg-zinc-100 dark:bg-[#2C2C30] rounded-lg">
                &larr; Kembali ke Jadwal
            </a>
        </div>
    </x-slot>

    <div class="py-6 space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="p-4 rounded-lg bg-emerald-50 dark:bg-[#16281D] border border-emerald-300 dark:border-emerald-700/60 text-emerald-800 dark:text-emerald-300 text-xs font-medium">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 rounded-lg bg-rose-50 dark:bg-[#2D1618] border border-rose-300 dark:border-rose-700/60 text-rose-800 dark:text-rose-300 text-xs font-medium">
                {{ session('error') }}
            </div>
        @endif

        @if($config?->is_schedule_locked)
            <div class="p-4 rounded-xl bg-amber-50 dark:bg-[#282115] border border-amber-300 dark:border-amber-700/60 text-amber-900 dark:text-amber-300 text-xs">
                <p class="font-bold mb-1">Perhatian: Jadwal Pelajaran Saat Ini Sedang Dikunci</p>
                <p class="text-amber-800/80 dark:text-amber-300/80">Waka Kurikulum telah mengunci jadwal. Buka kunci jadwal di menu Pengaturan Kurikulum sebelum melakukan import baru.</p>
            </div>
        @endif

        <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2C2C30] rounded-xl p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-[#EDEDF0] mb-2">Petunjuk Import FET</h3>
            <ul class="text-xs text-zinc-600 dark:text-[#97979B] space-y-1 list-disc list-inside mb-6">
                <li>Format yang didukung: <span class="font-mono font-bold text-[#0093dd]">XML</span> (ekspor asli FET - direkomendasikan), <span class="font-mono font-bold text-[#0093dd]">CSV</span>, dan <span class="font-mono font-bold text-[#0093dd]">PDF</span>.</li>
                <li>Jam pelajaran otomatis dipetakan ke slot jam sekolah resmi (Jam 1 s/d Jam 10).</li>
                <li>Slot jam berturutan (misal: Jam 1-3 mapel dan kelas sama) otomatis digabung menjadi 1 baris jadwal terpadu.</li>
                <li>Jadwal lama pada putaran ini yang sudah memiliki jurnal presensi <strong>akan tetap dipertahankan</strong> dan tidak terhapus.</li>
            </ul>

            <form action="{{ route('waka.schedules.import.preview') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-[#EDEDF0] mb-1">
                            Pilih Berkas Jadwal (.xml, .csv, .pdf)
                        </label>
                        <input type="file" name="schedule_file" accept=".xml,.csv,.pdf,.txt" required
                               class="block w-full text-xs text-zinc-500 dark:text-[#97979B] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[#0093dd] file:text-white hover:file:bg-[#007bb8] cursor-pointer">
                        @error('schedule_file')
                            <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-zinc-700 dark:text-[#EDEDF0] mb-1">
                            Target Putaran Siklus
                        </label>
                        <select name="cycle_type" class="w-full text-xs rounded-lg border-zinc-300 dark:border-[#3A3A3F] bg-zinc-50 dark:bg-[#252529] text-zinc-900 dark:text-[#EDEDF0]">
                            <option value="Putaran 2" selected>Putaran 2</option>
                            <option value="Putaran 1">Putaran 1</option>
                        </select>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" @if($config?->is_schedule_locked) disabled @endif
                            class="px-4 py-2 text-xs font-semibold text-white bg-[#0093dd] hover:bg-[#007bb8] disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors">
                        Unggah &amp; Tampilkan Preview
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
