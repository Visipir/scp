<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="text-[10px] font-bold font-mono text-zinc-400 uppercase tracking-widest">Master Data Akademik</div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 mt-0.5">
                    Data Kelas &amp; Tahun Ajaran
                </h1>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-zinc-100 text-zinc-800 border border-zinc-200 text-xs font-mono font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-zinc-600"></span> Akses: Waka &amp; BK / Kesiswaan
                </span>
            </div>
        </div>
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

        {{-- Top Control Bar: Filters & Actions --}}
        <div class="bg-white border border-zinc-200/80 rounded-lg p-4 sm:p-5">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                {{-- Filters --}}
                <form method="GET" action="{{ route('classes.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-3 flex-wrap">
                    <label class="text-xs font-bold font-mono uppercase tracking-wider text-zinc-500 shrink-0">Filter:</label>

                    <select name="academic_year" onchange="this.form.submit()" class="rounded-md border-zinc-300 bg-white px-3 py-2 text-xs font-mono font-semibold text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition cursor-pointer">
                        <option value="all" @selected(empty($selectedYear) || $selectedYear === 'all')>&mdash; Semua Tahun Ajaran &mdash;</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year }}" @selected($selectedYear === $year)>TP {{ $year }}</option>
                        @endforeach
                    </select>

                    <select name="grade_level" onchange="this.form.submit()" class="rounded-md border-zinc-300 bg-white px-3 py-2 text-xs font-mono font-semibold text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition cursor-pointer">
                        <option value="all" @selected(empty($selectedGrade) || $selectedGrade === 'all')>&mdash; Semua Tingkat &mdash;</option>
                        @foreach($gradeLevels as $grade)
                            <option value="{{ $grade }}" @selected($selectedGrade === $grade)>Tingkat {{ $grade }}</option>
                        @endforeach
                    </select>

                    <select name="major" onchange="this.form.submit()" class="rounded-md border-zinc-300 bg-white px-3 py-2 text-xs font-mono font-semibold text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition cursor-pointer">
                        <option value="all" @selected(empty($selectedMajor) || $selectedMajor === 'all')>&mdash; Semua Jurusan &mdash;</option>
                        @foreach($majors as $major)
                            <option value="{{ $major }}" @selected($selectedMajor === $major)>{{ $major }}</option>
                        @endforeach
                    </select>

                    @if((!empty($selectedYear) && $selectedYear !== 'all') || (!empty($selectedGrade) && $selectedGrade !== 'all') || (!empty($selectedMajor) && $selectedMajor !== 'all'))
                        <a href="{{ route('classes.index') }}" class="px-2.5 py-2 text-xs font-mono text-zinc-500 hover:text-zinc-900 underline">Reset</a>
                    @endif
                </form>

                {{-- Action Buttons --}}
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" @click="importModalOpen = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold uppercase tracking-wider rounded-md transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/></svg>
                        Import Data Siswa
                    </button>
                    <button type="button" @click="addModalOpen = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-zinc-900 hover:bg-black text-white text-xs font-semibold uppercase tracking-wider rounded-md transition"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Tambah Kelas
                    </button>
                </div>
            </div>
        </div>

        {{-- Tabel Data Kelas --}}
        <div class="bg-white border border-zinc-200/80 rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200/80 text-left">
                <thead class="bg-zinc-50 font-mono text-[10px] text-zinc-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3 font-bold">No.</th>
                        <th class="px-5 py-3 font-bold">Nama Rombel / Kelas</th>
                        <th class="px-5 py-3 font-bold">Tingkat</th>
                        <th class="px-5 py-3 font-bold">Jurusan</th>
                        <th class="px-5 py-3 font-bold">Tahun Ajaran</th>
                        <th class="px-5 py-3 font-bold">Siswa</th>
                        <th class="px-5 py-3 text-right font-bold">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 text-xs">
                    @forelse($classes as $index => $class)
                        <tr class="hover:bg-zinc-50/70 transition-colors">
                            <td class="px-5 py-3.5 font-mono text-zinc-400">{{ $classes->firstItem() + $index }}</td>
                            <td class="px-5 py-3.5 font-bold text-zinc-900">
                                <a href="{{ route('classes.show', $class) }}" class="hover:text-indigo-600 hover:underline flex items-center gap-1.5" title="Lihat isi kelas {{ $class->class_name }}">
                                    <span>{{ $class->class_name }}</span>
                                </a>
                            </td>
                            <td class="px-5 py-3.5">
                                @if($class->grade_level)
                                    <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-800 font-bold text-[10px]">{{ $class->grade_level }}</span>
                                @else
                                    <span class="text-zinc-300">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                @if($class->major)
                                    <span class="px-2 py-0.5 rounded bg-purple-50 text-purple-800 font-bold text-[10px]">{{ $class->major }}</span>
                                @else
                                    <span class="text-zinc-300">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-zinc-100 border border-zinc-200 font-mono text-[11px] font-bold text-zinc-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                    {{ $class->academic_year ?? '2026/2027' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-zinc-600">
                                <a href="{{ route('classes.show', $class) }}" class="inline-flex items-center gap-1 hover:text-indigo-600 font-semibold group" title="Lihat siswa di kelas ini">
                                    <span>{{ $class->students_count }} Siswa</span>
                                    <svg class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                                </a>
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono">
                                <div class="inline-flex items-center gap-2.5">
                                    <a href="{{ route('classes.show', $class) }}" 
                                       class="inline-flex items-center gap-1 px-2.5 py-1 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold transition text-[11px]"
                                       title="Lihat data siswa dan presensi kelas ini">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                        Lihat Isi Kelas
                                    </a>
                                    <button type="button"
                                        @click="exportClass = { id: {{ $class->id }}, class_name: '{{ addslashes($class->class_name) }}' }; exportModalOpen = true"
                                        class="text-emerald-700 hover:underline font-semibold">Export</button>
                                    <button type="button"
                                        @click="editClass = { id: {{ $class->id }}, class_name: '{{ addslashes($class->class_name) }}', academic_year: '{{ addslashes($class->academic_year ?? '2026/2027') }}' }; editModalOpen = true"
                                        class="text-zinc-900 hover:underline font-semibold">Edit</button>
                                    <form method="POST" action="{{ route('classes.destroy', $class) }}" class="inline" onsubmit="return confirm('Hapus data kelas {{ $class->class_name }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:underline font-semibold">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center font-mono text-xs text-zinc-400">
                                Tidak ada data kelas yang cocok dengan filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="pt-2 font-mono text-xs">
            {{ $classes->appends(request()->query())->links() }}
        </div>

        {{-- ============================================== --}}
        {{-- MODAL: Import Data Siswa (Drag & Drop)        --}}
        {{-- ============================================== --}}
        <div x-show="importModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen p-4 sm:p-0">
                <div x-show="importModalOpen" x-transition.opacity class="fixed inset-0 bg-zinc-900/60 backdrop-blur-xs" @click="importModalOpen = false"></div>

                <div x-show="importModalOpen" x-transition class="relative bg-white border border-zinc-200 rounded-lg text-left overflow-hidden shadow-xl sm:my-8 sm:max-w-2xl w-full p-6 sm:p-8">
                    {{-- Header --}}
                    <div class="border-b border-zinc-200/80 pb-4 mb-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-zinc-900 tracking-tight">Import Data Siswa</h3>
                                <p class="text-xs text-zinc-500 mt-0.5">Unggah file Daftar Hadir/Daftar Siswa resmi untuk membuat akun siswa otomatis.</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('classes.import-students') }}" enctype="multipart/form-data" class="space-y-5">
                        @csrf

                        {{-- Override Tahun Ajaran --}}
                        <div>
                            <x-input-label for="override_tahun_ajaran" value="Override Tahun Ajaran (Opsional)" />
                            <input id="override_tahun_ajaran" name="override_tahun_ajaran" type="text" placeholder="Contoh: 2026/2027"
                                class="w-full rounded-md border-zinc-300 bg-white px-3 py-2 text-sm font-mono text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition" />
                            <p class="text-[11px] text-zinc-400 font-mono mt-1">
                                &bull; Jika dikosongkan, sistem akan otomatis mendeteksi tahun ajaran dari isi dokumen.
                            </p>
                        </div>

                        {{-- Drag & Drop Upload Zone --}}
                        <div>
                            <div
                                class="relative flex flex-col items-center justify-center p-6 sm:p-8 border-2 border-dashed rounded-xl cursor-pointer transition-colors group"
                                :class="importFileName ? 'border-emerald-500 bg-emerald-50/20' : 'border-zinc-300 hover:border-zinc-500 bg-zinc-50/50'"
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
                                        :class="importFileName ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-500 group-hover:bg-zinc-200'">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                        </svg>
                                    </div>
                                    <div class="text-xs">
                                        <template x-if="!importFileName">
                                            <div>
                                                <span class="font-bold text-zinc-900 group-hover:underline">Klik untuk pilih berkas</span>
                                                <span class="text-zinc-500"> atau seret dokumen ke sini</span>
                                                <p class="text-[11px] text-zinc-400 font-mono mt-1">Mendukung .xlsx, .xls, dan .pdf — Maks 15MB</p>
                                            </div>
                                        </template>
                                        <template x-if="importFileName">
                                            <div class="font-mono text-zinc-800">
                                                <div class="font-bold text-emerald-800 flex items-center justify-center gap-1.5">
                                                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                                    <span x-text="importFileName"></span>
                                                </div>
                                                <p class="text-[11px] text-zinc-500 mt-0.5" x-text="'Ukuran: ' + importFileSize"></p>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <input x-ref="studentFileInput" name="student_file" type="file" accept=".xlsx,.xls,.pdf,application/pdf,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" class="hidden" required
                                    @change="handleImportFile($event.target.files[0])" />
                            </div>
                            <p class="text-xs text-rose-600 mt-1.5 font-medium" x-show="importFileError" x-text="importFileError"></p>
                        </div>

                        {{-- Info Box --}}
                        <div class="bg-indigo-50/70 border border-indigo-200/80 rounded-lg p-3 text-[11px] text-zinc-600 space-y-1">
                            <p><span class="font-bold text-indigo-900">Didukung Penuh AI Gemini:</span> Dokumen (baik PDF maupun Excel) akan dianalisis cerdas oleh AI Gemini untuk memvalidasi daftar hadir, mendeteksi Tahun Ajaran, nama kelas, nomor urut, dan identitas siswa.</p>
                            <p><span class="font-bold text-zinc-800">Auto-Provisioning Akun:</span> Sistem otomatis membuat akun siswa (<code class="bg-white px-1 py-0.5 rounded text-indigo-700 font-mono">username = NIS</code>, <code class="bg-white px-1 py-0.5 rounded text-indigo-700 font-mono">password = NIS</code>) dan menautkannya ke kelas.</p>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-between gap-4 pt-1">
                            <button type="button" @click="importModalOpen = false; importFileName = ''; importFileSize = ''; importFileError = '';"
                                class="px-4 py-2.5 border border-zinc-200 rounded-lg text-xs font-semibold text-zinc-600 hover:text-zinc-900 hover:bg-zinc-50 transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" :disabled="isUploading || !importFileName"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs uppercase tracking-wider rounded-lg transition disabled:opacity-50 cursor-pointer">
                                <svg x-show="!isUploading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                                </svg>
                                <svg x-show="isUploading" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="isUploading ? 'Memproses...' : 'Import & Buat Akun Siswa'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============================================== --}}
        {{-- MODAL: Tambah Kelas Baru                      --}}
        {{-- ============================================== --}}
        <div x-show="addModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen p-4 sm:p-0">
                <div x-show="addModalOpen" x-transition.opacity class="fixed inset-0 bg-zinc-900/60 backdrop-blur-xs" @click="addModalOpen = false"></div>
                <div x-show="addModalOpen" x-transition class="relative bg-white border border-zinc-200 rounded-lg text-left overflow-hidden shadow-xl sm:my-8 sm:max-w-lg w-full p-6 sm:p-8">
                    <div class="border-b border-zinc-200/80 pb-4 mb-5">
                        <h3 class="text-base font-bold text-zinc-900 tracking-tight">Tambah Data Kelas Baru</h3>
                        <p class="text-xs text-zinc-500 mt-0.5">Ketik nama kelas dan tahun ajaran. Tingkat & jurusan otomatis terdeteksi.</p>
                    </div>
                    <form method="POST" action="{{ route('classes.store') }}" class="space-y-4">
                        @csrf
                        <div>
                            <x-input-label for="add_class_name" value="Nama Kelas / Rombel" />
                            <x-text-input id="add_class_name" name="class_name" type="text" placeholder="contoh: XII RPL 1, XI TKJ 2" required />
                        </div>
                        <div>
                            <x-input-label for="add_academic_year" value="Tahun Ajaran" />
                            <input list="academic_year_datalist" id="add_academic_year" name="academic_year" type="text" placeholder="Ketik: 2026/2027"
                                value="{{ old('academic_year', $selectedYear && $selectedYear !== 'all' ? $selectedYear : '2026/2027') }}"
                                class="w-full rounded-md border-zinc-300 bg-white px-3 py-2 text-sm font-mono text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition" required />
                            <datalist id="academic_year_datalist">
                                @foreach($academicYears as $year) <option value="{{ $year }}"></option> @endforeach
                                <option value="2026/2027"></option><option value="2027/2028"></option><option value="2028/2029"></option>
                            </datalist>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100">
                            <button type="button" @click="addModalOpen = false" class="px-4 py-2 text-xs font-semibold uppercase text-zinc-600 hover:text-zinc-900 transition">Batal</button>
                            <x-primary-button>Simpan Kelas</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ============================================== --}}
        {{-- MODAL: Edit Kelas                             --}}
        {{-- ============================================== --}}
        <div x-show="editModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen p-4 sm:p-0">
                <div x-show="editModalOpen" x-transition.opacity class="fixed inset-0 bg-zinc-900/60 backdrop-blur-xs" @click="editModalOpen = false"></div>
                <div x-show="editModalOpen" x-transition class="relative bg-white border border-zinc-200 rounded-lg text-left overflow-hidden shadow-xl sm:my-8 sm:max-w-lg w-full p-6 sm:p-8">
                    <div class="border-b border-zinc-200/80 pb-4 mb-5">
                        <h3 class="text-base font-bold text-zinc-900 tracking-tight">Edit Data Kelas &amp; Tahun Ajaran</h3>
                    </div>
                    <form :action="'{{ url('classes') }}/' + editClass.id" method="POST" class="space-y-4">
                        @csrf @method('PUT')
                        <div>
                            <x-input-label for="edit_class_name" value="Nama Kelas" />
                            <input id="edit_class_name" name="class_name" type="text" x-model="editClass.class_name" required
                                class="w-full rounded-md border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition" />
                        </div>
                        <div>
                            <x-input-label for="edit_academic_year" value="Tahun Ajaran" />
                            <input list="academic_year_datalist" id="edit_academic_year" name="academic_year" type="text" x-model="editClass.academic_year" required
                                class="w-full rounded-md border-zinc-300 bg-white px-3 py-2 text-sm font-mono text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition" />
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100">
                            <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-xs font-semibold uppercase text-zinc-600 hover:text-zinc-900 transition">Batal</button>
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
                <div x-show="exportModalOpen" x-transition class="relative bg-white border border-zinc-200 rounded-lg text-left overflow-hidden shadow-xl sm:my-8 sm:max-w-lg w-full p-6 sm:p-8">
                    <div class="border-b border-zinc-200/80 pb-4 mb-5">
                        <h3 class="text-base font-bold text-zinc-900 tracking-tight">
                            Export Rekap Presensi: <span x-text="exportClass.class_name" class="text-emerald-700"></span>
                        </h3>
                        <p class="text-xs text-zinc-500 mt-0.5">Pilih rentang waktu untuk mengunduh total kehadiran siswa dalam format CSV.</p>
                    </div>
                    <form :action="'{{ url('classes') }}/' + exportClass.id + '/export'" method="GET" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="export_start_date" value="Dari Tanggal" />
                                <input id="export_start_date" name="start_date" type="date" required class="w-full rounded-md border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition" />
                            </div>
                            <div>
                                <x-input-label for="export_end_date" value="Sampai Tanggal" />
                                <input id="export_end_date" name="end_date" type="date" required class="w-full rounded-md border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition" />
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100">
                            <button type="button" @click="exportModalOpen = false" class="px-4 py-2 text-xs font-semibold uppercase text-zinc-600 hover:text-zinc-900 transition">Batal</button>
                            <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-zinc-900 hover:bg-black text-white text-xs font-semibold uppercase tracking-wider rounded transition">Unduh CSV</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
