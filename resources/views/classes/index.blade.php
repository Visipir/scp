<x-app-layout>
    <x-slot name="header">
        <x-page-header 
            title="Data Kelas" 
            subtitle="Manajemen rombel kelas, wali kelas, dan tahun ajaran aktif"
        >
            @if(auth()->user()->hasKesiswaanAccess())
            <button 
                type="button" 
                @click="importModalOpen = true"
                class="inline-flex items-center gap-2 px-3.5 py-2 bg-[#0093dd] hover:bg-[#0082c4] active:bg-[#006fa7] text-white text-xs font-semibold rounded-lg transition shadow-none cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                <span>Import Data Siswa</span>
            </button>
            <button 
                type="button" 
                @click="addModalOpen = true"
                class="inline-flex items-center gap-2 px-3.5 py-2 bg-zinc-900 dark:bg-zinc-800 hover:bg-black dark:hover:bg-zinc-700 text-white text-xs font-semibold rounded-lg border border-transparent dark:border-zinc-700 transition cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
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
        editClass: { id: null, class_name: '', academic_year: '' }, 
        exportClass: { id: null, class_name: '' },
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
    }">

        {{-- Alerts --}}
        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if(session('error'))
            <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
        @endif

        {{-- Top Control Bar: Filters --}}
        <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-4 sm:p-5">
            <form method="GET" action="{{ route('classes.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-3 flex-wrap">
                <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-[#97979B] shrink-0">Filter:</label>

                <select name="academic_year" onchange="this.form.submit()" class="rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2 text-xs font-semibold text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] shadow-none transition cursor-pointer">
                    <option value="all" @selected(empty($selectedYear) || $selectedYear === 'all')>&mdash; Semua Tahun Ajaran &mdash;</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year }}" @selected($selectedYear === $year)>TP {{ $year }}</option>
                    @endforeach
                </select>

                <select name="grade_level" onchange="this.form.submit()" class="rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2 text-xs font-semibold text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] shadow-none transition cursor-pointer">
                    <option value="all" @selected(empty($selectedGrade) || $selectedGrade === 'all')>&mdash; Semua Tingkat &mdash;</option>
                    @foreach($gradeLevels as $grade)
                        <option value="{{ $grade }}" @selected($selectedGrade === $grade)>Tingkat {{ $grade }}</option>
                    @endforeach
                </select>

                <select name="major" onchange="this.form.submit()" class="rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2 text-xs font-semibold text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] shadow-none transition cursor-pointer">
                    <option value="all" @selected(empty($selectedMajor) || $selectedMajor === 'all')>&mdash; Semua Jurusan &mdash;</option>
                    @foreach($majors as $major)
                        <option value="{{ $major }}" @selected($selectedMajor === $major)>{{ $major }}</option>
                    @endforeach
                </select>

                @if((!empty($selectedYear) && $selectedYear !== 'all') || (!empty($selectedGrade) && $selectedGrade !== 'all') || (!empty($selectedMajor) && $selectedMajor !== 'all'))
                    <a href="{{ route('classes.index') }}" class="px-2.5 py-2 text-xs text-zinc-500 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-white underline">Reset</a>
                @endif
            </form>
        </div>

        {{-- Tabel Data Kelas --}}
        <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-[#2B2B30] text-left">
                    <thead class="bg-zinc-50 dark:bg-[#232327] text-zinc-500 dark:text-[#97979B] text-xs font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3 font-semibold">No.</th>
                            <th class="px-5 py-3 font-semibold">Nama Rombel / Kelas</th>
                            <th class="px-5 py-3 font-semibold">Tingkat</th>
                            <th class="px-5 py-3 font-semibold">Jurusan</th>
                            <th class="px-5 py-3 font-semibold">Tahun Ajaran</th>
                            <th class="px-5 py-3 font-semibold">Wali &amp; BK</th>
                            <th class="px-5 py-3 font-semibold">Siswa</th>
                            <th class="px-5 py-3 text-right font-semibold">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-[#2B2B30] text-xs">
                        @forelse($classes as $index => $class)
                            <tr class="hover:bg-zinc-50/70 dark:hover:bg-[#26262A]/40 transition-colors">
                                <td class="px-5 py-3.5 font-mono text-zinc-400 dark:text-zinc-500">{{ $classes->firstItem() + $index }}</td>
                                <td class="px-5 py-3.5 font-bold text-zinc-900 dark:text-[#EDEDF0]">
                                    <a href="{{ route('classes.show', $class) }}" class="hover:text-[#0093dd] dark:hover:text-[#0093dd] flex items-center gap-1.5 transition" title="Lihat isi kelas {{ $class->class_name }}">
                                        <span>{{ $class->class_name }}</span>
                                    </a>
                                </td>
                                <td class="px-5 py-3.5">
                                    @if($class->grade_level)
                                        <span class="px-2 py-0.5 rounded bg-blue-50 dark:bg-[#16222F] text-blue-800 dark:text-blue-300 font-semibold text-[11px]">{{ $class->grade_level }}</span>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-600">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    @if($class->major)
                                        <span class="px-2 py-0.5 rounded bg-purple-50 dark:bg-[#25182D] text-purple-800 dark:text-purple-300 font-semibold text-[11px]">{{ $class->major }}</span>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-600">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 font-mono text-[11px] font-semibold text-zinc-800 dark:text-zinc-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400"></span>
                                        {{ $class->academic_year ?? '2026/2027' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-[9px] uppercase font-mono px-1 py-0.5 rounded bg-sky-50 dark:bg-[#16222F] text-[#0093dd] font-bold">Wali</span>
                                            <span class="text-zinc-900 dark:text-[#EDEDF0] font-medium text-[11px]">
                                                {{ $class->homeroomAssignment?->user?->fullname ?? '-' }}
                                            </span>
                                        </div>
                                        @if($class->counselorAssignments->isNotEmpty())
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[9px] uppercase font-mono px-1 py-0.5 rounded bg-purple-50 dark:bg-[#25182D] text-purple-700 dark:text-purple-300 font-bold">BK</span>
                                                <span class="text-zinc-500 dark:text-[#97979B] text-[11px]">
                                                    {{ $class->counselorAssignments->map(fn($ca) => $ca->user?->fullname)->filter()->join(', ') }}
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-zinc-600 dark:text-[#97979B]">
                                    <a href="{{ route('classes.show', $class) }}" class="inline-flex items-center gap-1 hover:text-[#0093dd] dark:hover:text-[#0093dd] font-semibold transition group" title="Lihat siswa di kelas ini">
                                        <span>{{ $class->students_count }} Siswa</span>
                                        <svg class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition text-[#0093dd]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                    </a>
                                </td>
                                <td class="px-5 py-3.5 text-right font-medium">
                                    <div class="inline-flex items-center gap-2.5">
                                        <a href="{{ route('classes.show', $class) }}" 
                                           class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-indigo-50 dark:bg-[#1D1E36] hover:bg-indigo-100 dark:hover:bg-[#2A2B4D] text-indigo-700 dark:text-indigo-300 font-semibold transition text-[11px]"
                                           title="Lihat data siswa dan presensi kelas ini">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            Lihat Isi Kelas
                                        </a>
                                        <button type="button"
                                            @click="exportClass = { id: {{ $class->id }}, class_name: '{{ addslashes($class->class_name) }}' }; exportModalOpen = true"
                                            class="text-emerald-700 dark:text-emerald-400 hover:underline font-semibold transition">Export</button>
                                        @if(auth()->user()->canSuperviseClass($class))
                                        <button type="button"
                                            @click="editClass = { id: {{ $class->id }}, class_name: '{{ addslashes($class->class_name) }}', academic_year: '{{ addslashes($class->academic_year ?? '2026/2027') }}' }; editModalOpen = true"
                                            class="text-zinc-900 dark:text-[#EDEDF0] hover:text-[#0093dd] dark:hover:text-[#0093dd] font-semibold transition">Edit</button>
                                        <form method="POST" action="{{ route('classes.destroy', $class) }}" class="inline" onsubmit="return confirm('Hapus data kelas {{ $class->class_name }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-rose-600 dark:text-rose-400 hover:underline font-semibold transition">Hapus</button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-10 text-center text-xs text-zinc-400 dark:text-[#97979B]">
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

        {{-- ============================================== --}}
        {{-- MODAL: Import Data Siswa                       --}}
        {{-- ============================================== --}}
        <div x-show="importModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen p-4 sm:p-0">
                <div x-show="importModalOpen" x-transition.opacity class="fixed inset-0 bg-zinc-900/60 backdrop-blur-xs" @click="if(!isUploading) importModalOpen = false"></div>
                <div x-show="importModalOpen" x-transition class="relative bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl text-left overflow-hidden shadow-xl sm:my-8 sm:max-w-xl w-full p-6 sm:p-8">
                    <div class="border-b border-zinc-200 dark:border-[#2B2B30] pb-4 mb-5">
                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">Import Data Siswa ke Rombel</h3>
                            <button type="button" @click="importModalOpen = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200" :disabled="isUploading">&times;</button>
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-[#97979B] mt-1">Unggah daftar siswa dalam format Excel (.xlsx, .xls) atau PDF presensi untuk dimasukkan otomatis.</p>
                    </div>

                    <form method="POST" action="{{ route('classes.import-students') }}" enctype="multipart/form-data" class="space-y-5"
                          @submit="isUploading = true; uploadProgressText = 'Sedang menganalisis dokumen dengan AI Gemini...'">
                        @csrf

                        {{-- Override Tahun Ajaran --}}
                        <div>
                            <x-input-label for="override_tahun_ajaran" value="Override Tahun Ajaran (Opsional)" />
                            <input id="override_tahun_ajaran" name="override_tahun_ajaran" type="text" placeholder="Contoh: 2026/2027"
                                class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] shadow-none transition" />
                            <p class="text-[11px] text-zinc-500 dark:text-[#97979B] mt-1">
                                Jika dikosongkan, sistem akan otomatis mendeteksi tahun ajaran dari isi dokumen.
                            </p>
                        </div>

                        {{-- Dropzone / File Picker --}}
                        <div>
                            <x-input-label value="File Dokumen (.xlsx, .xls, .pdf)" />
                            <div
                                class="relative flex flex-col items-center justify-center p-6 sm:p-8 border-2 border-dashed rounded-xl cursor-pointer transition-colors group"
                                :class="importFileName ? 'border-emerald-500 bg-emerald-50/20 dark:bg-emerald-950/20' : 'border-zinc-300 dark:border-zinc-700 hover:border-zinc-500 bg-zinc-50/50 dark:bg-[#26262A]/40'"
                                @click="$refs.studentFileInput.click()"
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
                                        :class="importFileName ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400 group-hover:bg-zinc-200 dark:group-hover:bg-zinc-700'">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                        </svg>
                                    </div>
                                    <div class="text-xs">
                                        <template x-if="!importFileName">
                                            <div>
                                                <span class="font-semibold text-zinc-900 dark:text-[#EDEDF0] group-hover:underline">Klik untuk pilih berkas</span>
                                                <span class="text-zinc-500 dark:text-[#97979B]"> atau seret dokumen ke sini</span>
                                                <p class="text-[11px] text-zinc-400 dark:text-zinc-500 mt-1">Mendukung .xlsx, .xls, dan .pdf — Maks 15MB</p>
                                            </div>
                                        </template>
                                        <template x-if="importFileName">
                                            <div class="text-zinc-800 dark:text-zinc-200">
                                                <div class="font-semibold text-emerald-800 dark:text-emerald-400 flex items-center justify-center gap-1.5">
                                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                                    <span x-text="importFileName"></span>
                                                </div>
                                                <p class="text-[11px] text-zinc-500 dark:text-[#97979B] mt-0.5" x-text="'Ukuran: ' + importFileSize"></p>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <input x-ref="studentFileInput" name="student_file" type="file" accept=".xlsx,.xls,.pdf,application/pdf,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" class="hidden" required
                                    @change="handleImportFile($event.target.files[0])" />
                            </div>
                            <p class="text-xs text-rose-600 dark:text-rose-400 mt-1.5 font-medium" x-show="importFileError" x-text="importFileError"></p>
                        </div>

                        {{-- Info Box --}}
                        <div class="bg-sky-50 dark:bg-sky-950/40 border border-sky-200 dark:border-sky-800 rounded-lg p-3 text-[11px] text-zinc-600 dark:text-[#97979B] space-y-1">
                            <p><span class="font-semibold text-sky-900 dark:text-sky-300">Didukung Penuh AI Gemini:</span> Dokumen akan dianalisis untuk mendeteksi rombel, NIS, dan nama lengkap siswa secara presisi.</p>
                        </div>

                        {{-- Submit / Progress --}}
                        <div class="flex justify-end gap-3 pt-3 border-t border-zinc-100 dark:border-[#2B2B30]">
                            <button type="button" @click="importModalOpen = false" class="px-4 py-2 text-xs font-semibold text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] transition" :disabled="isUploading">Batal</button>
                            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-[#0093dd] hover:bg-[#0082c4] text-white text-xs font-semibold rounded-lg transition disabled:opacity-50" :disabled="isUploading">
                                <span x-show="!isUploading">Mulai Ekstraksi AI</span>
                                <span x-show="isUploading" class="flex items-center gap-2">
                                    <svg class="animate-spin h-3.5 w-3.5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                                    <span x-text="uploadProgressText"></span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============================================== --}}
        {{-- MODAL: Tambah Rombel Kelas                     --}}
        {{-- ============================================== --}}
        <div x-show="addModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen p-4 sm:p-0">
                <div x-show="addModalOpen" x-transition.opacity class="fixed inset-0 bg-zinc-900/60 backdrop-blur-xs" @click="addModalOpen = false"></div>
                <div x-show="addModalOpen" x-transition class="relative bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl text-left overflow-hidden shadow-xl sm:my-8 sm:max-w-lg w-full p-6 sm:p-8">
                    <div class="border-b border-zinc-200 dark:border-[#2B2B30] pb-4 mb-5">
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">Tambah Rombel Kelas Baru</h3>
                        <p class="text-xs text-zinc-500 dark:text-[#97979B] mt-0.5">Format standar: <strong>X BR 1</strong>, <strong>XI AK 2</strong>, dsb.</p>
                    </div>
                    <form method="POST" action="{{ route('classes.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="add_class_name" value="Nama Rombel / Kelas" />
                            <input id="add_class_name" name="class_name" type="text" placeholder="Contoh: X RPL 1" required
                                class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] shadow-none transition" />
                        </div>
                        <div>
                            <x-input-label for="add_academic_year" value="Tahun Ajaran" />
                            <input list="academic_year_datalist" id="add_academic_year" name="academic_year" type="text" placeholder="2026/2027" value="2026/2027" required
                                class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs font-mono text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] shadow-none transition" />
                            <datalist id="academic_year_datalist">
                                <option value="2025/2026">
                                <option value="2026/2027">
                                <option value="2027/2028">
                            </datalist>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-[#2B2B30]">
                            <button type="button" @click="addModalOpen = false" class="px-4 py-2 text-xs font-semibold text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] transition">Batal</button>
                            <x-primary-button>Simpan Kelas</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============================================== --}}
        {{-- MODAL: Edit Rombel Kelas                       --}}
        {{-- ============================================== --}}
        <div x-show="editModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen p-4 sm:p-0">
                <div x-show="editModalOpen" x-transition.opacity class="fixed inset-0 bg-zinc-900/60 backdrop-blur-xs" @click="editModalOpen = false"></div>
                <div x-show="editModalOpen" x-transition class="relative bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl text-left overflow-hidden shadow-xl sm:my-8 sm:max-w-lg w-full p-6 sm:p-8">
                    <div class="border-b border-zinc-200 dark:border-[#2B2B30] pb-4 mb-5">
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">Edit Rombel Kelas</h3>
                    </div>
                    <form :action="'{{ url('classes') }}/' + editClass.id" method="POST" class="space-y-4">
                        @csrf @method('PUT')
                        <div>
                            <x-input-label for="edit_class_name" value="Nama Kelas" />
                            <input id="edit_class_name" name="class_name" type="text" x-model="editClass.class_name" required
                                class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] shadow-none transition" />
                        </div>
                        <div>
                            <x-input-label for="edit_academic_year" value="Tahun Ajaran" />
                            <input list="academic_year_datalist" id="edit_academic_year" name="academic_year" type="text" x-model="editClass.academic_year" required
                                class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs font-mono text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] shadow-none transition" />
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-[#2B2B30]">
                            <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-xs font-semibold text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] transition">Batal</button>
                            <x-primary-button>Perbarui Kelas</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============================================== --}}
        {{-- MODAL: Export Presensi                        --}}
        {{-- ============================================== --}}
        <div x-show="exportModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen p-4 sm:p-0">
                <div x-show="exportModalOpen" x-transition.opacity class="fixed inset-0 bg-zinc-900/60 backdrop-blur-xs" @click="exportModalOpen = false"></div>
                <div x-show="exportModalOpen" x-transition class="relative bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl text-left overflow-hidden shadow-xl sm:my-8 sm:max-w-lg w-full p-6 sm:p-8">
                    <div class="border-b border-zinc-200 dark:border-[#2B2B30] pb-4 mb-5">
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">
                            Export Rekap Presensi: <span x-text="exportClass.class_name" class="text-emerald-700 dark:text-emerald-400"></span>
                        </h3>
                        <p class="text-xs text-zinc-500 dark:text-[#97979B] mt-0.5">Pilih rentang waktu untuk mengunduh total kehadiran siswa dalam format CSV.</p>
                    </div>
                    <form :action="'{{ url('classes') }}/' + exportClass.id + '/export'" method="GET" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="export_start_date" value="Dari Tanggal" />
                                <input id="export_start_date" name="start_date" type="date" required class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] shadow-none transition" />
                            </div>
                            <div>
                                <x-input-label for="export_end_date" value="Sampai Tanggal" />
                                <input id="export_end_date" name="end_date" type="date" required class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] shadow-none transition" />
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-[#2B2B30]">
                            <button type="button" @click="exportModalOpen = false" class="px-4 py-2 text-xs font-semibold text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] transition">Batal</button>
                            <x-primary-button>Unduh CSV</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
