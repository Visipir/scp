<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('exams.index') }}" class="p-2 -ml-2 rounded-lg text-zinc-400 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] transition" title="Kembali ke Repositori">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                </a>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-bold font-mono text-[#97979B] uppercase tracking-wider">
                            Detail Konten #{{ $exam->id }}
                        </span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0] mt-0.5">
                        {{ $exam->title }}
                    </h1>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @php
                    $canManage = in_array(auth()->user()->role_id, [1]) || (auth()->user()->role_id == 2 && $exam->teacher_id == auth()->id());
                @endphp

                @if($canManage)
                    <a href="{{ route('exams.edit', $exam) }}" class="inline-flex items-center gap-1.5 px-3 py-2 bg-white dark:bg-[#232327] border border-zinc-200 dark:border-[#2B2B30] text-zinc-700 dark:text-[#EDEDF0] hover:bg-zinc-50 dark:hover:bg-[#26262A] text-xs font-semibold rounded-lg transition shadow-xs">
                        <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                        </svg>
                        Ubah Konten
                    </a>

                    <form action="{{ route('exams.destroy', $exam) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus materi/ujian ini?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 bg-rose-50 dark:bg-[#2D1618] border border-rose-200 dark:border-rose-700/60 text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-[#3D1D20] text-xs font-semibold rounded-lg transition shadow-xs">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                            </svg>
                            Hapus
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl space-y-6">
        @php
            $badge = $exam->category_badge;
            $timeStatus = $exam->time_status;
        @endphp

        <!-- Main Overview Card -->
        <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200/90 dark:border-[#2B2B30] rounded-2xl shadow-sm overflow-hidden">
            {{-- Top Accent Bar --}}
            <div class="h-1.5 w-full {{ $exam->category === 'ujian' ? 'bg-rose-500' : ($exam->category === 'bank_soal' ? 'bg-sky-500' : 'bg-emerald-500') }}"></div>

            <div class="p-6 sm:p-8 space-y-6">
                <!-- Badges Row -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold border tracking-wide font-mono uppercase {{ $badge['bg'] }}">
                            <span class="w-2 h-2 rounded-full {{ $badge['dot'] }}"></span>
                            {{ $badge['label'] }}
                        </span>

                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-zinc-100 dark:bg-[#232327] text-zinc-800 dark:text-[#EDEDF0] text-xs font-mono font-bold uppercase tracking-wider border border-zinc-200 dark:border-[#2B2B30]">
                            Kelas: {{ $exam->schoolClass->class_name }}
                        </span>
                    </div>

                    <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-mono font-semibold border {{ $timeStatus['badge'] }}">
                        {{ $timeStatus['text'] }}
                    </span>
                </div>

                <!-- Subject & Title -->
                <div>
                    <div class="text-xs font-mono font-semibold text-zinc-500 uppercase tracking-wider mb-1">
                        Mata Pelajaran: <span class="text-zinc-800 dark:text-[#EDEDF0]">{{ $exam->subject }}</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0] leading-tight">
                        {{ $exam->title }}
                    </h2>
                </div>

                <!-- Schedule & Teacher Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 bg-zinc-50/80 dark:bg-[#232327] rounded-xl border border-zinc-100 dark:border-[#2B2B30] font-mono text-xs">
                    <div>
                        <div class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider mb-0.5">Tanggal Pelaksanaan</div>
                        <div class="font-bold text-zinc-900 dark:text-[#EDEDF0]">
                            {{ \Carbon\Carbon::parse($exam->exam_date)->translatedFormat('l, d F Y') }}
                        </div>
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider mb-0.5">Rentang Waktu</div>
                        <div class="font-bold text-zinc-900 dark:text-[#EDEDF0]">
                            {{ $exam->time_window ?? 'Fleksibel / Penugasan' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider mb-0.5">Pengajar Pengampu</div>
                        <div class="font-bold text-zinc-900 dark:text-[#EDEDF0] truncate">
                            {{ $exam->teacher->fullname ?? 'Guru Pengampu' }}
                        </div>
                    </div>
                </div>

                <!-- Instructions / Description -->
                @if($exam->description)
                    <div class="space-y-2">
                        <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500">
                            Instruksi &amp; Petunjuk Pembelajaran
                        </label>
                        <div class="p-5 rounded-xl bg-zinc-50 dark:bg-[#232327] border border-zinc-200/70 dark:border-[#2B2B30] text-sm text-zinc-700 dark:text-[#EDEDF0] whitespace-pre-line leading-relaxed">
                            {{ $exam->description }}
                        </div>
                    </div>
                @endif

                <!-- Attachments Section -->
                <div class="space-y-3 pt-2">
                    <label class="text-xs font-semibold uppercase tracking-wider text-zinc-500">
                        Akses Berkas &amp; Tautan Ujian
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- File Download -->
                        @if($exam->material_file_path)
                            <div class="flex items-center justify-between p-4 bg-zinc-50 dark:bg-[#232327] border border-zinc-200 dark:border-[#2B2B30] rounded-xl">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-12 h-12 rounded-xl bg-zinc-100 dark:bg-[#232327] text-zinc-800 dark:text-[#EDEDF0] border border-zinc-200 dark:border-[#2B2B30] font-mono font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ $exam->file_extension ?? 'FILE' }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] truncate" title="{{ $exam->file_name }}">
                                            {{ $exam->file_name ?? basename($exam->material_file_path) }}
                                        </div>
                                        <div class="text-[11px] text-zinc-500 font-mono mt-0.5">
                                            Ukuran: {{ $exam->formatted_file_size ?? 'Tersedia' }}
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ route('exams.download', $exam) }}" 
                                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#0093dd] hover:bg-[#0082c4] active:bg-[#006fa7] text-white text-xs font-semibold rounded-lg transition shadow-xs shrink-0">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                    <span>Unduh</span>
                                </a>
                            </div>
                        @else
                            <div class="p-4 bg-zinc-50 dark:bg-[#232327] border border-dashed border-zinc-200 dark:border-[#2B2B30] rounded-xl text-center text-xs text-zinc-400 dark:text-[#97979B] italic flex items-center justify-center">
                                Tidak ada berkas lampiran yang diunggah
                            </div>
                        @endif

                        <!-- External Link -->
                        @if($exam->external_link)
                            <div class="flex items-center justify-between p-4 bg-indigo-50/70 dark:bg-[#16222F] border border-indigo-200 dark:border-sky-700/60 rounded-xl">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-12 h-12 rounded-xl bg-indigo-600 text-white flex items-center justify-center shrink-0">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-indigo-950 dark:text-sky-200">Aplikasi / CBT Online</div>
                                        <div class="text-[11px] text-indigo-700 dark:text-sky-300 font-mono truncate mt-0.5" title="{{ $exam->external_link }}">
                                            {{ $exam->external_link }}
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ $exam->external_link }}" 
                                   target="_blank" 
                                   rel="noopener noreferrer" 
                                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg transition shadow-xs shrink-0">
                                    <span>Buka</span>
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                    </svg>
                                </a>
                            </div>
                        @else
                            <div class="p-4 bg-zinc-50 dark:bg-[#232327] border border-dashed border-zinc-200 dark:border-[#2B2B30] rounded-xl text-center text-xs text-zinc-400 dark:text-[#97979B] italic flex items-center justify-center">
                                Tidak ada tautan CBT online
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Footer Details -->
            <div class="px-6 sm:px-8 py-4 bg-zinc-50 dark:bg-[#1D1D21] border-t border-zinc-100 dark:border-[#2B2B30] flex items-center justify-between text-xs text-zinc-500 dark:text-[#97979B] font-mono">
                <div>
                    Dipublikasikan: {{ $exam->created_at->translatedFormat('d M Y, H:i') }} WIB
                </div>
                <div>
                    Status: <span class="font-bold text-zinc-800 dark:text-[#EDEDF0] uppercase">{{ $exam->status }}</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
