<x-app-layout>
    <x-slot name="header">
        <x-page-header 
            title="Materi & Ujian" 
            subtitle="Modul pembelajaran, bank soal latihan, dan jadwal asesmen akademik"
        >
            @if(auth()->user()->role_id == 2)
                {{-- Toggle Scope for Teachers: My materials vs All School --}}
                @if(request('view_scope') === 'all')
                    <a href="{{ route('exams.index', request()->except('view_scope', 'page')) }}" 
                       class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-zinc-700 dark:text-zinc-200 bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] hover:bg-zinc-50 dark:hover:bg-[#26262A] rounded-lg transition shadow-xs">
                        <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                        Tampilkan Materi Saya
                    </a>
                @else
                    <a href="{{ route('exams.index', array_merge(request()->except('page'), ['view_scope' => 'all'])) }}" 
                       class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-zinc-700 dark:text-zinc-200 bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] hover:bg-zinc-50 dark:hover:bg-[#26262A] rounded-lg transition shadow-xs">
                        <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                        Lihat Semua Konten Sekolah
                    </a>
                @endif
            @endif

            @if(in_array(auth()->user()->role_id, [1, 2]))
                <a href="{{ route('exams.create') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-[#0093dd] hover:bg-[#0082c4] active:bg-[#006fa7] text-white text-xs font-semibold rounded-lg transition shadow-none shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Unggah Konten Baru
                </a>
            @endif
        </x-page-header>
    </x-slot>

    @php
        // Prepare clean structured exams payload for Alpine modal
        $examsData = [];
        foreach ($exams as $item) {
            $canManageItem = in_array(auth()->user()->role_id, [1]) || (auth()->user()->role_id == 2 && $item->teacher_id == auth()->id());
            $examsData[$item->id] = [
                'id' => $item->id,
                'title' => $item->title,
                'subject' => $item->subject,
                'category' => $item->category,
                'category_label' => $item->category_label,
                'badge' => $item->category_badge,
                'class_name' => $item->schoolClass->class_name ?? '-',
                'teacher_name' => $item->teacher->fullname ?? 'Guru Pengampu',
                'date_formatted' => \Carbon\Carbon::parse($item->exam_date)->translatedFormat('l, d F Y'),
                'time_window' => $item->time_window,
                'time_status' => $item->time_status,
                'description' => $item->description,
                'has_file' => !empty($item->material_file_path),
                'file_name' => $item->file_name ?? basename($item->material_file_path ?? ''),
                'file_size' => $item->formatted_file_size,
                'file_ext' => $item->file_extension,
                'download_url' => route('exams.download', $item),
                'external_link' => $item->external_link,
                'show_url' => route('exams.show', $item),
                'edit_url' => $canManageItem ? route('exams.edit', $item) : null,
            ];
        }
    @endphp

    <div class="space-y-6" x-data="{
        detailModalOpen: false,
        activeExam: null,
        examsData: {{ Js::from($examsData) }},
        openDetail(id) {
            if (this.examsData && this.examsData[id]) {
                this.activeExam = this.examsData[id];
                this.detailModalOpen = true;
                document.body.classList.add('overflow-hidden');
            }
        },
        closeDetail() {
            this.detailModalOpen = false;
            document.body.classList.remove('overflow-hidden');
        }
    }">
        {{-- Flash Messages --}}
        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if(session('error'))
            <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
        @endif

        {{-- Overview Stat Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5">
            <!-- Total Konten -->
            <div class="bg-white border border-zinc-200/90 rounded-xl p-4 sm:p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Total Repositori</span>
                    <span class="w-7 h-7 rounded-lg bg-zinc-100 flex items-center justify-center text-zinc-600">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-bold font-mono text-zinc-900 tracking-tight">{{ $stats['total'] }}</span>
                    <span class="text-xs text-zinc-500 font-medium">berkas/item</span>
                </div>
                <div class="mt-2 text-[11px] text-zinc-400 flex items-center gap-1.5">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-zinc-400"></span>
                    {{ $stats['files'] }} item berlampiran berkas
                </div>
            </div>

            <!-- Materi Pembelajaran -->
            <a href="{{ route('exams.index', array_merge(request()->except('category', 'page'), ['category' => 'materi'])) }}" 
               class="bg-white border {{ request('category') === 'materi' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-zinc-200/90' }} rounded-xl p-4 sm:p-5 shadow-xs hover:border-emerald-300 transition group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Materi Belajar</span>
                    <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-bold font-mono text-zinc-900 tracking-tight">{{ $stats['materi'] }}</span>
                    <span class="text-xs text-emerald-600 font-medium font-mono">Modul / Slide</span>
                </div>
                <div class="mt-2 text-[11px] text-zinc-400 flex items-center gap-1.5">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Panduan &amp; referensi KBM
                </div>
            </a>

            <!-- Bank Soal & LKPD -->
            <a href="{{ route('exams.index', array_merge(request()->except('category', 'page'), ['category' => 'bank_soal'])) }}" 
               class="bg-white border {{ request('category') === 'bank_soal' ? 'border-sky-500 ring-2 ring-sky-500/20' : 'border-zinc-200/90' }} rounded-xl p-4 sm:p-5 shadow-xs hover:border-sky-300 transition group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-sky-700 uppercase tracking-wider">Bank Soal &amp; LKPD</span>
                    <span class="w-7 h-7 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-bold font-mono text-zinc-900 tracking-tight">{{ $stats['bank_soal'] }}</span>
                    <span class="text-xs text-sky-600 font-medium font-mono">Tugas / LKPD</span>
                </div>
                <div class="mt-2 text-[11px] text-zinc-400 flex items-center gap-1.5">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                    Latihan &amp; lembar penugasan
                </div>
            </a>

            <!-- Jadwal Ujian -->
            <a href="{{ route('exams.index', array_merge(request()->except('category', 'page'), ['category' => 'ujian'])) }}" 
               class="bg-white border {{ request('category') === 'ujian' ? 'border-rose-500 ring-2 ring-rose-500/20' : 'border-zinc-200/90' }} rounded-xl p-4 sm:p-5 shadow-xs hover:border-rose-300 transition group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-rose-700 uppercase tracking-wider">Ujian &amp; Asesmen</span>
                    <span class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-bold font-mono text-zinc-900 tracking-tight">{{ $stats['ujian'] }}</span>
                    <span class="text-xs text-rose-600 font-medium font-mono">UH / PTS / PAS</span>
                </div>
                <div class="mt-2 text-[11px] text-zinc-400 flex items-center gap-1.5">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                    Jadwal evaluasi terstruktur
                </div>
            </a>
        </div>

        {{-- Filter & Search Toolbar --}}
        <div class="bg-white border border-zinc-200/90 rounded-xl p-4 sm:p-5 shadow-xs space-y-4">
            {{-- Category Segmented Tabs --}}
            <div class="flex flex-wrap items-center gap-2 border-b border-zinc-100 dark:border-[#2B2B30] pb-3">
                <a href="{{ route('exams.index', request()->except('category', 'page')) }}"
                   class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ !request()->filled('category') ? 'bg-zinc-900 dark:bg-[#0093dd] text-white shadow-xs' : 'text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A]' }}">
                    <span>Semua Konten</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono {{ !request()->filled('category') ? 'bg-white/20 text-white' : 'bg-zinc-100 dark:bg-[#232327] text-zinc-600 dark:text-[#97979B]' }}">
                        {{ $stats['total'] }}
                    </span>
                </a>
                <a href="{{ route('exams.index', array_merge(request()->except('page'), ['category' => 'materi'])) }}"
                   class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request('category') === 'materi' ? 'bg-emerald-600 text-white shadow-xs' : 'text-zinc-600 hover:text-emerald-700 hover:bg-emerald-50' }}">
                    <span class="w-2 h-2 rounded-full {{ request('category') === 'materi' ? 'bg-white' : 'bg-emerald-500' }}"></span>
                    <span>Materi Pembelajaran</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono {{ request('category') === 'materi' ? 'bg-emerald-700 text-emerald-100' : 'bg-zinc-100 text-zinc-600' }}">
                        {{ $stats['materi'] }}
                    </span>
                </a>
                <a href="{{ route('exams.index', array_merge(request()->except('page'), ['category' => 'bank_soal'])) }}"
                   class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request('category') === 'bank_soal' ? 'bg-sky-600 text-white shadow-xs' : 'text-zinc-600 hover:text-sky-700 hover:bg-sky-50' }}">
                    <span class="w-2 h-2 rounded-full {{ request('category') === 'bank_soal' ? 'bg-white' : 'bg-sky-500' }}"></span>
                    <span>Bank Soal &amp; Tugas</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono {{ request('category') === 'bank_soal' ? 'bg-sky-700 text-sky-100' : 'bg-zinc-100 text-zinc-600' }}">
                        {{ $stats['bank_soal'] }}
                    </span>
                </a>
                <a href="{{ route('exams.index', array_merge(request()->except('page'), ['category' => 'ujian'])) }}"
                   class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ request('category') === 'ujian' ? 'bg-rose-600 text-white shadow-xs' : 'text-zinc-600 hover:text-rose-700 hover:bg-rose-50' }}">
                    <span class="w-2 h-2 rounded-full {{ request('category') === 'ujian' ? 'bg-white' : 'bg-rose-500' }}"></span>
                    <span>Jadwal Ujian &amp; Asesmen</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono {{ request('category') === 'ujian' ? 'bg-rose-700 text-rose-100' : 'bg-zinc-100 text-zinc-600' }}">
                        {{ $stats['ujian'] }}
                    </span>
                </a>
            </div>

            {{-- Search & Select Filters --}}
            <form method="GET" action="{{ route('exams.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                {{-- Preserve Category & Scope if already selected --}}
                @if(request()->filled('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                @if(request()->filled('view_scope'))
                    <input type="hidden" name="view_scope" value="{{ request('view_scope') }}">
                @endif

                {{-- Search Box --}}
                <div class="lg:col-span-4 relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-zinc-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Cari materi, judul, guru, mapel..." 
                           class="w-full rounded-lg border-zinc-200 bg-zinc-50/50 pl-9 pr-4 py-2 text-xs text-zinc-900 focus:bg-white focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-xs placeholder:text-zinc-400">
                </div>

                {{-- Class Filter (hidden for students) --}}
                @if(auth()->user()->role_id != 4)
                    <div class="lg:col-span-3">
                        <select name="class_id" onchange="this.form.submit()" class="w-full rounded-lg border-zinc-200 bg-zinc-50/50 px-3 py-2 text-xs text-zinc-900 focus:bg-white focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-xs">
                            <option value="">-- Semua Kelas --</option>
                            @foreach($availableClasses as $c)
                                <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>
                                    {{ $c->class_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="lg:col-span-3">
                        <div class="w-full px-3 py-2 text-xs font-semibold text-zinc-700 bg-zinc-50 rounded-lg border border-zinc-200">
                            Kelas: {{ auth()->user()->schoolClass->class_name ?? '-' }}
                        </div>
                    </div>
                @endif

                {{-- Subject Filter --}}
                <div class="lg:col-span-3">
                    <select name="subject" onchange="this.form.submit()" class="w-full rounded-lg border-zinc-200 bg-zinc-50/50 px-3 py-2 text-xs text-zinc-900 focus:bg-white focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-xs">
                        <option value="">-- Semua Mata Pelajaran --</option>
                        @foreach($availableSubjects as $s)
                            <option value="{{ $s }}" @selected(request('subject') == $s)>
                                {{ $s }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Sort & Submit --}}
                <div class="lg:col-span-2 flex items-center gap-2">
                    <select name="sort" onchange="this.form.submit()" class="w-full rounded-lg border-zinc-200 bg-zinc-50/50 px-2.5 py-2 text-xs text-zinc-900 focus:bg-white focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-xs">
                        <option value="date_desc" @selected(request('sort', 'date_desc') == 'date_desc')>Tanggal Terbaru</option>
                        <option value="date_asc" @selected(request('sort') == 'date_asc')>Tanggal Terlama</option>
                        <option value="title_asc" @selected(request('sort') == 'title_asc')>Judul (A - Z)</option>
                    </select>

                    @if(request()->anyFilled(['search', 'class_id', 'subject', 'sort', 'category', 'view_scope']))
                        <a href="{{ route('exams.index') }}" title="Reset Filter" class="p-2 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition shrink-0 border border-zinc-200">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Main Resources Grid --}}
        @if($exams->isEmpty())
            <div class="bg-white border border-zinc-200/90 rounded-xl p-12 text-center space-y-3.5 shadow-xs">
                <div class="w-14 h-14 rounded-2xl bg-zinc-100 text-zinc-400 mx-auto flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-zinc-900">Belum Ada Materi atau Soal</h3>
                <p class="text-xs text-zinc-500 max-w-sm mx-auto">
                    @if(request()->anyFilled(['search', 'class_id', 'subject', 'category']))
                        Tidak ditemukan materi/ujian yang sesuai dengan filter pencarian Anda. Silakan reset filter atau coba kata kunci lain.
                    @else
                        Belum ada data materi pembelajaran, bank soal latihan, atau jadwal asesmen yang dipublikasikan.
                    @endif
                </p>
                <div class="pt-2 flex items-center justify-center gap-2">
                    @if(request()->anyFilled(['search', 'class_id', 'subject', 'category']))
                        <a href="{{ route('exams.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-zinc-100 hover:bg-[#26262A] text-zinc-700 text-xs font-semibold rounded-lg transition">
                            Reset Semua Filter
                        </a>
                    @endif
                    @if(in_array(auth()->user()->role_id, [1, 2]))
                        <a href="{{ route('exams.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-zinc-900 hover:bg-black text-white text-xs font-semibold rounded-lg uppercase tracking-wider transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Unggah Konten Sekarang
                        </a>
                    @endif
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($exams as $exam)
                    @php
                        $badge = $exam->category_badge;
                        $timeStatus = $exam->time_status;
                        $canManage = in_array(auth()->user()->role_id, [1]) || (auth()->user()->role_id == 2 && $exam->teacher_id == auth()->id());
                    @endphp

                    <div class="bg-white border border-zinc-200/90 rounded-xl overflow-hidden shadow-xs hover:shadow-md transition-all duration-200 flex flex-col group relative">
                        {{-- Top Accent Line based on Category --}}
                        <div class="h-1 w-full {{ $exam->category === 'ujian' ? 'bg-rose-500' : ($exam->category === 'bank_soal' ? 'bg-sky-500' : 'bg-emerald-500') }}"></div>

                        <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between space-y-4">
                            <!-- Card Header: Badges & Time Status -->
                            <div>
                                <div class="flex items-center justify-between gap-2 mb-2.5">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-semibold border tracking-wide font-mono uppercase {{ $badge['bg'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $badge['dot'] }}"></span>
                                        {{ $badge['label'] }}
                                    </span>

                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono font-semibold border {{ $timeStatus['badge'] }}">
                                        {{ $timeStatus['text'] }}
                                    </span>
                                </div>

                                <!-- Class & Subject Meta -->
                                <div class="flex items-center gap-2 mb-1.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-zinc-100 text-zinc-800 text-[10px] font-mono font-bold uppercase tracking-wider border border-zinc-200">
                                        {{ $exam->schoolClass->class_name }}
                                    </span>
                                    <span class="text-xs font-semibold text-zinc-500 truncate" title="{{ $exam->subject }}">
                                        &bull; {{ $exam->subject }}
                                    </span>
                                </div>

                                <!-- Title -->
                                <h3 class="text-base font-bold text-zinc-900 group-hover:text-black leading-snug cursor-pointer transition-colors"
                                    @click="openDetail({{ $exam->id }})">
                                    {{ $exam->title }}
                                </h3>

                                <!-- Date & Time Grid -->
                                <div class="mt-3 grid grid-cols-2 gap-2 text-xs bg-zinc-50/80 p-2.5 rounded-lg border border-zinc-100 font-mono">
                                    <div class="flex items-center gap-1.5 text-zinc-600">
                                        <svg class="w-3.5 h-3.5 text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5" />
                                        </svg>
                                        <span class="truncate">{{ \Carbon\Carbon::parse($exam->exam_date)->translatedFormat('d M Y') }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-zinc-600 justify-end">
                                        <svg class="w-3.5 h-3.5 text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="truncate">{{ $exam->time_window ?? 'Fleksibel' }}</span>
                                    </div>
                                </div>

                                <!-- Description snippet -->
                                @if($exam->description)
                                    <p class="mt-2.5 text-xs text-zinc-600 line-clamp-2 leading-relaxed">
                                        {{ $exam->description }}
                                    </p>
                                @endif
                            </div>

                            <!-- Teacher Info -->
                            <div class="pt-2 border-t border-zinc-100 flex items-center justify-between text-xs text-zinc-500">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-5 h-5 rounded-full bg-zinc-200 text-zinc-700 flex items-center justify-center font-bold text-[10px] shrink-0">
                                        {{ strtoupper(substr($exam->teacher->fullname ?? 'G', 0, 1)) }}
                                    </div>
                                    <span class="font-medium text-zinc-700 truncate" title="{{ $exam->teacher->fullname }}">
                                        {{ $exam->teacher->fullname }}
                                    </span>
                                </div>
                                <span class="text-[10px] font-mono text-zinc-400 shrink-0">
                                    {{ $exam->created_at->diffForHumans(null, true) }} lalu
                                </span>
                            </div>
                        </div>

                        <!-- Card Footer: Attachments & Actions -->
                        <div class="px-4 py-3 bg-zinc-50/90 border-t border-zinc-100 flex items-center justify-between gap-2">
                            <!-- Left Attachment Badges -->
                            <div class="flex items-center gap-2 min-w-0">
                                @if($exam->material_file_path)
                                    <a href="{{ route('exams.download', $exam) }}" 
                                       title="Unduh Berkas ({{ $exam->file_extension }} &bull; {{ $exam->formatted_file_size }})"
                                       class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-zinc-800 dark:text-[#EDEDF0] bg-white dark:bg-[#232327] hover:bg-zinc-100 dark:hover:bg-[#26262A] border border-zinc-200 dark:border-[#2B2B30] rounded-md transition shadow-xs">
                                        <svg class="w-3.5 h-3.5 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                        <span class="font-mono text-[10px] font-bold uppercase">{{ $exam->file_extension ?? 'FILE' }}</span>
                                        @if($exam->formatted_file_size)
                                            <span class="text-[10px] text-zinc-400 font-mono">({{ $exam->formatted_file_size }})</span>
                                        @endif
                                    </a>
                                @endif

                                @if($exam->external_link)
                                    <a href="{{ $exam->external_link }}" 
                                       target="_blank" 
                                       rel="noopener noreferrer" 
                                       title="Buka CBT / Tautan Online"
                                       class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-indigo-700 bg-indigo-50/80 hover:bg-indigo-100 border border-indigo-200 rounded-md transition shadow-xs">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                        <span>Link CBT</span>
                                    </a>
                                @endif

                                @if(!$exam->material_file_path && !$exam->external_link)
                                    <span class="text-[11px] text-zinc-400 italic">Tanpa berkas</span>
                                @endif
                            </div>

                            <!-- Right Action Buttons -->
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button" 
                                        @click="openDetail({{ $exam->id }})"
                                        class="p-1.5 text-zinc-500 hover:text-zinc-900 hover:bg-[#26262A] rounded-md transition cursor-pointer" 
                                        title="Lihat Detail Lengkap">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </button>

                                @if($canManage)
                                    <a href="{{ route('exams.edit', $exam) }}" 
                                       class="p-1.5 text-zinc-500 hover:text-zinc-900 hover:bg-[#26262A] rounded-md transition cursor-pointer" 
                                       title="Ubah Konten">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                    </a>

                                    <form action="{{ route('exams.destroy', $exam) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus materi/ujian ini? Berkas terkait akan dibersihkan.');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-zinc-400 hover:text-rose-600 hover:bg-rose-50 rounded-md transition cursor-pointer" title="Hapus Konten">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination Controls --}}
            <div class="pt-4">
                {{ $exams->links() }}
            </div>
        @endif

        {{-- ==================== INTERACTIVE QUICK DETAIL MODAL (ALPINE.JS) ==================== --}}
        <div x-show="detailModalOpen" 
             x-cloak 
             class="fixed inset-0 z-50 overflow-y-auto" 
             role="dialog" 
             aria-modal="true"
             @keydown.escape.window="closeDetail()">
            
            <!-- Backdrop with Blur -->
            <div x-show="detailModalOpen" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 @click="closeDetail()"
                 class="fixed inset-0 bg-zinc-900/60 backdrop-blur-sm transition-opacity"
                 aria-hidden="true"></div>

            <!-- Perfectly Centered Flex Container with Proper Margins & Padding -->
            <div class="fixed inset-0 z-10 flex items-center justify-center p-3 sm:p-4 md:p-6 overflow-y-auto pointer-events-none">
                <div x-show="detailModalOpen" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 scale-95 translate-y-3" 
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0" 
                     x-transition:leave-end="opacity-0 scale-95 translate-y-3" 
                     class="relative w-full max-w-lg md:max-w-xl max-h-[85vh] sm:max-h-[90vh] flex flex-col bg-white rounded-2xl shadow-2xl border border-zinc-200 overflow-hidden pointer-events-auto my-auto text-left"
                     @click.outside="closeDetail()">
                    
                    <template x-if="activeExam">
                        <div class="flex flex-col h-full min-h-0">
                            {{-- Modal Header --}}
                            <div class="px-5 sm:px-6 py-4 border-b border-zinc-100 flex items-start justify-between gap-3 bg-white shrink-0">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mb-2">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded text-[10px] sm:text-[11px] font-semibold border tracking-wide font-mono uppercase"
                                              :class="activeExam?.badge?.bg || 'bg-zinc-100 text-zinc-700 border-zinc-200'">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="activeExam?.badge?.dot || 'bg-zinc-400'"></span>
                                            <span x-text="activeExam?.category_label || 'Materi'"></span>
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-zinc-100 text-zinc-800 text-[10px] sm:text-[11px] font-mono font-bold uppercase tracking-wider border border-zinc-200"
                                              x-text="activeExam?.class_name"></span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-mono font-semibold border"
                                              :class="activeExam?.time_status?.badge || 'bg-zinc-100 text-zinc-700 border-zinc-200'"
                                              x-text="activeExam?.time_status?.text"></span>
                                    </div>
                                    <h3 class="text-base sm:text-lg font-bold text-zinc-900 leading-snug break-words" x-text="activeExam?.title"></h3>
                                    <p class="text-xs font-semibold text-zinc-500 mt-1 flex items-center gap-1.5 truncate">
                                        <svg class="w-3.5 h-3.5 text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                        </svg>
                                        <span x-text="activeExam?.subject"></span>
                                    </p>
                                </div>
                                <button type="button" @click="closeDetail()" class="p-1.5 -mr-1 -mt-1 text-zinc-400 hover:text-zinc-700 hover:bg-[#26262A] rounded-lg transition shrink-0 cursor-pointer" title="Tutup">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            {{-- Modal Body (Scrollable with smooth padding) --}}
                            <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1 min-h-0">
                                <!-- Details Grid -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs bg-zinc-50/90 p-3.5 sm:p-4 rounded-xl border border-zinc-200/80">
                                    <div class="space-y-0.5">
                                        <div class="text-[10px] font-mono font-bold uppercase tracking-wider text-zinc-400">Tanggal Pelaksanaan</div>
                                        <div class="font-semibold text-zinc-900 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5" />
                                            </svg>
                                            <span x-text="activeExam?.date_formatted"></span>
                                        </div>
                                    </div>
                                    <div class="space-y-0.5">
                                        <div class="text-[10px] font-mono font-bold uppercase tracking-wider text-zinc-400">Alokasi Waktu KBM</div>
                                        <div class="font-semibold text-zinc-900 font-mono flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span x-text="activeExam?.time_window || 'Fleksibel / Penugasan Mandiri'"></span>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-2 pt-2.5 border-t border-zinc-200/60 flex items-center justify-between gap-2">
                                        <div class="space-y-0.5 min-w-0">
                                            <div class="text-[10px] font-mono font-bold uppercase tracking-wider text-zinc-400">Pengajar Pengampu</div>
                                            <div class="font-semibold text-zinc-800 truncate" x-text="activeExam?.teacher_name"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Description Content -->
                                <template x-if="activeExam?.description">
                                    <div class="space-y-1.5">
                                        <div class="text-[10px] font-mono font-bold uppercase tracking-wider text-zinc-400">Instruksi &amp; Keterangan Tambahan</div>
                                        <div class="p-3.5 sm:p-4 rounded-xl bg-zinc-50/80 border border-zinc-200/80 text-xs text-zinc-700 whitespace-pre-line leading-relaxed break-words"
                                             x-text="activeExam?.description"></div>
                                    </div>
                                </template>

                                <!-- Attachments / Resources Box -->
                                <div class="space-y-2 pt-1">
                                    <div class="text-[10px] font-mono font-bold uppercase tracking-wider text-zinc-400">Akses Berkas &amp; Lampiran</div>
                                    
                                    <!-- File Download Card -->
                                    <template x-if="activeExam?.has_file">
                                        <div class="flex items-center justify-between p-3 sm:p-3.5 bg-zinc-50/80 hover:bg-[#26262A] border border-zinc-200 rounded-xl transition gap-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="w-10 h-10 rounded-lg bg-zinc-900 text-white font-mono font-bold text-xs flex items-center justify-center shrink-0"
                                                     x-text="activeExam?.file_ext || 'FILE'"></div>
                                                <div class="min-w-0">
                                                    <div class="text-xs font-bold text-zinc-900 truncate" x-text="activeExam?.file_name"></div>
                                                    <div class="text-[11px] text-zinc-500 font-mono mt-0.5" x-text="activeExam?.file_size || 'Berkas Lampiran'"></div>
                                                </div>
                                            </div>
                                            <a :href="activeExam?.download_url" 
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-zinc-900 hover:bg-black text-white text-xs font-semibold rounded-lg transition shadow-xs shrink-0 cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                </svg>
                                                <span>Unduh</span>
                                            </a>
                                        </div>
                                    </template>

                                    <!-- External CBT Link Card -->
                                    <template x-if="activeExam?.external_link">
                                        <div class="flex items-center justify-between p-3 sm:p-3.5 bg-indigo-50/70 border border-indigo-200 rounded-xl gap-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="w-10 h-10 rounded-lg bg-indigo-600 text-white flex items-center justify-center shrink-0">
                                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                                                    </svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="text-xs font-bold text-indigo-950">Tautan Ujian / CBT Online</div>
                                                    <div class="text-[11px] text-indigo-700 truncate font-mono mt-0.5" x-text="activeExam?.external_link"></div>
                                                </div>
                                            </div>
                                            <a :href="activeExam?.external_link" 
                                               target="_blank" 
                                               rel="noopener noreferrer" 
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition shadow-xs shrink-0 cursor-pointer">
                                                <span>Buka</span>
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                                </svg>
                                            </a>
                                        </div>
                                    </template>

                                    <!-- No attachments fallback -->
                                    <template x-if="!activeExam?.has_file && !activeExam?.external_link">
                                        <div class="p-3 text-center bg-zinc-50 border border-zinc-200/80 rounded-xl text-xs text-zinc-500 italic">
                                            Tidak ada berkas terlampir atau tautan eksternal untuk konten ini.
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Modal Footer --}}
                            <div class="px-5 sm:px-6 py-3.5 bg-zinc-50 border-t border-zinc-100 flex flex-wrap items-center justify-between gap-3 shrink-0">
                                <a :href="activeExam?.show_url" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900 transition flex items-center gap-1 py-1">
                                    <span>Halaman Detail Penuh</span>
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                </a>

                                <div class="flex items-center gap-2">
                                    <template x-if="activeExam?.edit_url">
                                        <a :href="activeExam?.edit_url" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-[#232327] border border-zinc-200 dark:border-[#2B2B30] text-zinc-700 dark:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] text-xs font-semibold rounded-lg transition shadow-xs">
                                            <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                            Ubah
                                        </a>
                                    </template>
                                    <button type="button" @click="closeDetail()" class="px-4 py-1.5 bg-zinc-900 hover:bg-black text-white text-xs font-semibold rounded-lg transition shadow-xs cursor-pointer">
                                        Tutup
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
