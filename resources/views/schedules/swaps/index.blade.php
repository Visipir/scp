<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="text-xs font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider">Manajemen Jadwal</div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0] mt-0.5">
                    Tukar Jadwal Mengajar (Guru)
                </h1>
            </div>
            <div>
                <a href="{{ route('schedules.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white bg-zinc-100 dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 transition">
                    &larr; Kembali ke Daftar Jadwal
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6 max-w-5xl" x-data="{
        activeTab: '{{ $pendingCount > 0 ? 'inbox' : 'create' }}',
        selectedDate: '{{ now()->format('Y-m-d') }}',
        selectedScheduleId: '',
        candidates: [],
        selectedTeacherId: '',
        selectedTargetScheduleId: '',
        loadingCandidates: false,
        candidateError: '',
        fetchCandidates() {
            if (!this.selectedDate || !this.selectedScheduleId) {
                this.candidates = [];
                return;
            }
            this.loadingCandidates = true;
            this.candidateError = '';
            this.selectedTeacherId = '';
            this.selectedTargetScheduleId = '';
            fetch(`{{ route('schedule-swaps.candidates') }}?date=${this.selectedDate}&schedule_id=${this.selectedScheduleId}`)
                .then(res => {
                    if (!res.ok) throw new Error('Gagal memuat kandidat guru');
                    return res.json();
                })
                .then(data => {
                    this.candidates = data.candidates || [];
                    this.loadingCandidates = false;
                })
                .catch(err => {
                    this.candidateError = err.message;
                    this.loadingCandidates = false;
                });
        }
    }">

        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if(session('error'))
            <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
        @endif

        @if($isScheduleLocked)
            <div class="p-4 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-xl flex items-start gap-3">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <div class="text-xs text-amber-800 dark:text-amber-200">
                    <strong class="font-semibold">Jadwal Sedang Dikunci:</strong> Waka Kurikulum sedang mengunci jadwal. Anda tidak dapat mengajukan atau menyetujui pertukaran jadwal saat ini.
                </div>
            </div>
        @endif

        {{-- Tab Navigation --}}
        <div class="flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-800 pb-2">
            <button
                type="button"
                @click="activeTab = 'inbox'"
                :class="activeTab === 'inbox' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white bg-zinc-100 dark:bg-zinc-800'"
                class="px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition inline-flex items-center gap-2 min-h-[44px]"
            >
                <span>Permintaan Masuk</span>
                @if($pendingCount > 0)
                    <span class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-rose-500 text-white">
                        {{ $pendingCount }}
                    </span>
                @endif
            </button>

            <button
                type="button"
                @click="activeTab = 'outbox'"
                :class="activeTab === 'outbox' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white bg-zinc-100 dark:bg-zinc-800'"
                class="px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition min-h-[44px]"
            >
                Permintaan Keluar / Riwayat
            </button>

            @if(!$isScheduleLocked)
                <button
                    type="button"
                    @click="activeTab = 'create'"
                    :class="activeTab === 'create' ? 'bg-blue-600 text-white shadow-xs' : 'text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/40 border border-blue-200 dark:border-blue-800'"
                    class="px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-wider transition min-h-[44px] ml-auto inline-flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Ajukan Tukar Jadwal</span>
                </button>
            @endif
        </div>

        {{-- TAB 1: INBOX (Permintaan Masuk) --}}
        <div x-show="activeTab === 'inbox'" class="space-y-4">
            @if($inbox->isEmpty())
                <div class="bg-white dark:bg-[#1C1C1E] border border-zinc-200 dark:border-zinc-800 rounded-xl p-8 text-center text-zinc-500 dark:text-zinc-400 text-sm">
                    Tidak ada permintaan tukar jadwal yang masuk untuk Anda.
                </div>
            @else
                <div class="grid grid-cols-1 gap-4">
                    @foreach($inbox as $swap)
                        <div class="bg-white dark:bg-[#1C1C1E] border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-xs space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">
                                        Dari: {{ $swap->requester->fullname }}
                                    </span>
                                    <span class="text-xs text-zinc-400">&bull;</span>
                                    <span class="text-xs text-zinc-500 font-mono">
                                        Tanggal Berlaku: {{ $swap->swap_date->translatedFormat('l, d F Y') }}
                                    </span>
                                </div>
                                <div>
                                    @if($swap->status === 'pending')
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-amber-50 text-amber-700 border border-amber-200">
                                            Menunggu Persetujuan
                                        </span>
                                    @elseif($swap->status === 'approved')
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Disetujui
                                        </span>
                                    @elseif($swap->status === 'rejected')
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-rose-50 text-rose-700 border border-rose-200">
                                            Ditolak
                                        </span>
                                    @elseif($swap->status === 'cancelled')
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-zinc-100 text-zinc-700 border border-zinc-200">
                                            Dibatalkan
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-slate-100 text-slate-700 border border-slate-200">
                                            Kedaluwarsa
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                                <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200/60 dark:border-zinc-700/60">
                                    <div class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider mb-1">
                                        Jadwal Yang Anda Berikan ke Pemohon:
                                    </div>
                                    <div class="font-bold text-zinc-900 dark:text-zinc-100 text-sm">
                                        {{ $swap->targetSchedule->subject }}
                                    </div>
                                    <div class="text-zinc-600 dark:text-zinc-400 mt-1">
                                        Kelas: <strong>{{ $swap->targetSchedule->schoolClass?->class_name }}</strong> &bull; Ruang: {{ $swap->targetSchedule->room?->room_name ?? '-' }}
                                    </div>
                                    <div class="text-zinc-500 font-mono mt-0.5">
                                        Pukul {{ substr($swap->targetSchedule->start_time, 0, 5) }} - {{ substr($swap->targetSchedule->end_time, 0, 5) }}
                                    </div>
                                </div>

                                <div class="p-3 bg-blue-50/60 dark:bg-blue-950/30 rounded-lg border border-blue-200/60 dark:border-blue-800/60">
                                    <div class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">
                                        Jadwal Yang Akan Anda Gantikan (Milik Pemohon):
                                    </div>
                                    <div class="font-bold text-zinc-900 dark:text-zinc-100 text-sm">
                                        {{ $swap->requesterSchedule->subject }}
                                    </div>
                                    <div class="text-zinc-600 dark:text-zinc-400 mt-1">
                                        Kelas: <strong>{{ $swap->requesterSchedule->schoolClass?->class_name }}</strong> &bull; Ruang: {{ $swap->requesterSchedule->room?->room_name ?? '-' }}
                                    </div>
                                    <div class="text-zinc-500 font-mono mt-0.5">
                                        Pukul {{ substr($swap->requesterSchedule->start_time, 0, 5) }} - {{ substr($swap->requesterSchedule->end_time, 0, 5) }}
                                    </div>
                                </div>
                            </div>

                            @if($swap->note)
                                <div class="text-xs text-zinc-600 dark:text-zinc-300 italic bg-zinc-50 dark:bg-zinc-800 p-2.5 rounded">
                                    Catatan: &ldquo;{{ $swap->note }}&rdquo;
                                </div>
                            @endif

                            @if($swap->status === 'pending' && !$isScheduleLocked)
                                <div class="flex items-center justify-end gap-3 pt-2">
                                    <form method="POST" action="{{ route('schedule-swaps.reject', $swap) }}" onsubmit="return confirm('Tolak permintaan tukar jadwal ini?');">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-rose-700 bg-rose-50 hover:bg-rose-100 rounded-lg border border-rose-200 transition min-h-[44px]"
                                        >
                                            Tolak
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('schedule-swaps.approve', $swap) }}" onsubmit="return confirm('Setujui pertukaran jadwal ini? Anda akan mengajar kelas pemohon pada tanggal tersebut.');">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="px-5 py-2 text-xs font-bold uppercase tracking-wider text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-xs transition min-h-[44px]"
                                        >
                                            Setujui Pertukaran
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- TAB 2: OUTBOX (Permintaan Keluar) --}}
        <div x-show="activeTab === 'outbox'" class="space-y-4">
            @if($outbox->isEmpty())
                <div class="bg-white dark:bg-[#1C1C1E] border border-zinc-200 dark:border-zinc-800 rounded-xl p-8 text-center text-zinc-500 dark:text-zinc-400 text-sm">
                    Belum ada riwayat pengajuan pertukaran jadwal yang Anda buat.
                </div>
            @else
                <div class="grid grid-cols-1 gap-4">
                    @foreach($outbox as $swap)
                        <div class="bg-white dark:bg-[#1C1C1E] border border-zinc-200 dark:border-zinc-800 rounded-xl p-5 shadow-xs space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">
                                        Kepada: {{ $swap->target->fullname }}
                                    </span>
                                    <span class="text-xs text-zinc-400">&bull;</span>
                                    <span class="text-xs text-zinc-500 font-mono">
                                        Tanggal: {{ $swap->swap_date->translatedFormat('l, d F Y') }}
                                    </span>
                                </div>
                                <div>
                                    @if($swap->status === 'pending')
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-amber-50 text-amber-700 border border-amber-200">
                                            Menunggu Respon Target
                                        </span>
                                    @elseif($swap->status === 'approved')
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            Disetujui
                                        </span>
                                    @elseif($swap->status === 'rejected')
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-rose-50 text-rose-700 border border-rose-200">
                                            Ditolak
                                        </span>
                                    @elseif($swap->status === 'cancelled')
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-zinc-100 text-zinc-700 border border-zinc-200">
                                            Dibatalkan
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-slate-100 text-slate-700 border border-slate-200">
                                            Kedaluwarsa
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                                <div class="p-3 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200/60 dark:border-zinc-700/60">
                                    <div class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider mb-1">
                                        Jadwal Anda:
                                    </div>
                                    <div class="font-bold text-zinc-900 dark:text-zinc-100 text-sm">
                                        {{ $swap->requesterSchedule->subject }}
                                    </div>
                                    <div class="text-zinc-600 dark:text-zinc-400 mt-1">
                                        Kelas: <strong>{{ $swap->requesterSchedule->schoolClass?->class_name }}</strong> &bull; Ruang: {{ $swap->requesterSchedule->room?->room_name ?? '-' }}
                                    </div>
                                    <div class="text-zinc-500 font-mono mt-0.5">
                                        Pukul {{ substr($swap->requesterSchedule->start_time, 0, 5) }} - {{ substr($swap->requesterSchedule->end_time, 0, 5) }}
                                    </div>
                                </div>

                                <div class="p-3 bg-blue-50/60 dark:bg-blue-950/30 rounded-lg border border-blue-200/60 dark:border-blue-800/60">
                                    <div class="text-[11px] font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-1">
                                        Jadwal Target Yang Diinginkan:
                                    </div>
                                    <div class="font-bold text-zinc-900 dark:text-zinc-100 text-sm">
                                        {{ $swap->targetSchedule->subject }}
                                    </div>
                                    <div class="text-zinc-600 dark:text-zinc-400 mt-1">
                                        Kelas: <strong>{{ $swap->targetSchedule->schoolClass?->class_name }}</strong> &bull; Ruang: {{ $swap->targetSchedule->room?->room_name ?? '-' }}
                                    </div>
                                    <div class="text-zinc-500 font-mono mt-0.5">
                                        Pukul {{ substr($swap->targetSchedule->start_time, 0, 5) }} - {{ substr($swap->targetSchedule->end_time, 0, 5) }}
                                    </div>
                                </div>
                            </div>

                            @if($swap->status === 'pending')
                                <div class="flex items-center justify-end pt-2">
                                    <form method="POST" action="{{ route('schedule-swaps.cancel', $swap) }}" onsubmit="return confirm('Batalkan permintaan tukar jadwal ini?');">
                                        @csrf
                                        <button
                                            type="submit"
                                            class="px-4 py-2 text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-200 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition min-h-[44px]"
                                        >
                                            Batalkan Permintaan
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- TAB 3: FORM AJUKAN PERTUKARAN --}}
        @if(!$isScheduleLocked)
            <div x-show="activeTab === 'create'" class="bg-white dark:bg-[#1C1C1E] border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 shadow-xs">
                <div class="border-b border-zinc-200 dark:border-zinc-800 pb-4 mb-5">
                    <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Formulir Pengajuan Tukar Jadwal</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Pilih tanggal dan jadwal Anda, lalu sistem akan mencari guru dan jadwal yang cocok untuk ditukar pada hari tersebut.
                    </p>
                </div>

                <form method="POST" action="{{ route('schedule-swaps.store') }}" class="space-y-5">
                    @csrf

                    {{-- Step 1: Tanggal Berlaku --}}
                    <div>
                        <label for="swap_date" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            1. Tanggal Berlaku Pertukaran
                        </label>
                        <input
                            type="date"
                            name="swap_date"
                            id="swap_date"
                            min="{{ now()->format('Y-m-d') }}"
                            x-model="selectedDate"
                            @change="fetchCandidates()"
                            required
                            class="w-full sm:w-72 text-sm rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#2C2C2E] text-zinc-900 dark:text-zinc-100"
                        >
                    </div>

                    {{-- Step 2: Jadwal Milik Pemohon --}}
                    <div>
                        <label for="requester_schedule_id" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            2. Pilih Jadwal Anda Yang Ingin Ditukar (Putaran: {{ $activeCycle }})
                        </label>
                        <select
                            name="requester_schedule_id"
                            id="requester_schedule_id"
                            x-model="selectedScheduleId"
                            @change="fetchCandidates()"
                            required
                            class="w-full text-sm rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#2C2C2E] text-zinc-900 dark:text-zinc-100"
                        >
                            <option value="">-- Pilih Jadwal Anda --</option>
                            @foreach($mySchedules as $sched)
                                <option value="{{ $sched->id }}">
                                    [{{ $sched->day_of_week }}] {{ substr($sched->start_time, 0, 5) }}-{{ substr($sched->end_time, 0, 5) }} &bull; {{ $sched->subject }} ({{ $sched->schoolClass?->class_name }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Loading / Error indicator --}}
                    <div x-show="loadingCandidates" class="text-xs text-blue-600 dark:text-blue-400 flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Mencari guru dan jadwal yang tersedia...
                    </div>

                    <div x-show="candidateError" class="text-xs text-rose-500 font-medium" x-text="candidateError"></div>

                    {{-- Step 3: Pilih Guru Target & Jadwal Target --}}
                    <div x-show="selectedScheduleId && !loadingCandidates">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="target_id" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    3. Pilih Guru Target
                                </label>
                                <select
                                    name="target_id"
                                    id="target_id"
                                    x-model="selectedTeacherId"
                                    required
                                    class="w-full text-sm rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#2C2C2E] text-zinc-900 dark:text-zinc-100"
                                >
                                    <option value="">-- Pilih Guru Target --</option>
                                    <template x-for="c in candidates" :key="c.teacher_id">
                                        <option :value="c.teacher_id" x-text="c.fullname"></option>
                                    </template>
                                </select>
                                <p x-show="candidates.length === 0" class="mt-1 text-xs text-zinc-500">
                                    Tidak ada guru yang memiliki jadwal di hari dan putaran yang sama pada tanggal tersebut.
                                </p>
                            </div>

                            <div>
                                <label for="target_schedule_id" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    4. Pilih Jadwal Guru Target
                                </label>
                                <select
                                    name="target_schedule_id"
                                    id="target_schedule_id"
                                    x-model="selectedTargetScheduleId"
                                    required
                                    class="w-full text-sm rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#2C2C2E] text-zinc-900 dark:text-zinc-100"
                                >
                                    <option value="">-- Pilih Jadwal Target --</option>
                                    <template x-if="selectedTeacherId">
                                        <template x-for="s in (candidates.find(c => c.teacher_id == selectedTeacherId)?.schedules || [])" :key="s.id">
                                            <option :value="s.id" x-text="`${s.start_time}-${s.end_time} • ${s.subject} (${s.class_name})`"></option>
                                        </template>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Step 4: Catatan --}}
                    <div>
                        <label for="note" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Catatan / Alasan Pertukaran (Opsional)
                        </label>
                        <textarea
                            name="note"
                            id="note"
                            rows="2"
                            placeholder="Contoh: Mengikuti dinas luar sekolah / keperluan mendesak..."
                            class="w-full text-sm rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#2C2C2E] text-zinc-900 dark:text-zinc-100"
                        ></textarea>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-zinc-100 dark:border-zinc-800">
                        <button
                            type="button"
                            @click="activeTab = 'inbox'"
                            class="px-4 py-2 text-xs font-semibold uppercase text-zinc-600 hover:text-zinc-900 transition min-h-[44px]"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="!selectedTargetScheduleId"
                            class="px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg shadow-xs transition min-h-[44px]"
                        >
                            Kirim Permintaan Tukar
                        </button>
                    </div>
                </form>
            </div>
        @endif

    </div>
</x-app-layout>
