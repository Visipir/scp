<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="text-xs font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider">Manajemen Jadwal</div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0] mt-0.5">
                    Edit Jadwal Pelajaran (Sistem Blok Jam)
                </h1>
            </div>
            <a href="{{ route('schedules.index') }}" class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0]">
                &larr; Kembali
            </a>
        </div>
    </x-slot>

    <div 
        class="max-w-3xl space-y-6"
        x-data="{
            matrix: {{ json_encode($scheduleMatrix) }},
            cycle: '{{ old('cycle_type', $schedule->cycle_type) }}',
            day: '{{ old('day_of_week', $schedule->day_of_week) }}',
            startPeriod: '{{ old('start_period', $schedule->start_period ?? 1) }}',
            endPeriod: '{{ old('end_period', $schedule->end_period ?? 3) }}',
            startTime: '{{ old('start_time', \Carbon\Carbon::parse($schedule->start_time)->format('H:i')) }}',
            endTime: '{{ old('end_time', \Carbon\Carbon::parse($schedule->end_time)->format('H:i')) }}',
            manualTime: false,
            get currentPeriods() {
                if (this.matrix[this.cycle] && this.matrix[this.cycle].periods[this.day]) {
                    return this.matrix[this.cycle].periods[this.day];
                }
                if (this.matrix[this.cycle] && this.matrix[this.cycle].periods) {
                    return this.matrix[this.cycle].periods;
                }
                return {};
            },
            get currentBreaks() {
                if (this.matrix[this.cycle] && this.matrix[this.cycle].breaks[this.day]) {
                    return this.matrix[this.cycle].breaks[this.day];
                }
                if (this.matrix[this.cycle] && this.matrix[this.cycle].breaks) {
                    return this.matrix[this.cycle].breaks;
                }
                return [];
            },
            onContextChange() {
                const periods = this.currentPeriods;
                const keys = Object.keys(periods).map(k => parseInt(k));
                if (keys.length > 0) {
                    const maxKey = Math.max(...keys);
                    const minKey = Math.min(...keys);
                    if (parseInt(this.startPeriod) > maxKey || parseInt(this.startPeriod) < minKey) {
                        this.startPeriod = minKey;
                    }
                    if (parseInt(this.endPeriod) > maxKey || parseInt(this.endPeriod) < parseInt(this.startPeriod)) {
                        this.endPeriod = Math.min(parseInt(this.startPeriod) + 2, maxKey);
                    }
                }
                this.updateTimes();
            },
            updateTimes() {
                const periods = this.currentPeriods;
                if (periods[this.startPeriod]) {
                    this.startTime = periods[this.startPeriod].start_time;
                }
                if (parseInt(this.endPeriod) < parseInt(this.startPeriod)) {
                    this.endPeriod = this.startPeriod;
                }
                if (periods[this.endPeriod]) {
                    this.endTime = periods[this.endPeriod].end_time;
                }
            },
            get totalJp() {
                return Math.max(1, (parseInt(this.endPeriod) - parseInt(this.startPeriod) + 1));
            },
            get totalMinutes() {
                let total = 0;
                const periods = this.currentPeriods;
                for (let i = parseInt(this.startPeriod); i <= parseInt(this.endPeriod); i++) {
                    if (periods[i]) {
                        total += (periods[i].duration || 35);
                    }
                }
                return total;
            }
        }"
        x-init="updateTimes()"
    >
        {{-- Ringkasan Struktur Blok Jam Aktif (Dinamis per Hari & Putaran) --}}
        <div class="bg-zinc-50 dark:bg-[#1E1E22] border border-zinc-200/80 dark:border-[#2B2B30] rounded-lg p-4 font-mono text-xs text-zinc-700 dark:text-[#EDEDF0]">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-zinc-200/60 dark:border-[#2B2B30] pb-2.5 mb-2.5">
                <span class="font-bold text-zinc-900 dark:text-[#EDEDF0] flex items-center gap-2">
                    <span>Struktur Waktu Operasional: <span class="text-zinc-900 dark:text-[#EDEDF0] font-extrabold" x-text="cycle + ' — Hari ' + day"></span></span>
                </span>
                <span class="text-[11px] text-zinc-500 font-semibold" x-show="cycle === 'Putaran 2'">
                    <span x-show="day === 'Senin'">Upacara Bendera: 07.00–08.00 (Non-KBM)</span>
                    <span x-show="day === 'Selasa'">Literasi: 07.00–07.30 (Non-KBM)</span>
                    <span x-show="day === 'Rabu' || day === 'Kamis'">KBM Mulai: 07.15 WIB</span>
                    <span x-show="day === 'Jumat'">Total: 8 JP (Mulai 07.15 WIB)</span>
                </span>
            </div>

            <!-- List of breaks & non-KBM for current day -->
            <div class="flex flex-wrap gap-2 text-[11px]">
                <template x-for="brk in currentBreaks" :key="brk.name">
                    <div class="px-2.5 py-1.5 rounded border border-amber-200/80 bg-amber-50/80 text-amber-900 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        <span class="font-bold" x-text="brk.name"></span>:
                        <span x-text="brk.start_time + '–' + brk.end_time + ' (' + brk.duration + 'm)'"></span>
                    </div>
                </template>
            </div>
        </div>

        <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200/80 dark:border-[#2B2B30] rounded-lg p-6 sm:p-8 shadow-none">
            <form method="POST" action="{{ route('schedules.update', $schedule) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <!-- Hidden start_time and end_time automatically synced from periods -->
                <input type="hidden" name="start_time" :value="startTime" />
                <input type="hidden" name="end_time" :value="endTime" />

                <div>
                    <x-input-label for="subject" value="Mata Pelajaran" />
                    <x-text-input id="subject" name="subject" type="text" :value="old('subject', $schedule->subject)" required />
                    <x-input-error :messages="$errors->get('subject')" class="mt-1.5" />
                </div>

                @if(auth()->user()->role_id === 1)
                    <div>
                        <x-input-label for="teacher_id" value="Guru Pengajar" />
                        <select id="teacher_id" name="teacher_id" class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-2 text-sm text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none transition" required>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected(old('teacher_id', $schedule->teacher_id) == $teacher->id)>
                                    {{ $teacher->fullname }} ({{ $teacher->username }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('teacher_id')" class="mt-1.5" />
                    </div>
                @else
                    <div>
                        <x-input-label value="Guru Pengajar" />
                        <input type="text" class="w-full rounded-md border-zinc-200 dark:border-[#2B2B30] bg-zinc-50 dark:bg-[#19191C] px-3 py-2 text-sm text-zinc-500 dark:text-[#97979B] cursor-not-allowed shadow-none" value="{{ $schedule->teacher->fullname }}" readonly disabled />
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="class_id" value="Kelas" />
                        <select id="class_id" name="class_id" class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-2 text-sm text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none transition" required>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" @selected(old('class_id', $schedule->class_id) == $class->id)>{{ $class->class_name }} {{ $class->academic_year ? '('.$class->academic_year.')' : '' }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('class_id')" class="mt-1.5" />
                    </div>
                    <div>
                        <x-input-label for="room_id" value="Ruangan" />
                        <select id="room_id" name="room_id" class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-2 text-sm text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none transition">
                            <option value="">-- Tanpa Ruangan --</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}" @selected(old('room_id', $schedule->room_id) == $room->id)>{{ $room->room_name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('room_id')" class="mt-1.5" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="cycle_type" value="Siklus Putaran" />
                        <select 
                            id="cycle_type" 
                            name="cycle_type" 
                            x-model="cycle" 
                            @change="onContextChange()" 
                            class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-2 text-sm font-mono text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none transition" 
                            required
                        >
                            @foreach(['Putaran 1','Putaran 2'] as $c)
                                <option value="{{ $c }}" @selected($schedule->cycle_type === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('cycle_type')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label for="day_of_week" value="Hari" />
                        <select 
                            id="day_of_week" 
                            name="day_of_week" 
                            x-model="day" 
                            @change="onContextChange()" 
                            class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-2 text-sm text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none transition" 
                            required
                        >
                            @foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $d)
                                <option value="{{ $d }}" @selected($schedule->day_of_week === $d)>{{ $d }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('day_of_week')" class="mt-1.5" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Pilihan Jam Pelajaran Mulai (Blok Jam) -->
                    <div>
                        <x-input-label for="start_period" value="Jam Ke (Mulai)" />
                        <select 
                            id="start_period" 
                            name="start_period" 
                            x-model="startPeriod" 
                            @change="updateTimes()" 
                            class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-2 text-sm font-mono text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none transition" 
                            required
                        >
                            <template x-for="(p, num) in currentPeriods" :key="'edit-start-' + num">
                                <option :value="num" x-text="p.label" :selected="num == startPeriod"></option>
                            </template>
                        </select>
                        <x-input-error :messages="$errors->get('start_period')" class="mt-1.5" />
                        <x-input-error :messages="$errors->get('start_time')" class="mt-1.5" />
                    </div>

                    <!-- Pilihan Jam Pelajaran Selesai (Blok Jam) -->
                    <div>
                        <x-input-label for="end_period" value="Jam Ke (Selesai)" />
                        <select 
                            id="end_period" 
                            name="end_period" 
                            x-model="endPeriod" 
                            @change="updateTimes()" 
                            class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-2 text-sm font-mono text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none transition" 
                            required
                        >
                            <template x-for="(p, num) in currentPeriods" :key="'edit-end-' + num">
                                <option 
                                    :value="num" 
                                    x-text="'Jam ' + num + ' (s.d ' + p.end_time + ')'" 
                                    :disabled="parseInt(num) < parseInt(startPeriod)"
                                    :selected="num == endPeriod"
                                ></option>
                            </template>
                        </select>
                        <x-input-error :messages="$errors->get('end_period')" class="mt-1.5" />
                        <x-input-error :messages="$errors->get('end_time')" class="mt-1.5" />
                    </div>
                </div>

                {{-- Toggle Manual Jam & Waktu KBM --}}
                <div class="pt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="manualTime" class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900">
                        <span class="text-xs font-semibold text-zinc-700">Opsi: Set Jam &amp; Waktu Secara Manual (Kustom)</span>
                    </label>
                </div>

                <div x-show="manualTime" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-3.5 bg-zinc-50 dark:bg-[#232327] border border-zinc-200 dark:border-[#2B2B30] rounded-md">
                    <div>
                        <x-input-label for="manual_edit_start_time" value="Waktu Mulai Manual (HH:mm)" />
                        <input type="time" id="manual_edit_start_time" x-model="startTime" class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-2 text-sm font-mono text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd]" />
                    </div>
                    <div>
                        <x-input-label for="manual_edit_end_time" value="Waktu Selesai Manual (HH:mm)" />
                        <input type="time" id="manual_edit_end_time" x-model="endTime" class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-2 text-sm font-mono text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd]" />
                    </div>
                </div>

                <!-- Live Period Summary Card -->
                <div class="p-3.5 bg-zinc-50 dark:bg-[#232327] border border-zinc-200/80 dark:border-[#2B2B30] rounded-md flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs font-mono">
                    <div class="flex items-center gap-2">
                        <span class="text-zinc-400">Alokasi Waktu:</span>
                        <span class="font-bold text-zinc-900 dark:text-[#EDEDF0]" x-text="'Jam ' + startPeriod + (startPeriod != endPeriod ? ' – ' + endPeriod : '')"></span>
                        <span class="text-zinc-400">&bull;</span>
                        <span class="font-bold text-zinc-700 dark:text-[#EDEDF0]" x-text="startTime + ' – ' + endTime + ' WIB'"></span>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-zinc-200 dark:bg-[#19191C] text-zinc-900 dark:text-[#EDEDF0] border border-transparent dark:border-[#2B2B30] font-bold self-start sm:self-auto" x-text="totalJp + ' JP (' + totalMinutes + ' Menit)'">
                    </span>
                </div>

                <div class="flex items-center gap-3 pt-3 border-t border-zinc-100 dark:border-[#2B2B30]">
                    <x-primary-button>Perbarui Jadwal</x-primary-button>
                    <a href="{{ route('schedules.index') }}" class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] px-3 py-2">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
