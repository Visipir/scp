<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0] flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    Jadwal Ruangan &amp; Lab
                </h1>
            </div>
            <div class="flex items-center gap-2">
                @if(auth()->user()->role_id === 1)
                    <x-ai-importer-modal type="Jadwal Ruangan" title="Impor AI (Gemini PDF)" />
                @endif
                <a href="{{ route('room-schedules.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold uppercase tracking-wider rounded-lg transition shadow-none shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    Tambah Jadwal
                </a>
            </div>
        </div>
    </x-slot>

    <div 
        class="py-6 space-y-6"
        x-data="{
            showImport: {{ $errors->has('schedule_pdf') || $errors->has('pdf_file') || $errors->has('cycle_type') ? 'true' : 'false' }},
            fileName: '',
            fileSize: '',
            fileError: '',
            isUploading: false,
            targetCycle: '{{ $config?->active_cycle ?? 'Putaran 1' }}',
            handleFile(file) {
                this.fileError = '';
                if (!file) return;

                if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
                    this.fileError = 'Berkas harus berupa dokumen format PDF (.pdf).';
                    return;
                }

                if (file.size > 10 * 1024 * 1024) {
                    this.fileError = 'Ukuran file melebihi batas 10 Megabyte (10MB).';
                    return;
                }

                this.fileName = file.name;
                this.fileSize = (file.size / 1024 / 1024).toFixed(2) + ' MB';
            }
        }"
        @toggle-import.window="showImport = !showImport"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            @if(session('success'))
                <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
            @endif
            @if(session('error'))
                <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
            @endif
            @if(session('info'))
                <x-alert-banner type="info">{{ session('info') }}</x-alert-banner>
            @endif

            {{-- MODAL POPUP IMPOR JADWAL FET PDF OTOMATIS DENGAN AI --}}
            @if(auth()->user()->role_id === 1)
                <div 
                    x-show="showImport" 
                    x-cloak 
                    style="display: none;"
                    class="fixed inset-0 z-50 overflow-y-auto"
                    aria-labelledby="modal-title" 
                    role="dialog" 
                    aria-modal="true"
                    @keydown.escape.window="showImport = false"
                >
                    <div 
                        x-show="showImport"
                        x-transition:enter="ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="ease-in duration-150"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        class="fixed inset-0 bg-zinc-900/60 backdrop-blur-sm transition-opacity"
                        @click="showImport = false"
                    ></div>

                    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-6">
                        <div 
                            x-show="showImport"
                            x-transition:enter="ease-out duration-200"
                            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                            x-transition:leave="ease-in duration-150"
                            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                            x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                            class="relative transform overflow-hidden rounded-2xl bg-white dark:bg-[#1D1D21] text-left shadow-2xl transition-all w-full max-w-2xl border border-zinc-200/80 dark:border-[#2B2B30] divide-y divide-zinc-200/80 dark:divide-[#2B2B30]"
                            @click.stop
                        >
                            <div class="p-5 sm:p-6 flex items-start justify-between gap-4">
                                <div>
                                    <h2 id="modal-title" class="text-sm sm:text-base font-bold text-zinc-900 dark:text-[#EDEDF0] tracking-tight flex items-center gap-2">
                                        <svg class="w-5 h-5 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                        Impor Jadwal Ruangan FET PDF dengan AI
                                    </h2>
                                    <p class="text-xs text-zinc-500 mt-1">
                                        Unggah dokumen PDF jadwal FET. Google Gemini AI akan mem-parsing seluruh data ruangan dan memetakannya ke slot master jam pelajaran.
                                    </p>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded bg-zinc-100 dark:bg-[#232327] text-zinc-700 dark:text-[#EDEDF0] text-[11px] font-mono font-semibold">
                                        Model: {{ $config->effective_gemini_schedule_model ?? 'gemini-3.5-flash' }}
                                    </span>
                                    <button 
                                        type="button" 
                                        @click="showImport = false"
                                        class="p-1.5 rounded-md text-zinc-400 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] transition cursor-pointer"
                                    >
                                        <span class="sr-only">Tutup</span>
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="p-5 sm:p-6 space-y-5">
                                @if(!$config || !$config->effective_gemini_api_key)
                                    <div class="p-4 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-start gap-3">
                                        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                                        </svg>
                                        <div>
                                            <span class="font-bold">Google Gemini API Key belum terpasang.</span>
                                            <p class="mt-0.5 text-amber-800">
                                                Silakan masukkan API Key di menu <a href="{{ route('waka.settings') }}" class="underline font-semibold">Pengaturan Sistem Kurikulum</a> sebelum mengimpor dokumen jadwal PDF.
                                            </p>
                                        </div>
                                    </div>
                                @else
                                    <form 
                                        method="POST" 
                                        action="{{ route('room-schedules.import') }}" 
                                        enctype="multipart/form-data" 
                                        class="space-y-5"
                                        @submit="isUploading = true"
                                    >
                                        @csrf

                                        <div>
                                            <label class="block text-xs font-semibold text-zinc-700 uppercase tracking-wider mb-2">
                                                Pilih Siklus Putaran Target
                                            </label>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <label 
                                                    class="flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition"
                                                    :class="targetCycle === 'Putaran 1' ? 'border-zinc-900 dark:border-[#0093dd] bg-zinc-50 dark:bg-[#19191C]' : 'border-zinc-200 dark:border-[#2B2B30] hover:border-zinc-300 dark:hover:border-[#3A3A3F] bg-white dark:bg-[#232327]'"
                                                >
                                                    <input 
                                                        type="radio" 
                                                        name="cycle_type" 
                                                        value="Putaran 1" 
                                                        x-model="targetCycle"
                                                        class="mt-0.5 text-zinc-900 focus:ring-zinc-900 border-zinc-300"
                                                    />
                                                    <div>
                                                        <span class="font-bold text-xs text-zinc-900 dark:text-[#EDEDF0] block font-mono">PUTARAN 1</span>
                                                        <span class="text-[11px] text-zinc-500 block mt-0.5">
                                                            Jadwal blok standar 35 menit per JP (Mulai 08.00 WIB pada hari Senin).
                                                        </span>
                                                    </div>
                                                </label>

                                                <label 
                                                    class="flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition"
                                                    :class="targetCycle === 'Putaran 2' ? 'border-zinc-900 dark:border-[#0093dd] bg-zinc-50 dark:bg-[#19191C]' : 'border-zinc-200 dark:border-[#2B2B30] hover:border-zinc-300 dark:hover:border-[#3A3A3F] bg-white dark:bg-[#232327]'"
                                                >
                                                    <input 
                                                        type="radio" 
                                                        name="cycle_type" 
                                                        value="Putaran 2" 
                                                        x-model="targetCycle"
                                                        class="mt-0.5 text-zinc-900 focus:ring-zinc-900 border-zinc-300"
                                                    />
                                                    <div>
                                                        <span class="font-bold text-xs text-zinc-900 dark:text-[#EDEDF0] block font-mono">PUTARAN 2 (Khusus TP 2026/2027)</span>
                                                        <span class="text-[11px] text-zinc-500 block mt-0.5">
                                                            Durasi dinamis: Upacara Senin, Literasi Selasa, 40m Rabu-Kamis, Sholat Jumat.
                                                        </span>
                                                    </div>
                                                </label>
                                            </div>
                                            <x-input-error :messages="$errors->get('cycle_type')" class="mt-1" />
                                        </div>

                                        <div>
                                            <div
                                                class="relative flex flex-col items-center justify-center p-6 sm:p-8 border-2 border-dashed rounded-xl cursor-pointer transition-colors group"
                                                :class="fileName ? 'border-emerald-500 bg-emerald-50/20 dark:bg-[#16281D]' : 'border-zinc-300 dark:border-[#2B2B30] hover:border-zinc-500 bg-zinc-50/50 dark:bg-[#19191C]'"
                                                @click="$refs.pdfInput.click()"
                                                @dragover.prevent=""
                                                @drop.prevent="
                                                    const file = $event.dataTransfer.files[0];
                                                    if (file) {
                                                        $refs.pdfInput.files = $event.dataTransfer.files;
                                                        handleFile(file);
                                                    }
                                                "
                                            >
                                                <div class="text-center space-y-2 pointer-events-none">
                                                    <div class="mx-auto w-10 h-10 rounded-full flex items-center justify-center transition-colors" :class="fileName ? 'bg-emerald-100 dark:bg-[#16281D] text-emerald-700 dark:text-emerald-300' : 'bg-zinc-100 dark:bg-[#232327] text-zinc-500 dark:text-[#97979B] group-hover:bg-[#26262A]'">
                                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                                        </svg>
                                                    </div>
                                                    <div class="text-xs">
                                                        <template x-if="!fileName">
                                                            <div>
                                                                <span class="font-bold text-zinc-900 dark:text-[#EDEDF0] group-hover:underline">Klik untuk pilih berkas PDF</span>
                                                                <span class="text-zinc-500"> atau seret dokumen FET ke sini</span>
                                                                <p class="text-[11px] text-zinc-400 font-mono mt-1">Maksimal 10MB (.pdf)</p>
                                                            </div>
                                                        </template>
                                                        <template x-if="fileName">
                                                            <div class="font-mono text-zinc-800 dark:text-[#EDEDF0]">
                                                                <div class="font-bold text-emerald-800 dark:text-emerald-400 flex items-center justify-center gap-1.5">
                                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                                                    <span x-text="fileName"></span>
                                                                </div>
                                                                <p class="text-[11px] text-zinc-500 mt-0.5" x-text="'Ukuran: ' + fileSize"></p>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>

                                                <input
                                                    x-ref="pdfInput"
                                                    id="schedule_pdf"
                                                    name="schedule_pdf"
                                                    type="file"
                                                    accept=".pdf,application/pdf"
                                                    class="hidden"
                                                    required
                                                    @change="handleFile($event.target.files[0])"
                                                />
                                            </div>

                                            <p class="text-xs text-rose-600 mt-1.5 font-medium" x-show="fileError" x-text="fileError"></p>
                                            <x-input-error :messages="$errors->get('schedule_pdf')" class="mt-1.5" />
                                        </div>

                                        <div class="p-3.5 bg-zinc-50 dark:bg-[#232327] border border-zinc-200 dark:border-[#2B2B30] rounded-xl">
                                            <label class="flex items-start gap-2.5 cursor-pointer">
                                                <input 
                                                    type="checkbox" 
                                                    name="replace_existing" 
                                                    value="1" 
                                                    class="mt-0.5 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                                                />
                                                <div class="text-xs">
                                                    <span class="font-bold text-zinc-900 dark:text-[#EDEDF0] block">Mode Revisi Jadwal (Timpa Jadwal Lama)</span>
                                                    <span class="text-zinc-500 text-[11px] block mt-0.5">
                                                        Centang jika ini adalah berkas revisi untuk mengganti jadwal ruangan lama pada siklus target secara otomatis.
                                                    </span>
                                                </div>
                                            </label>
                                        </div>

                                        <div class="flex items-center justify-between gap-4 pt-1">
                                            <button
                                                type="button"
                                                @click="showImport = false"
                                                class="px-4 py-2.5 border border-zinc-200 dark:border-[#2B2B30] rounded-lg text-xs font-semibold text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] transition cursor-pointer"
                                            >
                                                Batal
                                            </button>
                                            <button
                                                type="submit"
                                                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-zinc-900 hover:bg-black text-white font-semibold text-xs uppercase tracking-wider rounded-lg transition shadow-none cursor-pointer disabled:opacity-50"
                                                :disabled="isUploading"
                                                @click="if (!fileName) { $refs.pdfInput.click(); }"
                                            >
                                                <svg x-show="!isUploading" class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
                                                </svg>
                                                <svg x-show="isUploading" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span x-text="isUploading ? 'Sedang Mengekstrak dengan AI (Mohon Tunggu)...' : (fileName ? 'Mulai Ekstraksi &amp; Impor ke ' + targetCycle : 'Pilih Berkas PDF &amp; Ekstraksi')"></span>
                                            </button>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Filter Section -->
            <div class="bg-white dark:bg-[#1D1D21] rounded-xl shadow-sm border border-zinc-200 dark:border-[#2B2B30] p-4">
                <form method="GET" action="{{ route('room-schedules.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    <div>
                        <label for="day" class="block text-xs font-bold text-zinc-700 dark:text-[#EDEDF0] mb-1">Hari</label>
                        <select name="day" id="day" class="w-full rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-sm text-sm" onchange="this.form.submit()">
                            @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $day)
                                <option value="{{ $day }}" {{ $dayFilter == $day ? 'selected' : '' }}>{{ $day }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="cycle" class="block text-xs font-bold text-zinc-700 dark:text-[#EDEDF0] mb-1">Putaran</label>
                        <select name="cycle" id="cycle" class="w-full rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-sm text-sm" onchange="this.form.submit()">
                            @foreach(['Putaran 1', 'Putaran 2'] as $cycle)
                                <option value="{{ $cycle }}" {{ $cycleFilter == $cycle ? 'selected' : '' }}>{{ $cycle }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="class_id" class="block text-xs font-bold text-zinc-700 dark:text-[#EDEDF0] mb-1">Filter Kelas</label>
                        <select name="class_id" id="class_id" class="w-full rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-sm text-sm" onchange="this.form.submit()">
                            <option value="">-- Semua Kelas ({{ $allClasses->count() }}) --</option>
                            @foreach($allClasses as $c)
                                <option value="{{ $c->id }}" {{ $classFilter == $c->id ? 'selected' : '' }}>{{ $c->class_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            <!-- Matrix View -->
            @php
                $periods = $scheduleMatrix[$cycleFilter]['periods'][$dayFilter] ?? [];
                
                // Group schedules by class_id and period
                $schedulesDict = [];
                foreach($schedules as $sched) {
                    $schedulesDict[$sched->class_id][$sched->period] = $sched;
                }
            @endphp

            @if(empty($periods))
                <div class="bg-white dark:bg-[#1D1D21] rounded-xl shadow-sm border border-zinc-200 dark:border-[#2B2B30] p-12 text-center">
                    <svg class="w-12 h-12 text-zinc-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-[#EDEDF0]">Tidak ada jam pelajaran</h3>
                    <p class="text-zinc-500 text-sm mt-1">Konfigurasi jam pelajaran untuk {{ $dayFilter }} belum diatur.</p>
                </div>
            @else
                <!-- Matrix Grid: Kolom Kelas di atas, Ruangan di bawah, Baris Jam & Waktu -->
                <div class="bg-white dark:bg-[#1D1D21] rounded-xl shadow-sm border border-zinc-200 dark:border-[#2B2B30] overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-full">
                            <thead>
                                <tr class="bg-zinc-50 dark:bg-[#232327] border-b border-zinc-200 dark:border-[#2B2B30]">
                                    <th class="sticky left-0 z-20 bg-zinc-100 dark:bg-[#232327] border-r border-zinc-200 dark:border-[#2B2B30] p-3 min-w-[130px] sm:min-w-[150px] text-xs font-bold text-zinc-700 dark:text-[#EDEDF0] uppercase tracking-wider text-center shadow-xs">
                                        Jam Pelajaran &amp; Waktu
                                    </th>
                                    @foreach($classes as $c)
                                        <th class="border-r border-zinc-200 dark:border-[#2B2B30] p-3 min-w-[130px] sm:min-w-[150px] text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] text-center uppercase tracking-wide">
                                            <span class="inline-block px-2 py-0.5 rounded bg-zinc-200/70 dark:bg-[#232327] text-zinc-800 dark:text-[#EDEDF0] font-mono border border-transparent dark:border-[#2B2B30]">
                                                {{ $c->class_name }}
                                            </span>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-[#2B2B30]">
                                @foreach($periods as $period)
                                    <tr class="hover:bg-[#232327] transition">
                                        <!-- Kolom Jam Pelajaran & Waktu -->
                                        <td class="sticky left-0 z-10 bg-zinc-50/95 dark:bg-[#1D1D21] backdrop-blur-xs border-r border-zinc-200 dark:border-[#2B2B30] p-3 text-center shadow-xs">
                                            <div class="text-xs font-bold text-zinc-900 dark:text-[#EDEDF0]">Jam ke-{{ $period['period'] }}</div>
                                            <div class="text-[11px] font-mono text-zinc-500 mt-0.5">{{ $period['start_time'] }} - {{ $period['end_time'] }}</div>
                                        </td>
                                        
                                        <!-- Kolom Ruangan untuk masing-masing kelas -->
                                        @foreach($classes as $c)
                                            @php
                                                $sched = $schedulesDict[$c->id][$period['period']] ?? null;
                                            @endphp
                                            <td class="border-r border-zinc-200 dark:border-[#2B2B30] p-2 align-middle text-center relative group">
                                                @if($sched)
                                                    <div class="bg-indigo-50/80 border border-indigo-200/80 rounded-lg p-2 flex flex-col items-center justify-center text-center relative">
                                                        <!-- Ruangan Kelas -->
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-indigo-600 text-white shadow-xs">
                                                            {{ $sched->room->room_name ?? 'R. KBM' }}
                                                        </span>

                                                        @if(!empty($sched->subject))
                                                            <div class="text-[10px] font-medium text-zinc-700 leading-tight mt-1 max-w-[130px] truncate" title="{{ $sched->subject }}">
                                                                {{ $sched->subject }}
                                                            </div>
                                                        @endif

                                                        @if(!empty($sched->teacher))
                                                            <div class="text-[9px] text-zinc-400 max-w-[130px] truncate mt-0.5" title="{{ $sched->teacher->fullname }}">
                                                                {{ $sched->teacher->fullname }}
                                                            </div>
                                                        @endif

                                                        <!-- Tombol Hapus (Hover) -->
                                                        <form action="{{ route('room-schedules.destroy', $sched->id) }}" method="POST" class="absolute -top-1.5 -right-1.5 opacity-0 group-hover:opacity-100 transition m-0 p-0" onsubmit="return confirm('Hapus jadwal ruangan ini?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="w-5 h-5 rounded-full bg-rose-600 text-white flex items-center justify-center shadow hover:bg-rose-700" title="Hapus">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <span class="text-zinc-300 text-xs font-mono">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>
    
    <style>
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</x-app-layout>
