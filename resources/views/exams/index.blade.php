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
                        <svg aria-hidden="true" class="w-3.5 h-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                        Tampilkan Materi Saya
                    </a>
                @else
                    <a href="{{ route('exams.index', array_merge(request()->except('page'), ['view_scope' => 'all'])) }}" 
                       class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-zinc-700 dark:text-zinc-200 bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] hover:bg-zinc-50 dark:hover:bg-[#26262A] rounded-lg transition shadow-xs">
                        <svg aria-hidden="true" class="w-3.5 h-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                        </svg>
                        Lihat Semua Konten Sekolah
                    </a>
                @endif
            @endif

            @if(auth()->user()?->hasCurriculumAccess() || auth()->user()->role_id == 2)
                <a href="{{ route('exams.create') }}" class="inline-flex items-center gap-2 min-h-[44px] sm:min-h-0 px-3.5 py-2 bg-[#0073B1] hover:bg-[#005D8F] active:bg-[#004A73] text-white text-xs font-semibold rounded-lg transition shadow-none shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
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
            $canManageItem = auth()->user()?->hasCurriculumAccess() || (auth()->user()->role_id == 2 && $item->teacher_id == auth()->id());
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
            <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200/90 dark:border-[#2B2B30] rounded-xl p-4 sm:p-5 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Total Repositori</span>
                    <span class="w-7 h-7 rounded-lg bg-zinc-100 dark:bg-[#232327] flex items-center justify-center text-zinc-600 dark:text-[#97979B]">
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-bold font-mono text-zinc-900 dark:text-[#EDEDF0] tracking-tight">{{ $stats['total'] }}</span>
                    <span class="text-xs text-zinc-500 font-medium">berkas/item</span>
                </div>
                <div class="mt-2 text-[11px] text-zinc-400 flex items-center gap-1.5">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-zinc-400"></span>
                    {{ $stats['files'] }} item berlampiran berkas
                </div>
            </div>

            <!-- Materi Pembelajaran -->
            <a href="{{ route('exams.index', array_merge(request()->except('category', 'page'), ['category' => 'materi'])) }}" 
               class="bg-white dark:bg-[#1D1D21] border {{ request('category') === 'materi' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-zinc-200/90 dark:border-[#2B2B30]' }} rounded-xl p-4 sm:p-5 shadow-xs hover:border-emerald-300 transition group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Materi Belajar</span>
                    <span class="w-7 h-7 rounded-lg bg-emerald-50 dark:bg-[#16281D] text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-bold font-mono text-zinc-900 dark:text-[#EDEDF0] tracking-tight">{{ $stats['materi'] }}</span>
                    <span class="text-xs text-emerald-600 font-medium font-mono">Modul / Slide</span>
                </div>
                <div class="mt-2 text-[11px] text-zinc-400 flex items-center gap-1.5">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Panduan &amp; referensi KBM
                </div>
            </a>

            <!-- Bank Soal & LKPD -->
            <a href="{{ route('exams.index', array_merge(request()->except('category', 'page'), ['category' => 'bank_soal'])) }}" 
               class="bg-white dark:bg-[#1D1D21] border {{ request('category') === 'bank_soal' ? 'border-sky-500 ring-2 ring-sky-500/20' : 'border-zinc-200/90 dark:border-[#2B2B30]' }} rounded-xl p-4 sm:p-5 shadow-xs hover:border-sky-300 transition group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-sky-700 uppercase tracking-wider">Bank Soal &amp; LKPD</span>
                    <span class="w-7 h-7 rounded-lg bg-sky-50 dark:bg-[#16222F] text-sky-600 dark:text-sky-400 flex items-center justify-center">
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-bold font-mono text-zinc-900 dark:text-[#EDEDF0] tracking-tight">{{ $stats['bank_soal'] }}</span>
                    <span class="text-xs text-sky-600 font-medium font-mono">Tugas / LKPD</span>
                </div>
                <div class="mt-2 text-[11px] text-zinc-400 flex items-center gap-1.5">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                    Latihan &amp; lembar penugasan
                </div>
            </a>

            <!-- Jadwal Ujian -->
            <a href="{{ route('exams.index', array_merge(request()->except('category', 'page'), ['category' => 'ujian'])) }}" 
               class="bg-white dark:bg-[#1D1D21] border {{ request('category') === 'ujian' ? 'border-rose-500 ring-2 ring-rose-500/20' : 'border-zinc-200/90 dark:border-[#2B2B30]' }} rounded-xl p-4 sm:p-5 shadow-xs hover:border-rose-300 transition group">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-rose-700 uppercase tracking-wider">Ujian &amp; Asesmen</span>
                    <span class="w-7 h-7 rounded-lg bg-rose-50 dark:bg-[#2D1618] text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-bold font-mono text-zinc-900 dark:text-[#EDEDF0] tracking-tight">{{ $stats['ujian'] }}</span>
                    <span class="text-xs text-rose-600 font-medium font-mono">UH / PTS / PAS</span>
                </div>
                <div class="mt-2 text-[11px] text-zinc-400 flex items-center gap-1.5">
                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                    Jadwal evaluasi terstruktur
                </div>
            </a>
        </div>

        {{-- Filter & Search Toolbar --}}
        <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200/90 dark:border-[#2B2B30] rounded-xl p-4 sm:p-5 shadow-xs space-y-4">
            {{-- Category Segmented Tabs --}}
            @php
                $isAllCat = !request()->filled('category');
                $isMateriCat = request('category') === 'materi';
                $isBankSoalCat = request('category') === 'bank_soal';
                $isUjianCat = request('category') === 'ujian';
                $catTabBase = 'inline-flex items-center gap-2 min-h-[44px] sm:min-h-0 px-3 py-1.5 rounded-lg text-xs font-semibold transition';
                $catTabInactive = 'text-zinc-700 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A]';
                $catBadgeInactive = 'bg-zinc-100 dark:bg-[#232327] text-zinc-700 dark:text-[#9496A1]';
            @endphp
            <div class="flex flex-wrap items-center gap-2 border-b border-zinc-100 dark:border-[#2B2B30] pb-3">
                <a href="{{ route('exams.index', request()->except('category', 'page')) }}"
                   class="{{ $catTabBase }} {{ $isAllCat ? 'bg-[#0073B1] text-white shadow-xs' : $catTabInactive }}">
                    <span>Semua Konten</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[11px] font-mono {{ $isAllCat ? 'bg-[#004A73] text-white' : $catBadgeInactive }}">
                        {{ $stats['total'] }}
                    </span>
                </a>
                <a href="{{ route('exams.index', array_merge(request()->except('page'), ['category' => 'materi'])) }}"
                   class="{{ $catTabBase }} {{ $isMateriCat ? 'bg-emerald-700 text-white shadow-xs' : $catTabInactive }}">
                    <span class="w-2 h-2 rounded-full {{ $isMateriCat ? 'bg-white' : 'bg-emerald-500' }}"></span>
                    <span>Materi Pembelajaran</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[11px] font-mono {{ $isMateriCat ? 'bg-emerald-900 text-emerald-100' : $catBadgeInactive }}">
                        {{ $stats['materi'] }}
                    </span>
                </a>
                <a href="{{ route('exams.index', array_merge(request()->except('page'), ['category' => 'bank_soal'])) }}"
                   class="{{ $catTabBase }} {{ $isBankSoalCat ? 'bg-sky-700 text-white shadow-xs' : $catTabInactive }}">
                    <span class="w-2 h-2 rounded-full {{ $isBankSoalCat ? 'bg-white' : 'bg-sky-500' }}"></span>
                    <span>Bank Soal &amp; Tugas</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[11px] font-mono {{ $isBankSoalCat ? 'bg-sky-900 text-sky-100' : $catBadgeInactive }}">
                        {{ $stats['bank_soal'] }}
                    </span>
                </a>
                <a href="{{ route('exams.index', array_merge(request()->except('page'), ['category' => 'ujian'])) }}"
                   class="{{ $catTabBase }} {{ $isUjianCat ? 'bg-rose-700 text-white shadow-xs' : $catTabInactive }}">
                    <span class="w-2 h-2 rounded-full {{ $isUjianCat ? 'bg-white' : 'bg-rose-500' }}"></span>
                    <span>Jadwal Ujian &amp; Asesmen</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[11px] font-mono {{ $isUjianCat ? 'bg-rose-900 text-rose-100' : $catBadgeInactive }}">
                        {{ $stats['ujian'] }}
                    </span>
                </a>
            </div>

            {{-- Search & Select Filters --}}
            <search>
            <form method="GET" action="{{ route('exams.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                {{-- Preserve Category & Scope if already selected --}}
                @if(request()->filled('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                @if(request()->filled('view_scope'))
                    <input type="hidden" name="view_scope" value="{{ request('view_scope') }}">
                @endif

                {{-- Search Box --}}
                <div class="lg:col-span-3 relative">
                    <label for="exams_search" class="sr-only">Cari materi, judul, guru, atau mata pelajaran</label>
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-zinc-500 dark:text-[#9496A1]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input id="exams_search"
                           type="search" 
                           enterkeyhint="search"
                           name="search" 
                           value="{{ request('search') }}" 
                           placeholder="Cari materi, judul, guru, mapel…" 
                           class="w-full rounded-lg border-zinc-200 dark:border-[#2B2B30] bg-zinc-50/50 dark:bg-[#19191C] pl-9 pr-4 py-2 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:bg-white dark:focus:bg-[#19191C] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1] shadow-xs">
                </div>

                {{-- Class Filter (hidden for students) --}}
                @if(auth()->user()->role_id != 4)
                    <div class="lg:col-span-2">
                        <label for="exams_class_id" class="sr-only">Filter Kelas</label>
                        <select id="exams_class_id" name="class_id" class="w-full rounded-lg border-zinc-200 dark:border-[#2B2B30] bg-zinc-50/50 dark:bg-[#19191C] px-3 py-2 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:bg-white dark:focus:bg-[#19191C] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1] shadow-xs">
                            <option value="">-- Semua Kelas --</option>
                            @foreach($availableClasses as $c)
                                <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>
                                    {{ $c->class_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="lg:col-span-2">
                        <div class="w-full px-3 py-2 text-xs font-semibold text-zinc-700 dark:text-[#EDEDF0] bg-zinc-50 dark:bg-[#232327] rounded-lg border border-zinc-200 dark:border-[#2B2B30]">
                            Kelas: {{ auth()->user()->schoolClass->class_name ?? '-' }}
                        </div>
                    </div>
                @endif

                {{-- Subject Filter --}}
                <div class="lg:col-span-3">
                    <label for="exams_subject" class="sr-only">Filter Mata Pelajaran</label>
                    <select id="exams_subject" name="subject" class="w-full rounded-lg border-zinc-200 dark:border-[#2B2B30] bg-zinc-50/50 dark:bg-[#19191C] px-3 py-2 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:bg-white dark:focus:bg-[#19191C] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1] shadow-xs">
                        <option value="">-- Semua Mata Pelajaran --</option>
                        @foreach($availableSubjects as $s)
                            <option value="{{ $s }}" @selected(request('subject') == $s)>
                                {{ $s }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Sort --}}
                <div class="lg:col-span-2">
                    <label for="exams_sort" class="sr-only">Urutkan Hasil</label>
                    <select id="exams_sort" name="sort" class="w-full rounded-lg border-zinc-200 dark:border-[#2B2B30] bg-zinc-50/50 dark:bg-[#19191C] px-2.5 py-2 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:bg-white dark:focus:bg-[#19191C] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1] shadow-xs">
                        <option value="date_desc" @selected(request('sort', 'date_desc') == 'date_desc')>Tanggal Terbaru</option>
                        <option value="date_asc" @selected(request('sort') == 'date_asc')>Tanggal Terlama</option>
                        <option value="title_asc" @selected(request('sort') == 'title_asc')>Judul (A - Z)</option>
                    </select>
                </div>

                {{-- Action: Terapkan Filter & Reset --}}
                <div class="lg:col-span-2 flex items-center gap-1.5">
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 min-h-[44px] sm:min-h-0 px-3 py-2 bg-[#0073B1] hover:bg-[#005D8F] active:bg-[#004A73] text-white text-xs font-semibold rounded-lg transition shadow-xs cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                        </svg>
                        <span>Terapkan Filter</span>
                    </button>

                    @if(request()->anyFilled(['search', 'class_id', 'subject', 'sort', 'category', 'view_scope']))
                        <a href="{{ route('exams.index') }}" aria-label="Reset semua filter" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[34px] sm:min-w-[34px] p-2 text-zinc-600 dark:text-[#9496A1] hover:text-rose-700 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-[#2D1618] rounded-lg transition shrink-0 border border-zinc-200 dark:border-[#2B2B30]">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </div>
            </form>
            </search>
        </div>

        {{-- Main Resources Grid --}}
        @if($exams->isEmpty())
            <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200/90 dark:border-[#2B2B30] rounded-xl p-12 text-center space-y-3.5 shadow-xs">
                <div class="w-14 h-14 rounded-2xl bg-zinc-100 dark:bg-[#232327] text-zinc-500 dark:text-[#9496A1] mx-auto flex items-center justify-center">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </div>
                <h2 class="text-sm font-bold text-zinc-900 dark:text-[#EDEDF0]">Belum Ada Materi atau Soal</h2>
                <p class="text-xs text-zinc-600 dark:text-[#9496A1] max-w-sm mx-auto">
                    @if(request()->anyFilled(['search', 'class_id', 'subject', 'category']))
                        Tidak ditemukan materi/ujian yang sesuai dengan filter pencarian Anda. Silakan reset filter atau coba kata kunci lain.
                    @else
                        Belum ada data materi pembelajaran, bank soal latihan, atau jadwal asesmen yang dipublikasikan.
                    @endif
                </p>
                <div class="pt-2 flex flex-wrap items-center justify-center gap-2">
                    @if(request()->anyFilled(['search', 'class_id', 'subject', 'category']))
                        <a href="{{ route('exams.index') }}" class="inline-flex items-center gap-1.5 min-h-[44px] sm:min-h-0 px-3 py-2 bg-zinc-100 hover:bg-zinc-200 dark:bg-[#232327] dark:hover:bg-[#26262A] text-zinc-700 dark:text-[#EDEDF0] text-xs font-semibold rounded-lg transition">
                            Reset Semua Filter
                        </a>
                    @endif
                    @if(auth()->user()?->hasCurriculumAccess() || auth()->user()->role_id == 2)
                        <a href="{{ route('exams.create') }}" class="inline-flex items-center gap-2 min-h-[44px] sm:min-h-0 px-4 py-2 bg-[#0073B1] hover:bg-[#005D8F] text-white text-xs font-semibold rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Unggah Konten Sekarang
                        </a>
                    @endif
                </div>
            </div>
        @else
            <h2 class="sr-only">Daftar Materi Pembelajaran, Bank Soal, dan Jadwal Ujian</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach ($exams as $exam)
                    @php
                        $badge = $exam->category_badge;
                        $timeStatus = $exam->time_status;
                        $canManage = auth()->user()?->hasCurriculumAccess() || (auth()->user()->role_id == 2 && $exam->teacher_id == auth()->id());
                    @endphp

                    <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200/90 dark:border-[#2B2B30] rounded-xl overflow-hidden shadow-xs hover:shadow-md transition-[border-color,box-shadow] duration-200 flex flex-col group relative">
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
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-zinc-100 dark:bg-[#232327] text-zinc-800 dark:text-[#EDEDF0] text-[10px] font-mono font-bold uppercase tracking-wider border border-zinc-200 dark:border-[#2B2B30]">
                                        {{ $exam->schoolClass->class_name }}
                                    </span>
                                    <span class="text-xs font-semibold text-zinc-500 truncate" title="{{ $exam->subject }}">
                                        &bull; {{ $exam->subject }}
                                    </span>
                                </div>

                                <!-- Title -->
                                <h3 class="text-base font-bold text-zinc-900 dark:text-[#EDEDF0] group-hover:text-black dark:group-hover:text-white leading-snug cursor-pointer transition-colors"
                                    @click="openDetail({{ $exam->id }})">
                                    {{ $exam->title }}
                                </h3>

                                <!-- Date & Time Row -->
                                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs bg-zinc-50/80 dark:bg-[#232327] px-3 py-2.5 rounded-lg border border-zinc-100 dark:border-[#2B2B30] font-mono tabular-nums">
                                    <div class="flex items-center gap-1.5 text-zinc-700 dark:text-[#9496A1] whitespace-nowrap">
                                        <svg class="w-3.5 h-3.5 text-zinc-500 dark:text-[#9496A1] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5" />
                                        </svg>
                                        <span>{{ \Carbon\Carbon::parse($exam->exam_date)->translatedFormat('d M Y') }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5 text-zinc-700 dark:text-[#9496A1] whitespace-nowrap">
                                        <svg class="w-3.5 h-3.5 text-zinc-500 dark:text-[#9496A1] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>{{ $exam->time_window ?? 'Fleksibel' }}</span>
                                    </div>
                                </div>

                                <!-- Description snippet -->
                                @if($exam->description)
                                    <p class="mt-2.5 text-xs text-zinc-600 dark:text-[#B4B6C2] line-clamp-2 leading-relaxed">
                                        {{ $exam->description }}
                                    </p>
                                @endif
                            </div>

                            <!-- Teacher Info -->
                            <div class="pt-2 border-t border-zinc-100 dark:border-[#2B2B30] flex items-center justify-between text-xs text-zinc-600 dark:text-[#9496A1]">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-5 h-5 rounded-full bg-zinc-200 dark:bg-[#232327] text-zinc-700 dark:text-[#EDEDF0] flex items-center justify-center font-bold text-[11px] shrink-0">
                                        {{ strtoupper(substr($exam->teacher->fullname ?? 'G', 0, 1)) }}
                                    </div>
                                    <span class="font-medium text-zinc-700 dark:text-[#EDEDF0] truncate" title="{{ $exam->teacher->fullname }}">
                                        {{ $exam->teacher->fullname }}
                                    </span>
                                </div>
                                <span class="text-[11px] font-mono text-zinc-600 dark:text-[#9496A1] shrink-0">
                                    {{ $exam->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>

                        <!-- Card Footer: Attachments & Actions -->
                        <div class="px-4 py-3 bg-zinc-50/90 dark:bg-[#1E1E22] border-t border-zinc-100 dark:border-[#2B2B30] flex items-center justify-between gap-2">
                            <!-- Left Attachment Badges -->
                            <div class="flex items-center gap-2 min-w-0">
                                @if($exam->material_file_path)
                                    <a href="{{ route('exams.download', $exam) }}" 
                                       title="Unduh Berkas ({{ $exam->file_extension }} &bull; {{ $exam->formatted_file_size }})"
                                       class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-zinc-800 dark:text-[#EDEDF0] bg-white dark:bg-[#232327] hover:bg-zinc-100 dark:hover:bg-[#26262A] border border-zinc-200 dark:border-[#2B2B30] rounded-md transition shadow-xs">
                                        <svg class="w-3.5 h-3.5 text-zinc-600 dark:text-[#9496A1]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                        </svg>
                                        <span class="font-mono text-[11px] font-bold uppercase">{{ $exam->file_extension ?? 'FILE' }}</span>
                                        @if($exam->formatted_file_size)
                                            <span class="text-[11px] text-zinc-600 dark:text-[#9496A1] font-mono">({{ $exam->formatted_file_size }})</span>
                                        @endif
                                    </a>
                                @endif

                                @if($exam->external_link)
                                    <a href="{{ $exam->external_link }}" 
                                       target="_blank" 
                                       rel="noopener noreferrer" 
                                       title="Buka CBT / Tautan Online"
                                       class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:text-sky-300 bg-indigo-50/80 dark:bg-[#16222F] hover:bg-indigo-100 dark:hover:bg-[#203144] border border-indigo-200 dark:border-sky-700/60 rounded-md transition shadow-xs">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                        <span>Link CBT</span>
                                    </a>
                                @endif

                                @if(!$exam->material_file_path && !$exam->external_link)
                                    <span class="text-[11px] text-zinc-600 dark:text-[#9496A1] italic">Tanpa berkas</span>
                                @endif
                            </div>

                            <!-- Right Action Buttons -->
                            <div class="flex items-center gap-1 shrink-0">
                                <button type="button" 
                                        @click="openDetail({{ $exam->id }})"
                                        class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[32px] sm:min-w-[32px] p-1.5 text-zinc-600 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] rounded-md transition cursor-pointer" 
                                        aria-label="Lihat detail {{ $exam->title }}">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </button>

                                @if($canManage)
                                    <a href="{{ route('exams.edit', $exam) }}" 
                                       class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[32px] sm:min-w-[32px] p-1.5 text-zinc-600 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] rounded-md transition cursor-pointer" 
                                       aria-label="Ubah konten {{ $exam->title }}">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                    </a>

                                    <form action="{{ route('exams.destroy', $exam) }}" method="POST" data-confirm="Apakah Anda yakin ingin menghapus materi/ujian '{{ addslashes($exam->title) }}'? Berkas terkait akan dibersihkan." data-confirm-title="Hapus Materi / Ujian" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[32px] sm:min-w-[32px] p-1.5 text-zinc-500 dark:text-[#9496A1] hover:text-rose-700 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-[#2D1618] rounded-md transition cursor-pointer" aria-label="Hapus konten {{ $exam->title }}">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
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

        {{-- Modal detail materi/ujian --}}
        <dialog closedby="any"
             x-effect="detailModalOpen ? ($el.open || $el.showModal()) : ($el.open && $el.close())"
             @close="closeDetail()"
             @cancel.prevent="closeDetail()"
             aria-labelledby="exam-detail-modal-title"
             class="m-auto w-[calc(100%-2rem)] max-w-lg md:max-w-xl max-h-[85dvh] sm:max-h-[90dvh] open:flex flex-col bg-white dark:bg-[#1D1D21] rounded-2xl shadow-2xl border border-zinc-200 dark:border-[#2B2B30] overflow-hidden text-left p-0">
                    
                    <template x-if="activeExam">
                        <div class="flex flex-col h-full min-h-0">
                            {{-- Modal Header --}}
                            <div class="px-5 sm:px-6 py-4 border-b border-zinc-100 dark:border-[#2B2B30] flex items-start justify-between gap-3 bg-white dark:bg-[#1D1D21] shrink-0">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mb-2">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded text-[10px] sm:text-[11px] font-semibold border tracking-wide font-mono uppercase"
                                              :class="activeExam?.badge?.bg || 'bg-zinc-100 dark:bg-[#232327] text-zinc-700 dark:text-[#EDEDF0] border-zinc-200 dark:border-[#2B2B30]'">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="activeExam?.badge?.dot || 'bg-zinc-400'"></span>
                                            <span x-text="activeExam?.category_label || 'Materi'"></span>
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-zinc-100 dark:bg-[#232327] text-zinc-800 dark:text-[#EDEDF0] text-[10px] sm:text-[11px] font-mono font-bold uppercase tracking-wider border border-zinc-200 dark:border-[#2B2B30]"
                                              x-text="activeExam?.class_name"></span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] sm:text-[11px] font-mono font-semibold border"
                                              :class="activeExam?.time_status?.badge || 'bg-zinc-100 dark:bg-[#232327] text-zinc-700 dark:text-[#EDEDF0] border-zinc-200 dark:border-[#2B2B30]'"
                                              x-text="activeExam?.time_status?.text"></span>
                                    </div>
                                    <h2 id="exam-detail-modal-title" class="text-base sm:text-lg font-bold text-zinc-900 dark:text-[#EDEDF0] leading-snug break-words" x-text="activeExam?.title"></h2>
                                    <p class="text-xs font-semibold text-zinc-600 dark:text-[#9496A1] mt-1 flex items-center gap-1.5 truncate">
                                        <svg class="w-3.5 h-3.5 text-zinc-500 dark:text-[#9496A1] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                        </svg>
                                        <span x-text="activeExam?.subject"></span>
                                    </p>
                                </div>
                                <button type="button" @click="closeDetail()" aria-label="Tutup modal detail" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[32px] sm:min-w-[32px] p-1.5 -mr-1 -mt-1 text-zinc-500 hover:text-zinc-900 dark:text-[#9496A1] dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-[#26262A] rounded-lg transition shrink-0 cursor-pointer">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            {{-- Modal Body (Scrollable with smooth padding) --}}
                            <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1 min-h-0">
                                <!-- Details Grid -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs bg-zinc-50/90 dark:bg-[#232327] p-3.5 sm:p-4 rounded-xl border border-zinc-200/80 dark:border-[#2B2B30]">
                                    <div class="space-y-0.5">
                                        <div class="text-[11px] font-mono font-bold uppercase tracking-wider text-zinc-600 dark:text-[#9496A1]">Tanggal Pelaksanaan</div>
                                        <div class="font-semibold text-zinc-900 dark:text-[#EDEDF0] flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-zinc-500 dark:text-[#9496A1] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 9v7.5" />
                                            </svg>
                                            <span x-text="activeExam?.date_formatted"></span>
                                        </div>
                                    </div>
                                    <div class="space-y-0.5">
                                        <div class="text-[11px] font-mono font-bold uppercase tracking-wider text-zinc-600 dark:text-[#9496A1]">Alokasi Waktu KBM</div>
                                        <div class="font-semibold text-zinc-900 dark:text-[#EDEDF0] font-mono flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-zinc-500 dark:text-[#9496A1] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span x-text="activeExam?.time_window || 'Fleksibel / Penugasan Mandiri'"></span>
                                        </div>
                                    </div>
                                    <div class="sm:col-span-2 pt-2.5 border-t border-zinc-200/60 dark:border-[#2B2B30] flex items-center justify-between gap-2">
                                        <div class="space-y-0.5 min-w-0">
                                            <div class="text-[11px] font-mono font-bold uppercase tracking-wider text-zinc-600 dark:text-[#9496A1]">Pengajar Pengampu</div>
                                            <div class="font-semibold text-zinc-800 dark:text-[#EDEDF0] truncate" x-text="activeExam?.teacher_name"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Description Content -->
                                <template x-if="activeExam?.description">
                                    <div class="space-y-1.5">
                                        <div class="text-[11px] font-mono font-bold uppercase tracking-wider text-zinc-600 dark:text-[#9496A1]">Instruksi &amp; Keterangan Tambahan</div>
                                        <div class="p-3.5 sm:p-4 rounded-xl bg-zinc-50/80 dark:bg-[#232327] border border-zinc-200/80 dark:border-[#2B2B30] text-xs text-zinc-700 dark:text-[#EDEDF0] whitespace-pre-line leading-relaxed break-words"
                                             x-text="activeExam?.description"></div>
                                    </div>
                                </template>

                                <!-- Attachments / Resources Box -->
                                <div class="space-y-2 pt-1">
                                    <div class="text-[11px] font-mono font-bold uppercase tracking-wider text-zinc-600 dark:text-[#9496A1]">Akses Berkas &amp; Lampiran</div>
                                    
                                    <!-- File Download Card -->
                                    <template x-if="activeExam?.has_file">
                                        <div class="flex items-center justify-between p-3 sm:p-3.5 bg-zinc-50/80 dark:bg-[#232327] hover:bg-zinc-100 dark:hover:bg-[#26262A] border border-zinc-200 dark:border-[#2B2B30] rounded-xl transition gap-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="w-10 h-10 rounded-lg bg-zinc-100 dark:bg-[#1D1D21] text-zinc-800 dark:text-[#EDEDF0] border border-zinc-200 dark:border-[#2B2B30] font-mono font-bold text-xs flex items-center justify-center shrink-0"
                                                     x-text="activeExam?.file_ext || 'FILE'"></div>
                                                <div class="min-w-0">
                                                    <div class="text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] truncate" x-text="activeExam?.file_name"></div>
                                                    <div class="text-[11px] text-zinc-600 dark:text-[#9496A1] font-mono mt-0.5" x-text="activeExam?.file_size || 'Berkas Lampiran'"></div>
                                                </div>
                                            </div>
                                            <a :href="activeExam?.download_url" 
                                               class="inline-flex items-center gap-1.5 min-h-[44px] sm:min-h-0 px-3 py-1.5 bg-[#0073B1] hover:bg-[#005D8F] text-white text-xs font-semibold rounded-lg transition shadow-xs shrink-0 cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                </svg>
                                                <span>Unduh</span>
                                            </a>
                                        </div>
                                    </template>

                                    <!-- External CBT Link Card -->
                                    <template x-if="activeExam?.external_link">
                                        <div class="flex items-center justify-between p-3 sm:p-3.5 bg-indigo-50/70 dark:bg-[#16222F] border border-indigo-200 dark:border-sky-700/60 rounded-xl gap-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="w-10 h-10 rounded-lg bg-indigo-600 text-white flex items-center justify-center shrink-0">
                                                    <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                                                    </svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="text-xs font-bold text-indigo-950 dark:text-sky-200">Tautan Ujian / CBT Online</div>
                                                    <div class="text-[11px] text-indigo-700 dark:text-sky-300 truncate font-mono mt-0.5" x-text="activeExam?.external_link"></div>
                                                </div>
                                            </div>
                                            <a :href="activeExam?.external_link" 
                                               target="_blank" 
                                               rel="noopener noreferrer" 
                                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition shadow-xs shrink-0 cursor-pointer">
                                                <span>Buka</span>
                                                <svg aria-hidden="true" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                                </svg>
                                            </a>
                                        </div>
                                    </template>

                                    <!-- No attachments fallback -->
                                    <template x-if="!activeExam?.has_file && !activeExam?.external_link">
                                        <div class="p-3 text-center bg-zinc-50 dark:bg-[#232327] border border-zinc-200/80 dark:border-[#2B2B30] rounded-xl text-xs text-zinc-500 dark:text-[#97979B] italic">
                                            Tidak ada berkas terlampir atau tautan eksternal untuk konten ini.
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Modal Footer --}}
                            <div class="px-5 sm:px-6 py-3.5 bg-zinc-50 dark:bg-[#1D1D21] border-t border-zinc-100 dark:border-[#2B2B30] flex flex-wrap items-center justify-between gap-3 shrink-0">
                                <a :href="activeExam?.show_url" class="text-xs font-semibold text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] transition flex items-center gap-1 py-1">
                                    <span>Halaman Detail Penuh</span>
                                    <svg aria-hidden="true" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                </a>

                                <div class="flex items-center gap-2">
                                    <template x-if="activeExam?.edit_url">
                                        <a :href="activeExam?.edit_url" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-[#232327] border border-zinc-200 dark:border-[#2B2B30] text-zinc-700 dark:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] text-xs font-semibold rounded-lg transition shadow-xs">
                                            <svg aria-hidden="true" class="w-3.5 h-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                            Ubah
                                        </a>
                                    </template>
                                    <button type="button" @click="closeDetail()" class="px-4 py-1.5 bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-[#2B2B30] dark:hover:bg-[#3A3A3F] dark:text-[#EDEDF0] text-xs font-semibold rounded-lg transition shadow-xs cursor-pointer">
                                        Tutup
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
        </dialog>
    </div>
</x-app-layout>
