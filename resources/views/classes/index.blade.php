<x-app-layout>
    <x-slot name="header">
        <x-page-header 
            title="Data Kelas" 
            subtitle="Manajemen rombel kelas, wali kelas, dan tahun ajaran aktif"
        >
            @if(auth()->user()->hasKesiswaanAccess())
            <button 
                type="button" 
                @click="$dispatch('open-import-student-modal', { trigger: $el })"
                class="inline-flex items-center justify-center gap-2 min-h-[44px] sm:min-h-0 px-3.5 py-2 bg-[#0073B1] hover:bg-[#005D8F] active:bg-[#004A73] text-white text-xs font-semibold rounded-lg transition shadow-none cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                <span>Import Data Siswa</span>
            </button>
            <button 
                type="button" 
                @click="$dispatch('open-add-class-modal', { trigger: $el })"
                class="inline-flex items-center justify-center gap-2 min-h-[44px] sm:min-h-0 px-3.5 py-2 bg-white dark:bg-[#232327] hover:bg-zinc-100 dark:hover:bg-[#26262A] text-zinc-700 dark:text-[#EDEDF0] text-xs font-semibold rounded-lg border border-zinc-200 dark:border-[#2B2B30] transition cursor-pointer shadow-2xs"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                <span>Tambah Kelas</span>
            </button>
            @endif
        </x-page-header>
    </x-slot>

    <div class="space-y-6" x-data="{ 
        addModalOpen: false, 
        editModalOpen: false, 
        exportModalOpen: false, 
        importModalOpen: false,
        isUploading: false,
        uploadProgressText: '',
        importFileName: '',
        importFileSize: '',
        importFileError: '',
        lastFocused: null,
        editClass: { id: null, class_name: '', academic_year: '' }, 
        exportClass: { id: null, class_name: '' },
        openImportModal(trigger) {
            this.lastFocused = trigger || document.activeElement;
            this.importModalOpen = true;
            this.$nextTick(() => document.getElementById('override_tahun_ajaran')?.focus());
        },
        openAddModal(trigger) {
            this.lastFocused = trigger || document.activeElement;
            this.addModalOpen = true;
            this.$nextTick(() => document.getElementById('add_class_name')?.focus());
        },
        openEditModal(cls, trigger) {
            this.lastFocused = trigger || document.activeElement;
            this.editClass = cls;
            this.editModalOpen = true;
            this.$nextTick(() => document.getElementById('edit_class_name')?.focus());
        },
        openExportModal(cls, trigger) {
            this.lastFocused = trigger || document.activeElement;
            this.exportClass = cls;
            this.exportModalOpen = true;
            this.$nextTick(() => document.getElementById('export_start_date')?.focus());
        },
        closeModals(force = false) {
            if (!force && this.isUploading) return;
            const wasOpen = this.addModalOpen || this.editModalOpen || this.exportModalOpen || this.importModalOpen;
            this.addModalOpen = false;
            this.editModalOpen = false;
            this.exportModalOpen = false;
            if (!this.isUploading) this.importModalOpen = false;
            if (wasOpen && this.lastFocused && typeof this.lastFocused.focus === 'function') {
                this.$nextTick(() => this.lastFocused.focus());
            }
        },
        handleImportFile(file) {
            this.importFileError = '';
            if (!file) return;
            const validTypes = [
                'application/pdf',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel'
            ];
            const validExts = ['pdf', 'xlsx', 'xls'];
            const ext = file.name.split('.').pop().toLowerCase();
            if (!validTypes.includes(file.type) && !validExts.includes(ext)) {
                this.importFileError = 'Format file harus .xlsx, .xls, atau .pdf';
                return;
            }
            if (file.size > 15 * 1024 * 1024) {
                this.importFileError = 'Ukuran file melebihi batas 15MB.';
                return;
            }
            this.importFileName = file.name;
            const sizeMB = (file.size / (1024 * 1024)).toFixed(2);
            this.importFileSize = sizeMB + ' MB';
        }
    }"
    @open-import-student-modal.window="openImportModal($event.detail?.trigger)"
    @open-add-class-modal.window="openAddModal($event.detail?.trigger)"
    @keydown.escape.window="closeModals()"
    >

        {{-- Alerts --}}
        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if(session('error'))
            <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
        @endif

        {{-- Top Control Bar: Filters --}}
        <search class="block bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-4 sm:p-5">
            <form method="GET" action="{{ route('classes.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-3 flex-wrap">
                <span class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-[#9496A1] shrink-0">Filter:</span>

                <label for="filter_academic_year" class="sr-only">Filter Tahun Ajaran</label>
                <select id="filter_academic_year" name="academic_year" class="rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2 text-xs font-semibold text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0073B1] focus:border-[#0073B1] shadow-none transition cursor-pointer">
                    <option value="all" @selected(empty($selectedYear) || $selectedYear === 'all')>&mdash; Semua Tahun Ajaran &mdash;</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year }}" @selected($selectedYear === $year)>TP {{ $year }}</option>
                    @endforeach
                </select>

                <label for="filter_grade_level" class="sr-only">Filter Tingkat</label>
                <select id="filter_grade_level" name="grade_level" class="rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2 text-xs font-semibold text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0073B1] focus:border-[#0073B1] shadow-none transition cursor-pointer">
                    <option value="all" @selected(empty($selectedGrade) || $selectedGrade === 'all')>&mdash; Semua Tingkat &mdash;</option>
                    @foreach($gradeLevels as $grade)
                        <option value="{{ $grade }}" @selected($selectedGrade === $grade)>Tingkat {{ $grade }}</option>
                    @endforeach
                </select>

                <label for="filter_major" class="sr-only">Filter Jurusan</label>
                <select id="filter_major" name="major" class="rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2 text-xs font-semibold text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0073B1] focus:border-[#0073B1] shadow-none transition cursor-pointer">
                    <option value="all" @selected(empty($selectedMajor) || $selectedMajor === 'all')>&mdash; Semua Jurusan &mdash;</option>
                    @foreach($majors as $major)
                        <option value="{{ $major }}" @selected($selectedMajor === $major)>{{ $major }}</option>
                    @endforeach
                </select>

                <button type="submit" class="min-h-[44px] sm:min-h-[34px] px-4 py-2 rounded-lg bg-[#0073B1] hover:bg-[#005D8F] text-white text-xs font-semibold transition cursor-pointer">
                    Terapkan Filter
                </button>

                @if((!empty($selectedYear) && $selectedYear !== 'all') || (!empty($selectedGrade) && $selectedGrade !== 'all') || (!empty($selectedMajor) && $selectedMajor !== 'all'))
                    <a href="{{ route('classes.index') }}" class="px-2.5 py-2 text-xs text-zinc-600 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-white underline">Reset</a>
                @endif
            </form>
        </search>

        {{-- Tabel Data Kelas --}}
        <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl overflow-hidden shadow-xs">
            <div tabindex="0" role="region" aria-label="Daftar Rombongan Belajar" class="overflow-x-auto table-scroll-region">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-[#2B2B30] text-left tabular-nums">
                    <caption class="sr-only">Daftar Rombel Kelas, Wali Kelas, dan Guru BK</caption>
                    <thead class="bg-zinc-50 dark:bg-[#232327] text-zinc-600 dark:text-[#9496A1] text-xs font-semibold uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="px-3.5 py-3 font-semibold w-12">No.</th>
                            <th scope="col" class="px-3.5 py-3 font-semibold">Rombel / Kelas</th>
                            <th scope="col" class="px-3.5 py-3 font-semibold">Tingkat &amp; Jurusan</th>
                            <th scope="col" class="px-3.5 py-3 font-semibold whitespace-nowrap">TP</th>
                            <th scope="col" class="px-3.5 py-3 font-semibold">Wali &amp; BK</th>
                            <th scope="col" class="px-3.5 py-3 font-semibold whitespace-nowrap">Siswa</th>
                            <th scope="col" class="px-3.5 py-3 text-right font-semibold">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-[#2B2B30] text-xs">
                        @forelse($classes as $index => $class)
                            <tr class="hover:bg-zinc-50/70 dark:hover:bg-[#26262A]/40 transition-colors">
                                <td class="px-3.5 py-3 font-mono text-zinc-500 dark:text-[#9496A1]">{{ $classes->firstItem() + $index }}</td>
                                <td class="px-3.5 py-3 font-bold text-zinc-900 dark:text-[#EDEDF0] whitespace-nowrap">
                                    <a href="{{ route('classes.show', $class) }}" class="hover:text-[#0073B1] dark:hover:text-[#38BDF8] flex items-center gap-1.5 transition" title="Lihat isi kelas {{ $class->class_name }}">
                                        <span>{{ $class->class_name }}</span>
                                    </a>
                                </td>
                                <td class="px-3.5 py-3">
                                    <div class="flex flex-wrap items-center gap-1">
                                        @if($class->grade_level)
                                            <span class="px-2 py-0.5 rounded bg-blue-50 dark:bg-[#16222F] text-blue-800 dark:text-blue-300 font-semibold text-[11px] whitespace-nowrap">{{ $class->grade_level }}</span>
                                        @endif
                                        @if($class->major)
                                            <span class="px-2 py-0.5 rounded bg-purple-50 dark:bg-[#25182D] text-purple-800 dark:text-purple-300 font-semibold text-[11px] whitespace-nowrap">{{ $class->major }}</span>
                                        @endif
                                        @if(!$class->grade_level && !$class->major)
                                            <span class="text-zinc-500 dark:text-[#9496A1]">-</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3.5 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 font-mono text-[11px] font-semibold text-zinc-800 dark:text-zinc-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400"></span>
                                        {{ $class->academic_year ?? '2026/2027' }}
                                    </span>
                                </td>
                                <td class="px-3.5 py-3 min-w-[140px]">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[11px] uppercase font-mono px-1.5 py-0.5 rounded bg-sky-50 dark:bg-[#16222F] text-[#0073B1] dark:text-[#38BDF8] font-bold shrink-0">Wali</span>
                                            <span class="text-zinc-900 dark:text-[#EDEDF0] font-medium text-[11px]">
                                                {{ $class->homeroomAssignment?->user?->fullname ?? '-' }}
                                            </span>
                                        </div>
                                        @if($class->counselorAssignments->isNotEmpty())
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[11px] uppercase font-mono px-1.5 py-0.5 rounded bg-purple-50 dark:bg-[#25182D] text-purple-800 dark:text-purple-300 font-bold shrink-0">BK</span>
                                                <span class="text-zinc-600 dark:text-[#9496A1] text-[11px]">
                                                    {{ $class->counselorAssignments->map(fn($ca) => $ca->user?->fullname)->filter()->join(', ') }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3.5 py-3 text-zinc-700 dark:text-[#9496A1] whitespace-nowrap">
                                    <a href="{{ route('classes.show', $class) }}" class="inline-flex items-center gap-1 hover:text-[#0073B1] dark:hover:text-[#38BDF8] font-semibold transition group" title="Lihat siswa di kelas ini">
                                        <span>{{ $class->students_count }} Siswa</span>
                                        <svg class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition text-[#0073B1] dark:text-[#38BDF8]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                    </a>
                                </td>
                                <td class="px-3.5 py-3 text-right font-medium whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('classes.show', $class) }}" 
                                           class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded bg-indigo-50 dark:bg-[#1D1E36] hover:bg-indigo-100 dark:hover:bg-[#2A2B4D] text-indigo-700 dark:text-indigo-300 font-semibold transition text-[11px] whitespace-nowrap min-h-[44px] sm:min-h-[32px]"
                                           aria-label="Lihat isi kelas {{ $class->class_name }}"
                                           title="Lihat data siswa dan presensi kelas ini">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            <span>Isi Kelas</span>
                                        </a>
                                        <button type="button"
                                            @click="openExportModal({ id: {{ $class->id }}, class_name: '{{ addslashes($class->class_name) }}' }, $el)"
                                            class="inline-flex items-center px-1.5 min-h-[44px] sm:min-h-[32px] text-emerald-700 dark:text-emerald-400 hover:underline font-semibold transition cursor-pointer">Export</button>
                                        @if(auth()->user()->canSuperviseClass($class))
                                        <button type="button"
                                            @click="openEditModal({ id: {{ $class->id }}, class_name: '{{ addslashes($class->class_name) }}', academic_year: '{{ addslashes($class->academic_year ?? '2026/2027') }}' }, $el)"
                                            class="inline-flex items-center px-1.5 min-h-[44px] sm:min-h-[32px] text-zinc-900 dark:text-[#EDEDF0] hover:text-[#0073B1] dark:hover:text-[#38BDF8] font-semibold transition cursor-pointer">Edit</button>
                                        <form method="POST" action="{{ route('classes.destroy', $class) }}" class="inline" data-confirm="Hapus data kelas {{ $class->class_name }}?" data-confirm-title="Hapus Rombel Kelas">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="inline-flex items-center px-1.5 min-h-[44px] sm:min-h-[32px] text-rose-700 dark:text-rose-400 hover:underline font-semibold transition cursor-pointer">Hapus</button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-10 text-center text-xs text-zinc-600 dark:text-[#9496A1]">
                                    Tidak ada data rombel kelas yang terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="pt-2 text-xs">
            {{ $classes->links() }}
        </div>

        {{-- Modal import data siswa --}}
        <dialog closedby="any" x-effect="importModalOpen ? ($el.open || $el.showModal()) : ($el.open && $el.close())" @close="closeModals()" @cancel.prevent="closeModals()" aria-labelledby="import-student-modal-title" class="m-auto w-[calc(100%-2rem)] sm:max-w-xl bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl text-left overflow-y-auto shadow-xl p-6 sm:p-8">
                    <div class="border-b border-zinc-200 dark:border-[#2B2B30] pb-4 mb-5">
                        <div class="flex items-center justify-between gap-4">
                            <h2 id="import-student-modal-title" class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">Import Data Siswa ke Rombel</h2>
                            <button type="button" @click="closeModals()" aria-label="Tutup modal" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[32px] sm:min-w-[32px] rounded-lg text-zinc-500 hover:text-zinc-900 dark:text-[#9496A1] dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-[#26262A] transition" :disabled="isUploading">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <p class="text-xs text-zinc-600 dark:text-[#9496A1] mt-1">Unggah daftar siswa dalam format Excel (.xlsx, .xls) atau PDF presensi untuk dimasukkan otomatis.</p>
                    </div>

                    <form method="POST" action="{{ route('classes.import-students') }}" enctype="multipart/form-data" class="space-y-5"
                          @submit="isUploading = true; uploadProgressText = 'Sedang menganalisis dokumen dengan AI Gemini…'">
                        @csrf

                        {{-- Override Tahun Ajaran --}}
                        <div>
                            <x-input-label for="override_tahun_ajaran" value="Override Tahun Ajaran (Opsional)" />
                            <input id="override_tahun_ajaran" name="override_tahun_ajaran" type="text" placeholder="Contoh: 2026/2027"
                                class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0073B1] focus:border-[#0073B1] shadow-none transition" />
                            <p class="text-[11px] text-zinc-600 dark:text-[#9496A1] mt-1">
                                Jika dikosongkan, sistem akan otomatis mendeteksi tahun ajaran dari isi dokumen.
                            </p>
                        </div>

                        {{-- Dropzone / File Picker --}}
                        <div>
                            <x-input-label for="import_student_file" value="File Dokumen (.xlsx, .xls, .pdf)" />
                            <label
                                for="import_student_file"
                                class="relative flex flex-col items-center justify-center p-6 sm:p-8 border-2 border-dashed rounded-xl cursor-pointer transition-colors group"
                                :class="importFileName ? 'border-emerald-500 bg-emerald-50/20 dark:bg-emerald-950/20' : 'border-zinc-300 dark:border-zinc-700 hover:border-zinc-500 bg-zinc-50/50 dark:bg-[#26262A]/40'"
                                @dragover.prevent=""
                                @drop.prevent="
                                    const file = $event.dataTransfer.files[0];
                                    if (file) {
                                        $refs.studentFileInput.files = $event.dataTransfer.files;
                                        handleImportFile(file);
                                    }
                                "
                            >
                                <div class="text-center space-y-2 pointer-events-none">
                                    <div class="mx-auto w-10 h-10 rounded-full flex items-center justify-center transition-colors"
                                        :class="importFileName ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 group-hover:bg-zinc-200 dark:group-hover:bg-zinc-700'">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                        </svg>
                                    </div>
                                    <div class="text-xs">
                                        <template x-if="!importFileName">
                                            <div>
                                                <span class="font-semibold text-zinc-900 dark:text-[#EDEDF0] group-hover:underline">Klik untuk pilih berkas</span>
                                                <span class="text-zinc-600 dark:text-[#9496A1]"> atau seret dokumen ke sini</span>
                                                <p class="text-[11px] text-zinc-500 dark:text-[#9496A1] mt-1">Mendukung .xlsx, .xls, dan .pdf — Maks 15MB</p>
                                            </div>
                                        </template>
                                        <template x-if="importFileName">
                                            <div class="text-zinc-800 dark:text-zinc-200">
                                                <div class="font-semibold text-emerald-800 dark:text-emerald-400 flex items-center justify-center gap-1.5">
                                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                                    <span x-text="importFileName"></span>
                                                </div>
                                                <p class="text-[11px] text-zinc-600 dark:text-[#9496A1] mt-0.5" x-text="'Ukuran: ' + importFileSize"></p>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <input id="import_student_file" x-ref="studentFileInput" name="student_file" type="file" accept=".xlsx,.xls,.pdf,application/pdf,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" class="sr-only" required
                                    @change="handleImportFile($event.target.files[0])" />
                            </label>
                            <p class="text-xs text-rose-700 dark:text-rose-400 mt-1.5 font-medium" aria-live="polite" x-show="importFileError" x-text="importFileError"></p>
                        </div>

                        {{-- Info Box --}}
                        <div class="bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800 rounded-lg p-3 text-[11px] text-zinc-700 dark:text-[#9496A1] space-y-1">
                            <p><span class="font-semibold text-sky-900 dark:text-sky-300">Didukung Penuh AI Gemini:</span> Dokumen akan dianalisis untuk mendeteksi rombel, NIS, dan nama lengkap siswa secara presisi.</p>
                        </div>

                        {{-- Submit / Progress --}}
                        <div class="flex justify-end gap-3 pt-3 border-t border-zinc-100 dark:border-[#2B2B30]">
                            <button type="button" @click="closeModals()" class="px-4 py-2 min-h-[44px] sm:min-h-0 text-xs font-semibold text-zinc-600 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-[#EDEDF0] transition" :disabled="isUploading">Batal</button>
                            <button type="submit" class="inline-flex items-center justify-center gap-2 min-h-[44px] sm:min-h-0 px-4 py-2 bg-[#0073B1] hover:bg-[#005D8F] active:bg-[#004A73] text-white text-xs font-semibold rounded-lg transition disabled:opacity-50 cursor-pointer" :disabled="isUploading">
                                <span x-show="!isUploading">Mulai Ekstraksi AI</span>
                                <span x-show="isUploading" class="flex items-center gap-2">
                                    <svg class="animate-spin h-3.5 w-3.5 text-white" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                                    <span x-text="uploadProgressText"></span>
                                </span>
                            </button>
                        </div>
                    </form>
        </dialog>

        {{-- Modal tambah rombel kelas --}}
        <dialog closedby="any" x-effect="addModalOpen ? ($el.open || $el.showModal()) : ($el.open && $el.close())" @close="closeModals()" @cancel.prevent="closeModals()" aria-labelledby="add-class-modal-title" class="m-auto w-[calc(100%-2rem)] sm:max-w-lg bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl text-left overflow-y-auto shadow-xl p-6 sm:p-8">
                    <div class="border-b border-zinc-200 dark:border-[#2B2B30] pb-4 mb-5 flex items-start justify-between gap-4">
                        <div>
                            <h2 id="add-class-modal-title" class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">Tambah Rombel Kelas Baru</h2>
                            <p class="text-xs text-zinc-600 dark:text-[#9496A1] mt-0.5">Format standar: <strong>X BR 1</strong>, <strong>XI AK 2</strong>, dsb.</p>
                        </div>
                        <button type="button" @click="closeModals()" aria-label="Tutup modal" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[32px] sm:min-w-[32px] rounded-lg text-zinc-500 hover:text-zinc-900 dark:text-[#9496A1] dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('classes.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="add_class_name" value="Nama Rombel / Kelas" />
                            <input id="add_class_name" name="class_name" type="text" placeholder="Contoh: X RPL 1" required
                                class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0073B1] focus:border-[#0073B1] shadow-none transition" />
                        </div>
                        <div>
                            <x-input-label for="add_academic_year" value="Tahun Ajaran" />
                            <input list="academic_year_datalist" id="add_academic_year" name="academic_year" type="text" placeholder="2026/2027" value="2026/2027" required
                                class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs font-mono text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0073B1] focus:border-[#0073B1] shadow-none transition" />
                            <datalist id="academic_year_datalist">
                                <option value="2025/2026">
                                <option value="2026/2027">
                                <option value="2027/2028">
                            </datalist>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-[#2B2B30]">
                            <button type="button" @click="closeModals()" class="px-4 py-2 min-h-[44px] sm:min-h-0 text-xs font-semibold text-zinc-600 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-[#EDEDF0] transition">Batal</button>
                            <x-primary-button>Simpan Kelas</x-primary-button>
                        </div>
                    </form>
        </dialog>

        {{-- Modal edit rombel kelas --}}
        <dialog closedby="any" x-effect="editModalOpen ? ($el.open || $el.showModal()) : ($el.open && $el.close())" @close="closeModals()" @cancel.prevent="closeModals()" aria-labelledby="edit-class-modal-title" class="m-auto w-[calc(100%-2rem)] sm:max-w-lg bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl text-left overflow-y-auto shadow-xl p-6 sm:p-8">
                    <div class="border-b border-zinc-200 dark:border-[#2B2B30] pb-4 mb-5 flex items-center justify-between gap-4">
                        <h2 id="edit-class-modal-title" class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">Edit Rombel Kelas</h2>
                        <button type="button" @click="closeModals()" aria-label="Tutup modal" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[32px] sm:min-w-[32px] rounded-lg text-zinc-500 hover:text-zinc-900 dark:text-[#9496A1] dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form :action="'{{ url('classes') }}/' + editClass.id" method="POST" class="space-y-4">
                        @csrf @method('PUT')
                        <div>
                            <x-input-label for="edit_class_name" value="Nama Kelas" />
                            <input id="edit_class_name" name="class_name" type="text" x-model="editClass.class_name" required
                                class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0073B1] focus:border-[#0073B1] shadow-none transition" />
                        </div>
                        <div>
                            <x-input-label for="edit_academic_year" value="Tahun Ajaran" />
                            <input list="academic_year_datalist" id="edit_academic_year" name="academic_year" type="text" x-model="editClass.academic_year" required
                                class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs font-mono text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0073B1] focus:border-[#0073B1] shadow-none transition" />
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-[#2B2B30]">
                            <button type="button" @click="closeModals()" class="px-4 py-2 min-h-[44px] sm:min-h-0 text-xs font-semibold text-zinc-600 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-[#EDEDF0] transition">Batal</button>
                            <x-primary-button>Perbarui Kelas</x-primary-button>
                        </div>
                    </form>
        </dialog>

        {{-- Modal export presensi --}}
        <dialog closedby="any" x-effect="exportModalOpen ? ($el.open || $el.showModal()) : ($el.open && $el.close())" @close="closeModals()" @cancel.prevent="closeModals()" aria-labelledby="export-class-modal-title" class="m-auto w-[calc(100%-2rem)] sm:max-w-lg bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl text-left overflow-y-auto shadow-xl p-6 sm:p-8">
                    <div class="border-b border-zinc-200 dark:border-[#2B2B30] pb-4 mb-5 flex items-start justify-between gap-4">
                        <div>
                            <h2 id="export-class-modal-title" class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">
                                Export Rekap Presensi: <span x-text="exportClass.class_name" class="text-emerald-700 dark:text-emerald-400"></span>
                            </h2>
                            <p class="text-xs text-zinc-600 dark:text-[#9496A1] mt-0.5">Pilih rentang waktu untuk mengunduh total kehadiran siswa dalam format CSV.</p>
                        </div>
                        <button type="button" @click="closeModals()" aria-label="Tutup modal" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[32px] sm:min-w-[32px] rounded-lg text-zinc-500 hover:text-zinc-900 dark:text-[#9496A1] dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form :action="'{{ url('classes') }}/' + exportClass.id + '/export'" method="GET" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="export_start_date" value="Dari Tanggal" />
                                <input id="export_start_date" name="start_date" type="date" required class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0073B1] focus:border-[#0073B1] shadow-none transition" />
                            </div>
                            <div>
                                <x-input-label for="export_end_date" value="Sampai Tanggal" />
                                <input id="export_end_date" name="end_date" type="date" required class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0073B1] focus:border-[#0073B1] shadow-none transition" />
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-[#2B2B30]">
                            <button type="button" @click="closeModals()" class="px-4 py-2 min-h-[44px] sm:min-h-0 text-xs font-semibold text-zinc-600 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-[#EDEDF0] transition">Batal</button>
                            <x-primary-button>Unduh CSV</x-primary-button>
                        </div>
                    </form>
        </dialog>

    </div>
</x-app-layout>
