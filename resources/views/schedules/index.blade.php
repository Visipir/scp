<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900">
                    @if(auth()->user()->role_id === 2)
                        Jadwal Mengajar
                    @else
                        Jadwal Pelajaran
                    @endif
                </h1>
            </div>
            <div class="flex items-center gap-2">
                @if(auth()->user()->role_id === 1)
                    <x-ai-importer-modal type="Jadwal Pelajaran" title="Impor Jadwal (PDF AI)" />
                @endif
                @if($config && !$config->is_schedule_locked && auth()->user()->role_id === 2)
                    <a href="{{ route('schedules.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-[#FD366E] hover:bg-[#E0285D] active:bg-[#C92051] text-white text-xs font-semibold uppercase tracking-wider rounded-lg transition shadow-none shrink-0 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Tambah Jadwal Blok
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div 
        class="space-y-6"
        x-data="{
            showImport: {{ $errors->has('schedule_pdf') || $errors->has('cycle_type') ? 'true' : 'false' }},
            showMatrix: false,
            viewMode: 'list',
            activeDay: '{{ $defaultDay ?? (in_array($todayName ?? '', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']) ? $todayName : 'all') }}',
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

        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if(session('error'))
            <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
        @endif

        {{-- MODAL POPUP IMPOR JADWAL FET PDF OTOMATIS (WAKA KURIKULUM) --}}
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
                        class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all w-full max-w-2xl border border-zinc-200/80 divide-y divide-zinc-200/80"
                        @click.stop
                    >
                        <div class="p-5 sm:p-6 flex items-start justify-between gap-4">
                            <div>
                                <h2 id="modal-title" class="text-sm sm:text-base font-bold text-zinc-900 tracking-tight flex items-center gap-2">
                                    <svg class="w-5 h-5 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                    Impor Jadwal FET PDF Otomatis dengan AI
                                </h2>
                                <p class="text-xs text-zinc-500 mt-1">
                                    Unggah dokumen PDF jadwal FET. Google Gemini AI akan mem-parsing seluruh jadwal kelas dan memetakannya ke slot master jam pelajaran.
                                </p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded bg-zinc-100 text-zinc-700 text-[11px] font-mono font-semibold">
                                    Model: {{ $config->effective_gemini_schedule_model ?? 'gemini-3.5-flash' }}
                                </span>
                                <button 
                                    type="button" 
                                    @click="showImport = false"
                                    class="p-1.5 rounded-md text-zinc-400 hover:text-zinc-900 hover:bg-zinc-100 transition cursor-pointer"
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
                                    action="{{ route('schedules.import') }}" 
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
                                                :class="targetCycle === 'Putaran 1' ? 'border-zinc-900 bg-zinc-50' : 'border-zinc-200 hover:border-zinc-300 bg-white'"
                                            >
                                                <input 
                                                    type="radio" 
                                                    name="cycle_type" 
                                                    value="Putaran 1" 
                                                    x-model="targetCycle"
                                                    class="mt-0.5 text-zinc-900 focus:ring-zinc-900 border-zinc-300"
                                                />
                                                <div>
                                                    <span class="font-bold text-xs text-zinc-900 block font-mono">PUTARAN 1</span>
                                                    <span class="text-[11px] text-zinc-500 block mt-0.5">
                                                        Jadwal blok standar 35 menit per JP (Mulai 08.00 WIB pada hari Senin).
                                                    </span>
                                                </div>
                                            </label>

                                            <label 
                                                class="flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition"
                                                :class="targetCycle === 'Putaran 2' ? 'border-zinc-900 bg-zinc-50' : 'border-zinc-200 hover:border-zinc-300 bg-white'"
                                            >
                                                <input 
                                                    type="radio" 
                                                    name="cycle_type" 
                                                    value="Putaran 2" 
                                                    x-model="targetCycle"
                                                    class="mt-0.5 text-zinc-900 focus:ring-zinc-900 border-zinc-300"
                                                />
                                                <div>
                                                    <span class="font-bold text-xs text-zinc-900 block font-mono">PUTARAN 2 (Khusus TP 2026/2027)</span>
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
                                            :class="fileName ? 'border-emerald-500 bg-emerald-50/20' : 'border-zinc-300 hover:border-zinc-500 bg-zinc-50/50'"
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
                                                <div class="mx-auto w-10 h-10 rounded-full flex items-center justify-center transition-colors" :class="fileName ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-500 group-hover:bg-zinc-200'">
                                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                                    </svg>
                                                </div>
                                                <div class="text-xs">
                                                    <template x-if="!fileName">
                                                        <div>
                                                            <span class="font-bold text-zinc-900 group-hover:underline">Klik untuk pilih berkas PDF</span>
                                                            <span class="text-zinc-500"> atau seret dokumen FET ke sini</span>
                                                            <p class="text-[11px] text-zinc-400 font-mono mt-1">Maksimal 10MB (.pdf)</p>
                                                        </div>
                                                    </template>
                                                    <template x-if="fileName">
                                                        <div class="font-mono text-zinc-800">
                                                            <div class="font-bold text-emerald-800 flex items-center justify-center gap-1.5">
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

                                    <div class="p-3.5 bg-zinc-50 border border-zinc-200 rounded-xl">
                                        <label class="flex items-start gap-2.5 cursor-pointer">
                                            <input 
                                                type="checkbox" 
                                                name="replace_existing" 
                                                value="1" 
                                                class="mt-0.5 rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900"
                                            />
                                            <div class="text-xs">
                                                <span class="font-bold text-zinc-900 block">Mode Revisi Jadwal (Timpa Jadwal Lama)</span>
                                                <span class="text-zinc-500 text-[11px] block mt-0.5">
                                                    Centang jika ini adalah berkas revisi untuk mengganti jadwal lama pada siklus putaran target secara otomatis.
                                                </span>
                                            </div>
                                        </label>
                                    </div>

                                    <div class="flex items-center justify-between gap-4 pt-1">
                                        <button
                                            type="button"
                                            @click="showImport = false"
                                            class="px-4 py-2.5 border border-zinc-200 rounded-lg text-xs font-semibold text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50 transition cursor-pointer"
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

        {{-- 1. RINGKASAN STATISTIK JADWAL --}}
        @php
            $totalJp = $schedules->sum('total_jp');
            $totalSessions = $schedules->count();
            $totalClasses = $schedules->pluck('class_id')->unique()->count();
            $activeDays = $schedules->pluck('day_of_week')->unique()->count();
            $orderedDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $groupedSchedules = $schedules->groupBy('day_of_week');
        @endphp

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="bg-white border border-zinc-200/90 rounded-xl p-4 flex items-center gap-3.5 shadow-sm">
                <div class="w-11 h-11 rounded-lg bg-zinc-900 text-white flex items-center justify-center shrink-0 font-bold font-mono text-xs shadow-sm">
                    JP
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-mono font-semibold text-zinc-400 uppercase tracking-wider">Total Beban</div>
                    <div class="text-base sm:text-lg font-bold text-zinc-900 font-mono tracking-tight mt-0.5">
                        {{ $totalJp }} <span class="text-xs font-normal text-zinc-500 font-sans">JP / pekan</span>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-zinc-200/90 rounded-xl p-4 flex items-center gap-3.5 shadow-sm">
                <div class="w-11 h-11 rounded-lg bg-zinc-100 text-zinc-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-mono font-semibold text-zinc-400 uppercase tracking-wider">Total Sesi KBM</div>
                    <div class="text-base sm:text-lg font-bold text-zinc-900 font-mono tracking-tight mt-0.5">
                        {{ $totalSessions }} <span class="text-xs font-normal text-zinc-500 font-sans">jadwal</span>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-zinc-200/90 rounded-xl p-4 flex items-center gap-3.5 shadow-sm">
                <div class="w-11 h-11 rounded-lg bg-zinc-100 text-zinc-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-mono font-semibold text-zinc-400 uppercase tracking-wider">Kelas Terhubung</div>
                    <div class="text-base sm:text-lg font-bold text-zinc-900 font-mono tracking-tight mt-0.5">
                        {{ $totalClasses }} <span class="text-xs font-normal text-zinc-500 font-sans">rombel</span>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-zinc-200/90 rounded-xl p-4 flex items-center gap-3.5 shadow-sm">
                <div class="w-11 h-11 rounded-lg bg-zinc-100 text-zinc-700 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-mono font-semibold text-zinc-400 uppercase tracking-wider">Hari Aktif Mengajar</div>
                    <div class="text-base sm:text-lg font-bold text-zinc-900 font-mono tracking-tight mt-0.5">
                        {{ $activeDays }} <span class="text-xs font-normal text-zinc-500 font-sans">hari / pekan</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. FILTER & KONTROL BAR --}}
        <div class="bg-white border border-zinc-200/90 rounded-xl p-4 sm:p-5 shadow-sm space-y-3.5">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3.5">
                {{-- Siklus Putaran & Filter Waka --}}
                <div class="flex flex-wrap items-center gap-2.5">
                    {{-- Cycle Switcher (Segmented Control) --}}
                    <div class="inline-flex rounded-lg border border-[#2B2B30] p-0.5 bg-[#19191C] text-xs font-semibold">
                        @foreach(['Putaran 1', 'Putaran 2'] as $cycle)
                            <a 
                                href="{{ route('schedules.index', array_merge(request()->query(), ['cycle' => $cycle])) }}" 
                                class="h-7 px-3.5 rounded-md transition cursor-pointer flex items-center justify-center {{ $cycleFilter === $cycle ? 'bg-[#3A3A3F] text-white shadow-none font-semibold' : 'text-[#97979B] hover:text-[#EDEDF0] hover:bg-[#26262A]' }}"
                            >
                                {{ $cycle }}
                            </a>
                        @endforeach
                    </div>

                    {{-- Waka Filters (Role 1 Only) --}}
                    @if(auth()->user()->role_id === 1)
                        <form method="GET" action="{{ route('schedules.index') }}" class="flex flex-wrap items-center gap-2">
                            <input type="hidden" name="cycle" value="{{ $cycleFilter }}">

                            <select name="teacher_id" onchange="this.form.submit()" class="rounded-lg border border-[#2B2B30] bg-[#19191C] py-1.5 px-3 text-xs text-[#EDEDF0] focus:border-[#FD366E] focus:ring-1 focus:ring-[#FD366E]">
                                <option value="">-- Semua Guru ({{ $teachers->count() }}) --</option>
                                @foreach($teachers as $t)
                                    <option value="{{ $t->id }}" @selected(request('teacher_id') == $t->id)>
                                        {{ $t->fullname }}
                                    </option>
                                @endforeach
                            </select>

                            <select name="class_id" onchange="this.form.submit()" class="rounded-lg border border-[#2B2B30] bg-[#19191C] py-1.5 px-3 text-xs text-[#EDEDF0] focus:border-[#FD366E] focus:ring-1 focus:ring-[#FD366E]">
                                <option value="">-- Semua Kelas ({{ $classes->count() }}) --</option>
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>
                                        {{ $c->class_name }}
                                    </option>
                                @endforeach
                            </select>

                            @if(request('teacher_id') || request('class_id'))
                                <a href="{{ route('schedules.index', ['cycle' => $cycleFilter]) }}" class="text-xs text-[#FD366E] hover:underline font-semibold ml-1">
                                    Reset Filter
                                </a>
                            @endif
                        </form>
                    @endif
                </div>

                {{-- View Mode Toggle & Aturan Jam --}}
                <div class="flex items-center gap-2">
                    {{-- Switcher List vs Grid --}}
                    <div class="inline-flex rounded-lg border border-[#2B2B30] p-0.5 bg-[#19191C] text-xs font-semibold">
                        <button 
                            type="button" 
                            @click="viewMode = 'list'"
                            :class="viewMode === 'list' ? 'bg-[#3A3A3F] text-white shadow-none font-semibold' : 'text-[#97979B] hover:text-[#EDEDF0] hover:bg-[#26262A]'"
                            class="h-7 px-3 rounded-md flex items-center gap-1.5 transition cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                            <span>Daftar</span>
                        </button>
                        <button 
                            type="button" 
                            @click="viewMode = 'grid'"
                            :class="viewMode === 'grid' ? 'bg-[#3A3A3F] text-white shadow-none font-semibold' : 'text-[#97979B] hover:text-[#EDEDF0] hover:bg-[#26262A]'"
                            class="h-7 px-3 rounded-md flex items-center gap-1.5 transition cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            <span>Matriks Mingguan</span>
                        </button>
                    </div>

                    {{-- Toggle Matrix Button --}}
                    <button 
                        type="button"
                        @click="showMatrix = !showMatrix"
                        class="h-8 inline-flex items-center gap-1.5 px-3 text-xs font-medium border border-[#2B2B30] rounded-lg transition cursor-pointer"
                        :class="showMatrix ? 'bg-[#3A3A3F] text-white shadow-none' : 'bg-transparent text-[#EDEDF0] hover:bg-[#26262A]'"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span x-text="showMatrix ? 'Tutup Jam Belajar' : 'Aturan Jam'"></span>
                    </button>
                </div>
            </div>

            {{-- Day Pills Tab Selector --}}
            <div class="pt-3 border-t border-[#2B2B30] flex items-center gap-2 overflow-x-auto pb-1 text-xs">
                <button 
                    type="button" 
                    @click="activeDay = 'all'"
                    :class="activeDay === 'all' ? 'bg-[#3A3A3F] text-white font-semibold' : 'bg-[#232327] hover:bg-[#26262A] text-[#97979B] hover:text-[#EDEDF0] font-medium border border-[#2B2B30]'"
                    class="h-8 px-3 rounded-lg transition cursor-pointer shrink-0 flex items-center gap-1.5"
                >
                    <span>Semua Hari</span>
                    <span 
                        :class="activeDay === 'all' ? 'bg-white/20 text-white' : 'bg-[#2B2B30] text-[#97979B]'"
                        class="text-[10px] font-mono px-1.5 py-0.5 rounded leading-none"
                    >
                        {{ $schedules->count() }}
                    </span>
                </button>
                @foreach($orderedDays as $day)
                    @php
                        $countDay = $groupedSchedules->get($day)?->count() ?? 0;
                        $jpDay = $groupedSchedules->get($day)?->sum('total_jp') ?? 0;
                        $isToday = ($todayName === $day);
                    @endphp
                    <button 
                        type="button" 
                        @click="activeDay = '{{ $day }}'"
                        :class="activeDay === '{{ $day }}' ? 'bg-[#3A3A3F] text-white font-semibold' : 'bg-[#232327] hover:bg-[#26262A] text-[#97979B] hover:text-[#EDEDF0] font-medium border border-[#2B2B30]'"
                        class="h-8 px-3 rounded-lg transition cursor-pointer shrink-0 flex items-center gap-1.5"
                    >
                        <span>{{ $day }}</span>
                        <span 
                            :class="activeDay === '{{ $day }}' ? 'bg-white/20 text-white' : 'bg-[#2B2B30] text-[#97979B]'"
                            class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded leading-none"
                        >
                            {{ $jpDay }} JP
                        </span>
                        @if($isToday)
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" title="Hari Ini"></span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Collapsible Master Jam Pelajaran Accordion --}}
        <div 
            x-show="showMatrix" 
            x-cloak 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
        >
            @if($cycleFilter === 'Putaran 2')
                <div class="bg-white border border-zinc-200/90 rounded-xl p-4 sm:p-5 font-mono text-xs text-zinc-700 space-y-3 shadow-sm">
                    <div class="flex items-center justify-between border-b border-zinc-100 pb-2.5">
                        <span class="font-bold text-zinc-900 text-xs">Aturan Jam Pelajaran Putaran 2 (TP 2026/2027)</span>
                        <span class="text-[11px] text-zinc-400">Durasi Blok &amp; Istirahat Resmi</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 text-[11px]">
                        <div class="p-3 bg-zinc-50 rounded-lg border border-zinc-200/80 space-y-1">
                            <div class="font-bold text-zinc-900 border-b border-zinc-200 pb-1.5 flex justify-between">
                                <span>SENIN</span> <span class="text-zinc-400">10 JP</span>
                            </div>
                            <div class="text-[10px] text-zinc-400 pt-0.5">07.00–08.00 Upacara</div>
                            <div>Jam 1–3 : 08.00–09.45 (35m)</div>
                            <div class="text-amber-700 font-semibold">Istirahat I : 09.45–10.00</div>
                            <div>Jam 4–6 : 10.00–11.45 (35m)</div>
                            <div class="text-amber-700 font-semibold">Ishoma : 11.45–12.45</div>
                            <div>Jam 7–10 : 12.45–15.10</div>
                        </div>
                        <div class="p-3 bg-zinc-50 rounded-lg border border-zinc-200/80 space-y-1">
                            <div class="font-bold text-zinc-900 border-b border-zinc-200 pb-1.5 flex justify-between">
                                <span>SELASA</span> <span class="text-zinc-400">10 JP</span>
                            </div>
                            <div class="text-[10px] text-zinc-400 pt-0.5">07.00–07.30 Literasi</div>
                            <div>Jam 1–3 : 07.30–09.30 (40m)</div>
                            <div class="text-amber-700 font-semibold">Istirahat I : 09.30–09.45</div>
                            <div>Jam 4–6 : 09.45–11.45 (40m)</div>
                            <div class="text-amber-700 font-semibold">Ishoma : 11.45–12.45</div>
                            <div>Jam 7–10 : 12.45–15.10</div>
                        </div>
                        <div class="p-3 bg-zinc-50 rounded-lg border border-zinc-200/80 space-y-1">
                            <div class="font-bold text-zinc-900 border-b border-zinc-200 pb-1.5 flex justify-between">
                                <span>RABU &amp; KAMIS</span> <span class="text-zinc-400">10 JP</span>
                            </div>
                            <div>Jam 1–3 : 07.15–09.15 (40m)</div>
                            <div class="text-amber-700 font-semibold">Istirahat I : 09.15–09.30</div>
                            <div>Jam 4–6 : 09.30–11.30 (40m)</div>
                            <div class="text-amber-700 font-semibold">Ishoma : 11.30–12.30</div>
                            <div>Jam 7–10 : 12.30–15.10 (40m)</div>
                        </div>
                        <div class="p-3 bg-zinc-50 rounded-lg border border-zinc-200/80 space-y-1">
                            <div class="font-bold text-zinc-900 border-b border-zinc-200 pb-1.5 flex justify-between">
                                <span>JUMAT</span> <span class="text-zinc-400">8 JP</span>
                            </div>
                            <div>Jam 1–3 : 07.15–09.15 (40m)</div>
                            <div class="text-amber-700 font-semibold">Istirahat I : 09.15–09.30</div>
                            <div>Jam 4–6 : 09.30–11.25</div>
                            <div class="text-amber-700 font-semibold">Sholat Jumat : 11.25–12.40</div>
                            <div>Jam 7–8 : 12.40–13.50</div>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white border border-zinc-200/90 rounded-xl p-4 sm:p-5 font-mono text-xs text-zinc-700 shadow-sm">
                    <div class="font-bold text-zinc-900 mb-2.5">Sistem Jam Pelajaran Putaran 1 (35 Menit / JP)</div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 text-[11px]">
                        <div class="p-2.5 bg-zinc-50 rounded-lg border border-zinc-200">Blok Pagi 1: Jam 1–3 (08.00–09.45)</div>
                        <div class="p-2.5 bg-amber-50 rounded-lg border border-amber-200 text-amber-900">Istirahat 1: 09.45–10.00</div>
                        <div class="p-2.5 bg-zinc-50 rounded-lg border border-zinc-200">Blok Pagi 2: Jam 4–6 (10.00–11.45)</div>
                        <div class="p-2.5 bg-amber-50 rounded-lg border border-amber-200 text-amber-900">Ishoma: 11.45–12.45</div>
                        <div class="p-2.5 bg-zinc-50 rounded-lg border border-zinc-200">Blok Siang 1: Jam 7–10 (12.45–15.05)</div>
                        <div class="p-2.5 bg-zinc-50 rounded-lg border border-zinc-200">Blok Siang 2: Jam 11–12 (15.05–16.15)</div>
                    </div>
                </div>
            @endif
        </div>

        {{-- 3. TAMPILAN JADWAL (EMPTY STATE / LIST / GRID) --}}
        @if($schedules->isEmpty())
            <div class="bg-white border border-zinc-200/90 rounded-xl p-12 text-center space-y-3.5 shadow-sm">
                <div class="w-12 h-12 rounded-full bg-zinc-100 text-zinc-400 mx-auto flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-zinc-900">
                    @if(auth()->user()->role_id === 2)
                        Belum ada jadwal mengajar untuk {{ $cycleFilter }}
                    @else
                        Belum ada jadwal untuk {{ $cycleFilter }}
                    @endif
                </h3>
                <p class="text-xs text-zinc-500 max-w-sm mx-auto">
                    @if(auth()->user()->role_id === 2)
                        Tidak ditemukan jadwal mengajar yang terhubung dengan akun Anda pada {{ $cycleFilter }}.
                    @else
                        Silakan unggah dokumen PDF jadwal FET atau tambahkan jadwal blok KBM secara manual.
                    @endif
                </p>
                @if(auth()->user()->role_id === 1)
                    <div class="pt-2">
                        <button 
                            type="button" 
                            @click="showImport = true"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-zinc-900 hover:bg-black text-white text-xs font-semibold rounded-lg uppercase tracking-wider transition cursor-pointer"
                        >
                            <svg class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                            </svg>
                            <span>Unggah PDF Jadwal FET</span>
                        </button>
                    </div>
                @endif
            </div>
        @else
            {{-- A. MODE LIST / KARTU (TIMELINE HARIAN) --}}
            <div x-show="viewMode === 'list'" class="space-y-8">
                @foreach($orderedDays as $day)
                    @if($groupedSchedules->has($day))
                        @php 
                            $daySchedules = $groupedSchedules[$day]; 
                            $totalDayJp = $daySchedules->sum('total_jp');
                            $isToday = ($todayName === $day);
                        @endphp
                        <div 
                            x-show="activeDay === 'all' || activeDay === '{{ $day }}'" 
                            class="space-y-3.5"
                        >
                            {{-- Day Header Strip --}}
                            <div class="flex items-center justify-between px-1 pb-1">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-2.5 h-2.5 rounded-full {{ $isToday ? 'bg-emerald-500 ring-4 ring-emerald-100' : 'bg-zinc-900' }}"></span>
                                    <h2 class="font-bold text-sm sm:text-base tracking-tight text-zinc-900 uppercase font-mono">
                                        HARI {{ $day }}
                                    </h2>
                                    @if($isToday)
                                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold uppercase font-mono">
                                            Hari Ini
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 font-mono text-xs text-zinc-500">
                                    <span class="px-2.5 py-0.5 rounded-md bg-zinc-100 text-zinc-700 font-semibold border border-zinc-200/80 text-[11px]">
                                        {{ $daySchedules->count() }} Sesi
                                    </span>
                                    <span class="px-2.5 py-0.5 rounded-md bg-zinc-900 text-white font-bold text-[11px]">
                                        {{ $totalDayJp }} JP
                                    </span>
                                </div>
                            </div>

                            {{-- Cards Container --}}
                            <div class="space-y-3">
                                @foreach($daySchedules as $schedule)
                                    @php
                                        $canManageSchedule = (auth()->user()->role_id === 1) || (!$config?->is_schedule_locked && $schedule->teacher_id === auth()->id());
                                    @endphp
                                    <div class="bg-white border border-zinc-200/90 rounded-xl p-4 sm:p-5 hover:border-zinc-300 hover:shadow-sm transition flex flex-col md:flex-row md:items-center justify-between gap-4">
                                        
                                        {{-- Waktu & Blok Jam --}}
                                        <div class="flex items-center gap-3.5 sm:w-60 shrink-0">
                                            <div class="w-12 h-12 rounded-xl bg-zinc-900 text-white flex flex-col items-center justify-center font-mono shrink-0 shadow-sm">
                                                <span class="text-[8px] uppercase tracking-wider text-zinc-400 font-bold leading-none">JAM</span>
                                                <span class="text-sm sm:text-base font-extrabold leading-tight mt-0.5">
                                                    {{ $schedule->start_period }}&ndash;{{ $schedule->end_period }}
                                                </span>
                                            </div>
                                            <div class="space-y-1">
                                                <div class="font-mono text-xs sm:text-sm font-bold text-zinc-900 flex items-center gap-1.5 leading-none">
                                                    <svg class="w-3.5 h-3.5 text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0 1 18 0z"/></svg>
                                                    <span>{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} &ndash; {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}</span>
                                                </div>
                                                <div class="flex items-center gap-1.5 font-mono text-[11px] text-zinc-500">
                                                    <span class="px-1.5 py-0.5 rounded bg-zinc-100 text-zinc-700 font-bold border border-zinc-200/80 leading-none">
                                                        {{ $schedule->total_jp }} JP
                                                    </span>
                                                    <span class="text-zinc-300">&bull;</span>
                                                    <span class="text-[10px] text-zinc-400 uppercase tracking-wide font-medium">{{ $schedule->cycle_type }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Detail Mata Pelajaran, Kelas, Ruang --}}
                                        <div class="min-w-0 flex-1 space-y-2">
                                            <h3 class="text-sm sm:text-base font-bold text-zinc-900 leading-snug">
                                                {{ $schedule->subject }}
                                            </h3>
                                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-blue-50 text-blue-800 border border-blue-200/80 font-mono font-bold text-[11px] leading-none">
                                                    Kelas: {{ $schedule->schoolClass->class_name }}
                                                </span>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-zinc-100 text-zinc-700 border border-zinc-200 font-mono text-[11px] leading-none">
                                                    <svg class="w-3 h-3 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                                                    {{ $schedule->room->room_name ?? '-' }}
                                                </span>
                                                @if(auth()->user()->role_id !== 2)
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-zinc-50 text-zinc-600 border border-zinc-200 text-[11px] leading-none">
                                                        <svg class="w-3 h-3 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                        {{ $schedule->teacher->fullname }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Quick Actions (Unified Height) --}}
                                        <div class="flex items-center justify-end gap-2 shrink-0 pt-3 md:pt-0 border-t md:border-t-0 border-zinc-100">
                                            @if(auth()->user()->role_id === 2)
                                                <a 
                                                    href="{{ route('journals.create', ['schedule_id' => $schedule->id]) }}" 
                                                    class="h-8 inline-flex items-center gap-1.5 px-3.5 bg-zinc-900 hover:bg-black text-white text-xs font-semibold rounded-lg transition shadow-none cursor-pointer shrink-0"
                                                >
                                                    <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                    <span>Isi Jurnal</span>
                                                </a>
                                            @endif

                                            @if($canManageSchedule)
                                                <a 
                                                    href="{{ route('schedules.edit', $schedule) }}" 
                                                    class="h-8 inline-flex items-center px-3 bg-white border border-zinc-200 hover:bg-zinc-50 text-zinc-700 text-xs font-semibold rounded-lg transition shrink-0"
                                                >
                                                    Edit
                                                </a>
                                                <form method="POST" action="{{ route('schedules.destroy', $schedule) }}" class="inline-flex items-center m-0 p-0" onsubmit="return confirm('Hapus jadwal ini?')">
                                                    @csrf @method('DELETE')
                                                    <button class="h-8 w-8 inline-flex items-center justify-center text-zinc-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer shrink-0" title="Hapus Jadwal">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach

                {{-- State jika hari tertentu yang dipilih tidak memiliki jadwal KBM --}}
                @foreach($orderedDays as $day)
                    @if(!$groupedSchedules->has($day) || $groupedSchedules[$day]->isEmpty())
                        <div 
                            x-show="activeDay === '{{ $day }}'" 
                            class="bg-white border border-zinc-200/90 rounded-xl p-8 sm:p-12 text-center space-y-3 shadow-sm"
                        >
                            <div class="w-12 h-12 rounded-full bg-zinc-100 text-zinc-400 mx-auto flex items-center justify-center">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-zinc-900">
                                    Tidak Ada Jadwal KBM pada Hari {{ $day }}
                                    @if($todayName === $day)
                                        <span class="inline-flex items-center px-2 py-0.5 ml-1 rounded-full text-[10px] font-mono font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">Hari Ini</span>
                                    @endif
                                </h3>
                                <p class="text-xs text-zinc-500 mt-1 max-w-sm mx-auto">
                                    @if(auth()->user()->role_id === 2)
                                        Anda tidak memiliki jadwal mengajar yang terdaftar pada hari {{ $day }}.
                                    @else
                                        Tidak ada sesi jam pelajaran yang dijadwalkan pada hari {{ $day }}.
                                    @endif
                                </p>
                            </div>
                            <div class="pt-2">
                                <button 
                                    type="button" 
                                    @click="activeDay = 'all'" 
                                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-zinc-700 bg-zinc-100 hover:bg-zinc-200 rounded-lg transition cursor-pointer"
                                >
                                    <span>Tampilkan Semua Hari</span>
                                    <span class="font-mono text-[10px] text-zinc-500">({{ $schedules->count() }} Sesi)</span>
                                </button>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- B. MODE GRID MINGGUAN (TIMETABLE BOARD) --}}
            <div x-show="viewMode === 'grid'" class="overflow-x-auto pb-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 min-w-[800px]">
                    @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $day)
                        @php
                            $daySchedules = $groupedSchedules->get($day) ?? collect();
                            $totalDayJp = $daySchedules->sum('total_jp');
                            $isToday = ($todayName === $day);
                        @endphp
                        <div class="bg-zinc-50/70 border {{ $isToday ? 'border-zinc-900 ring-1 ring-zinc-900' : 'border-zinc-200/90' }} rounded-xl overflow-hidden flex flex-col">
                            {{-- Day Col Header --}}
                            <div class="px-3.5 py-3 {{ $isToday ? 'bg-zinc-900 text-white' : 'bg-white text-zinc-900 border-b border-zinc-200/80' }} flex items-center justify-between">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-xs uppercase font-mono tracking-wider">{{ $day }}</span>
                                    @if($isToday)
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                                    @endif
                                </div>
                                <span class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded {{ $isToday ? 'bg-white/20 text-white' : 'bg-zinc-100 text-zinc-700' }}">
                                    {{ $totalDayJp }} JP
                                </span>
                            </div>

                            {{-- Day Schedule Stack --}}
                            <div class="p-2.5 space-y-2.5 flex-1">
                                @forelse($daySchedules as $schedule)
                                    <div class="bg-white border border-zinc-200 rounded-lg p-3 hover:border-zinc-400 hover:shadow-sm transition space-y-2 flex flex-col justify-between">
                                        <div class="space-y-1.5">
                                            <div class="flex items-center justify-between text-[10px] font-mono">
                                                <span class="font-bold px-1.5 py-0.5 rounded bg-zinc-900 text-white leading-none">
                                                    {{ $schedule->period_label }}
                                                </span>
                                                <span class="text-zinc-500 font-semibold">
                                                    {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}&ndash;{{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                                </span>
                                            </div>

                                            <h4 class="font-bold text-zinc-900 text-xs leading-snug line-clamp-2">
                                                {{ $schedule->subject }}
                                            </h4>

                                            <div class="flex flex-wrap items-center gap-1 text-[10px] font-mono pt-0.5">
                                                <span class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-800 border border-blue-200 font-bold leading-none">
                                                    {{ $schedule->schoolClass->class_name }}
                                                </span>
                                                <span class="px-1.5 py-0.5 rounded bg-zinc-100 text-zinc-600 border border-zinc-200 leading-none">
                                                    {{ $schedule->room->room_name ?? '-' }}
                                                </span>
                                            </div>
                                        </div>

                                        @if(auth()->user()->role_id === 2)
                                            <div class="pt-1.5 border-t border-zinc-100">
                                                <a 
                                                    href="{{ route('journals.create', ['schedule_id' => $schedule->id]) }}" 
                                                    class="block text-center py-1.5 bg-zinc-900 hover:bg-black text-white text-[10px] font-semibold rounded-md transition shadow-none"
                                                >
                                                    Isi Jurnal &rarr;
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="py-10 text-center text-xs font-mono text-zinc-400">
                                        Tidak ada jadwal
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
