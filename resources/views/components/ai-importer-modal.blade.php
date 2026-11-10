@props(['type', 'title' => 'Import dengan AI'])

@php
    $sysConfig = \App\Models\SystemConfiguration::first();
    $defaultCycle = $sysConfig ? $sysConfig->active_cycle : 'Putaran 1';
    $defaultModel = $sysConfig?->effective_gemini_schedule_model ?? 'gemini-3.5-flash-lite';
@endphp

<div 
    x-data="{ 
        open: false, 
        isUploading: false,
        uploadProgressText: '',
        fileName: '', 
        fileSize: '',
        fileError: '',
        targetCycle: '{{ $defaultCycle }}',
        selectedModel: '{{ $defaultModel }}',
        handleFile(file) {
            this.fileError = '';
            if (!file) return;

            if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
                this.fileError = 'Berkas harus berupa dokumen format PDF (.pdf).';
                return;
            }

            if (file.size > 15 * 1024 * 1024) {
                this.fileError = 'Ukuran file melebihi batas 15 Megabyte (15MB).';
                return;
            }

            this.fileName = file.name;
            this.fileSize = (file.size / 1024 / 1024).toFixed(2) + ' MB';
        }
    }" 
    class="inline-block"
    @keydown.escape.window="if(!isUploading) open = false"
>
    <!-- Trigger Button -->
    <button 
        type="button" 
        @click="open = true" 
        class="inline-flex items-center gap-2 px-3 py-1.5 bg-zinc-900 text-white text-xs font-semibold rounded-md hover:bg-black transition shadow-xs cursor-pointer"
    >
        <svg class="w-3.5 h-3.5 text-zinc-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
        </svg>
        <span>{{ $title }}</span>
    </button>

    <!-- Modal Overlay -->
    <div 
        x-show="open" 
        x-cloak 
        style="display: none;" 
        class="fixed inset-0 z-50 overflow-y-auto" 
        role="dialog" 
        aria-modal="true"
    >
        <div 
            x-show="open"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-zinc-900/60 backdrop-blur-sm transition-opacity"
            @click="if(!isUploading) open = false"
        ></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-6">
            <div 
                x-show="open"
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
                        <h2 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-[#EDEDF0] tracking-tight flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                            Impor {{ $type }} Otomatis dengan AI
                        </h2>
                        <p class="text-xs text-zinc-500 mt-1">
                            Unggah dokumen PDF. Google Gemini AI akan mem-parsing seluruh data {{ strtolower($type) }} secara cerdas ke sistem database.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded bg-zinc-100 dark:bg-[#232327] text-zinc-700 dark:text-[#EDEDF0] text-[11px] font-mono font-semibold" x-text="'Model: ' + selectedModel">
                        </span>
                        <button 
                            type="button" 
                            @click="if(!isUploading) open = false"
                            class="p-1.5 rounded-md text-zinc-400 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] transition cursor-pointer disabled:opacity-40"
                            :disabled="isUploading"
                        >
                            <span class="sr-only">Tutup</span>
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-5 sm:p-6 space-y-5">
                    @php
                        $geminiConfig = \App\Models\SystemConfiguration::first();
                    @endphp
                    @if(!$geminiConfig || !$geminiConfig->gemini_api_key)
                        <div class="p-4 rounded-lg bg-amber-50 dark:bg-[#282115] border border-amber-200 dark:border-amber-700/60 text-amber-900 dark:text-amber-200 text-xs flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                            <div>
                                <span class="font-bold">Google Gemini API Key belum terpasang.</span>
                                <p class="mt-0.5 text-amber-800 dark:text-amber-300">
                                    Silakan masukkan API Key di menu Pengaturan Sistem Kurikulum sebelum menggunakan fitur AI ini.
                                </p>
                            </div>
                        </div>
                    @else
                        <form 
                            method="POST" 
                            action="{{ route('ai-importer.process') }}" 
                            enctype="multipart/form-data" 
                            class="space-y-5"
                            @submit="isUploading = true"
                        >
                            @csrf
                            <input type="hidden" name="document_type" value="{{ $type }}">

                            @if(in_array($type, ['Jadwal Pelajaran', 'Jadwal Ruangan']))
                                <div>
                                    <label class="block text-xs font-semibold text-zinc-700 dark:text-[#EDEDF0] uppercase tracking-wider mb-2">
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
                                </div>
                            @endif

                            <div>
                                <label class="block text-xs font-semibold text-zinc-700 dark:text-[#EDEDF0] uppercase tracking-wider mb-2">
                                    Pilih Model Google Gemini AI
                                </label>
                                <select 
                                    name="ai_model" 
                                    x-model="selectedModel"
                                    class="w-full rounded-xl border border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3.5 py-2.5 text-xs font-mono text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none transition"
                                >
                                    <option value="gemini-3.5-flash-lite">gemini-3.5-flash-lite (Gemini 3.5 Flash Lite — Cepat &amp; Hemat Kuota)</option>
                                    <option value="gemini-3.1-flash-lite">gemini-3.1-flash-lite (Gemini 3.1 Flash Lite — Alternatif Kuota Tinggi)</option>
                                    <option value="gemini-3.5-flash">gemini-3.5-flash (Gemini 3.5 Flash — Penalaran Multimodal Tinggi)</option>
                                    <option value="gemini-3.7-flash">gemini-3.7-flash (Gemini 3.7 Flash — Penalaran Multimodal Ekstra Kuat)</option>
                                </select>
                                <p class="text-[11px] text-zinc-500 mt-1">
                                    Pilih model AI yang digunakan untuk mengekstrak dokumen ini. Gunakan <strong>Gemini 3.5 Flash Lite</strong> untuk proses cepat dan hemat batas kuota.
                                </p>
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
                                                    <span class="text-zinc-500"> atau seret dokumen ke sini</span>
                                                    <p class="text-[11px] text-zinc-400 font-mono mt-1">Maksimal 15MB (.pdf)</p>
                                                </div>
                                            </template>
                                            <template x-if="fileName">
                                                <div class="font-mono text-zinc-800 dark:text-[#EDEDF0]">
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
                                        name="document_file"
                                        type="file"
                                        accept=".pdf,application/pdf"
                                        class="hidden"
                                        required
                                        @change="handleFile($event.target.files[0])"
                                    />
                                </div>

                                <p class="text-xs text-rose-600 mt-1.5 font-medium" x-show="fileError" x-text="fileError"></p>
                            </div>

                            <div class="flex items-center justify-between gap-4 pt-1">
                                <button
                                    type="button"
                                    @click="open = false"
                                    class="px-4 py-2.5 border border-zinc-200 dark:border-[#2B2B30] rounded-lg text-xs font-semibold text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] transition cursor-pointer"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-zinc-900 hover:bg-black text-white font-semibold text-xs uppercase tracking-wider rounded-lg transition shadow-none cursor-pointer disabled:opacity-50"
                                    :disabled="isUploading"
                                    @click="
                                        if (!fileName) { 
                                            $refs.pdfInput.click(); 
                                            return;
                                        }
                                        isUploading = true;
                                        
                                        let formData = new FormData($el.closest('form'));
                                        let xhr = new XMLHttpRequest();
                                        xhr.open('POST', $el.closest('form').action);
                                        
                                        xhr.upload.addEventListener('progress', (e) => {
                                            if (e.lengthComputable) {
                                                let loadedMB = (e.loaded / (1024*1024)).toFixed(2);
                                                let totalMB = (e.total / (1024*1024)).toFixed(2);
                                                let percent = Math.round((e.loaded / e.total) * 100);
                                                uploadProgressText = `${loadedMB}MB / ${totalMB}MB (${percent}%)`;
                                            }
                                        });

                                        xhr.onload = () => {
                                            if (xhr.status >= 200 && xhr.status < 300) {
                                                document.open();
                                                document.write(xhr.responseText);
                                                document.close();
                                            } else {
                                                isUploading = false;
                                                fileError = 'Gagal mengunggah file. Silakan coba lagi.';
                                                // Removed incorrect redirect logic on 5xx errors
                                            }
                                        };

                                        xhr.onerror = () => {
                                            isUploading = false;
                                            fileError = 'Terjadi kesalahan jaringan saat mengunggah.';
                                        };

                                        xhr.send(formData);
                                    "
                                >
                                    <svg x-show="!isUploading" class="w-4 h-4 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
                                    </svg>
                                    <svg x-show="isUploading" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="isUploading ? 'Sedang Mengekstrak... ' + (uploadProgressText || '') : (fileName ? 'Mulai Ekstraksi &amp; Impor' : 'Pilih Berkas PDF &amp; Ekstraksi')"></span>
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
