<x-app-layout>
    <x-slot name="header">
        <x-page-header 
            title="Dashboard" 
            subtitle="Ringkasan jadwal mengajar hari ini dan pengisian jurnal KBM"
        >
            <a href="{{ route('journals.index') }}" class="inline-flex items-center gap-1.5 min-h-[44px] sm:min-h-[36px] px-3 py-1.5 bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] text-xs font-semibold text-zinc-800 dark:text-[#EDEDF0] rounded-lg hover:bg-zinc-100 dark:hover:bg-[#26262A] transition">
                <svg aria-hidden="true" class="w-3.5 h-3.5 text-zinc-600 dark:text-[#B4B6C2]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                Riwayat Jurnal
            </a>
        </x-page-header>
    </x-slot>

    <div class="space-y-6 dashboard-touch-scope">

        {{-- Notifikasi tukar jadwal pending --}}
        @if(($pendingSwapCount ?? 0) > 0)
            <section aria-labelledby="pending-swap-heading" class="bg-blue-50 dark:bg-[#16222F] border border-blue-200 dark:border-sky-800/60 rounded-xl p-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <span class="inline-flex items-center justify-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded font-mono shrink-0 mt-0.5 bg-[#0073B1] text-white">
                            TUKAR JADWAL
                        </span>
                        <div>
                            <h2 id="pending-swap-heading" class="text-sm font-bold text-blue-900 dark:text-sky-200 tracking-tight">
                                Ada {{ $pendingSwapCount }} Permintaan Tukar Jadwal Menunggu Konfirmasi
                            </h2>
                            <p class="text-xs text-blue-800 dark:text-sky-300 mt-1">
                                Rekan guru telah mengajukan pertukaran jadwal mengajar dengan Anda. Harap tinjau dan konfirmasi.
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('schedule-swaps.index') }}" class="inline-flex items-center justify-center gap-1.5 min-h-[44px] sm:min-h-[36px] px-4 py-2 bg-[#0073B1] hover:bg-[#005D8F] text-white text-xs font-semibold uppercase tracking-wider rounded-lg transition shrink-0 cursor-pointer shadow-none">
                        Tinjau Sekarang &rarr;
                    </a>
                </div>
            </section>
        @endif

        {{-- Banner libur KBM --}}
        @if($kbmSuspendedEvent ?? false)
            <section aria-labelledby="kbm-suspended-heading" class="bg-amber-50 dark:bg-[#2A2215] border border-amber-200 dark:border-amber-700/60 rounded-xl p-5">
                <div class="flex items-start gap-3.5">
                    <span class="inline-flex items-center justify-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded font-mono shrink-0 mt-0.5 bg-amber-500 text-zinc-950">
                        KBM DILIBURKAN
                    </span>
                    <div>
                        <h2 id="kbm-suspended-heading" class="text-sm font-bold text-amber-950 dark:text-amber-200 tracking-tight">
                            Hari ini: {{ ucwords(str_replace("_", " ", $kbmSuspendedEvent->category)) }} - {{ $kbmSuspendedEvent->title }}
                        </h2>
                        <p class="text-xs text-amber-900 dark:text-amber-300 mt-1">
                            Keterangan: <span class="font-semibold">{{ $kbmSuspendedEvent->description ?? "Tidak ada pembelajaran reguler." }}</span>.
                            Guru tidak diwajibkan mengirimkan jurnal pembelajaran harian untuk hari ini.
                        </p>
                    </div>
                </div>
            </section>
        @endif

        {{-- Peringatan jurnal belum terisi --}}
        @if(!empty($alerts))
            <div class="space-y-3" role="region" aria-label="Peringatan Jurnal Belum Diisi">
                @foreach($alerts as $schedule)
                    <div class="bg-amber-50 dark:bg-[#2A2215] border border-amber-200 dark:border-amber-700/60 rounded-xl p-5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-start gap-3.5">
                                <span class="inline-flex items-center justify-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded font-mono shrink-0 mt-0.5 bg-amber-500 text-zinc-950">
                                    PERINGATAN
                                </span>
                                <div>
                                    <h2 class="text-sm font-bold text-amber-950 dark:text-amber-200 tracking-tight">
                                        Jurnal KBM Belum Diisi!
                                    </h2>
                                    <p class="text-xs text-amber-900 dark:text-[#EDEDF0] mt-1 font-medium">
                                        <span>{{ $schedule->subject }}</span> &mdash;
                                        <span class="font-bold">{{ $schedule->schoolClass?->class_name ?? '-' }}</span> &bull;
                                        <span>Ruang {{ $schedule->room?->room_name ?? '-' }}</span>
                                    </p>
                                    <p class="text-xs font-mono text-amber-800 dark:text-amber-300 mt-1">
                                        Selesai pukul {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }} (melewati batas toleransi 30 menit)
                                    </p>
                                </div>
                            </div>
                            <a href="{{ route('journals.create') }}" class="inline-flex items-center justify-center gap-1.5 min-h-[44px] sm:min-h-[36px] px-4 py-2 bg-[#0073B1] hover:bg-[#005D8F] text-white text-xs font-semibold uppercase tracking-wider rounded-lg transition shrink-0 cursor-pointer shadow-none">
                                <svg aria-hidden="true" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                Isi Sekarang
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Panel perwalian kelas --}}
        @if($homeroomClass)
            <section aria-labelledby="homeroom-heading" class="cv-auto bg-emerald-50 dark:bg-[#15231B] border border-emerald-300 dark:border-emerald-700/60 rounded-xl p-5 sm:p-6 shadow-xs">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-emerald-200 dark:border-emerald-800/40">
                    <div class="flex items-start gap-3.5">
                        <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-[#1E3025] text-emerald-800 dark:text-emerald-300 flex items-center justify-center shrink-0 mt-0.5 border border-emerald-300 dark:border-emerald-600/50">
                            <svg aria-hidden="true" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded font-mono bg-emerald-600 text-white">
                                    WALI KELAS
                                </span>
                                <h2 id="homeroom-heading" class="text-base font-bold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">
                                    Kelas Perwalian: {{ $homeroomClass->class_name }}
                                </h2>
                                <span class="px-2 py-0.5 text-[11px] font-mono rounded bg-white dark:bg-[#1E3025] text-zinc-700 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-700/60">
                                    TP {{ $homeroomClass->academic_year ?? '2026/2027' }}
                                </span>
                            </div>
                            <p class="text-xs text-zinc-600 dark:text-emerald-200/85 mt-1">
                                Kelola data {{ $homeroomStats['total_students'] ?? 0 }} siswa perwalian, cek presensi harian, evaluasi catatan kehadiran, dan pantau status izin siswa.
                            </p>
                        </div>
                    </div>
                </div>

                {{-- 4 Menu Utama Wali Kelas --}}
                <div class="card-grid-aligned grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-4">
                    <!-- 1. Cek List Siswa -->
                    <a href="{{ route('classes.show', $homeroomClass->id) }}" class="card-subgrid-link group rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)]">
                        <article class="card-subgrid bg-white dark:bg-[#1E3025] border border-emerald-200 dark:border-emerald-600/40 group-hover:border-emerald-500 rounded-lg p-3.5 transition">
                            <div>
                                <div class="flex items-center justify-between gap-2 text-zinc-700 dark:text-emerald-200 mb-2">
                                    <span class="text-[11px] font-mono uppercase tracking-wider font-semibold">Cek List Siswa</span>
                                    <span class="text-xs font-bold text-zinc-900 dark:text-white tabular-nums">{{ $homeroomStats['total_students'] ?? 0 }} Siswa</span>
                                </div>
                                <p class="text-xs text-zinc-600 dark:text-emerald-100/85">Daftar siswa lengkap, nomor presensi, NIS, dan data rombel kelas.</p>
                            </div>
                            <footer class="mt-3 pt-2 border-t border-emerald-100 dark:border-emerald-700/50 flex items-center justify-between text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                <span>Buka Cek List &rarr;</span>
                            </footer>
                        </article>
                    </a>

                    <!-- 2. Presensi Siswa -->
                    <a href="{{ route('classes.show', ['class' => $homeroomClass->id, 'tab' => 'absences']) }}" class="card-subgrid-link group rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)]">
                        <article class="card-subgrid bg-white dark:bg-[#1E3025] border border-emerald-200 dark:border-emerald-600/40 group-hover:border-emerald-500 rounded-lg p-3.5 transition">
                            <div>
                                <div class="flex items-center justify-between gap-2 text-zinc-700 dark:text-emerald-200 mb-2">
                                    <span class="text-[11px] font-mono uppercase tracking-wider font-semibold">Presensi Siswa</span>
                                    <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300 tabular-nums">{{ $homeroomStats['attendance_summary']['hadir'] ?? 0 }} Hadir Hari Ini</span>
                                </div>
                                <p class="text-xs text-zinc-600 dark:text-emerald-100/85">Rekap kehadiran harian/bulanan, persentase kehadiran, dan ekspor CSV.</p>
                            </div>
                            <footer class="mt-3 pt-2 border-t border-emerald-100 dark:border-emerald-700/50 flex items-center justify-between text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                <span>Rekap Presensi &rarr;</span>
                            </footer>
                        </article>
                    </a>

                    <!-- 3. Catatan Siswa -->
                    <a href="{{ route('classes.show', ['class' => $homeroomClass->id, 'tab' => 'absences']) }}" class="card-subgrid-link group rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)]">
                        <article class="card-subgrid bg-white dark:bg-[#1E3025] border border-emerald-200 dark:border-emerald-600/40 group-hover:border-emerald-500 rounded-lg p-3.5 transition">
                            <div>
                                <div class="flex items-center justify-between gap-2 text-zinc-700 dark:text-emerald-200 mb-2">
                                    <span class="text-[11px] font-mono uppercase tracking-wider font-semibold">Catatan Siswa</span>
                                    <span class="text-xs font-bold text-rose-700 dark:text-rose-300 tabular-nums">{{ ($homeroomStats['attendance_summary']['alpa'] ?? 0) + ($homeroomStats['attendance_summary']['sakit'] ?? 0) + ($homeroomStats['attendance_summary']['izin'] ?? 0) }} Rekap Absen</span>
                                </div>
                                <p class="text-xs text-zinc-600 dark:text-emerald-100/85">Catatan per siswa yang tidak hadir, sakit, alpa, izin, atau terlambat.</p>
                            </div>
                            <footer class="mt-3 pt-2 border-t border-emerald-100 dark:border-emerald-700/50 flex items-center justify-between text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                <span>Lihat Catatan &rarr;</span>
                            </footer>
                        </article>
                    </a>

                    <!-- 4. Izin Siswa (Approved BK maupun Tidak / Pending) -->
                    <a href="{{ route('leave-requests.index', ['class_id' => $homeroomClass->id]) }}" class="card-subgrid-link group rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--ring-focus)]">
                        <article class="card-subgrid bg-white dark:bg-[#1E3025] border border-emerald-200 dark:border-emerald-600/40 group-hover:border-emerald-500 rounded-lg p-3.5 transition">
                            <div>
                                <div class="flex items-center justify-between gap-2 text-zinc-700 dark:text-emerald-200 mb-2">
                                    <span class="text-[11px] font-mono uppercase tracking-wider font-semibold">Izin Siswa Perwalian</span>
                                    @if(($homeroomStats['leaves']['pending'] ?? 0) > 0)
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 dark:bg-[#282115] text-amber-900 dark:text-amber-300 border border-amber-300 dark:border-amber-700/60 font-mono tabular-nums">
                                            {{ $homeroomStats['leaves']['pending'] }} Menunggu BK
                                        </span>
                                    @else
                                        <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200 tabular-nums">{{ $homeroomStats['leaves']['total'] ?? 0 }} Total</span>
                                    @endif
                                </div>
                                <p class="text-xs text-zinc-600 dark:text-emerald-100/85">
                                    Pantau izin siswa (Disetujui: {{ $homeroomStats['leaves']['approved'] ?? 0 }}, Ditolak: {{ $homeroomStats['leaves']['rejected'] ?? 0 }}, Menunggu: {{ $homeroomStats['leaves']['pending'] ?? 0 }}).
                                </p>
                            </div>
                            <footer class="mt-3 pt-2 border-t border-emerald-100 dark:border-emerald-700/50 flex items-center justify-between text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                                <span>Semua Izin Siswa &rarr;</span>
                            </footer>
                        </article>
                    </a>
                </div>
            </section>
        @endif

        {{-- 2. SWISS MINIMALIST JOURNAL FORM CARD --}}
        <section aria-labelledby="journal-form-heading" class="cv-auto bg-white dark:bg-[#1E1E22] border border-zinc-200/80 dark:border-[#2C2C30] rounded-lg p-6 sm:p-8">
            <div class="border-b border-zinc-200/80 dark:border-[#2C2C30] pb-4 mb-6">
                <h2 id="journal-form-heading" class="text-base font-bold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">
                    Input Jurnal Pembelajaran Harian
                </h2>
                <p class="text-xs text-zinc-600 dark:text-[#97979B] mt-0.5">
                    Dokumentasikan pelaksanaan kegiatan belajar mengajar secara objektif dan akurat.
                </p>
            </div>

            @if(isset($fillTimeCheck) && !$fillTimeCheck['allowed'])
                <div class="mb-5 bg-amber-50 dark:bg-[#282115] border border-amber-300 dark:border-amber-700/60 rounded-lg p-3.5 text-xs text-amber-900 dark:text-amber-300 flex items-start gap-2.5">
                    <svg aria-hidden="true" class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <div>
                        <span class="font-bold">Pengisian Jurnal Belum Dibuka:</span>
                        <span>{{ $fillTimeCheck['message'] }}</span>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('journals.store') }}" enctype="multipart/form-data" aria-labelledby="journal-form-heading" data-accessible-validation class="space-y-6" 
                x-data="{ 
                    fileName: '', 
                    photoPreview: null, 
                    fileError: '',
                    isManual: {{ old('is_manual') || (isset($errors) && ($errors->has('schedule_id') || $errors->has('date'))) || !isset($defaultSchedule) || !$defaultSchedule ? 'true' : 'false' }},
                    selectedScheduleId: '{{ old('schedule_id', $defaultSchedule?->id) }}',
                    selectedDate: '{{ old('date', $defaultDate ?? now()->format('Y-m-d')) }}',
                    schedulesMap: {{ Js::from($schedules->mapWithKeys(fn($s) => [$s->id => ['id' => $s->id, 'class_id' => $s->class_id, 'class_name' => $s->schoolClass->class_name ?? '']])) }},
                    studentsByClass: {{ Js::from($studentsByClass ?? []) }},
                    absentStudents: {{ Js::from($initialAbsentStudents ?? old('absent_students', [])) }},
                    isSyncingLeaves: false,
                    init() {
                        this.$watch('selectedScheduleId', () => this.syncApprovedLeaves());
                        this.$watch('selectedDate', () => this.syncApprovedLeaves());
                    },
                    async syncApprovedLeaves() {
                        if (!this.selectedScheduleId || !this.selectedDate) return;
                        this.isSyncingLeaves = true;
                        try {
                            const res = await fetch(`{{ route('journals.leaves') }}?schedule_id=${this.selectedScheduleId}&date=${this.selectedDate}`, {
                                headers: { 'Accept': 'application/json' }
                            });
                            if (res.ok) {
                                const data = await res.json();
                                const newLeaves = data.leaves || [];
                                const manualEntries = this.absentStudents.filter(item => !item.is_approved_leave);
                                const manualIds = new Set(manualEntries.map(item => String(item.student_id)));

                                const leaveEntries = [];
                                for (const leave of newLeaves) {
                                    if (!manualIds.has(String(leave.student_id))) {
                                        leaveEntries.push({
                                            student_id: String(leave.student_id),
                                            student_name: leave.student_name,
                                            student_username: leave.student_username,
                                            status: leave.status,
                                            category: leave.category,
                                            notes: leave.notes,
                                            is_approved_leave: true,
                                            status_approval: leave.status_approval,
                                            leave_id: leave.leave_id,
                                            leave_reason: leave.leave_reason,
                                            decision_label: leave.decision_label
                                        });
                                    }
                                }
                                this.absentStudents = [...leaveEntries, ...manualEntries];
                            }
                        } catch (e) {
                            console.error('Failed to sync approved leaves:', e);
                        } finally {
                            this.isSyncingLeaves = false;
                        }
                    },
                    get currentClassId() {
                        return this.schedulesMap[this.selectedScheduleId]?.class_id;
                    },
                    get currentStudents() {
                        return this.studentsByClass[this.currentClassId] || [];
                    },
                    getStudentName(id) {
                        if (!id) return '';
                        const std = (this.currentStudents || []).find(s => String(s.id) === String(id));
                        return std ? (std.fullname + (std.username ? ' (' + std.username + ')' : '')) : ('Siswa #' + id);
                    },
                    addAbsentStudent(status = 'Sakit', defaultNote = '') {
                        this.absentStudents.push({ student_id: '', status: status, notes: defaultNote });
                    },
                    addLeavingMidClass() {
                        this.absentStudents.push({ student_id: '', status: 'Izin', notes: 'Izin keluar kelas: ' });
                    },
                    setPreset(idx, status, note) {
                        if (this.absentStudents[idx]) {
                            this.absentStudents[idx].status = status;
                            this.absentStudents[idx].notes = note;
                        }
                    },
                    removeAbsentStudent(idx) {
                        this.absentStudents.splice(idx, 1);
                    }
                }"
            >
                @csrf
                <input type="hidden" name="is_manual" :value="isManual ? '1' : '0'" />

                {{-- Sesi Jadwal (Otomatis / Manual) --}}
                <div>
                    @if(!isset($defaultSchedule) || !$defaultSchedule)
                        <div class="mb-3 p-3 bg-zinc-50 dark:bg-[#19191C] border border-zinc-200 dark:border-[#2B2B30] rounded-lg flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                            <span class="font-medium text-zinc-700 dark:text-[#EDEDF0]">
                                Tidak ada jadwal hari ini{{ isset($todayName) ? " ({$todayName})" : '' }}. Silakan pilih tanggal dan jadwal hari lain di bawah ini.
                            </span>
                            <a href="{{ route('schedules.index') }}" class="text-[#0073B1] dark:text-[#38BDF8] hover:underline font-semibold shrink-0">
                                Lihat Jadwal Hari Lain &rarr;
                            </a>
                        </div>
                    @endif
                    @if(isset($defaultSchedule) && $defaultSchedule)
                        <div x-show="!isManual" class="p-3.5 bg-zinc-50 dark:bg-[#19191C] border border-zinc-200 dark:border-[#2B2B30] rounded-lg flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <span class="font-bold text-xs text-zinc-900 dark:text-[#EDEDF0] truncate block">
                                    {{ $defaultSchedule->schoolClass->class_name }} &bull; {{ $defaultSchedule->subject }}
                                </span>
                                <p class="text-[11px] font-mono text-zinc-500 dark:text-[#97979B] mt-0.5">
                                    {{ $defaultSchedule->day_of_week }}, {{ \Carbon\Carbon::parse($defaultDate)->translatedFormat('d M Y') }} &bull; {{ $defaultSchedule->period_label }} ({{ \Carbon\Carbon::parse($defaultSchedule->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($defaultSchedule->end_time)->format('H:i') }}) &bull; {{ $defaultSchedule->room->room_name ?? 'Tanpa Ruangan' }} ({{ $config->active_cycle ?? 'Putaran 1' }})
                                </p>
                            </div>
                            <button type="button" @click="isManual = true" class="inline-flex items-center min-h-[36px] text-xs font-semibold text-zinc-600 dark:text-[#B4B6C2] hover:text-zinc-900 dark:hover:text-[#EDEDF0] underline shrink-0 cursor-pointer">
                                Ubah
                            </button>
                            <input type="hidden" name="schedule_id" :value="selectedScheduleId" :disabled="isManual" />
                            <input type="hidden" name="date" :value="selectedDate" :disabled="isManual" />
                        </div>
                    @endif

                    <div x-show="isManual" x-cloak class="p-3.5 bg-zinc-50 dark:bg-[#19191C] border border-zinc-200 dark:border-[#2B2B30] rounded-lg space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-zinc-700 dark:text-[#EDEDF0]">Pilih Jadwal &amp; Tanggal Manual</span>
                            @if(isset($defaultSchedule) && $defaultSchedule)
                                <button type="button" @click="isManual = false; selectedScheduleId = '{{ $defaultSchedule->id }}'; selectedDate = '{{ $defaultDate }}'" class="inline-flex items-center min-h-[36px] text-xs text-zinc-600 dark:text-[#B4B6C2] hover:text-zinc-900 dark:hover:text-[#EDEDF0] underline cursor-pointer">
                                    Batal (Sesuai Jadwal)
                                </button>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <x-input-label for="date" value="Tanggal Pelaksanaan" />
                                <input id="date" name="date" type="date" x-model="selectedDate" :disabled="!isManual" required data-error="date-client-error" class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-1.5 text-xs font-mono text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1]" />
                                <span id="date-client-error" class="sr-only">Tanggal pelaksanaan wajib diisi.</span>
                                <x-input-error :messages="$errors->get('date')" class="mt-1" />
                            </div>
                            @if(isset($schedules) && count($schedules) > 0)
                                <div>
                                    <x-input-label for="schedule_id" value="Jadwal Kelas &amp; Jam" />
                                    <select id="schedule_id" name="schedule_id" x-model="selectedScheduleId" :disabled="!isManual" required data-error="schedule-client-error" class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-1.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1]">
                                        <option value="" disabled>-- Pilih Jadwal Pembelajaran --</option>
                                        @foreach($schedules as $sched)
                                            <option value="{{ $sched->id }}">
                                                {{ $sched->day_of_week }} | {{ $sched->schoolClass->class_name ?? 'Kelas' }} &mdash; {{ $sched->subject }} ({{ $sched->period_label }}){{ ($sched->is_substitute ?? false) ? ' [Jadwal Tukar / Pengganti]' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span id="schedule-client-error" class="sr-only">Jadwal kelas dan jam wajib dipilih.</span>
                                    <x-input-error :messages="$errors->get('schedule_id')" class="mt-1" />
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Topic Description --}}
                <div>
                    <x-input-label for="topic_description" value="Topik & Deskripsi Pembelajaran" />
                    <textarea
                        id="topic_description"
                        name="topic_description"
                        rows="4"
                        required
                        data-error="topic-client-error"
                        placeholder="Uraikan ringkasan materi, capaian pembelajaran, atau catatan evaluasi kelas…"
                        class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] p-3 text-sm text-zinc-900 dark:text-[#EDEDF0] placeholder:text-zinc-500 dark:placeholder:text-[#9496A1] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1] shadow-none transition resize-y"
                    >{{ old('topic_description') }}</textarea>
                    <span id="topic-client-error" class="sr-only">Topik dan deskripsi pembelajaran wajib diisi.</span>
                    <x-input-error :messages="$errors->get('topic_description')" class="mt-1.5" />
                </div>

                {{-- Presensi Siswa (Default Hadir, Keterangan Hanya Sakit/Izin/Alpa) --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <x-input-label value="Presensi Siswa (Default: Seluruh Siswa Hadir)" />
                        <span class="text-[11px] font-mono text-zinc-500 dark:text-[#97979B]" x-text="absentStudents.length > 0 ? (absentStudents.length + ' tidak hadir') : 'Semua hadir'"></span>
                    </div>

                    <div class="p-3.5 bg-zinc-50 dark:bg-[#19191C] border border-zinc-200 dark:border-[#2B2B30] rounded-lg space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                            <span class="text-zinc-600 dark:text-[#97979B] text-[11px] leading-relaxed">
                                Siswa otomatis berstatus <strong class="text-emerald-700 dark:text-emerald-400 font-bold">Hadir</strong>. Catat hanya siswa yang tidak hadir atau izin:
                            </span>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button" @click="addAbsentStudent('Sakit', '')" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white dark:bg-[#232327] border border-zinc-300 dark:border-[#2B2B30] text-[11px] font-semibold text-zinc-700 dark:text-[#EDEDF0] hover:text-zinc-900 dark:hover:text-white rounded-md hover:bg-zinc-100 dark:hover:bg-[#2C2C30] transition cursor-pointer shadow-none">
                                    <svg aria-hidden="true" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    + Catat Tidak Hadir
                                </button>
                                <button type="button" @click="addLeavingMidClass()" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-amber-50 dark:bg-[#282115] border border-amber-300 dark:border-amber-700/60 text-[11px] font-bold text-amber-900 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-[#382F1E] rounded-md transition cursor-pointer shadow-none">
                                    <svg aria-hidden="true" class="w-3 h-3 text-amber-700 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                                    + Izin Keluar di Tengah KBM
                                </button>
                            </div>
                        </div>

                        {{-- Nihil Message --}}
                        <div x-show="absentStudents.length === 0" class="text-[11px] font-mono text-emerald-800 dark:text-emerald-300 bg-emerald-50 dark:bg-[#16281D] border border-emerald-300 dark:border-emerald-700/60 p-2.5 rounded text-center">
                            &check; Seluruh siswa hadir (Nihil catatan ketidakhadiran)
                        </div>

                        {{-- Absent List --}}
                        <div class="space-y-2.5" x-show="absentStudents.length > 0">
                            <template x-for="(item, idx) in absentStudents" :key="idx">
                                <div class="p-3 bg-zinc-50 dark:bg-[#232327] border border-zinc-200 dark:border-[#2B2B30] rounded-lg shadow-sm space-y-2" :class="item.is_approved_leave ? 'ring-1 ring-sky-500/40 border-sky-300 dark:border-sky-600' : ''">
                                    {{-- Banner Izin yang disetujui BK / Wali --}}
                                    <div x-show="item.is_approved_leave" class="flex flex-wrap items-center justify-between gap-1.5 text-[11px] font-mono text-blue-700 dark:text-sky-300 bg-blue-50/80 dark:bg-[#16222F] px-2.5 py-1.5 rounded border border-blue-200/80 dark:border-sky-700/50">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <svg aria-hidden="true" class="w-3.5 h-3.5 shrink-0 text-blue-600 dark:text-sky-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span class="font-bold shrink-0" x-text="item.decision_label ? item.decision_label : (item.status_approval === 'Approved' ? 'Izin Resmi Disetujui' : 'Menunggu Persetujuan BK/Wali')"></span>:
                                            <span class="truncate max-w-xs text-zinc-700 dark:text-[#EDEDF0]" x-text="item.leave_reason"></span>
                                        </div>
                                        <span class="text-[10px] text-blue-600/90 dark:text-sky-400 font-semibold">(Otomatis Terpilih <strong x-text="item.status"></strong>)</span>
                                    </div>

                                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <span class="font-mono text-zinc-600 dark:text-[#9496A1] text-xs font-bold w-5" x-text="(idx + 1) + '.'"></span>
                                        </div>

                                        {{-- Student Selection / Display --}}
                                        <template x-if="item.is_approved_leave">
                                            <div class="flex-1 w-full min-w-0 flex items-center justify-between gap-2 px-3 py-1.5 rounded-md bg-white dark:bg-[#19191C] border border-blue-200 dark:border-sky-800/60">
                                                <div class="flex items-center gap-2 truncate">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider"
                                                        :class="item.category === 'Sakit' ? 'bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300' : (item.category === 'Dispensasi' ? 'bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-sky-300' : 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300')"
                                                        x-text="item.category || item.status">
                                                    </span>
                                                    <span class="font-bold text-xs text-zinc-900 dark:text-[#EDEDF0] truncate" x-text="item.student_name ? (item.student_name + (item.student_username ? ' (' + item.student_username + ')' : '')) : getStudentName(item.student_id)"></span>
                                                </div>
                                                <span class="text-[11px] font-mono text-zinc-600 dark:text-[#9496A1] shrink-0" x-text="item.status_approval === 'Approved' ? 'Resmi Disetujui' : 'Menunggu BK/Wali'"></span>
                                                <input type="hidden" :name="'absent_students[' + idx + '][student_id]'" :value="item.student_id">
                                                <input type="hidden" :name="'absent_students[' + idx + '][leave_id]'" :value="item.leave_id">
                                            </div>
                                        </template>
                                        <template x-if="!item.is_approved_leave">
                                            <div class="flex-1 w-full min-w-0">
                                                <select :name="'absent_students[' + idx + '][student_id]'" x-model="item.student_id" :aria-label="'Pilih siswa tidak hadir ke-' + (idx + 1)" required class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] text-xs py-1.5 px-2.5 text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1]">
                                                    <option value="">-- Pilih Siswa --</option>
                                                    <template x-for="std in currentStudents" :key="std.id">
                                                        <option :value="std.id" x-text="std.fullname + (std.username ? ' (' + std.username + ')' : '')" :selected="item.student_id == std.id"></option>
                                                    </template>
                                                </select>
                                            </div>
                                        </template>

                                        <div class="w-full sm:w-36 shrink-0">
                                            <select :name="'absent_students[' + idx + '][status]'" x-model="item.status" :aria-label="'Status kehadiran siswa ke-' + (idx + 1)" required class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] text-xs py-1.5 px-2.5 font-semibold text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1]">
                                                <option value="Izin">Izin</option>
                                                <option value="Sakit">Sakit</option>
                                                <option value="Alpa">Alpa</option>
                                                <option value="Terlambat">Terlambat</option>
                                            </select>
                                        </div>
                                        <button type="button" @click="removeAbsentStudent(idx)" :aria-label="item.is_approved_leave ? 'Abaikan izin resmi siswa ke-' + (idx + 1) : 'Hapus catatan siswa ke-' + (idx + 1)" class="self-end sm:self-auto inline-flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[32px] sm:min-w-[32px] text-zinc-600 dark:text-[#9496A1] hover:text-rose-600 dark:hover:text-rose-400 p-1.5 transition cursor-pointer rounded hover:bg-rose-50 dark:hover:bg-rose-500/10" :title="item.is_approved_leave ? 'Abaikan izin resmi / tandai hadir' : 'Hapus Catatan'">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>

                                    {{-- Catatan / Alasan Keluar Kelas dengan Quick Chips --}}
                                    <div class="pt-1.5 border-t border-zinc-100 dark:border-[#2B2B30] space-y-1.5">
                                        <div class="flex flex-wrap items-center justify-between gap-1.5">
                                            <label :for="'absent-notes-' + idx" class="text-[11px] font-bold text-zinc-600 dark:text-[#B4B6C2] uppercase tracking-wider font-mono">
                                                Alasan / Catatan Keluar Kelas:
                                            </label>
                                            <div class="flex flex-wrap items-center gap-1.5 text-xs">
                                                <span class="text-zinc-600 dark:text-[#9496A1] font-mono text-[11px]">Pilih Cepat:</span>
                                                <button type="button" @click="setPreset(idx, 'Sakit', 'Izin keluar kelas: sakit ke UKS')" class="min-h-[28px] px-2 py-1 rounded bg-purple-50 dark:bg-[#25182D] text-purple-800 dark:text-purple-300 hover:bg-purple-100 dark:hover:bg-[#352240] border border-purple-200 dark:border-purple-700/60 transition font-mono text-[11px] cursor-pointer">
                                                    + Ke UKS (Sakit)
                                                </button>
                                                <button type="button" @click="setPreset(idx, 'Izin', 'Izin keluar kelas: dipanggil Guru BK')" class="min-h-[28px] px-2 py-1 rounded bg-blue-50 dark:bg-[#16222F] text-blue-800 dark:text-sky-300 hover:bg-blue-100 dark:hover:bg-[#1E3042] border border-blue-200 dark:border-sky-700/60 transition font-mono text-[11px] cursor-pointer">
                                                    + Dipanggil BK
                                                </button>
                                                <button type="button" @click="setPreset(idx, 'Izin', 'Izin keluar kelas: dispensasi tugas/lomba sekolah')" class="min-h-[28px] px-2 py-1 rounded bg-blue-50 dark:bg-[#16222F] text-blue-800 dark:text-sky-300 hover:bg-blue-100 dark:hover:bg-[#1E3042] border border-blue-200 dark:border-sky-700/60 transition font-mono text-[11px] cursor-pointer">
                                                    + Dispensasi
                                                </button>
                                                <button type="button" @click="setPreset(idx, 'Izin', 'Izin keluar kelas: pulang lebih awal (dijemput)')" class="min-h-[28px] px-2 py-1 rounded bg-amber-50 dark:bg-[#282115] text-amber-800 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-[#382F1E] border border-amber-200 dark:border-amber-700/60 transition font-mono text-[11px] cursor-pointer">
                                                    + Pulang Cepat
                                                </button>
                                                <button type="button" @click="setPreset(idx, 'Alpa', 'Meninggalkan kelas tanpa izin di tengah KBM')" class="min-h-[28px] px-2 py-1 rounded bg-rose-50 dark:bg-[#2D1618] text-rose-800 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-[#3D1E20] border border-rose-200 dark:border-rose-700/60 transition font-mono text-[11px] cursor-pointer">
                                                    + Tanpa Izin
                                                </button>
                                            </div>
                                        </div>
                                        <input :id="'absent-notes-' + idx" type="text" :name="'absent_students[' + idx + '][notes]'" x-model="item.notes" placeholder="Tuliskan alasan keluar kelas (misal: sakit perut ke UKS jam ke-3, dipanggil BK, dll)…" class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] text-xs py-1.5 px-3 text-zinc-900 dark:text-[#EDEDF0] placeholder:text-zinc-500 dark:placeholder:text-[#9496A1] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1]">
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Documentation Photo Upload --}}
                <div>
                    <x-input-label for="photo" value="Dokumentasi Foto KBM (Wajib, Maks 5MB, Auto Compress) *" />
                    
                    <div class="relative">
                        <label for="photo" class="group flex flex-col items-center justify-center w-full min-h-[120px] rounded-lg border border-dashed border-zinc-300 dark:border-[#2B2B30] bg-zinc-50/50 dark:bg-[#19191C] hover:bg-zinc-100/50 dark:hover:bg-[#232327] hover:border-zinc-400 dark:hover:border-[#3A3A3F] focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-[var(--ring-focus)] transition cursor-pointer p-4 text-center">
                            
                            {{-- Preview Image if Selected --}}
                            <template x-if="photoPreview">
                                <div class="relative w-full max-h-48 rounded overflow-hidden mb-2">
                                    <img :src="photoPreview" width="640" height="192" decoding="async" class="w-full h-48 object-cover rounded border border-zinc-200 dark:border-[#2B2B30]" alt="Preview dokumentasi" />
                                    <button type="button" aria-label="Hapus pratinjau foto dokumentasi" @click.prevent="photoPreview = null; fileName = ''; fileError = ''; $refs.photoInput.value = ''" class="absolute top-2 right-2 min-h-[32px] min-w-[32px] inline-flex items-center justify-center bg-zinc-900/80 hover:bg-black text-white rounded p-1 transition cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>

                            {{-- Dropzone Guide --}}
                            <template x-if="!photoPreview">
                                <div class="flex flex-col items-center">
                                    <svg aria-hidden="true" class="w-6 h-6 text-zinc-600 dark:text-[#9496A1] group-hover:text-zinc-800 dark:group-hover:text-[#F1F1F4] mb-2 transition" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                                    </svg>
                                    <p class="text-xs font-semibold text-zinc-700 dark:text-[#EDEDF0]">
                                        Unggah foto bukti pembelajaran (JPG, PNG)
                                    </p>
                                    <p id="photo-help" class="mt-1 text-[11px] font-mono text-zinc-600 dark:text-[#9496A1]" x-text="fileName ? fileName : 'Maksimal ukuran file: 5MB (Otomatis Diresize &amp; Kompres)'"></p>
                                </div>
                            </template>

                            <input
                                x-ref="photoInput"
                                id="photo"
                                name="photo"
                                type="file"
                                accept="image/jpeg,image/png,image/jpg"
                                class="visually-hidden"
                                data-error="photo-client-error"
                                aria-describedby="photo-help"
                                required
                                @change="
                                    const file = $event.target.files[0];
                                    fileError = '';
                                    $event.target.setCustomValidity('');
                                    if (file) {
                                        if (file.size > 5 * 1024 * 1024) {
                                            fileError = 'Ukuran foto melebihi 5MB.';
                                            $event.target.value = '';
                                            $event.target.setCustomValidity(fileError);
                                            fileName = '';
                                            photoPreview = null;
                                        } else {
                                            fileName = file.name;
                                            const reader = new FileReader();
                                            reader.onload = (e) => { photoPreview = e.target.result; };
                                            reader.readAsDataURL(file);
                                        }
                                    } else {
                                        fileName = '';
                                        photoPreview = null;
                                    }
                                "
                            />
                        </label>
                    </div>
                    <p id="photo-client-error" class="text-xs text-rose-700 dark:text-rose-400 mt-1.5 font-medium" x-show="fileError" x-text="fileError"></p>
                    <x-input-error :messages="$errors->get('photo')" class="mt-1.5" />
                </div>

                {{-- Action Submit --}}
                <div class="pt-2">
                    @if(isset($fillTimeCheck) && !$fillTimeCheck['allowed'])
                        <button type="button" disabled class="w-full min-h-[44px] inline-flex items-center justify-center gap-2 px-5 py-3 bg-zinc-300 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 font-semibold text-xs uppercase tracking-wider rounded-lg opacity-75 cursor-not-allowed">
                            <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                            Belum Dapat Diisi (Menunggu Jam Pelajaran)
                        </button>
                    @else
                        <button type="submit" class="w-full min-h-[44px] inline-flex items-center justify-center gap-2 px-5 py-3 bg-[#0073B1] hover:bg-[#005D8F] active:bg-[#004A73] text-white font-semibold text-xs uppercase tracking-wider rounded-lg transition shadow-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0073B1] focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-[#19191C] cursor-pointer">
                            <svg aria-hidden="true" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            Simpan Jurnal &amp; Sinkronkan Presensi
                        </button>
                    @endif
                </div>
            </form>
        </section>

    </div>
</x-app-layout>