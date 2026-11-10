<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('exams.index') }}" class="p-2 -ml-2 rounded-lg text-zinc-400 hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] transition" title="Kembali ke Repositori">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0]">
                    Publikasi Materi &amp; Ujian Baru
                </h1>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl" x-data="{
        category: '{{ old('category', 'materi') }}',
        selectedClasses: {{ json_encode(old('class_ids', [])) }},
        selectedFileName: '',
        selectedFileSize: '',
        selectedFileExt: '',
        isUploading: false,
        uploadProgress: 0,
        uploadProgressText: '',
        fileError: '',

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) {
                this.resetFile();
                return;
            }
            if (file.size > 20 * 1024 * 1024) {
                this.fileError = 'Ukuran berkas melebihi batas maksimal 20 MB.';
                this.resetFile();
                return;
            }
            this.fileError = '';
            this.selectedFileName = file.name;
            const ext = file.name.split('.').pop().toUpperCase();
            this.selectedFileExt = ext;
            
            if (file.size >= 1048576) {
                this.selectedFileSize = (file.size / 1048576).toFixed(1) + ' MB';
            } else {
                this.selectedFileSize = Math.round(file.size / 1024) + ' KB';
            }
        },

        resetFile() {
            this.selectedFileName = '';
            this.selectedFileSize = '';
            this.selectedFileExt = '';
            const input = document.getElementById('material_file');
            if (input) input.value = '';
        },

        selectAllClasses(ids) {
            this.selectedClasses = ids;
        },

        clearAllClasses() {
            this.selectedClasses = [];
        },

        submitForm() {
            if (!this.$refs.form.reportValidity()) return;
            if (this.selectedClasses.length === 0) {
                alert('Pilih minimal satu kelas tujuan materi/ujian ini.');
                return;
            }

            const fileInput = document.getElementById('material_file');
            if (fileInput && fileInput.files.length > 0) {
                this.isUploading = true;
                const formData = new FormData(this.$refs.form);
                const xhr = new XMLHttpRequest();
                xhr.open('POST', this.$refs.form.action);

                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        const loadedMB = (e.loaded / (1024 * 1024)).toFixed(1);
                        const totalMB = (e.total / (1024 * 1024)).toFixed(1);
                        this.uploadProgress = percent;
                        this.uploadProgressText = `Mengunggah berkas... ${loadedMB} MB / ${totalMB} MB (${percent}%)`;
                    }
                });

                xhr.addEventListener('load', () => {
                    if (xhr.status >= 200 && xhr.status < 400) {
                        // Successful submission / redirect
                        window.location.href = xhr.responseURL || '{{ route('exams.index') }}';
                    } else {
                        this.isUploading = false;
                        alert('Terjadi kesalahan saat mengunggah: ' + xhr.statusText);
                    }
                });

                xhr.addEventListener('error', () => {
                    this.isUploading = false;
                    alert('Gagal mengunggah berkas. Periksa koneksi internet Anda.');
                });

                xhr.send(formData);
            } else {
                this.$refs.form.submit();
            }
        }
    }">
        <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200/90 dark:border-[#2C2C30] rounded-2xl shadow-sm overflow-hidden">
            <form x-ref="form" action="{{ route('exams.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="p-6 sm:p-8 space-y-8">
                    <!-- SECTION 1: KATEGORI KONTEN -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] uppercase tracking-wider font-mono">
                                1. Jenis Konten Akademik <span class="text-rose-500">*</span>
                            </label>
                            <span class="text-[11px] text-zinc-500 dark:text-[#97979B]">Pilih tipe sumber daya yang akan dibagikan</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <!-- Option 1: Materi -->
                            <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition select-none"
                                   :class="category === 'materi' ? 'border-emerald-500 bg-emerald-50 dark:bg-[#16281D] ring-2 ring-emerald-500/30' : 'border-zinc-200 dark:border-[#2C2C30] hover:border-zinc-300 dark:hover:border-[#3A3A3F] bg-white dark:bg-[#1E1E22]'">
                                <input type="radio" name="category" value="materi" x-model="category" class="sr-only">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-[#1E3828] text-emerald-800 dark:text-emerald-300 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                        </svg>
                                    </span>
                                    <div>
                                        <span class="text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] block">Materi Pembelajaran</span>
                                        <span class="text-[10px] font-mono text-emerald-700 dark:text-emerald-400 font-semibold uppercase">Modul / Slide / PDF</span>
                                    </div>
                                </div>
                                <p class="mt-2.5 text-[11px] text-zinc-500 dark:text-[#97979B] leading-relaxed">
                                    Modul ajar, slide presentasi materi, catatan rangkuman, atau panduan praktik KBM.
                                </p>
                            </label>

                            <!-- Option 2: Bank Soal & LKPD -->
                            <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition select-none"
                                   :class="category === 'bank_soal' ? 'border-sky-500 bg-sky-50 dark:bg-[#16222F] ring-2 ring-sky-500/30' : 'border-zinc-200 dark:border-[#2C2C30] hover:border-zinc-300 dark:hover:border-[#3A3A3F] bg-white dark:bg-[#1E1E22]'">
                                <input type="radio" name="category" value="bank_soal" x-model="category" class="sr-only">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-sky-100 dark:bg-[#1E3042] text-sky-800 dark:text-sky-300 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                        </svg>
                                    </span>
                                    <div>
                                        <span class="text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] block">Bank Soal &amp; LKPD</span>
                                        <span class="text-[10px] font-mono text-sky-700 dark:text-sky-400 font-semibold uppercase">Latihan / Tugas</span>
                                    </div>
                                </div>
                                <p class="mt-2.5 text-[11px] text-zinc-500 dark:text-[#97979B] leading-relaxed">
                                    Lembar Kerja Peserta Didik, kumpulan bank soal latihan, dan instrumen tugas mandiri.
                                </p>
                            </label>

                            <!-- Option 3: Ujian & Asesmen -->
                            <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition select-none"
                                   :class="category === 'ujian' ? 'border-rose-500 bg-rose-50 dark:bg-[#2D1618] ring-2 ring-rose-500/30' : 'border-zinc-200 dark:border-[#2C2C30] hover:border-zinc-300 dark:hover:border-[#3A3A3F] bg-white dark:bg-[#1E1E22]'">
                                <input type="radio" name="category" value="ujian" x-model="category" class="sr-only">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-rose-100 dark:bg-[#3D1E20] text-rose-800 dark:text-rose-300 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                                        </svg>
                                    </span>
                                    <div>
                                        <span class="text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] block">Ujian &amp; Asesmen</span>
                                        <span class="text-[10px] font-mono text-rose-700 dark:text-rose-400 font-semibold uppercase">UH / PTS / PAS / UKK</span>
                                    </div>
                                </div>
                                <p class="mt-2.5 text-[11px] text-zinc-500 dark:text-[#97979B] leading-relaxed">
                                    Jadwal ulangan harian, asesmen tengah/akhir semester, link CBT, atau lembar ujian resmi.
                                </p>
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('category')" class="mt-1.5" />
                    </div>

                    <!-- SECTION 2: KELAS TUJUAN (MULTI-BROADCAST) -->
                    <div class="pt-6 border-t border-zinc-100 dark:border-[#2C2C30]">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                            <div>
                                <label class="text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] uppercase tracking-wider font-mono">
                                    2. Kelas Sasaran Distribusi <span class="text-rose-500">*</span>
                                </label>
                                <p class="text-[11px] text-zinc-500 dark:text-[#97979B] mt-0.5">
                                    Dapat memilih satu atau lebih kelas sekaligus (multi-broadcast)
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" 
                                        @click="selectAllClasses({{ json_encode($allClasses->pluck('id')) }})"
                                        class="px-2.5 py-1 text-[11px] font-semibold text-zinc-700 dark:text-[#EDEDF0] bg-zinc-100 dark:bg-[#2C2C30] hover:bg-zinc-200 dark:hover:bg-[#3A3A3F] rounded-md transition">
                                    Pilih Semua Kelas
                                </button>
                                <button type="button" 
                                        @click="clearAllClasses()"
                                        class="px-2.5 py-1 text-[11px] font-semibold text-zinc-500 dark:text-[#97979B] hover:text-zinc-800 dark:hover:text-white bg-zinc-50 dark:bg-[#1E1E22] hover:bg-zinc-100 dark:hover:bg-[#2C2C30] border border-zinc-200 dark:border-[#2C2C30] rounded-md transition">
                                    Hapus Pilihan
                                </button>
                            </div>
                        </div>

                        <!-- Class Selection Grid -->
                        <div class="p-4 bg-zinc-50/70 dark:bg-[#19191C] border border-zinc-200 dark:border-[#2B2B30] rounded-xl max-h-56 overflow-y-auto">
                            @php
                                // Group classes by Grade (X, XI, XII)
                                $groupedClasses = $allClasses->groupBy(function($c) {
                                    if (str_starts_with($c->class_name, 'X ') || $c->class_name === 'X') return 'Kelas X';
                                    if (str_starts_with($c->class_name, 'XI ') || $c->class_name === 'XI') return 'Kelas XI';
                                    if (str_starts_with($c->class_name, 'XII ') || $c->class_name === 'XII') return 'Kelas XII';
                                    return 'Lainnya';
                                });
                            @endphp

                            <div class="space-y-4">
                                @foreach($groupedClasses as $gradeTitle => $classes)
                                    <div>
                                        <div class="text-xs font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider mb-2">
                                            {{ $gradeTitle }}
                                        </div>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                                            @foreach($classes as $c)
                                                <label class="flex items-center gap-2 p-2 rounded-lg border text-xs cursor-pointer select-none transition"
                                                       :class="selectedClasses.includes({{ $c->id }}) ? 'bg-[#0093dd] text-white border-[#0093dd] font-semibold shadow-xs' : 'bg-white dark:bg-[#1E1E22] text-zinc-700 dark:text-[#EDEDF0] border-zinc-200 dark:border-[#2B2B30] hover:bg-zinc-50 dark:hover:bg-[#26262A]'">
                                                    <input type="checkbox" 
                                                           name="class_ids[]" 
                                                           value="{{ $c->id }}" 
                                                           x-model.number="selectedClasses" 
                                                           class="rounded border-zinc-300 dark:border-[#3A3A3F] text-[#0093dd] focus:ring-0">
                                                    <span class="truncate">{{ $c->class_name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="mt-2 flex items-center justify-between text-xs">
                            <span class="text-zinc-500 dark:text-[#97979B]">
                                Terpilih: <strong class="text-zinc-900 dark:text-[#EDEDF0] font-mono" x-text="selectedClasses.length"></strong> kelas
                            </span>
                            <x-input-error :messages="$errors->get('class_ids')" />
                        </div>
                    </div>

                    <!-- SECTION 3: MATA PELAJARAN & JUDUL -->
                    <div class="pt-6 border-t border-zinc-100 dark:border-[#2C2C30] grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Mata Pelajaran -->
                        <div>
                            <label class="block text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] uppercase tracking-wider font-mono mb-2">
                                3. Mata Pelajaran <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   name="subject" 
                                   list="subject-suggestions" 
                                   value="{{ old('subject') }}" 
                                   required 
                                   placeholder="Contoh: Pemrograman Web, Matematika..." 
                                   class="w-full rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-4 py-2.5 text-sm text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-xs">
                            <datalist id="subject-suggestions">
                                @foreach($teacherSubjects as $subj)
                                    <option value="{{ $subj }}">
                                @endforeach
                            </datalist>
                            <p class="mt-1.5 text-[11px] text-zinc-400 dark:text-[#97979B]">Pilih dari daftar rekomendasi pengampu atau ketik baru.</p>
                            <x-input-error :messages="$errors->get('subject')" class="mt-1" />
                        </div>

                        <!-- Judul Konten -->
                        <div>
                            <label class="block text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] uppercase tracking-wider font-mono mb-2">
                                4. Judul Materi / Asesmen <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   name="title" 
                                   value="{{ old('title') }}" 
                                   required 
                                   placeholder="Contoh: Modul 03 - Basis Data Relasional, UTS Gasal..." 
                                   class="w-full rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-4 py-2.5 text-sm text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-xs">
                            <p class="mt-1.5 text-[11px] text-zinc-400 dark:text-[#97979B]">Judul ringkas dan jelas yang mudah dikenali siswa.</p>
                            <x-input-error :messages="$errors->get('title')" class="mt-1" />
                        </div>
                    </div>

                    <!-- SECTION 4: TANGGAL & RENTANG WAKTU -->
                    <div class="pt-6 border-t border-zinc-100 dark:border-[#2C2C30]">
                        <label class="block text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] uppercase tracking-wider font-mono mb-2">
                            5. Tanggal &amp; Waktu Pelaksanaan <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <!-- Tanggal Pelaksanaan -->
                            <div>
                                <label class="block text-[11px] font-semibold text-zinc-600 dark:text-[#97979B] mb-1">Tanggal Berlaku / Ujian</label>
                                <input type="date" 
                                       name="exam_date" 
                                       value="{{ old('exam_date', date('Y-m-d')) }}" 
                                       required 
                                       class="w-full rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3.5 py-2.5 text-sm text-zinc-900 dark:text-[#EDEDF0] font-mono focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-xs">
                                <x-input-error :messages="$errors->get('exam_date')" class="mt-1" />
                            </div>

                            <!-- Jam Mulai -->
                            <div>
                                <label class="block text-[11px] font-semibold text-zinc-600 dark:text-[#97979B] mb-1">Jam Mulai <span class="text-zinc-400 font-normal">(opsional)</span></label>
                                <input type="time" 
                                       name="start_time" 
                                       value="{{ old('start_time') }}" 
                                       class="w-full rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3.5 py-2.5 text-sm text-zinc-900 dark:text-[#EDEDF0] font-mono focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-xs">
                                <x-input-error :messages="$errors->get('start_time')" class="mt-1" />
                            </div>

                            <!-- Jam Selesai -->
                            <div>
                                <label class="block text-[11px] font-semibold text-zinc-600 dark:text-[#97979B] mb-1">Jam Selesai <span class="text-zinc-400 font-normal">(opsional)</span></label>
                                <input type="time" 
                                       name="end_time" 
                                       value="{{ old('end_time') }}" 
                                       class="w-full rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3.5 py-2.5 text-sm text-zinc-900 dark:text-[#EDEDF0] font-mono focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-xs">
                                <x-input-error :messages="$errors->get('end_time')" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 5: PETUNJUK / DESKRIPSI -->
                    <div class="pt-6 border-t border-zinc-100 dark:border-[#2C2C30]">
                        <label class="block text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] uppercase tracking-wider font-mono mb-2">
                            6. Petunjuk Belajar / Catatan Instruksi <span class="text-zinc-400 font-normal lowercase">(opsional)</span>
                        </label>
                        <textarea name="description" 
                                  rows="3" 
                                  class="w-full rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-4 py-2.5 text-sm text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-xs placeholder:text-zinc-400 dark:placeholder:text-[#5B5B61] leading-relaxed" 
                                  placeholder="Tuliskan petunjuk pengerjaan tugas, tata tertib ujian, atau ruang lingkup materi yang dipelajari...">{{ old('description') }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>

                    <!-- SECTION 6: LAMPIRAN BERKAS & TAUTAN CBT EKSTERNAL -->
                    <div class="pt-6 border-t border-zinc-100 dark:border-[#2C2C30] space-y-4">
                        <label class="block text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] uppercase tracking-wider font-mono">
                            7. Lampiran Berkas &amp; Tautan Eksternal
                        </label>

                        <!-- File Upload Box -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-semibold text-zinc-700 dark:text-[#EDEDF0]">Berkas Dokumen / Arsip (Opsional)</span>
                                <span class="text-[11px] text-zinc-400 dark:text-[#97979B]">PDF, Word, PPT, Excel, ZIP, Gambar maks. 20MB</span>
                            </div>

                            <div class="border-2 border-dashed border-zinc-300 dark:border-[#2B2B30] hover:border-zinc-400 dark:hover:border-[#3A3A3F] bg-zinc-50/50 dark:bg-[#19191C] hover:bg-zinc-100 dark:hover:bg-[#26262A] rounded-xl p-6 transition text-center cursor-pointer relative"
                                 @click="document.getElementById('material_file').click()"
                                 @dragover.prevent="$el.classList.add('border-zinc-900', 'bg-zinc-100')"
                                 @dragleave.prevent="$el.classList.remove('border-zinc-900', 'bg-zinc-100')"
                                 @drop.prevent="
                                     $el.classList.remove('border-zinc-900', 'bg-zinc-100');
                                     if ($event.dataTransfer.files.length) {
                                         document.getElementById('material_file').files = $event.dataTransfer.files;
                                         handleFileSelect({ target: document.getElementById('material_file') });
                                     }
                                 ">
                                <input type="file" 
                                       id="material_file" 
                                       name="material_file" 
                                       class="sr-only" 
                                       accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.rar,.jpg,.jpeg,.png"
                                       @change="handleFileSelect($event)">

                                <template x-if="!selectedFileName">
                                    <div class="space-y-2">
                                        <div class="w-10 h-10 rounded-full bg-zinc-100 dark:bg-[#232327] text-zinc-500 dark:text-[#97979B] mx-auto flex items-center justify-center">
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                                            </svg>
                                        </div>
                                        <div class="text-xs text-zinc-600 dark:text-[#97979B]">
                                            <span class="font-bold text-zinc-900 dark:text-[#EDEDF0] hover:underline">Klik untuk memilih berkas</span> atau seret dokumen ke sini
                                        </div>
                                        <p class="text-[11px] text-zinc-400 dark:text-[#97979B] font-mono">PDF &bull; DOCX &bull; PPTX &bull; XLSX &bull; ZIP &bull; RAR (Maks 20MB)</p>
                                    </div>
                                </template>

                                <template x-if="selectedFileName">
                                    <div class="flex items-center justify-between p-3 bg-white dark:bg-[#232327] border border-zinc-200 dark:border-[#2B2B30] rounded-xl shadow-xs text-left" @click.stop>
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-10 h-10 rounded-lg bg-zinc-900 dark:bg-[#0093dd] text-white font-mono font-bold text-xs flex items-center justify-center shrink-0"
                                                 x-text="selectedFileExt"></div>
                                            <div class="min-w-0">
                                                <div class="text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] truncate" x-text="selectedFileName"></div>
                                                <div class="text-[11px] text-zinc-500 dark:text-[#97979B] font-mono" x-text="selectedFileSize"></div>
                                            </div>
                                        </div>
                                        <button type="button" 
                                                @click="resetFile()" 
                                                class="p-1.5 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/15 rounded-lg transition" 
                                                title="Hapus Berkas">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            <p class="text-xs text-rose-600 mt-1" x-show="fileError" x-text="fileError"></p>
                            <x-input-error :messages="$errors->get('material_file')" class="mt-1" />
                        </div>

                        <!-- External Link Input (CBT / Google Forms / Drive) -->
                        <div class="pt-2">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold text-zinc-700 dark:text-[#EDEDF0]">
                                    Tautan CBT / Ujian Online / Cloud Drive <span class="text-zinc-400 font-normal">(opsional)</span>
                                </label>
                                <span class="text-[11px] text-zinc-400 dark:text-[#97979B]">Google Forms, Quizizz, CBT, Drive</span>
                            </div>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                                    </svg>
                                </div>
                                <input type="url" 
                                       name="external_link" 
                                       value="{{ old('external_link') }}" 
                                       placeholder="https://forms.gle/... atau https://cbt.sekolah.sch.id/..." 
                                       class="w-full rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] pl-9 pr-4 py-2.5 text-sm text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-xs font-mono placeholder:text-zinc-400 dark:placeholder:text-[#5B5B61]">
                            </div>
                            <x-input-error :messages="$errors->get('external_link')" class="mt-1" />
                        </div>
                    </div>

                    <!-- UPLOAD PROGRESS BAR -->
                    <div x-show="isUploading" x-cloak class="p-4 bg-zinc-900 dark:bg-[#232327] text-white rounded-xl space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-medium" x-text="uploadProgressText"></span>
                            <span class="font-mono font-bold" x-text="uploadProgress + '%'"></span>
                        </div>
                        <div class="w-full bg-zinc-800 dark:bg-[#19191C] rounded-full h-2 overflow-hidden">
                            <div class="bg-[#0093dd] h-2 rounded-full transition-all duration-150" :style="`width: ${uploadProgress}%`"></div>
                        </div>
                    </div>
                </div>

                <!-- Form Submit Footer -->
                <div class="px-6 sm:px-8 py-4 bg-zinc-50 dark:bg-[#19191C] border-t border-zinc-200/90 dark:border-[#2C2C30] flex items-center justify-between">
                    <a href="{{ route('exams.index') }}" 
                       x-show="!isUploading" 
                       class="px-4 py-2 text-xs font-semibold text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] rounded-lg transition">
                        Batal
                    </a>
                    <div class="flex items-center gap-3">
                        <button type="button" 
                                :disabled="isUploading"
                                @click="submitForm()" 
                                class="inline-flex items-center gap-2 px-6 py-2.5 bg-zinc-900 hover:bg-black dark:bg-[#0093dd] dark:hover:bg-[#0082c4] text-white text-xs font-semibold uppercase tracking-wider rounded-lg transition shadow-xs disabled:opacity-50 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                            </svg>
                            <span x-text="isUploading ? 'Sedang Memproses...' : 'Publikasikan Konten'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
