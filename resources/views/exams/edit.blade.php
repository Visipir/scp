<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('exams.index') }}" class="p-2 -ml-2 rounded-lg text-zinc-400 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] transition" title="Kembali">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0]">
                    Ubah Materi / Ujian
                </h1>
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl" x-data="{
        category: '{{ old('category', $exam->category) }}',
        removeFile: false,
        selectedFileName: '',
        selectedFileSize: '',
        selectedFileExt: '',
        fileError: '',

        handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) {
                this.resetFile();
                return;
            }
            if (file.size > 20 * 1024 * 1024) {
                this.fileError = 'Ukuran berkas melebihi 20 MB.';
                this.resetFile();
                return;
            }
            this.fileError = '';
            this.selectedFileName = file.name;
            this.selectedFileExt = file.name.split('.').pop().toUpperCase();
            if (file.size >= 1048576) {
                this.selectedFileSize = (file.size / 1048576).toFixed(1) + ' MB';
            } else {
                this.selectedFileSize = Math.round(file.size / 1024) + ' KB';
            }
            this.removeFile = false;
        },

        resetFile() {
            this.selectedFileName = '';
            this.selectedFileSize = '';
            this.selectedFileExt = '';
            const input = document.getElementById('material_file');
            if (input) input.value = '';
        }
    }">
        <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200/90 dark:border-[#2B2B30] rounded-2xl shadow-sm overflow-hidden">
            <form action="{{ route('exams.update', $exam) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="p-6 sm:p-8 space-y-8">
                    <!-- SECTION 1: KATEGORI KONTEN -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] uppercase tracking-wider font-mono">
                                1. Jenis Konten Akademik <span class="text-rose-500">*</span>
                            </label>
                            <span class="text-[11px] text-zinc-500">Pilih tipe konten</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <!-- Option 1: Materi -->
                            <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition select-none"
                                   :class="category === 'materi' ? 'border-emerald-500 bg-emerald-50 dark:bg-[#16281D] ring-2 ring-emerald-500/30' : 'border-zinc-200 dark:border-[#2B2B30] hover:border-zinc-300 dark:hover:border-[#3A3A3F] bg-white dark:bg-[#232327]'">
                                <input type="radio" name="category" value="materi" x-model="category" class="sr-only">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                        </svg>
                                    </span>
                                    <div>
                                        <span class="text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] block">Materi Pembelajaran</span>
                                        <span class="text-[10px] font-mono text-emerald-700 font-semibold uppercase">Modul / Slide</span>
                                    </div>
                                </div>
                            </label>

                            <!-- Option 2: Bank Soal & LKPD -->
                            <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition select-none"
                                   :class="category === 'bank_soal' ? 'border-sky-500 bg-sky-50 dark:bg-[#16222F] ring-2 ring-sky-500/30' : 'border-zinc-200 dark:border-[#2B2B30] hover:border-zinc-300 dark:hover:border-[#3A3A3F] bg-white dark:bg-[#232327]'">
                                <input type="radio" name="category" value="bank_soal" x-model="category" class="sr-only">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-sky-100 text-sky-800 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                        </svg>
                                    </span>
                                    <div>
                                        <span class="text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] block">Bank Soal &amp; LKPD</span>
                                        <span class="text-[10px] font-mono text-sky-700 font-semibold uppercase">Latihan / Tugas</span>
                                    </div>
                                </div>
                            </label>

                            <!-- Option 3: Ujian & Asesmen -->
                            <label class="relative flex flex-col p-4 rounded-xl border cursor-pointer transition select-none"
                                   :class="category === 'ujian' ? 'border-rose-500 bg-rose-50 dark:bg-[#2D1618] ring-2 ring-rose-500/30' : 'border-zinc-200 dark:border-[#2B2B30] hover:border-zinc-300 dark:hover:border-[#3A3A3F] bg-white dark:bg-[#232327]'">
                                <input type="radio" name="category" value="ujian" x-model="category" class="sr-only">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-8 h-8 rounded-lg bg-rose-100 text-rose-800 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                                        </svg>
                                    </span>
                                    <div>
                                        <span class="text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] block">Ujian &amp; Asesmen</span>
                                        <span class="text-[10px] font-mono text-rose-700 font-semibold uppercase">UH / PTS / PAS</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('category')" class="mt-1.5" />
                    </div>

                    <!-- SECTION 2: KELAS & MAPEL -->
                    <div class="pt-6 border-t border-zinc-100 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Kelas Sasaran -->
                        <div>
                            <label class="block text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] uppercase tracking-wider font-mono mb-2">
                                2. Kelas Sasaran <span class="text-rose-500">*</span>
                            </label>
                            <select name="class_id" required class="w-full rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3.5 py-2.5 text-sm text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-xs">
                                @foreach($allClasses as $c)
                                    <option value="{{ $c->id }}" @selected(old('class_id', $exam->class_id) == $c->id)>
                                        {{ $c->class_name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('class_id')" class="mt-1" />
                        </div>

                        <!-- Mata Pelajaran -->
                        <div>
                            <label class="block text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] uppercase tracking-wider font-mono mb-2">
                                3. Mata Pelajaran <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" 
                                   name="subject" 
                                   value="{{ old('subject', $exam->subject) }}" 
                                   required 
                                   class="w-full rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-4 py-2.5 text-sm text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-xs">
                            <x-input-error :messages="$errors->get('subject')" class="mt-1" />
                        </div>
                    </div>

                    <!-- SECTION 3: JUDUL KONTEN -->
                    <div class="pt-6 border-t border-zinc-100">
                        <label class="block text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] uppercase tracking-wider font-mono mb-2">
                            4. Judul Materi / Asesmen <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               name="title" 
                               value="{{ old('title', $exam->title) }}" 
                               required 
                               class="w-full rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-4 py-2.5 text-sm text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-xs">
                        <x-input-error :messages="$errors->get('title')" class="mt-1" />
                    </div>

                    <!-- SECTION 4: TANGGAL & RENTANG WAKTU -->
                    <div class="pt-6 border-t border-zinc-100">
                        <label class="block text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] uppercase tracking-wider font-mono mb-2">
                            5. Tanggal &amp; Waktu Pelaksanaan <span class="text-rose-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-zinc-600 mb-1">Tanggal Berlaku / Ujian</label>
                                <input type="date" 
                                       name="exam_date" 
                                       value="{{ old('exam_date', $exam->exam_date ? \Carbon\Carbon::parse($exam->exam_date)->format('Y-m-d') : '') }}" 
                                       required 
                                       class="w-full rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3.5 py-2.5 text-sm text-zinc-900 dark:text-[#EDEDF0] font-mono focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-xs">
                                <x-input-error :messages="$errors->get('exam_date')" class="mt-1" />
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-zinc-600 mb-1">Jam Mulai <span class="text-zinc-400 font-normal">(opsional)</span></label>
                                <input type="time" 
                                       name="start_time" 
                                       value="{{ old('start_time', $exam->start_time ? \Carbon\Carbon::parse($exam->start_time)->format('H:i') : '') }}" 
                                       class="w-full rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3.5 py-2.5 text-sm text-zinc-900 dark:text-[#EDEDF0] font-mono focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-xs">
                                <x-input-error :messages="$errors->get('start_time')" class="mt-1" />
                            </div>

                            <div>
                                <label class="block text-[11px] font-semibold text-zinc-600 mb-1">Jam Selesai <span class="text-zinc-400 font-normal">(opsional)</span></label>
                                <input type="time" 
                                       name="end_time" 
                                       value="{{ old('end_time', $exam->end_time ? \Carbon\Carbon::parse($exam->end_time)->format('H:i') : '') }}" 
                                       class="w-full rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3.5 py-2.5 text-sm text-zinc-900 dark:text-[#EDEDF0] font-mono focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-xs">
                                <x-input-error :messages="$errors->get('end_time')" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 5: PETUNJUK / DESKRIPSI -->
                    <div class="pt-6 border-t border-zinc-100">
                        <label class="block text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] uppercase tracking-wider font-mono mb-2">
                            6. Petunjuk Belajar / Catatan Instruksi <span class="text-zinc-400 font-normal lowercase">(opsional)</span>
                        </label>
                        <textarea name="description" 
                                  rows="3" 
                                  class="w-full rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-4 py-2.5 text-sm text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-xs placeholder:text-zinc-400 dark:placeholder:text-[#5B5B61] leading-relaxed">{{ old('description', $exam->description) }}</textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-1" />
                    </div>

                    <!-- SECTION 6: KELOLA BERKAS & TAUTAN -->
                    <div class="pt-6 border-t border-zinc-100 space-y-4">
                        <label class="block text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] uppercase tracking-wider font-mono">
                            7. Lampiran Berkas &amp; Tautan Eksternal
                        </label>

                        <!-- Existing File Card (if any) -->
                        @if($exam->material_file_path)
                            <div class="p-4 bg-zinc-50 dark:bg-[#232327] border border-zinc-200 dark:border-[#2B2B30] rounded-xl space-y-3" x-show="!removeFile">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-zinc-700 dark:text-[#EDEDF0] uppercase tracking-wider font-mono">Berkas Saat Ini</span>
                                    <label class="flex items-center gap-1.5 text-xs text-rose-600 hover:text-rose-800 cursor-pointer font-medium">
                                        <input type="checkbox" name="remove_file" value="1" x-model="removeFile" class="rounded border-zinc-300 text-rose-600 focus:ring-0">
                                        <span>Hapus lampiran berkas ini</span>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-lg">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="w-9 h-9 rounded bg-zinc-900 text-white font-mono font-bold text-xs flex items-center justify-center shrink-0">
                                            {{ $exam->file_extension ?? 'FILE' }}
                                        </span>
                                        <div class="min-w-0">
                                            <div class="text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] truncate">{{ $exam->file_name ?? basename($exam->material_file_path) }}</div>
                                            <div class="text-[11px] text-zinc-500 font-mono">{{ $exam->formatted_file_size }}</div>
                                        </div>
                                    </div>
                                    <a href="{{ route('exams.download', $exam) }}" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-zinc-700 dark:text-[#EDEDF0] bg-zinc-100 dark:bg-[#232327] hover:bg-zinc-200 dark:hover:bg-[#26262A] rounded-md transition">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                        Unduh
                                    </a>
                                </div>
                            </div>
                        @endif

                        <!-- Replace / Upload New File -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="text-xs font-semibold text-zinc-700 dark:text-[#EDEDF0]">
                                    {{ $exam->material_file_path ? 'Ganti dengan Berkas Baru (Opsional)' : 'Unggah Berkas Baru (Opsional)' }}
                                </span>
                                <span class="text-[11px] text-zinc-400">Maks. 20MB</span>
                            </div>

                            <div class="border-2 border-dashed border-zinc-300 dark:border-[#2B2B30] hover:border-zinc-400 dark:hover:border-[#3A3A3F] bg-zinc-50/50 dark:bg-[#19191C] hover:bg-zinc-100 dark:hover:bg-[#26262A] rounded-xl p-5 transition text-center cursor-pointer relative"
                                 @click="document.getElementById('material_file').click()">
                                <input type="file" 
                                       id="material_file" 
                                       name="material_file" 
                                       class="sr-only" 
                                       accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.rar,.jpg,.jpeg,.png"
                                       @change="handleFileSelect($event)">

                                <template x-if="!selectedFileName">
                                    <div class="text-xs text-zinc-600">
                                        <span class="font-bold text-zinc-900 dark:text-[#EDEDF0] hover:underline">Klik untuk memilih berkas pengganti</span>
                                        <p class="text-[11px] text-zinc-400 mt-1">PDF, Word, PPT, Excel, ZIP hingga 20MB</p>
                                    </div>
                                </template>

                                <template x-if="selectedFileName">
                                    <div class="flex items-center justify-between p-3 bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-lg text-left" @click.stop>
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="w-8 h-8 rounded bg-zinc-900 text-white font-mono font-bold text-xs flex items-center justify-center shrink-0"
                                                  x-text="selectedFileExt"></span>
                                            <div class="min-w-0">
                                                <div class="text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] truncate" x-text="selectedFileName"></div>
                                                <div class="text-[11px] text-zinc-500 font-mono" x-text="selectedFileSize"></div>
                                            </div>
                                        </div>
                                        <button type="button" @click="resetFile()" class="p-1 text-zinc-400 hover:text-rose-600 transition">
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

                        <!-- External Link Input -->
                        <div class="pt-2">
                            <label class="block text-xs font-semibold text-zinc-700 dark:text-[#EDEDF0] mb-1.5">
                                Tautan CBT / Ujian Online / Cloud Drive <span class="text-zinc-400 font-normal">(opsional)</span>
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-4 w-4 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                                    </svg>
                                </div>
                                <input type="url" 
                                       name="external_link" 
                                       value="{{ old('external_link', $exam->external_link) }}" 
                                       placeholder="https://forms.gle/... atau https://cbt.sekolah.sch.id/..." 
                                       class="w-full rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] pl-9 pr-4 py-2.5 text-sm text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-xs font-mono placeholder:text-zinc-400 dark:placeholder:text-[#5B5B61]">
                            </div>
                            <x-input-error :messages="$errors->get('external_link')" class="mt-1" />
                        </div>
                    </div>
                </div>

                <!-- Form Submit Footer -->
                <div class="px-6 sm:px-8 py-4 bg-zinc-50 dark:bg-[#1D1D21] border-t border-zinc-200/90 dark:border-[#2B2B30] flex items-center justify-between">
                    <a href="{{ route('exams.index') }}" class="px-4 py-2 text-xs font-semibold text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] rounded-lg transition">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-zinc-900 hover:bg-black text-white text-xs font-semibold uppercase tracking-wider rounded-lg transition shadow-xs cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
