<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0]">
                        @if(auth()->user()->role_id === 2)
                            Jadwal Mengajar
                        @else
                            Jadwal Pelajaran
                        @endif
                    </h1>
                    @if($config?->is_schedule_locked)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                            Terkunci
                        </span>
                    @endif
                </div>
                <p class="text-xs text-zinc-500 dark:text-[#97979B] mt-1">
                    @if(auth()->user()->role_id === 2)
                        Alokasi waktu tatap muka mengajar Anda pada siklus {{ $cycleFilter }}.
                    @else
                        Manajemen alokasi mata pelajaran, kelas, ruang, dan guru pengampu KBM.
                    @endif
                </p>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap items-center gap-2.5">
                @if(auth()->user()->role_id === 1)
                    <a 
                        href="{{ route('schedule-swaps.index') }}" 
                        class="h-9 inline-flex items-center gap-2 px-3.5 bg-white dark:bg-[#19191C] hover:bg-zinc-50 dark:hover:bg-[#26262A] text-zinc-700 dark:text-[#EDEDF0] text-xs font-semibold rounded-lg transition border border-zinc-200 dark:border-[#2B2B30] shadow-2xs"
                    >
                        <svg class="w-4 h-4 text-zinc-500 dark:text-[#97979B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                        </svg>
                        <span>Monitoring Tukar</span>
                    </a>

                    <x-ai-importer-modal type="Jadwal Pelajaran" title="Impor Jadwal AI" />

                    @if(!$config?->is_schedule_locked)
                        <a 
                            href="{{ route('schedules.create') }}" 
                            class="h-9 inline-flex items-center gap-1.5 px-3.5 bg-[#0093dd] hover:bg-[#0082c4] active:bg-[#006fa7] text-white text-xs font-semibold rounded-lg transition shadow-2xs"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            <span>Tambah Jadwal</span>
                        </a>
                    @endif
                @elseif(auth()->user()->role_id === 2)
                    <a 
                        href="{{ route('schedule-swaps.index') }}" 
                        class="h-9 inline-flex items-center gap-2 px-3.5 bg-white dark:bg-[#19191C] hover:bg-zinc-50 dark:hover:bg-[#26262A] text-zinc-700 dark:text-[#EDEDF0] text-xs font-semibold rounded-lg transition border border-zinc-200 dark:border-[#2B2B30] shadow-2xs"
                    >
                        <svg class="w-4 h-4 text-zinc-500 dark:text-[#97979B]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/>
                        </svg>
                        <span>Tukar Jadwal</span>
                    </a>

                    @if(!$config?->is_schedule_locked)
                        <a 
                            href="{{ route('schedules.create') }}" 
                            class="h-9 inline-flex items-center gap-1.5 px-3.5 bg-[#0093dd] hover:bg-[#0082c4] active:bg-[#006fa7] text-white text-xs font-semibold rounded-lg transition shadow-2xs"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            <span>Tambah Jadwal</span>
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </x-slot>

    @php
        $totalJp = $schedules->sum('total_jp');
        $totalSessions = $schedules->count();
        $totalClasses = $schedules->pluck('class_id')->unique()->count();
        $activeDays = $schedules->pluck('day_of_week')->unique()->count();
        $orderedDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $groupedSchedules = $schedules->groupBy('day_of_week');
    @endphp

    <div 
        class="space-y-6"
        x-data="{
            viewMode: 'table',
            activeDay: '{{ $defaultDay ?? 'all' }}',
            searchQuery: '',
            showMatrix: false,
            matches(day, subject, className, teacherName, roomName) {
                if (this.activeDay !== 'all' && this.activeDay !== day) return false;
                if (!this.searchQuery.trim()) return true;
                const q = this.searchQuery.toLowerCase().trim();
                return (subject || '').toLowerCase().includes(q) ||
                       (className || '').toLowerCase().includes(q) ||
                       (teacherName || '').toLowerCase().includes(q) ||
                       (roomName || '').toLowerCase().includes(q);
            }
        }"
    >
        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if(session('error'))
            <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
        @endif

        {{-- 1. EXECUTIVE METRICS RIBBON (Dense, Scannable, Professional) --}}
        <div class="bg-white dark:bg-[#19191C] border border-zinc-200/80 dark:border-[#2B2B30] rounded-xl p-4 sm:p-5 shadow-2xs">
            <div class="grid grid-cols-2 md:grid-cols-4 divide-y md:divide-y-0 md:divide-x divide-zinc-100 dark:divide-[#2B2B30] gap-4 md:gap-0">
                <div class="md:px-4 first:pl-0">
                    <div class="text-[11px] font-medium text-zinc-500 dark:text-[#97979B] uppercase tracking-wider">Total Beban Mengajar</div>
                    <div class="mt-1 flex items-baseline gap-1.5">
                        <span class="text-2xl font-bold font-mono tracking-tight text-zinc-900 dark:text-[#EDEDF0] tabular-nums">{{ $totalJp }}</span>
                        <span class="text-xs text-zinc-500 dark:text-[#97979B]">JP / pekan</span>
                    </div>
                </div>

                <div class="md:px-4 pt-3 md:pt-0">
                    <div class="text-[11px] font-medium text-zinc-500 dark:text-[#97979B] uppercase tracking-wider">Total Sesi KBM</div>
                    <div class="mt-1 flex items-baseline gap-1.5">
                        <span class="text-2xl font-bold font-mono tracking-tight text-zinc-900 dark:text-[#EDEDF0] tabular-nums">{{ $totalSessions }}</span>
                        <span class="text-xs text-zinc-500 dark:text-[#97979B]">sesi jadwal</span>
                    </div>
                </div>

                <div class="md:px-4 pt-3 md:pt-0">
                    <div class="text-[11px] font-medium text-zinc-500 dark:text-[#97979B] uppercase tracking-wider">Rombongan Belajar</div>
                    <div class="mt-1 flex items-baseline gap-1.5">
                        <span class="text-2xl font-bold font-mono tracking-tight text-zinc-900 dark:text-[#EDEDF0] tabular-nums">{{ $totalClasses }}</span>
                        <span class="text-xs text-zinc-500 dark:text-[#97979B]">kelas tercover</span>
                    </div>
                </div>

                <div class="md:px-4 last:pr-0 pt-3 md:pt-0">
                    <div class="text-[11px] font-medium text-zinc-500 dark:text-[#97979B] uppercase tracking-wider">Hari Efektif KBM</div>
                    <div class="mt-1 flex items-baseline gap-1.5">
                        <span class="text-2xl font-bold font-mono tracking-tight text-zinc-900 dark:text-[#EDEDF0] tabular-nums">{{ $activeDays }}</span>
                        <span class="text-xs text-zinc-500 dark:text-[#97979B]">hari / pekan</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. CONTROL HUB (Cycle, Filters, Search & View Switcher) --}}
        <div class="bg-white dark:bg-[#19191C] border border-zinc-200/80 dark:border-[#2B2B30] rounded-xl p-4 space-y-4 shadow-2xs">
            {{-- Top Controls Row --}}
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                {{-- Left: Cycle Switcher + Admin Dropdowns --}}
                <div class="flex flex-wrap items-center gap-2.5">
                    {{-- Segmented Putaran Switcher --}}
                    <div class="inline-flex rounded-lg border border-zinc-200 dark:border-[#2B2B30] p-0.5 bg-zinc-100 dark:bg-[#121214] text-xs font-semibold">
                        @foreach(['Putaran 1', 'Putaran 2'] as $cycle)
                            @php
                                $isActiveCycle = ($config?->active_cycle === $cycle);
                                $isSelected = ($cycleFilter === $cycle);
                            @endphp
                            <a 
                                href="{{ route('schedules.index', array_merge(request()->query(), ['cycle' => $cycle])) }}" 
                                class="h-8 px-3 rounded-md transition cursor-pointer flex items-center gap-1.5 {{ $isSelected ? 'bg-[#0093dd] text-white shadow-xs font-semibold' : 'text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-200 dark:hover:bg-[#26262A]' }}"
                            >
                                @if($isActiveCycle)
                                    <span class="w-1.5 h-1.5 rounded-full {{ $isSelected ? 'bg-white' : 'bg-emerald-500' }}"></span>
                                @endif
                                <span>{{ $cycle }}</span>
                            </a>
                        @endforeach
                    </div>

                    {{-- Admin Dropdowns (Role 1) --}}
                    @if(auth()->user()->role_id === 1)
                        <form method="GET" action="{{ route('schedules.index') }}" class="flex flex-wrap items-center gap-2">
                            <input type="hidden" name="cycle" value="{{ $cycleFilter }}">

                            <select 
                                name="teacher_id" 
                                onchange="this.form.submit()" 
                                class="h-9 rounded-lg border border-zinc-200 dark:border-[#2B2B30] bg-zinc-50 dark:bg-[#232327] py-1 px-3 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd]"
                            >
                                <option value="">Semua Guru ({{ $teachers->count() }})</option>
                                @foreach($teachers as $t)
                                    <option value="{{ $t->id }}" @selected(request('teacher_id') == $t->id)>
                                        {{ $t->fullname }}
                                    </option>
                                @endforeach
                            </select>

                            <select 
                                name="class_id" 
                                onchange="this.form.submit()" 
                                class="h-9 rounded-lg border border-zinc-200 dark:border-[#2B2B30] bg-zinc-50 dark:bg-[#232327] py-1 px-3 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd]"
                            >
                                <option value="">Semua Kelas ({{ $classes->count() }})</option>
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}" @selected(request('class_id') == $c->id)>
                                        {{ $c->class_name }}
                                    </option>
                                @endforeach
                            </select>

                            @if(request('teacher_id') || request('class_id'))
                                <a 
                                    href="{{ route('schedules.index', ['cycle' => $cycleFilter]) }}" 
                                    class="h-9 inline-flex items-center px-2.5 text-xs text-rose-600 dark:text-rose-400 hover:underline font-semibold"
                                >
                                    Reset
                                </a>
                            @endif
                        </form>
                    @endif
                </div>

                {{-- Right: Live Search, View Toggle & Aturan Jam --}}
                <div class="flex flex-wrap items-center gap-2.5">
                    {{-- Instant Client-Side Search --}}
                    <div class="relative w-full sm:w-56">
                        <input 
                            type="text" 
                            x-model="searchQuery" 
                            placeholder="Cari mapel, kelas, guru..." 
                            class="w-full h-8.5 rounded-lg border border-zinc-200 dark:border-[#2B2B30] bg-zinc-50 dark:bg-[#232327] py-1 pl-8 pr-7 text-xs text-zinc-900 dark:text-[#EDEDF0] placeholder-zinc-400 focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] transition"
                        />
                        <svg class="w-3.5 h-3.5 text-zinc-400 absolute left-2.5 top-2.5 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                        <button 
                            type="button" 
                            x-show="searchQuery" 
                            @click="searchQuery = ''" 
                            class="absolute right-2 top-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- View Mode Switcher (Table, Cards, Grid) --}}
                    <div class="inline-flex rounded-lg border border-zinc-200 dark:border-[#2B2B30] p-0.5 bg-zinc-100 dark:bg-[#121214] text-xs font-semibold">
                        <button 
                            type="button" 
                            @click="viewMode = 'table'"
                            :class="viewMode === 'table' ? 'bg-[#0093dd] text-white shadow-xs font-semibold' : 'text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-200 dark:hover:bg-[#26262A]'"
                            class="h-7.5 px-2.5 rounded-md flex items-center gap-1.5 transition cursor-pointer"
                            title="Tampilan Tabel Rinci"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                            <span class="hidden sm:inline">Tabel</span>
                        </button>
                        <button 
                            type="button" 
                            @click="viewMode = 'cards'"
                            :class="viewMode === 'cards' ? 'bg-[#0093dd] text-white shadow-xs font-semibold' : 'text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-200 dark:hover:bg-[#26262A]'"
                            class="h-7.5 px-2.5 rounded-md flex items-center gap-1.5 transition cursor-pointer"
                            title="Tampilan Kartu Timeline"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M12 17.25h8.25" />
                            </svg>
                            <span class="hidden sm:inline">Kartu</span>
                        </button>
                        <button 
                            type="button" 
                            @click="viewMode = 'grid'"
                            :class="viewMode === 'grid' ? 'bg-[#0093dd] text-white shadow-xs font-semibold' : 'text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-200 dark:hover:bg-[#26262A]'"
                            class="h-7.5 px-2.5 rounded-md flex items-center gap-1.5 transition cursor-pointer"
                            title="Tampilan Matriks Mingguan"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15m-10.5-15h15a2.25 2.25 0 012.25 2.25v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75A2.25 2.25 0 014.5 4.5z" />
                            </svg>
                            <span class="hidden sm:inline">Matriks</span>
                        </button>
                    </div>

                    {{-- Aturan Jam Reference Toggle --}}
                    <button 
                        type="button"
                        @click="showMatrix = !showMatrix"
                        class="h-8.5 inline-flex items-center gap-1.5 px-3 text-xs font-medium border rounded-lg transition cursor-pointer"
                        :class="showMatrix ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900 border-transparent shadow-xs' : 'bg-zinc-50 dark:bg-[#232327] border-zinc-200 dark:border-[#2B2B30] text-zinc-700 dark:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A]'"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span x-text="showMatrix ? 'Tutup Aturan Jam' : 'Aturan Jam Belajar'"></span>
                    </button>
                </div>
            </div>

            {{-- Day Pills Navigation Row --}}
            <div class="pt-3 border-t border-zinc-100 dark:border-[#2B2B30] flex items-center gap-2 overflow-x-auto pb-1 text-xs scrollbar-none">
                <button 
                    type="button" 
                    @click="activeDay = 'all'"
                    :class="activeDay === 'all' ? 'bg-[#0093dd] text-white font-semibold shadow-xs' : 'bg-zinc-50 dark:bg-[#232327] hover:bg-zinc-100 dark:hover:bg-[#26262A] text-zinc-700 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] font-medium border border-zinc-200 dark:border-[#2B2B30]'"
                    class="h-8 px-3 rounded-lg transition cursor-pointer shrink-0 flex items-center gap-1.5"
                >
                    <span>Semua Hari</span>
                    <span 
                        :class="activeDay === 'all' ? 'bg-white/20 text-white' : 'bg-zinc-200/80 text-zinc-700 dark:bg-[#19191C] dark:text-[#97979B]'"
                        class="text-[10px] font-mono px-1.5 py-0.5 rounded leading-none"
                    >
                        {{ $schedules->count() }}
                    </span>
                </button>
                @foreach($orderedDays as $day)
                    @php
                        $countDay = $groupedSchedules->get($day)?->count() ?? 0;
                        $jpDay = $groupedSchedules->get($day)?->sum('total_jp') ?? 0;
                        $isToday = ($todayName === $day);
                    @endphp
                    <button 
                        type="button" 
                        @click="activeDay = '{{ $day }}'"
                        :class="activeDay === '{{ $day }}' ? 'bg-[#0093dd] text-white font-semibold shadow-xs' : 'bg-zinc-50 dark:bg-[#232327] hover:bg-zinc-100 dark:hover:bg-[#26262A] text-zinc-700 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] font-medium border border-zinc-200 dark:border-[#2B2B30]'"
                        class="h-8 px-3 rounded-lg transition cursor-pointer shrink-0 flex items-center gap-1.5"
                    >
                        <span>{{ $day }}</span>
                        <span 
                            :class="activeDay === '{{ $day }}' ? 'bg-white/20 text-white' : 'bg-zinc-200/80 text-zinc-700 dark:bg-[#19191C] dark:text-[#97979B]'"
                            class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded leading-none"
                        >
                            {{ $jpDay }} JP
                        </span>
                        @if($isToday)
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400" title="Hari Ini"></span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        {{-- 3. COLLAPSIBLE ATURAN JAM BELAJAR REFERENCE --}}
        <div 
            x-show="showMatrix" 
            x-cloak 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
        >
            <div class="bg-white dark:bg-[#19191C] border border-zinc-200/80 dark:border-[#2B2B30] rounded-xl p-5 font-mono text-xs shadow-2xs space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-100 dark:border-[#2B2B30] pb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-[#0093dd]"></span>
                        <span class="font-bold text-zinc-900 dark:text-[#EDEDF0] text-xs font-sans">
                            Aturan Alokasi Jam Belajar &mdash; {{ $cycleFilter }}
                        </span>
                    </div>
                    <span class="text-[11px] text-zinc-400 font-sans">Pedoman Durasi Resmi KBM</span>
                </div>

                @if($cycleFilter === 'Putaran 2')
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 text-[11px]">
                        <div class="p-3 bg-zinc-50 dark:bg-[#232327] rounded-lg border border-zinc-200/80 dark:border-[#2B2B30] space-y-1">
                            <div class="font-bold text-zinc-900 dark:text-[#EDEDF0] border-b border-zinc-200 dark:border-[#2B2B30] pb-1.5 flex justify-between">
                                <span>SENIN</span> <span class="text-zinc-500">10 JP</span>
                            </div>
                            <div class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold pt-1">07.00–08.00 Upacara Bendera</div>
                            <div class="text-zinc-700 dark:text-zinc-300">Jam 1–3 : 08.00–09.45 (35m)</div>
                            <div class="text-zinc-400 text-[10px]">Istirahat I : 09.45–10.00</div>
                            <div class="text-zinc-700 dark:text-zinc-300">Jam 4–6 : 10.00–11.45 (35m)</div>
                            <div class="text-zinc-400 text-[10px]">Ishoma : 11.45–12.45</div>
                            <div class="text-zinc-700 dark:text-zinc-300">Jam 7–10 : 12.45–15.10</div>
                        </div>

                        <div class="p-3 bg-zinc-50 dark:bg-[#232327] rounded-lg border border-zinc-200/80 dark:border-[#2B2B30] space-y-1">
                            <div class="font-bold text-zinc-900 dark:text-[#EDEDF0] border-b border-zinc-200 dark:border-[#2B2B30] pb-1.5 flex justify-between">
                                <span>SELASA</span> <span class="text-zinc-500">10 JP</span>
                            </div>
                            <div class="text-[10px] text-blue-600 dark:text-blue-400 font-semibold pt-1">07.00–07.30 Literasi / Wali Kelas</div>
                            <div class="text-zinc-700 dark:text-zinc-300">Jam 1–3 : 07.30–09.30 (40m)</div>
                            <div class="text-zinc-400 text-[10px]">Istirahat I : 09.30–09.45</div>
                            <div class="text-zinc-700 dark:text-zinc-300">Jam 4–6 : 09.45–11.45 (40m)</div>
                            <div class="text-zinc-400 text-[10px]">Ishoma : 11.45–12.45</div>
                            <div class="text-zinc-700 dark:text-zinc-300">Jam 7–10 : 12.45–15.10</div>
                        </div>

                        <div class="p-3 bg-zinc-50 dark:bg-[#232327] rounded-lg border border-zinc-200/80 dark:border-[#2B2B30] space-y-1">
                            <div class="font-bold text-zinc-900 dark:text-[#EDEDF0] border-b border-zinc-200 dark:border-[#2B2B30] pb-1.5 flex justify-between">
                                <span>RABU &amp; KAMIS</span> <span class="text-zinc-500">10 JP</span>
                            </div>
                            <div class="text-zinc-700 dark:text-zinc-300 pt-1">Jam 1–3 : 07.15–09.15 (40m)</div>
                            <div class="text-zinc-400 text-[10px]">Istirahat I : 09.15–09.30</div>
                            <div class="text-zinc-700 dark:text-zinc-300">Jam 4–6 : 09.30–11.30 (40m)</div>
                            <div class="text-zinc-400 text-[10px]">Ishoma : 11.30–12.30</div>
                            <div class="text-zinc-700 dark:text-zinc-300">Jam 7–10 : 12.30–15.10 (40m)</div>
                        </div>

                        <div class="p-3 bg-zinc-50 dark:bg-[#232327] rounded-lg border border-zinc-200/80 dark:border-[#2B2B30] space-y-1">
                            <div class="font-bold text-zinc-900 dark:text-[#EDEDF0] border-b border-zinc-200 dark:border-[#2B2B30] pb-1.5 flex justify-between">
                                <span>JUMAT</span> <span class="text-zinc-500">8 JP</span>
                            </div>
                            <div class="text-zinc-700 dark:text-zinc-300 pt-1">Jam 1–3 : 07.15–09.15 (40m)</div>
                            <div class="text-zinc-400 text-[10px]">Istirahat I : 09.15–09.30</div>
                            <div class="text-zinc-700 dark:text-zinc-300">Jam 4–6 : 09.30–11.25</div>
                            <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">Sholat Jumat : 11.25–12.40</div>
                            <div class="text-zinc-700 dark:text-zinc-300">Jam 7–8 : 12.40–13.50</div>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 text-[11px]">
                        <div class="p-2.5 bg-zinc-50 dark:bg-[#232327] rounded-lg border border-zinc-200 dark:border-[#2B2B30] text-zinc-700 dark:text-zinc-300">
                            <div class="font-bold text-zinc-900 dark:text-white">Blok Pagi 1</div>
                            <div class="text-zinc-500 mt-0.5">Jam 1–3 (08.00–09.45)</div>
                        </div>
                        <div class="p-2.5 bg-amber-500/10 rounded-lg border border-amber-500/20 text-amber-800 dark:text-amber-300">
                            <div class="font-bold">Istirahat I</div>
                            <div class="mt-0.5">09.45–10.00 (15m)</div>
                        </div>
                        <div class="p-2.5 bg-zinc-50 dark:bg-[#232327] rounded-lg border border-zinc-200 dark:border-[#2B2B30] text-zinc-700 dark:text-zinc-300">
                            <div class="font-bold text-zinc-900 dark:text-white">Blok Pagi 2</div>
                            <div class="text-zinc-500 mt-0.5">Jam 4–6 (10.00–11.45)</div>
                        </div>
                        <div class="p-2.5 bg-amber-500/10 rounded-lg border border-amber-500/20 text-amber-800 dark:text-amber-300">
                            <div class="font-bold">Ishoma</div>
                            <div class="mt-0.5">11.45–12.45 (60m)</div>
                        </div>
                        <div class="p-2.5 bg-zinc-50 dark:bg-[#232327] rounded-lg border border-zinc-200 dark:border-[#2B2B30] text-zinc-700 dark:text-zinc-300">
                            <div class="font-bold text-zinc-900 dark:text-white">Blok Siang 1</div>
                            <div class="text-zinc-500 mt-0.5">Jam 7–10 (12.45–15.05)</div>
                        </div>
                        <div class="p-2.5 bg-zinc-50 dark:bg-[#232327] rounded-lg border border-zinc-200 dark:border-[#2B2B30] text-zinc-700 dark:text-zinc-300">
                            <div class="font-bold text-zinc-900 dark:text-white">Blok Sore</div>
                            <div class="text-zinc-500 mt-0.5">Jam 11–12 (15.05–16.15)</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- 4. EMPTY STATE (When Database is Empty) --}}
        @if($schedules->isEmpty())
            <div class="bg-white dark:bg-[#19191C] border border-zinc-200/80 dark:border-[#2B2B30] rounded-xl p-12 text-center space-y-4 shadow-2xs">
                <div class="w-12 h-12 rounded-xl bg-zinc-100 dark:bg-[#26262A] text-zinc-400 mx-auto flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-[#EDEDF0]">
                        Belum Ada Alokasi Jadwal pada {{ $cycleFilter }}
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-[#97979B] mt-1 max-w-sm mx-auto">
                        @if(auth()->user()->role_id === 2)
                            Tidak ditemukan jadwal mengajar yang terhubung dengan akun Anda pada {{ $cycleFilter }}.
                        @else
                            Silakan impor jadwal dari berkas PDF menggunakan modul AI atau input alokasi blok jam manual.
                        @endif
                    </p>
                </div>
                @if(auth()->user()->role_id === 1)
                    <div class="pt-2">
                        <x-ai-importer-modal type="Jadwal Pelajaran" title="Unggah & Impor PDF AI" />
                    </div>
                @endif
            </div>
        @else
            {{-- VIEW A: STRUCTURED DATA TABLE (Standard Industry High-Density Grid) --}}
            <div x-show="viewMode === 'table'" class="space-y-4">
                <div class="bg-white dark:bg-[#19191C] border border-zinc-200/80 dark:border-[#2B2B30] rounded-xl overflow-hidden shadow-2xs">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs divide-y divide-zinc-200/80 dark:divide-[#2B2B30]">
                            <thead class="bg-zinc-50 dark:bg-[#121214] text-zinc-500 dark:text-[#97979B] uppercase font-semibold text-[11px] tracking-wider">
                                <tr>
                                    <th class="py-3 px-4 w-36">Hari & Jam</th>
                                    <th class="py-3 px-4 min-w-[200px]">Mata Pelajaran</th>
                                    <th class="py-3 px-4 w-32">Kelas</th>
                                    <th class="py-3 px-4 w-28">Ruang</th>
                                    @if(auth()->user()->role_id !== 2)
                                        <th class="py-3 px-4 min-w-[160px]">Guru Pengampu</th>
                                    @endif
                                    <th class="py-3 px-4 w-20 text-center">Beban</th>
                                    <th class="py-3 px-4 w-28 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-[#2B2B30]/60">
                                @foreach($schedules as $schedule)
                                    @php
                                        $canManageSchedule = (auth()->user()->role_id === 1) || (!$config?->is_schedule_locked && $schedule->teacher_id === auth()->id());
                                        $isToday = ($todayName === $schedule->day_of_week);
                                    @endphp
                                    <tr 
                                        x-show="matches('{{ $schedule->day_of_week }}', '{{ addslashes($schedule->subject) }}', '{{ addslashes($schedule->schoolClass?->class_name ?? '') }}', '{{ addslashes($schedule->teacher?->fullname ?? '') }}', '{{ addslashes($schedule->room?->room_name ?? '') }}')"
                                        class="hover:bg-zinc-50/80 dark:hover:bg-[#26262A]/50 transition group"
                                    >
                                        {{-- Hari & Jam --}}
                                        <td class="py-3 px-4 align-middle whitespace-nowrap">
                                            <div class="flex items-center gap-1.5 font-semibold text-zinc-900 dark:text-[#EDEDF0]">
                                                <span>{{ $schedule->day_of_week }}</span>
                                                @if($isToday)
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" title="Hari Ini"></span>
                                                @endif
                                            </div>
                                            <div class="font-mono text-[11px] text-zinc-500 dark:text-[#97979B] mt-0.5 tabular-nums">
                                                {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} &ndash; {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                            </div>
                                            <div class="text-[10px] font-mono text-zinc-400 mt-0.5">
                                                {{ $schedule->period_label }}
                                            </div>
                                        </td>

                                        {{-- Mata Pelajaran --}}
                                        <td class="py-3 px-4 align-middle">
                                            <div class="font-bold text-zinc-900 dark:text-[#EDEDF0] text-xs sm:text-sm">
                                                {{ $schedule->subject }}
                                            </div>
                                            <div class="text-[10px] text-zinc-400 font-mono mt-0.5">
                                                {{ $schedule->cycle_type }}
                                            </div>
                                        </td>

                                        {{-- Kelas --}}
                                        <td class="py-3 px-4 align-middle whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-semibold font-mono bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20">
                                                {{ $schedule->schoolClass->class_name }}
                                            </span>
                                        </td>

                                        {{-- Ruang --}}
                                        <td class="py-3 px-4 align-middle whitespace-nowrap">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-mono text-zinc-600 dark:text-zinc-400 bg-zinc-100 dark:bg-[#232327] border border-zinc-200 dark:border-[#2B2B30]">
                                                <svg class="w-3 h-3 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                                                </svg>
                                                {{ $schedule->room->room_name ?? '-' }}
                                            </span>
                                        </td>

                                        {{-- Guru Pengampu (Admin/Waka only) --}}
                                        @if(auth()->user()->role_id !== 2)
                                            <td class="py-3 px-4 align-middle">
                                                <div class="text-zinc-800 dark:text-[#EDEDF0] font-medium text-xs">
                                                    {{ $schedule->teacher->fullname }}
                                                </div>
                                                <div class="text-[10px] text-zinc-400 font-mono">
                                                    NIP/User: {{ $schedule->teacher->username }}
                                                </div>
                                            </td>
                                        @endif

                                        {{-- Beban JP --}}
                                        <td class="py-3 px-4 align-middle text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded font-mono font-bold text-[11px] bg-zinc-100 dark:bg-[#232327] text-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-[#2B2B30]">
                                                {{ $schedule->total_jp }} JP
                                            </span>
                                        </td>

                                        {{-- Aksi --}}
                                        <td class="py-3 px-4 align-middle text-right whitespace-nowrap">
                                            <div class="flex items-center justify-end gap-1.5">
                                                @if(auth()->user()->role_id === 2)
                                                    <a 
                                                        href="{{ route('journals.create', ['schedule_id' => $schedule->id]) }}" 
                                                        class="h-7 px-2.5 inline-flex items-center gap-1 bg-[#0093dd] hover:bg-[#0082c4] text-white text-[11px] font-semibold rounded-md transition shadow-2xs cursor-pointer"
                                                    >
                                                        <span>Isi Jurnal</span>
                                                    </a>
                                                @endif

                                                @if($canManageSchedule)
                                                    <a 
                                                        href="{{ route('schedules.edit', $schedule) }}" 
                                                        class="h-7 px-2 inline-flex items-center text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white bg-zinc-100 dark:bg-[#26262A] hover:bg-zinc-200 dark:hover:bg-[#303036] rounded-md transition text-[11px] font-medium border border-zinc-200/80 dark:border-[#2B2B30]"
                                                        title="Edit Jadwal"
                                                    >
                                                        Edit
                                                    </a>
                                                    <form method="POST" action="{{ route('schedules.destroy', $schedule) }}" class="inline" onsubmit="return confirm('Hapus alokasi jadwal ini?')">
                                                        @csrf @method('DELETE')
                                                        <button 
                                                            type="submit" 
                                                            class="h-7 w-7 inline-flex items-center justify-center text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-md transition cursor-pointer" 
                                                            title="Hapus"
                                                        >
                                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- VIEW B: TIMELINE CARDS (Daily Agenda View) --}}
            <div x-show="viewMode === 'cards'" class="space-y-6">
                @foreach($orderedDays as $day)
                    @if($groupedSchedules->has($day))
                        @php 
                            $daySchedules = $groupedSchedules[$day]; 
                            $totalDayJp = $daySchedules->sum('total_jp');
                            $isToday = ($todayName === $day);
                        @endphp
                        <div 
                            x-show="activeDay === 'all' || activeDay === '{{ $day }}'" 
                            class="space-y-3"
                        >
                            {{-- Day Separator Header --}}
                            <div class="flex items-center justify-between px-1">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full {{ $isToday ? 'bg-emerald-500 ring-4 ring-emerald-500/20' : 'bg-zinc-400 dark:bg-zinc-600' }}"></span>
                                    <h2 class="font-bold text-sm sm:text-base tracking-tight text-zinc-900 dark:text-[#EDEDF0] uppercase font-mono">
                                        {{ $day }}
                                    </h2>
                                    @if($isToday)
                                        <span class="px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 text-[10px] font-bold uppercase font-mono">
                                            Hari Ini
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 font-mono text-xs">
                                    <span class="text-zinc-500 dark:text-[#97979B]">{{ $daySchedules->count() }} Sesi</span>
                                    <span class="px-2 py-0.5 rounded bg-zinc-100 dark:bg-[#232327] text-zinc-700 dark:text-zinc-300 font-bold border border-zinc-200 dark:border-[#2B2B30]">
                                        {{ $totalDayJp }} JP
                                    </span>
                                </div>
                            </div>

                            {{-- Cards Stack --}}
                            <div class="space-y-2.5">
                                @foreach($daySchedules as $schedule)
                                    @php
                                        $canManageSchedule = (auth()->user()->role_id === 1) || (!$config?->is_schedule_locked && $schedule->teacher_id === auth()->id());
                                    @endphp
                                    <div 
                                        x-show="matches('{{ $schedule->day_of_week }}', '{{ addslashes($schedule->subject) }}', '{{ addslashes($schedule->schoolClass?->class_name ?? '') }}', '{{ addslashes($schedule->teacher?->fullname ?? '') }}', '{{ addslashes($schedule->room?->room_name ?? '') }}')"
                                        class="bg-white dark:bg-[#19191C] border border-zinc-200/80 dark:border-[#2B2B30] rounded-xl p-4 sm:p-5 hover:border-zinc-300 dark:hover:border-zinc-700 transition flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-2xs"
                                    >
                                        {{-- Time Column --}}
                                        <div class="flex items-center gap-3.5 sm:w-60 shrink-0">
                                            <div class="w-12 h-12 rounded-xl bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 flex flex-col items-center justify-center font-mono shrink-0 shadow-2xs">
                                                <span class="text-[8px] uppercase tracking-wider opacity-70 font-bold leading-none">JAM</span>
                                                <span class="text-sm font-extrabold leading-tight mt-0.5">
                                                    {{ $schedule->start_period }}&ndash;{{ $schedule->end_period }}
                                                </span>
                                            </div>
                                            <div class="space-y-1">
                                                <div class="font-mono text-xs sm:text-sm font-bold text-zinc-900 dark:text-[#EDEDF0] flex items-center gap-1.5 tabular-nums">
                                                    <span>{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }} &ndash; {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}</span>
                                                </div>
                                                <div class="flex items-center gap-1.5 font-mono text-[11px] text-zinc-500 dark:text-[#97979B]">
                                                    <span class="px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-[#232327] text-zinc-700 dark:text-zinc-300 font-bold border border-zinc-200/80 dark:border-[#2B2B30]">
                                                        {{ $schedule->total_jp }} JP
                                                    </span>
                                                    <span>&bull;</span>
                                                    <span>{{ $schedule->cycle_type }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Subject & Meta Info --}}
                                        <div class="min-w-0 flex-1 space-y-2">
                                            <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-[#EDEDF0] leading-snug">
                                                {{ $schedule->subject }}
                                            </h3>
                                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-semibold font-mono bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20">
                                                    Kelas: {{ $schedule->schoolClass->class_name }}
                                                </span>
                                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-mono text-zinc-600 dark:text-zinc-400 bg-zinc-100 dark:bg-[#232327] border border-zinc-200 dark:border-[#2B2B30]">
                                                    <svg class="w-3 h-3 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/>
                                                    </svg>
                                                    {{ $schedule->room->room_name ?? '-' }}
                                                </span>
                                                @if(auth()->user()->role_id !== 2)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] text-zinc-600 dark:text-zinc-300 bg-zinc-50 dark:bg-[#232327] border border-zinc-200 dark:border-[#2B2B30]">
                                                        <svg class="w-3 h-3 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                        </svg>
                                                        {{ $schedule->teacher->fullname }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Actions --}}
                                        <div class="flex items-center justify-end gap-2 shrink-0 pt-3 md:pt-0 border-t md:border-t-0 border-zinc-100 dark:border-[#2B2B30]">
                                            @if(auth()->user()->role_id === 2)
                                                <a 
                                                    href="{{ route('journals.create', ['schedule_id' => $schedule->id]) }}" 
                                                    class="h-8 inline-flex items-center gap-1.5 px-3 bg-[#0093dd] hover:bg-[#0082c4] text-white text-xs font-semibold rounded-lg transition shadow-2xs"
                                                >
                                                    <span>Isi Jurnal</span>
                                                </a>
                                            @endif

                                            @if($canManageSchedule)
                                                <a 
                                                    href="{{ route('schedules.edit', $schedule) }}" 
                                                    class="h-8 inline-flex items-center px-3 bg-white dark:bg-[#26262A] border border-zinc-200 dark:border-[#2B2B30] hover:bg-zinc-50 dark:hover:bg-[#303036] text-zinc-700 dark:text-zinc-200 text-xs font-medium rounded-lg transition"
                                                >
                                                    Edit
                                                </a>
                                                <form method="POST" action="{{ route('schedules.destroy', $schedule) }}" class="inline" onsubmit="return confirm('Hapus alokasi jadwal ini?')">
                                                    @csrf @method('DELETE')
                                                    <button 
                                                        type="submit" 
                                                        class="h-8 w-8 inline-flex items-center justify-center text-zinc-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition cursor-pointer" 
                                                        title="Hapus"
                                                    >
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- VIEW C: WEEKLY TIMETABLE GRID (Board View) --}}
            <div x-show="viewMode === 'grid'" class="overflow-x-auto pb-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 min-w-[850px]">
                    @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $day)
                        @php
                            $daySchedules = $groupedSchedules->get($day) ?? collect();
                            $totalDayJp = $daySchedules->sum('total_jp');
                            $isToday = ($todayName === $day);
                        @endphp
                        <div class="bg-zinc-50/70 dark:bg-[#121214] border {{ $isToday ? 'border-[#0093dd] ring-1 ring-[#0093dd]' : 'border-zinc-200/80 dark:border-[#2B2B30]' }} rounded-xl overflow-hidden flex flex-col shadow-2xs">
                            {{-- Day Header Strip --}}
                            <div class="px-3.5 py-3 {{ $isToday ? 'bg-[#0093dd] text-white' : 'bg-white dark:bg-[#19191C] text-zinc-900 dark:text-[#EDEDF0] border-b border-zinc-200/80 dark:border-[#2B2B30]' }} flex items-center justify-between">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-xs uppercase font-mono tracking-wider">{{ $day }}</span>
                                    @if($isToday)
                                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                    @endif
                                </div>
                                <span class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded {{ $isToday ? 'bg-white/20 text-white' : 'bg-zinc-100 dark:bg-[#26262A] text-zinc-700 dark:text-zinc-300' }}">
                                    {{ $totalDayJp }} JP
                                </span>
                            </div>

                            {{-- Cards Container --}}
                            <div class="p-2.5 space-y-2.5 flex-1">
                                @forelse($daySchedules as $schedule)
                                    <div 
                                        x-show="matches('{{ $schedule->day_of_week }}', '{{ addslashes($schedule->subject) }}', '{{ addslashes($schedule->schoolClass?->class_name ?? '') }}', '{{ addslashes($schedule->teacher?->fullname ?? '') }}', '{{ addslashes($schedule->room?->room_name ?? '') }}')"
                                        class="bg-white dark:bg-[#19191C] border border-zinc-200 dark:border-[#2B2B30] rounded-lg p-3 hover:border-zinc-400 dark:hover:border-zinc-600 transition space-y-2 flex flex-col justify-between shadow-2xs"
                                    >
                                        <div class="space-y-1.5">
                                            <div class="flex items-center justify-between text-[10px] font-mono">
                                                <span class="font-bold px-1.5 py-0.5 rounded bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 leading-none">
                                                    {{ $schedule->period_label }}
                                                </span>
                                                <span class="text-zinc-500 dark:text-[#97979B] font-semibold tabular-nums">
                                                    {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}&ndash;{{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                                </span>
                                            </div>

                                            <h4 class="font-bold text-zinc-900 dark:text-[#EDEDF0] text-xs leading-snug line-clamp-2">
                                                {{ $schedule->subject }}
                                            </h4>

                                            <div class="flex flex-wrap items-center gap-1 text-[10px] font-mono pt-0.5">
                                                <span class="px-1.5 py-0.5 rounded bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20 font-bold leading-none">
                                                    {{ $schedule->schoolClass->class_name }}
                                                </span>
                                                <span class="px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-[#232327] text-zinc-600 dark:text-zinc-400 border border-zinc-200 dark:border-[#2B2B30] leading-none">
                                                    {{ $schedule->room->room_name ?? '-' }}
                                                </span>
                                            </div>

                                            @if(auth()->user()->role_id !== 2)
                                                <div class="text-[10px] text-zinc-500 dark:text-[#97979B] truncate">
                                                    {{ $schedule->teacher->fullname }}
                                                </div>
                                            @endif
                                        </div>

                                        @if(auth()->user()->role_id === 2)
                                            <div class="pt-1.5 border-t border-zinc-100 dark:border-[#2B2B30]">
                                                <a 
                                                    href="{{ route('journals.create', ['schedule_id' => $schedule->id]) }}" 
                                                    class="block text-center py-1 bg-[#0093dd] hover:bg-[#0082c4] text-white text-[10px] font-semibold rounded transition"
                                                >
                                                    Isi Jurnal
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="py-10 text-center text-xs text-zinc-400 dark:text-zinc-600">
                                        Tidak ada alokasi jadwal.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
