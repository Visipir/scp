<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="text-[10px] font-bold font-mono text-zinc-400 uppercase tracking-widest">Ruang Guru</div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 mt-0.5">
                    Dashboard Guru & Jurnal Mengajar
                </h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('journals.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent border border-[#2B2B30] text-xs font-semibold uppercase tracking-wider text-[#EDEDF0] rounded-lg hover:bg-[#26262A] transition">
                    <svg class="w-3.5 h-3.5 text-[#97979B]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                    Riwayat Jurnal
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6 max-w-4xl">

        {{-- KBM DILIBURKAN BANNER --}}
        @if($kbmSuspendedEvent ?? false)
            <div class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-5">
                <div class="flex items-start gap-3.5">
                    <span class="inline-flex items-center justify-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded font-mono shrink-0 mt-0.5 bg-amber-500/20 text-amber-300 border border-amber-500/30">
                        KBM DILIBURKAN
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-amber-300 tracking-tight">
                            Hari ini: {{ ucwords(str_replace("_", " ", $kbmSuspendedEvent->category)) }} - {{ $kbmSuspendedEvent->title }}
                        </h3>
                        <p class="text-xs text-amber-200/80 mt-1">
                            Keterangan: <span class="font-semibold">{{ $kbmSuspendedEvent->description ?? "Tidak ada pembelajaran reguler." }}</span>.
                            Guru tidak diwajibkan mengirimkan jurnal pembelajaran harian untuk hari ini.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        {{-- 1. SWISS MINIMALIST NOTIFICATION ALERTS (Unfilled Journals) --}}
        @if(!empty($alerts))
            <div class="space-y-3">
                @foreach($alerts as $schedule)
                    <div class="bg-[#FD366E]/10 border border-[#FD366E]/30 rounded-xl p-5">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-start gap-3.5">
                                <span class="inline-flex items-center justify-center px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded font-mono shrink-0 mt-0.5 bg-[#FD366E] text-white">
                                    PERINGATAN
                                </span>
                                <div>
                                    <h3 class="text-sm font-bold text-white tracking-tight">
                                        Jurnal KBM Belum Diisi!
                                    </h3>
                                    <p class="text-xs text-[#EDEDF0] mt-1 font-medium">
                                        <span>{{ $schedule->subject }}</span> &mdash;
                                        <span class="font-bold">{{ $schedule->schoolClass?->class_name ?? '-' }}</span> &bull;
                                        <span>Ruang {{ $schedule->room?->room_name ?? '-' }}</span>
                                    </p>
                                    <p class="text-[11px] font-mono text-[#FD366E] mt-1">
                                        Selesai pukul {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }} (melewati batas toleransi 30 menit)
                                    </p>
                                </div>
                            </div>
                            <a href="{{ route('journals.create') }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-[#FD366E] hover:bg-[#E0285D] text-white text-xs font-semibold uppercase tracking-wider rounded-lg transition shrink-0 cursor-pointer shadow-none">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                Isi Sekarang
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- 2. SWISS MINIMALIST JOURNAL FORM CARD --}}
        <div class="bg-white border border-zinc-200/80 rounded-lg p-6 sm:p-8">
            <div class="border-b border-zinc-200/80 pb-4 mb-6">
                <h2 class="text-base font-bold text-zinc-900 tracking-tight">
                    Input Jurnal Pembelajaran Harian
                </h2>
                <p class="text-xs text-zinc-500 mt-0.5">
                    Dokumentasikan pelaksanaan kegiatan belajar mengajar secara objektif dan akurat.
                </p>
            </div>

            <form method="POST" action="{{ route('journals.store') }}" enctype="multipart/form-data" class="space-y-6" 
                x-data="{ 
                    fileName: '', 
                    photoPreview: null, 
                    fileError: '',
                    isManual: {{ old('is_manual') || (isset($errors) && ($errors->has('schedule_id') || $errors->has('date'))) || !isset($defaultSchedule) || !$defaultSchedule ? 'true' : 'false' }},
                    selectedScheduleId: '{{ old('schedule_id', $defaultSchedule?->id) }}',
                    selectedDate: '{{ old('date', $defaultDate ?? now()->format('Y-m-d')) }}',
                    schedulesMap: {{ Js::from($schedules->mapWithKeys(fn($s) => [$s->id => ['id' => $s->id, 'class_id' => $s->class_id, 'class_name' => $s->schoolClass->class_name ?? '']])) }},
                    studentsByClass: {{ Js::from($studentsByClass ?? []) }},
                    absentStudents: {{ Js::from(old('absent_students', [])) }},
                    get currentClassId() {
                        return this.schedulesMap[this.selectedScheduleId]?.class_id;
                    },
                    get currentStudents() {
                        return this.studentsByClass[this.currentClassId] || [];
                    },
                    addAbsentStudent() {
                        this.absentStudents.push({ student_id: '', status: 'Sakit' });
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
                    @if(isset($defaultSchedule) && $defaultSchedule)
                        <div x-show="!isManual" class="p-3.5 bg-zinc-50 border border-zinc-200 rounded-lg flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <span class="font-bold text-xs text-zinc-900 truncate block">
                                    {{ $defaultSchedule->schoolClass->class_name }} &bull; {{ $defaultSchedule->subject }}
                                </span>
                                <p class="text-[11px] font-mono text-zinc-500 mt-0.5">
                                    {{ $defaultSchedule->day_of_week }}, {{ \Carbon\Carbon::parse($defaultDate)->translatedFormat('d M Y') }} &bull; {{ $defaultSchedule->period_label }} ({{ \Carbon\Carbon::parse($defaultSchedule->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($defaultSchedule->end_time)->format('H:i') }}) &bull; {{ $defaultSchedule->room->room_name ?? 'Tanpa Ruangan' }} ({{ $config->active_cycle ?? 'Putaran 1' }})
                                </p>
                            </div>
                            <button type="button" @click="isManual = true" class="text-xs font-semibold text-zinc-600 hover:text-zinc-900 underline shrink-0 cursor-pointer">
                                Ubah
                            </button>
                            <input type="hidden" name="schedule_id" :value="selectedScheduleId" :disabled="isManual" />
                            <input type="hidden" name="date" :value="selectedDate" :disabled="isManual" />
                        </div>
                    @endif

                    <div x-show="isManual" x-cloak class="p-3.5 bg-zinc-50 border border-zinc-200 rounded-lg space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-zinc-700">Pilih Jadwal &amp; Tanggal Manual</span>
                            @if(isset($defaultSchedule) && $defaultSchedule)
                                <button type="button" @click="isManual = false; selectedScheduleId = '{{ $defaultSchedule->id }}'; selectedDate = '{{ $defaultDate }}'" class="text-xs text-zinc-500 hover:text-zinc-900 underline cursor-pointer">
                                    Batal (Sesuai Jadwal)
                                </button>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <x-input-label for="date" value="Tanggal Pelaksanaan" />
                                <input id="date" name="date" type="date" x-model="selectedDate" :disabled="!isManual" required class="w-full rounded-md border-zinc-300 bg-white px-3 py-1.5 text-xs font-mono text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900" />
                                <x-input-error :messages="$errors->get('date')" class="mt-1" />
                            </div>
                            @if(isset($schedules) && count($schedules) > 0)
                                <div>
                                    <x-input-label for="schedule_id" value="Jadwal Kelas &amp; Jam" />
                                    <select id="schedule_id" name="schedule_id" x-model="selectedScheduleId" :disabled="!isManual" required class="w-full rounded-md border-zinc-300 bg-white px-3 py-1.5 text-xs text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
                                        <option value="" disabled>-- Pilih Jadwal Pembelajaran --</option>
                                        @foreach($schedules as $sched)
                                            <option value="{{ $sched->id }}">
                                                {{ $sched->day_of_week }} | {{ $sched->schoolClass->class_name ?? 'Kelas' }} &mdash; {{ $sched->subject }} ({{ $sched->period_label }})
                                            </option>
                                        @endforeach
                                    </select>
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
                        placeholder="Uraikan ringkasan materi, capaian pembelajaran, atau catatan evaluasi kelas..."
                        class="w-full rounded-md border-zinc-300 bg-white p-3 text-sm text-zinc-900 placeholder:text-zinc-400 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition resize-y"
                    >{{ old('topic_description') }}</textarea>
                    <x-input-error :messages="$errors->get('topic_description')" class="mt-1.5" />
                </div>

                {{-- Presensi Siswa (Default Hadir, Keterangan Hanya Sakit/Izin/Alpa) --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <x-input-label value="Presensi Siswa (Default: Seluruh Siswa Hadir)" />
                        <span class="text-[11px] font-mono text-zinc-500" x-text="absentStudents.length > 0 ? (absentStudents.length + ' tidak hadir') : 'Semua hadir'"></span>
                    </div>

                    <div class="p-3.5 bg-zinc-50 border border-zinc-200 rounded-lg space-y-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-zinc-600 text-[11px]">
                                Siswa otomatis berstatus <strong class="text-emerald-700">Hadir</strong>. Catat hanya siswa yang tidak hadir:
                            </span>
                            <button type="button" @click="addAbsentStudent()" class="inline-flex items-center gap-1 px-2.5 py-1 bg-white border border-zinc-300 text-[11px] font-semibold text-zinc-700 hover:text-zinc-900 rounded hover:bg-zinc-100 transition cursor-pointer">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                + Catat Tidak Hadir
                            </button>
                        </div>

                        {{-- Nihil Message --}}
                        <div x-show="absentStudents.length === 0" class="text-[11px] font-mono text-emerald-800 bg-emerald-50/70 border border-emerald-200/70 p-2.5 rounded text-center">
                            &check; Seluruh siswa hadir (Nihil catatan ketidakhadiran)
                        </div>

                        {{-- Absent List --}}
                        <div class="space-y-2" x-show="absentStudents.length > 0">
                            <template x-for="(item, idx) in absentStudents" :key="idx">
                                <div class="flex items-center gap-2 p-2 bg-white border border-zinc-200 rounded-md">
                                    <div class="flex-1 min-w-0">
                                        <select :name="'absent_students[' + idx + '][student_id]'" x-model="item.student_id" required class="w-full rounded border-zinc-300 text-xs py-1 px-2 text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
                                            <option value="">-- Pilih Siswa --</option>
                                            <template x-for="std in currentStudents" :key="std.id">
                                                <option :value="std.id" x-text="std.fullname" :selected="item.student_id == std.id"></option>
                                            </template>
                                        </select>
                                    </div>
                                    <div class="w-28 shrink-0">
                                        <select :name="'absent_students[' + idx + '][status]'" x-model="item.status" required class="w-full rounded border-zinc-300 text-xs py-1 px-2 font-semibold text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
                                            <option value="Sakit">Sakit</option>
                                            <option value="Izin">Izin</option>
                                            <option value="Alpa">Alpa</option>
                                        </select>
                                    </div>
                                    <button type="button" @click="removeAbsentStudent(idx)" class="text-zinc-400 hover:text-rose-600 p-1 transition cursor-pointer" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Documentation Photo Upload --}}
                <div>
                    <x-input-label for="photo" value="Dokumentasi Foto KBM (Wajib, Maks 5MB, Auto Compress) *" />
                    
                    <div class="relative">
                        <label for="photo" class="group flex flex-col items-center justify-center w-full min-h-[120px] rounded-lg border border-dashed border-zinc-300 bg-zinc-50/50 hover:bg-zinc-100 hover:border-zinc-400 transition cursor-pointer p-4 text-center">
                            
                            {{-- Preview Image if Selected --}}
                            <template x-if="photoPreview">
                                <div class="relative w-full max-h-48 rounded overflow-hidden mb-2">
                                    <img :src="photoPreview" class="w-full h-48 object-cover rounded border border-zinc-200" alt="Preview dokumentasi" />
                                    <button type="button" @click.prevent="photoPreview = null; fileName = ''; fileError = ''; $refs.photoInput.value = ''" class="absolute top-2 right-2 bg-zinc-900/80 hover:bg-black text-white rounded p-1 transition">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>

                            {{-- Dropzone Guide --}}
                            <template x-if="!photoPreview">
                                <div class="flex flex-col items-center">
                                    <svg class="w-6 h-6 text-zinc-400 group-hover:text-zinc-600 mb-2 transition" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                                    </svg>
                                    <p class="text-xs font-semibold text-zinc-700">
                                        Unggah foto bukti pembelajaran (JPG, PNG)
                                    </p>
                                    <p class="mt-1 text-[11px] font-mono text-zinc-400" x-text="fileName ? fileName : 'Maksimal ukuran file: 5MB (Otomatis Diresize &amp; Kompres)'"></p>
                                </div>
                            </template>

                            <input
                                x-ref="photoInput"
                                id="photo"
                                name="photo"
                                type="file"
                                accept="image/jpeg,image/png,image/jpg"
                                class="sr-only"
                                required
                                @change="
                                    const file = $event.target.files[0];
                                    fileError = '';
                                    if (file) {
                                        if (file.size > 5 * 1024 * 1024) {
                                            fileError = 'Ukuran foto melebihi 5MB.';
                                            $event.target.value = '';
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
                    <p class="text-xs text-[#FD366E] mt-1.5 font-medium" x-show="fileError" x-text="fileError"></p>
                    <x-input-error :messages="$errors->get('photo')" class="mt-1.5" />
                </div>

                {{-- Action Submit --}}
                <div class="pt-2">
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-[#FD366E] hover:bg-[#E0285D] active:bg-[#C92051] text-white font-semibold text-xs uppercase tracking-wider rounded-lg transition shadow-none focus:outline-none focus:ring-2 focus:ring-[#FD366E] focus:ring-offset-2 focus:ring-offset-[#19191C] cursor-pointer">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        Simpan Jurnal &amp; Sinkronkan Presensi
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>
