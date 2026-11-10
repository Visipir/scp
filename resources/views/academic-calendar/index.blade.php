<x-app-layout>
    <x-slot name="header">
        <x-page-header 
            title="Kalender Akademik" 
            subtitle="Agenda kegiatan sekolah dan penetapan hari tidak efektif KBM"
        >
            <x-ai-importer-modal type="Kalender Pendidikan" title="Import via AI (PDF)" />
        </x-page-header>
    </x-slot>

    <div 
        class="space-y-6"
        x-data="{
            detailModalOpen: false,
            selectedDate: '',
            selectedFormattedDate: '',
            selectedEvents: [],
            openDayDetail(day) {
                this.selectedDate = day.date;
                this.selectedFormattedDate = day.formattedDate || day.date;
                this.selectedEvents = day.events || [];
                this.detailModalOpen = true;
            },
            prefillForm(date) {
                this.detailModalOpen = false;
                const startInput = document.getElementById('start_date');
                const endInput = document.getElementById('end_date');
                if (startInput) startInput.value = date;
                if (endInput) endInput.value = date;
                const formElem = document.getElementById('manual-add-form');
                if (formElem) {
                    formElem.scrollIntoView({ behavior: 'smooth' });
                    const titleInput = document.getElementById('title');
                    if (titleInput) titleInput.focus();
                }
            }
        }"
    >

        <!-- Flash Messages -->
        @if (session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if (session('error'))
            <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
        @endif

        {{-- Status hari ini --}}
        @if($todayEvent)
            <div class="bg-amber-50/90 dark:bg-[#282115] border border-amber-200 dark:border-amber-700/60 rounded-lg p-4 sm:p-5 flex items-start gap-3.5 shadow-xs">
                <span class="inline-flex items-center px-2 py-0.5 rounded bg-amber-500 text-zinc-950 font-mono text-[10px] font-bold uppercase shrink-0 mt-0.5">
                    HARI INI
                </span>
                <div class="text-xs">
                    <h3 class="font-bold text-amber-950 dark:text-amber-200 text-sm">
                        Hari Ini Tidak Efektif KBM: {{ $todayEvent->title }}
                    </h3>
                    <p class="text-amber-800 dark:text-amber-300 mt-0.5 leading-relaxed">
                        Kategori: <strong class="font-semibold">{{ ucwords(str_replace('_', ' ', $todayEvent->category)) }}</strong> &bull; 
                        Tanggal: <span class="font-mono font-medium">{{ $todayEvent->start_date->translatedFormat('d M Y') }} s/d {{ $todayEvent->end_date->translatedFormat('d M Y') }}</span>.
                        Seluruh guru dibebaskan dari batas waktu &amp; peringatan keterlambatan pengisian jurnal.
                    </p>
                </div>
            </div>
        @else
            <div class="bg-emerald-50/70 dark:bg-[#16281D] border border-emerald-200/80 dark:border-emerald-700/60 rounded-lg p-3 sm:px-4 sm:py-3 flex items-center justify-between text-xs text-emerald-800 dark:text-emerald-300 font-medium">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                    <span>Status Hari Ini ({{ Carbon\Carbon::today()->translatedFormat('l, d F Y') }}): <strong>KBM Efektif Berjalan Normal</strong> (Peringatan pengisian jurnal aktif).</span>
                </div>
            </div>
        @endif

        {{-- Kalender visual --}}
        <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl shadow-xs overflow-hidden">
            
            {{-- Navigation & Month Selector Bar --}}
            <div class="p-4 sm:p-5 border-b border-zinc-200 dark:border-[#2B2B30] flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-[#1D1D21]">
                {{-- Month Title & Previous / Next Controls --}}
                <div class="flex items-center gap-3">
                    <div class="inline-flex items-center rounded-lg border border-zinc-200 dark:border-[#2B2B30] bg-zinc-50 dark:bg-[#19191C] p-0.5 shadow-2xs">
                        <a 
                            href="{{ route('academic-calendar.index', ['month' => $prevMonthDate->month, 'year' => $prevMonthDate->year]) }}" 
                            class="p-2 min-h-[36px] min-w-[36px] flex items-center justify-center rounded-md text-zinc-600 hover:text-zinc-900 dark:text-[#97979B] dark:hover:text-[#EDEDF0] hover:bg-zinc-200 dark:hover:bg-[#26262A] transition cursor-pointer"
                            title="Bulan Sebelumnya ({{ $monthNames[$prevMonthDate->month] }} {{ $prevMonthDate->year }})"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                            </svg>
                        </a>
                        <a 
                            href="{{ route('academic-calendar.index', ['month' => $nextMonthDate->month, 'year' => $nextMonthDate->year]) }}" 
                            class="p-2 min-h-[36px] min-w-[36px] flex items-center justify-center rounded-md text-zinc-600 hover:text-zinc-900 dark:text-[#97979B] dark:hover:text-[#EDEDF0] hover:bg-zinc-200 dark:hover:bg-[#26262A] transition cursor-pointer"
                            title="Bulan Berikutnya ({{ $monthNames[$nextMonthDate->month] }} {{ $nextMonthDate->year }})"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                            </svg>
                        </a>
                    </div>

                    <div>
                        <h2 class="text-lg sm:text-xl font-black text-zinc-900 dark:text-[#EDEDF0] tracking-tight font-mono flex items-center gap-2">
                            <span>{{ $monthNames[$month] }}</span>
                            <span class="text-zinc-400 font-semibold">{{ $year }}</span>
                        </h2>
                    </div>
                </div>

                {{-- Month / Year Jump Form & Today Button --}}
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <form method="GET" action="{{ route('academic-calendar.index') }}" class="flex items-center gap-1.5">
                        <select 
                            name="month" 
                            onchange="this.form.submit()" 
                            class="rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] py-1.5 pl-3 pr-8 text-xs font-semibold text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none cursor-pointer"
                        >
                            @foreach($monthNames as $mNum => $mName)
                                <option value="{{ $mNum }}" @selected($month == $mNum)>{{ $mName }}</option>
                            @endforeach
                        </select>

                        <select 
                            name="year" 
                            onchange="this.form.submit()" 
                            class="rounded-lg border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] py-1.5 pl-3 pr-8 text-xs font-semibold font-mono text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none cursor-pointer"
                        >
                            @foreach(range(2025, 2028) as $y)
                                <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                            @endforeach
                        </select>
                    </form>

                    @if($month != now()->month || $year != now()->year)
                        <a 
                            href="{{ route('academic-calendar.index') }}" 
                            class="px-2.5 py-1.5 rounded-lg border border-zinc-200 dark:border-[#2B2B30] bg-white dark:bg-[#232327] text-xs font-semibold text-zinc-700 dark:text-[#EDEDF0] hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-[#26262A] transition shadow-2xs"
                        >
                            Bulan Ini
                        </a>
                    @endif

                    <a 
                        href="#manual-add-form" 
                        class="px-3 py-1.5 rounded-lg bg-[#0093dd] hover:bg-[#0082c4] text-white text-xs font-semibold transition inline-flex items-center gap-1 shadow-2xs cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>Tambah Agenda</span>
                    </a>
                </div>
            </div>

            {{-- Mini Stats Bar: Statistik Bulan Terpilih --}}
            <div class="px-4 py-2.5 bg-zinc-50/70 dark:bg-[#19191C] border-b border-zinc-200 dark:border-[#2B2B30] grid grid-cols-2 sm:grid-cols-4 gap-2.5 text-xs">
                <div class="flex items-center gap-2 bg-white dark:bg-[#232327] p-2 rounded-lg border border-zinc-200/80 dark:border-[#2B2B30] shadow-2xs">
                    <span class="w-6 h-6 rounded-md bg-emerald-100 dark:bg-[#16281D] text-emerald-700 dark:text-emerald-300 flex items-center justify-center shrink-0 font-bold">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <div class="text-[9px] font-mono uppercase text-zinc-400 font-bold truncate">Hari Efektif KBM</div>
                        <div class="font-bold text-zinc-900 dark:text-[#EDEDF0] font-mono text-xs sm:text-sm leading-tight">{{ $statsEffectiveDays }} Hari</div>
                    </div>
                </div>

                <div class="flex items-center gap-2 bg-white dark:bg-[#232327] p-2 rounded-lg border border-zinc-200/80 dark:border-[#2B2B30] shadow-2xs">
                    <span class="w-6 h-6 rounded-md bg-rose-100 dark:bg-[#2D1618] text-rose-700 dark:text-rose-300 flex items-center justify-center shrink-0 font-bold">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <div class="text-[9px] font-mono uppercase text-zinc-400 font-bold truncate">Libur &amp; KBM Off</div>
                        <div class="font-bold text-rose-700 dark:text-rose-400 font-mono text-xs sm:text-sm leading-tight">{{ $statsOffDays }} Hari</div>
                    </div>
                </div>

                <div class="flex items-center gap-2 bg-white dark:bg-[#232327] p-2 rounded-lg border border-zinc-200/80 dark:border-[#2B2B30] shadow-2xs">
                    <span class="w-6 h-6 rounded-md bg-indigo-100 dark:bg-[#16222F] text-indigo-700 dark:text-sky-300 flex items-center justify-center shrink-0 font-bold">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <div class="text-[9px] font-mono uppercase text-zinc-400 font-bold truncate">Agenda Bulan Ini</div>
                        <div class="font-bold text-indigo-900 dark:text-sky-300 font-mono text-xs sm:text-sm leading-tight">{{ $monthEvents->count() }} Kegiatan</div>
                    </div>
                </div>

                <div class="flex items-center gap-2 bg-white dark:bg-[#232327] p-2 rounded-lg border border-zinc-200/80 dark:border-[#2B2B30] shadow-2xs">
                    <span class="w-6 h-6 rounded-md bg-zinc-100 dark:bg-[#19191C] text-zinc-700 dark:text-[#97979B] flex items-center justify-center shrink-0 font-bold">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <div class="text-[9px] font-mono uppercase text-zinc-400 font-bold truncate">Total Hari Kalender</div>
                        <div class="font-bold text-zinc-800 dark:text-[#EDEDF0] font-mono text-xs sm:text-sm leading-tight">{{ $statsTotalDays }} Hari</div>
                    </div>
                </div>
            </div>

            {{-- Color Legend Bar --}}
            <div class="px-4 py-2 bg-white dark:bg-[#1D1D21] border-b border-zinc-200 dark:border-[#2B2B30] flex flex-wrap items-center justify-between gap-y-1.5 text-[11px] text-zinc-600 dark:text-[#97979B]">
                <div class="flex flex-wrap items-center gap-x-3.5 gap-y-1">
                    <span class="font-bold text-zinc-800 dark:text-[#EDEDF0] uppercase tracking-wider font-mono text-[10px]">Keterangan:</span>
                    
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-xs bg-rose-500 shadow-2xs"></span>
                        <span class="font-medium text-zinc-700">Libur Nasional (Tgl Merah)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-xs bg-amber-400 shadow-2xs"></span>
                        <span class="font-medium text-zinc-700">Libur Bersama / Semester</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-xs bg-purple-500 shadow-2xs"></span>
                        <span class="font-medium text-zinc-700">Agenda Sekolah (KBM Off)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-xs bg-sky-500 shadow-2xs"></span>
                        <span class="font-medium text-zinc-700">Agenda Sekolah (KBM Efektif)</span>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-zinc-900"></span>
                        <span class="font-medium text-zinc-700">Hari Ini</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <span class="font-bold text-rose-600 font-mono text-xs">Min</span>
                        <span class="text-zinc-500">Tgl Merah</span>
                    </div>
                </div>
            </div>

            {{-- Day Names Header (Minggu s/d Sabtu) --}}
            <div class="grid grid-cols-7 border-b border-zinc-200 dark:border-[#2B2B30] bg-zinc-50/90 dark:bg-[#232327] text-center py-2 text-xs font-bold uppercase tracking-wider font-mono">
                <div class="text-rose-600"><span class="sm:hidden">Min</span><span class="hidden sm:inline">Minggu</span></div>
                <div class="text-zinc-700"><span class="sm:hidden">Sen</span><span class="hidden sm:inline">Senin</span></div>
                <div class="text-zinc-700"><span class="sm:hidden">Sel</span><span class="hidden sm:inline">Selasa</span></div>
                <div class="text-zinc-700"><span class="sm:hidden">Rab</span><span class="hidden sm:inline">Rabu</span></div>
                <div class="text-zinc-700"><span class="sm:hidden">Kam</span><span class="hidden sm:inline">Kamis</span></div>
                <div class="text-zinc-700"><span class="sm:hidden">Jum</span><span class="hidden sm:inline">Jumat</span></div>
                <div class="text-zinc-700"><span class="sm:hidden">Sab</span><span class="hidden sm:inline">Sabtu</span></div>
            </div>

            {{-- Unified Calendar Grid: Crisp 1px borders --}}
            <div class="grid grid-cols-7 bg-zinc-200 dark:bg-[#2B2B30] gap-px border-b border-zinc-200 dark:border-[#2B2B30]">
                @foreach($calendarGrid as $day)
                    @php
                        $hasEvents = !empty($day['events']);
                        $isCurrent = $day['isCurrentMonth'];
                        $isSunday = $day['isSunday'];
                        $isToday = $day['isToday'];
                        $cat = $day['primaryCategory'];

                        // Dual light/dark high-contrast holiday & status coloring
                        if (!$isCurrent) {
                            $cellBg = 'bg-zinc-50/70 hover:bg-zinc-100/70 dark:bg-[#19191C] dark:hover:bg-[#232327] opacity-60';
                            $dateTextColor = 'text-zinc-400 dark:text-zinc-500 font-normal';
                        } elseif ($cat === 'libur_nasional') {
                            $cellBg = 'bg-rose-50/80 hover:bg-rose-100/70 dark:bg-[#2D1618] dark:hover:bg-[#3D1E20] border-t-2 border-rose-500';
                            $dateTextColor = 'text-rose-700 dark:text-rose-400 font-bold';
                        } elseif ($cat === 'libur_bersama') {
                            $cellBg = 'bg-amber-50/80 hover:bg-amber-100/70 dark:bg-[#282115] dark:hover:bg-[#382F1E] border-t-2 border-amber-400';
                            $dateTextColor = 'text-amber-800 dark:text-amber-400 font-bold';
                        } elseif ($cat === 'agenda_sekolah') {
                            $cellBg = $day['isKbmOff'] 
                                ? 'bg-purple-50/80 hover:bg-purple-100/70 dark:bg-[#25182D] dark:hover:bg-[#352240] border-t-2 border-purple-500' 
                                : 'bg-sky-50/80 hover:bg-sky-100/70 dark:bg-[#16222F] dark:hover:bg-[#1E3042] border-t-2 border-sky-500';
                            $dateTextColor = $isSunday ? 'text-rose-700 dark:text-rose-400 font-bold' : ($day['isKbmOff'] ? 'text-purple-700 dark:text-purple-300 font-bold' : 'text-sky-700 dark:text-sky-300 font-bold');
                        } elseif ($isSunday) {
                            $cellBg = 'bg-rose-50/30 hover:bg-rose-100/50 dark:bg-[#231517] dark:hover:bg-[#2D1618]';
                            $dateTextColor = 'text-rose-600 dark:text-rose-400 font-bold';
                        } else {
                            $cellBg = 'bg-white hover:bg-zinc-50 dark:bg-[#1D1D21] dark:hover:bg-[#232327]';
                            $dateTextColor = 'text-zinc-900 dark:text-[#EDEDF0] font-semibold';
                        }
                    @endphp

                    <div 
                        @click="openDayDetail({{ json_encode($day) }})"
                        role="button"
                        tabindex="0"
                        title="{{ $day['formattedDate'] }} - Klik untuk detail"
                        class="min-h-[72px] sm:min-h-[92px] p-1.5 sm:p-2 flex flex-col justify-start transition-colors relative cursor-pointer group {{ $cellBg }}"
                    >
                        {{-- Day Header (Number + Badges) --}}
                        <div class="flex items-center justify-between">
                            @if($isToday)
                                <span class="w-6 h-6 rounded-full bg-[#0093dd] text-white font-bold text-xs flex items-center justify-center font-mono shadow-xs ring-2 ring-[#0093dd] ring-offset-1">
                                    {{ $day['dayNumber'] }}
                                </span>
                            @else
                                <span class="font-mono text-xs sm:text-sm {{ $dateTextColor }} group-hover:scale-105 transition-transform">
                                    {{ $day['dayNumber'] }}
                                </span>
                            @endif

                            {{-- Multiple events badge (only show if > 1) --}}
                            @if(count($day['events']) > 1)
                                <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[9px] font-mono font-bold bg-zinc-200 dark:bg-white/10 text-zinc-700 dark:text-zinc-300">
                                    {{ count($day['events']) }}
                                </span>
                            @endif
                        </div>

                        {{-- Event Pills (Desktop / Tablet) --}}
                        @if($hasEvents)
                            <div class="mt-1 space-y-1 w-full">
                                @foreach(array_slice($day['events'], 0, 2) as $ev)
                                    @php
                                        $pillStyle = match($ev['category']) {
                                            'libur_nasional' => 'bg-rose-600 text-white shadow-2xs',
                                            'libur_bersama'  => 'bg-amber-600 text-white shadow-2xs',
                                            'agenda_sekolah' => $ev['is_kbm_off'] ? 'bg-purple-600 text-white shadow-2xs' : 'bg-sky-600 text-white shadow-2xs',
                                            default          => 'bg-zinc-800 text-white',
                                        };
                                    @endphp
                                    <div 
                                        class="px-1.5 py-0.5 rounded text-[10px] font-semibold leading-tight truncate transition {{ $pillStyle }}"
                                        title="{{ $ev['title'] }} ({{ ucwords(str_replace('_', ' ', $ev['category'])) }}) - {{ $ev['is_kbm_off'] ? 'KBM Libur' : 'KBM Efektif' }}"
                                    >
                                        {{ $ev['title'] }}
                                    </div>
                                @endforeach

                                @if(count($day['events']) > 2)
                                    <div class="text-[9px] font-mono font-bold text-zinc-500 pl-0.5">
                                        +{{ count($day['events']) - 2 }} lainnya
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Section: Agenda & Hari Libur Bulan Ini --}}
            <div class="p-4 sm:p-5 bg-zinc-50/50 dark:bg-[#19191C] border-t border-zinc-200 dark:border-[#2B2B30]">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] uppercase font-mono tracking-wider flex items-center gap-2">
                        <span>Agenda &amp; Hari Libur Bulan {{ $monthNames[$month] }} {{ $year }}</span>
                        <span class="px-2 py-0.5 rounded-full bg-zinc-200 dark:bg-[#232327] text-zinc-700 dark:text-[#EDEDF0] text-[10px] font-mono">{{ $monthEvents->count() }}</span>
                    </h3>
                </div>

                @if($monthEvents->isNotEmpty())
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($monthEvents as $mEv)
                            <div class="bg-white dark:bg-[#232327] p-3.5 rounded-lg border border-zinc-200 dark:border-[#2B2B30] shadow-2xs flex flex-col justify-between hover:border-zinc-300 dark:hover:border-[#3A3A3F] transition">
                                <div>
                                    <div class="flex items-start justify-between gap-2">
                                        <h4 class="font-bold text-zinc-900 dark:text-[#EDEDF0] text-xs sm:text-sm line-clamp-1">{{ $mEv->title }}</h4>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-mono font-bold uppercase tracking-wider shrink-0
                                            {{ match($mEv->category) {
                                                'libur_nasional' => 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-[#2D1618] dark:text-rose-300 dark:border-rose-700/60',
                                                'libur_bersama'  => 'bg-amber-50 text-amber-800 border border-amber-200 dark:bg-[#282115] dark:text-amber-300 dark:border-amber-700/60',
                                                'agenda_sekolah' => 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-[#16222F] dark:text-sky-300 dark:border-sky-700/60',
                                                default          => 'bg-zinc-100 dark:bg-[#232327] text-zinc-700 dark:text-[#EDEDF0] border border-zinc-200 dark:border-[#2B2B30]',
                                            } }}">
                                            {{ ucwords(str_replace('_', ' ', $mEv->category)) }}
                                        </span>
                                    </div>

                                    <div class="mt-2 text-[11px] text-zinc-500 font-mono flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                        </svg>
                                        <span>
                                            @if($mEv->start_date->equalTo($mEv->end_date))
                                                {{ $mEv->start_date->translatedFormat('d F Y') }}
                                            @else
                                                {{ $mEv->start_date->translatedFormat('d M') }} &ndash; {{ $mEv->end_date->translatedFormat('d M Y') }}
                                            @endif
                                        </span>
                                    </div>

                                    @if($mEv->description)
                                        <p class="mt-1.5 text-[11px] text-zinc-500 line-clamp-2 leading-relaxed">{{ $mEv->description }}</p>
                                    @endif
                                </div>

                                <div class="mt-3 pt-2.5 border-t border-zinc-100 flex items-center justify-between text-[11px]">
                                    <span class="inline-flex items-center gap-1 font-semibold {{ $mEv->is_kbm_off ? 'text-rose-700' : 'text-emerald-700' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $mEv->is_kbm_off ? 'bg-rose-600' : 'bg-emerald-600' }}"></span>
                                        {{ $mEv->is_kbm_off ? 'KBM Diliburkan' : 'KBM Efektif' }}
                                    </span>

                                    <form action="{{ route('academic-calendar.destroy', $mEv->id) }}" method="POST" onsubmit="return confirm('Hapus agenda ini?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-zinc-400 hover:text-rose-600 transition cursor-pointer p-0.5" title="Hapus Agenda">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-6 text-center text-zinc-400 dark:text-[#97979B] text-xs bg-white dark:bg-[#232327] rounded-lg border border-dashed border-zinc-200 dark:border-[#2B2B30]">
                        Tidak ada agenda sekolah atau hari libur khusus pada bulan {{ $monthNames[$month] }} {{ $year }}.
                    </div>
                @endif
            </div>

        </div>

        {{-- Modal detail hari --}}
        <div 
            x-show="detailModalOpen" 
            x-cloak 
            style="display: none;" 
            class="fixed inset-0 z-50 overflow-y-auto" 
            role="dialog" 
            aria-modal="true"
            @keydown.escape.window="detailModalOpen = false"
        >
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div 
                    x-show="detailModalOpen" 
                    x-cloak 
                    style="display: none;"
                    x-transition:enter="ease-out duration-200" 
                    x-transition:enter-start="opacity-0" 
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-150" 
                    x-transition:leave-start="opacity-100" 
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-zinc-900/60 backdrop-blur-xs transition-opacity" 
                    @click="detailModalOpen = false"
                ></div>

                <div 
                    x-show="detailModalOpen" 
                    x-cloak 
                    style="display: none;"
                    x-transition:enter="ease-out duration-200" 
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-150" 
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:max-w-md w-full p-5 sm:p-6"
                >
                    <div class="flex items-start justify-between gap-3 border-b border-zinc-100 dark:border-[#2B2B30] pb-3 mb-4">
                        <div>
                            <span class="text-xs font-semibold text-zinc-500 uppercase tracking-wider">Detail Agenda / Tanggal</span>
                            <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-[#EDEDF0] tracking-tight mt-0.5" x-text="selectedFormattedDate"></h3>
                        </div>
                        <button 
                            type="button" 
                            @click="detailModalOpen = false" 
                            class="p-1.5 rounded-md text-zinc-400 hover:text-zinc-700 dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] transition cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    {{-- If Day Has Events --}}
                    <div x-show="selectedEvents && selectedEvents.length > 0" class="space-y-3">
                        <template x-for="event in selectedEvents" :key="event.id">
                            <div class="p-3.5 rounded-lg border border-zinc-200 dark:border-[#2B2B30] bg-zinc-50/60 dark:bg-[#232327] space-y-2">
                                <div class="flex items-start justify-between gap-2">
                                    <h4 class="font-bold text-zinc-900 dark:text-[#EDEDF0] text-xs sm:text-sm" x-text="event.title"></h4>
                                    <span 
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider shrink-0"
                                        :class="{
                                            'bg-rose-100 dark:bg-[#2D1618] text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-700/60': event.category === 'libur_nasional',
                                            'bg-amber-100 dark:bg-[#282115] text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-700/60': event.category === 'libur_bersama',
                                            'bg-purple-100 dark:bg-[#25182D] text-purple-800 dark:text-purple-300 border border-purple-200 dark:border-purple-700/60': event.category === 'agenda_sekolah'
                                        }"
                                        x-text="event.category.replace('_', ' ')"
                                    ></span>
                                </div>

                                <div class="text-[11px] text-zinc-500 font-mono flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                                    </svg>
                                    <span x-text="event.start_date + ' s/d ' + event.end_date"></span>
                                </div>

                                <div class="pt-1 flex items-center justify-between border-t border-zinc-200/60 dark:border-[#2B2B30] text-xs">
                                    <span class="text-zinc-500 text-[11px]">Kewajiban Jurnal KBM:</span>
                                    <span 
                                        class="font-semibold text-[11px] inline-flex items-center gap-1"
                                        :class="event.is_kbm_off ? 'text-rose-700' : 'text-emerald-700'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="event.is_kbm_off ? 'bg-rose-600' : 'bg-emerald-600'"></span>
                                        <span x-text="event.is_kbm_off ? 'Diliburkan (Off)' : 'Efektif'"></span>
                                    </span>
                                </div>

                                <div x-show="event.description" class="text-[11px] text-zinc-600 dark:text-[#97979B] bg-white dark:bg-[#1D1D21] p-2 rounded border border-zinc-100 dark:border-[#2B2B30] leading-relaxed" x-text="event.description"></div>
                            </div>
                        </template>
                    </div>

                    {{-- If Day Has No Events --}}
                    <div x-show="!selectedEvents || selectedEvents.length === 0" class="py-6 text-center text-xs text-zinc-500 space-y-2">
                        <div class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-[#16281D] text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-2 border border-emerald-200 dark:border-emerald-700/60">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </div>
                        <p class="font-semibold text-zinc-800 dark:text-[#EDEDF0]">Hari Efektif KBM Berjalan Normal</p>
                        <p class="text-zinc-400 text-[11px]">Tidak ada agenda sekolah atau hari libur yang tercatat pada tanggal ini.</p>
                    </div>

                    <div class="mt-5 pt-3 border-t border-zinc-100 dark:border-[#2B2B30] flex items-center justify-between gap-2">
                        <button 
                            type="button" 
                            @click="prefillForm(selectedDate)"
                            class="px-3 py-1.5 bg-zinc-100 dark:bg-[#232327] hover:bg-zinc-200 dark:hover:bg-[#26262A] text-zinc-800 dark:text-[#EDEDF0] text-xs font-semibold rounded-md transition cursor-pointer inline-flex items-center gap-1"
                        >
                            <svg class="w-3.5 h-3.5 text-zinc-600 dark:text-[#97979B]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <span>Tambah Agenda Tanggal Ini</span>
                        </button>

                        <button 
                            type="button" 
                            @click="detailModalOpen = false" 
                            class="px-4 py-1.5 bg-zinc-100 hover:bg-zinc-200 text-zinc-800 dark:bg-[#2B2B30] dark:hover:bg-[#3A3A3F] dark:text-[#EDEDF0] text-xs font-semibold rounded-md transition cursor-pointer"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form agenda dan import --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Manual Add Form -->
            <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-5 shadow-xs flex flex-col justify-between" id="manual-add-form">
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-[#EDEDF0] border-b border-zinc-100 dark:border-[#2B2B30] pb-3 mb-4 flex items-center justify-between">
                        <span>Tambah Agenda / Hari Libur Manual</span>
                        <span class="text-[10px] font-mono text-zinc-400 font-normal uppercase">Form Entry</span>
                    </h3>
                    <form action="{{ route('academic-calendar.store') }}" method="POST" class="space-y-3.5 text-xs">
                        @csrf
                        <div>
                            <x-input-label for="title" value="Nama Agenda / Keterangan Libur *" />
                            <input type="text" id="title" name="title" required placeholder="Contoh: Libur Hari Raya, Ujian Sekolah, dsb." class="mt-1 block w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] text-xs text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd]">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-input-label for="start_date" value="Tanggal Mulai *" />
                                <input type="date" id="start_date" name="start_date" required class="mt-1 block w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] text-xs text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd]">
                            </div>
                            <div>
                                <x-input-label for="end_date" value="Tanggal Selesai *" />
                                <input type="date" id="end_date" name="end_date" required class="mt-1 block w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] text-xs text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd]">
                            </div>
                        </div>
                        <div>
                            <x-input-label for="category" value="Kategori *" />
                            <select id="category" name="category" required class="mt-1 block w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] text-xs text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd]">
                                <option value="libur_nasional">Libur Nasional</option>
                                <option value="libur_bersama">Libur Bersama</option>
                                <option value="agenda_sekolah">Agenda Sekolah</option>
                            </select>
                        </div>
                        <div>
                            <label class="flex items-center gap-2 cursor-pointer mt-1">
                                <input type="checkbox" name="is_kbm_off" value="1" checked class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                                <span class="text-xs text-zinc-700 font-medium">KBM Diliburkan (Nonaktifkan Alert Jurnal Terlambat)</span>
                            </label>
                        </div>
                        <div>
                            <x-input-label for="description" value="Catatan Tambahan (Opsional)" />
                            <textarea id="description" name="description" rows="2" placeholder="Catatan atau keterangan opsional..." class="mt-1 block w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] text-xs text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd]"></textarea>
                        </div>
                        <div class="pt-2">
                            <button type="submit" class="w-full px-4 py-2 bg-[#0093dd] hover:bg-[#0082c4] text-white text-xs font-semibold rounded-md transition shadow-none cursor-pointer">
                                Simpan Agenda
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Import CSV & AI Card -->
            <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200/80 dark:border-[#2B2B30] rounded-xl p-5 shadow-xs flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between border-b border-zinc-100 pb-3 mb-4">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-[#EDEDF0]">Import File Kaldik (CSV / AI)</h3>
                        <span class="text-[10px] font-mono text-zinc-400 uppercase">Mass Upload</span>
                    </div>

                    <p class="text-xs text-zinc-500 leading-relaxed mb-4">
                        Anda dapat mengunggah file CSV atau memanfaatkan dokumen PDF Kalender Pendidikan resmi sekolah dengan AI Gemini untuk ekstraksi instan.
                    </p>

                    <form action="{{ route('academic-calendar.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="p-4 bg-zinc-50 dark:bg-[#19191C] rounded-lg border border-dashed border-zinc-300 dark:border-[#2B2B30] text-xs">
                            <label class="block font-semibold text-zinc-800 dark:text-[#EDEDF0] mb-1">Unggah Format File CSV</label>
                            <span class="block text-[11px] text-zinc-500 dark:text-[#97979B] mb-2">Format: Title, StartDate (YYYY-MM-DD), EndDate, Category, IsKBMOff (1/0)</span>
                            <input type="file" name="file" accept=".csv,text/csv" required class="block w-full text-xs text-zinc-600 dark:text-[#97979B] file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-zinc-200 dark:file:bg-[#2B2B30] file:text-zinc-800 dark:file:text-[#EDEDF0] hover:file:bg-zinc-300 dark:hover:file:bg-[#3A3A3F]">
                        </div>
                        <button type="submit" class="w-full px-4 py-2 bg-[#0093dd] hover:bg-[#0082c4] text-white text-xs font-semibold rounded-md transition shadow-none cursor-pointer">
                            Unggah &amp; Ekstrak CSV
                        </button>
                    </form>
                </div>

                <div class="mt-6 pt-4 border-t border-zinc-100 flex items-center justify-between text-xs">
                    <span class="text-zinc-500">Punya dokumen Kaldik PDF?</span>
                    <x-ai-importer-modal type="Kalender Pendidikan" title="Gunakan AI Gemini PDF" />
                </div>
            </div>

        </div>

        {{-- Daftar agenda --}}
        <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200/80 dark:border-[#2B2B30] rounded-xl overflow-hidden shadow-xs">
            <div class="p-5 border-b border-zinc-200/80 dark:border-[#2B2B30] flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-zinc-50/50 dark:bg-[#19191C]">
                <div>
                    <h2 class="text-sm font-bold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">
                        Daftar Lengkap Agenda &amp; Hari Tidak Efektif
                    </h2>
                    <p class="text-xs text-zinc-500 mt-0.5 font-mono">
                        Total {{ $calendars->count() }} agenda / hari libur terdaftar di sistem.
                    </p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-zinc-200/80 dark:border-[#2B2B30] bg-zinc-50 dark:bg-[#232327] text-[11px] font-bold font-mono text-zinc-500 dark:text-[#97979B] uppercase tracking-wider">
                            <th class="py-3 px-4">Rentang Tanggal</th>
                            <th class="py-3 px-4">Nama Agenda / Kegiatan</th>
                            <th class="py-3 px-4">Kategori</th>
                            <th class="py-3 px-4 text-center">Status KBM</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200/60 dark:divide-[#2B2B30] text-xs text-zinc-700 dark:text-[#EDEDF0]">
                        @forelse($calendars as $cal)
                            <tr class="hover:bg-[#232327] transition-colors {{ $cal->is_kbm_off ? 'bg-zinc-50/30 dark:bg-[#232327]/30' : '' }}">
                                <td class="py-3 px-4 whitespace-nowrap font-mono">
                                    @if($cal->start_date->equalTo($cal->end_date))
                                        {{ $cal->start_date->translatedFormat('d M Y') }}
                                    @else
                                        {{ $cal->start_date->translatedFormat('d M') }} &ndash; {{ $cal->end_date->translatedFormat('d M Y') }}
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-semibold text-zinc-900 dark:text-[#EDEDF0]">
                                    {{ $cal->title }}
                                    @if($cal->description)
                                        <span class="block text-[11px] font-normal text-zinc-400 mt-0.5">{{ $cal->description }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono font-bold uppercase tracking-wider
                                        {{ match($cal->category) {
                                            'libur_nasional' => 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-[#2D1618] dark:text-rose-300 dark:border-rose-700/60',
                                            'libur_bersama'  => 'bg-amber-50 text-amber-800 border border-amber-200 dark:bg-[#282115] dark:text-amber-300 dark:border-amber-700/60',
                                            'agenda_sekolah' => 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-[#16222F] dark:text-sky-300 dark:border-sky-700/60',
                                            default          => 'bg-zinc-100 dark:bg-[#232327] text-zinc-700 dark:text-[#EDEDF0] border border-zinc-200 dark:border-[#2B2B30]',
                                        } }}">
                                        {{ ucwords(str_replace('_', ' ', $cal->category)) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap text-center">
                                    @if($cal->is_kbm_off)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span> Libur (Off)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Efektif KBM
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap text-right font-mono">
                                    <form action="{{ route('academic-calendar.destroy', $cal->id) }}" method="POST" onsubmit="return confirm('Hapus agenda/libur ini?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:text-rose-800 font-semibold hover:underline cursor-pointer">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-zinc-400 text-xs">
                                    Belum ada agenda atau kalender akademik yang didaftarkan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
