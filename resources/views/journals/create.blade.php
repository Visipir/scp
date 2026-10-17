<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold tracking-tight text-zinc-900">
                    Isi Jurnal Mengajar
                </h1>
                <p class="text-xs text-zinc-500 font-mono mt-0.5">Dokumentasi KBM &amp; Presensi Kelas</p>
            </div>
            <a href="{{ route('journals.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-zinc-200 text-xs font-semibold text-zinc-600 hover:text-zinc-900 rounded-md transition">
                &larr; Kembali
            </a>
        </div>
    </x-slot>

    <div class="max-w-xl space-y-4">
        @if($kbmSuspendedEvent ?? false)
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-xs text-amber-900 flex items-center gap-2">
                <span class="font-bold uppercase font-mono px-1.5 py-0.5 rounded bg-amber-200 text-[10px]">Info</span>
                <span>KBM Diliburkan: {{ $kbmSuspendedEvent->title }} - {{ $kbmSuspendedEvent->description }}</span>
            </div>
        @endif

        <div class="bg-white border border-zinc-200 rounded-lg p-5 sm:p-6">
            <form x-ref="form" method="POST" action="{{ route('journals.store') }}" enctype="multipart/form-data" class="space-y-4" 
                x-data="{ 
                    isUploading: false,
                    uploadProgressText: '',
                    fileName: '', 
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
                    @if(isset($defaultSchedule) && $defaultSchedule)
                        <div x-show="!isManual" class="p-3.5 bg-zinc-50 border border-zinc-200 rounded-lg flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-xs text-zinc-900 truncate">
                                        {{ $defaultSchedule->schoolClass->class_name }} &bull; {{ $defaultSchedule->subject }}
                                    </span>
                                </div>
                                <p class="text-[11px] font-mono text-zinc-500 mt-0.5">
                                    {{ $defaultSchedule->day_of_week }}, {{ \Carbon\Carbon::parse($defaultDate)->translatedFormat('d M Y') }} &bull; {{ $defaultSchedule->period_label }} ({{ \Carbon\Carbon::parse($defaultSchedule->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($defaultSchedule->end_time)->format('H:i') }}) &bull; {{ $defaultSchedule->room->room_name ?? 'Tanpa Ruangan' }}
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
                                <x-input-label for="date" value="Tanggal" />
                                <input id="date" name="date" type="date" x-model="selectedDate" :disabled="!isManual" required class="w-full rounded-md border-zinc-300 bg-white px-3 py-1.5 text-xs font-mono text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900" />
                                <x-input-error :messages="$errors->get('date')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="schedule_id" value="Jadwal Kelas" />
                                <select id="schedule_id" name="schedule_id" x-model="selectedScheduleId" :disabled="!isManual" required class="w-full rounded-md border-zinc-300 bg-white px-3 py-1.5 text-xs text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
                                    <option value="">-- Pilih Jadwal --</option>
                                    @foreach($schedules as $schedule)
                                        <option value="{{ $schedule->id }}">
                                            {{ $schedule->day_of_week }} | {{ $schedule->period_label }} | {{ $schedule->schoolClass->class_name }} - {{ $schedule->subject }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('schedule_id')" class="mt-1" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Materi / Topik --}}
                <div>
                    <x-input-label for="topic_description" value="Uraian Materi / Topik Pembelajaran" />
                    <textarea id="topic_description" name="topic_description" rows="3" class="w-full rounded-md border-zinc-300 bg-white p-3 text-xs text-zinc-900 placeholder:text-zinc-400 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 resize-y" required placeholder="Tuliskan ringkasan materi yang diajarkan...">{{ old('topic_description') }}</textarea>
                    <x-input-error :messages="$errors->get('topic_description')" class="mt-1" />
                </div>

                {{-- Presensi Siswa (Default Hadir, Catat Siswa Tidak Hadir / Izin Keluar Kelas) --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <x-input-label value="Presensi Siswa (Default: Seluruh Siswa Hadir)" />
                        <span class="text-[11px] font-mono font-semibold" :class="absentStudents.length > 0 ? 'text-amber-700' : 'text-emerald-700'" x-text="absentStudents.length > 0 ? (absentStudents.length + ' siswa dicatat tidak hadir / izin keluar') : 'Semua hadir'"></span>
                    </div>

                    <div class="p-3.5 bg-zinc-50 border border-zinc-200 rounded-lg space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                            <span class="text-zinc-600 text-[11px] leading-relaxed">
                                Seluruh siswa otomatis berstatus <strong class="text-emerald-700 font-bold">Hadir</strong>. Catat hanya siswa yang tidak hadir dari awal atau <strong>izin meninggalkan kelas di tengah KBM</strong>.
                            </span>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button" @click="addAbsentStudent('Sakit', '')" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-white border border-zinc-300 text-[11px] font-semibold text-zinc-700 hover:text-zinc-900 rounded-md hover:bg-zinc-100 transition cursor-pointer shadow-none">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                                    + Catat Tidak Hadir
                                </button>
                                <button type="button" @click="addLeavingMidClass()" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-amber-50 border border-amber-300 text-[11px] font-bold text-amber-900 hover:bg-amber-100 rounded-md transition cursor-pointer shadow-none">
                                    <svg class="w-3 h-3 text-amber-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                                    + Izin Keluar di Tengah KBM
                                </button>
                            </div>
                        </div>

                        {{-- Nihil Message --}}
                        <div x-show="absentStudents.length === 0" class="text-xs font-mono text-emerald-800 bg-emerald-50 border border-emerald-200/80 p-3 rounded-md flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Seluruh siswa hadir lengkap. (Nihil catatan ketidakhadiran / izin keluar)</span>
                        </div>

                        {{-- Absent List --}}
                        <div class="space-y-2.5" x-show="absentStudents.length > 0">
                            <template x-for="(item, idx) in absentStudents" :key="idx">
                                <div class="p-3 bg-white border border-zinc-200 rounded-lg shadow-sm space-y-2">
                                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <span class="font-mono text-zinc-400 text-xs font-bold w-5" x-text="(idx + 1) + '.'"></span>
                                        </div>
                                        <div class="flex-1 w-full min-w-0">
                                            <select :name="'absent_students[' + idx + '][student_id]'" x-model="item.student_id" required class="w-full rounded-md border-zinc-300 text-xs py-1.5 px-2.5 text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
                                                <option value="">-- Pilih Siswa --</option>
                                                <template x-for="std in currentStudents" :key="std.id">
                                                    <option :value="std.id" x-text="std.fullname + (std.username ? ' (' + std.username + ')' : '')" :selected="item.student_id == std.id"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="w-full sm:w-36 shrink-0">
                                            <select :name="'absent_students[' + idx + '][status]'" x-model="item.status" required class="w-full rounded-md border-zinc-300 text-xs py-1.5 px-2.5 font-semibold text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
                                                <option value="Izin">Izin</option>
                                                <option value="Sakit">Sakit</option>
                                                <option value="Alpa">Alpa</option>
                                                <option value="Terlambat">Terlambat</option>
                                            </select>
                                        </div>
                                        <button type="button" @click="removeAbsentStudent(idx)" class="self-end sm:self-auto text-zinc-400 hover:text-rose-600 p-1.5 transition cursor-pointer rounded hover:bg-rose-50" title="Hapus Catatan">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>

                                    {{-- Catatan / Alasan Keluar Kelas dengan Quick Chips --}}
                                    <div class="pt-1.5 border-t border-zinc-100 space-y-1.5">
                                        <div class="flex flex-wrap items-center justify-between gap-1">
                                            <label class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider font-mono">
                                                Alasan / Catatan Keluar Kelas:
                                            </label>
                                            <div class="flex flex-wrap items-center gap-1 text-[10px]">
                                                <span class="text-zinc-400 font-mono">Pilih Cepat:</span>
                                                <button type="button" @click="setPreset(idx, 'Sakit', 'Izin keluar kelas: sakit ke UKS')" class="px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 transition font-mono">
                                                    + Ke UKS (Sakit)
                                                </button>
                                                <button type="button" @click="setPreset(idx, 'Izin', 'Izin keluar kelas: dipanggil Guru BK')" class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 transition font-mono">
                                                    + Dipanggil BK
                                                </button>
                                                <button type="button" @click="setPreset(idx, 'Izin', 'Izin keluar kelas: dispensasi tugas/lomba sekolah')" class="px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 transition font-mono">
                                                    + Dispensasi
                                                </button>
                                                <button type="button" @click="setPreset(idx, 'Izin', 'Izin keluar kelas: pulang lebih awal (dijemput)')" class="px-1.5 py-0.5 rounded bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 transition font-mono">
                                                    + Pulang Cepat
                                                </button>
                                                <button type="button" @click="setPreset(idx, 'Alpa', 'Meninggalkan kelas tanpa izin di tengah KBM')" class="px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 transition font-mono">
                                                    + Tanpa Izin
                                                </button>
                                            </div>
                                        </div>
                                        <input type="text" :name="'absent_students[' + idx + '][notes]'" x-model="item.notes" placeholder="Tuliskan alasan keluar kelas (misal: sakit perut ke UKS jam ke-3, dipanggil BK, dll)..." class="w-full rounded-md border-zinc-300 text-xs py-1.5 px-3 text-zinc-900 placeholder:text-zinc-400 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900">
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Foto Dokumentasi --}}
                <div>
                    <x-input-label for="photo" value="Foto Dokumentasi KBM (Wajib, Maks 5MB) *" />
                    <div class="mt-1">
                        <label for="photo" class="flex flex-col items-center justify-center w-full min-h-[90px] border border-dashed border-zinc-300 rounded-md cursor-pointer bg-zinc-50/60 hover:bg-zinc-100 transition p-3 text-center">
                            <svg class="w-5 h-5 text-zinc-400 mb-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z"/></svg>
                            <p class="text-xs font-medium text-zinc-700" x-text="fileName ? fileName : 'Klik untuk pilih foto kamera / galeri'"></p>
                            <p class="text-[10px] text-zinc-400 mt-0.5">Otomatis dikompresi tanpa merubah orientasi</p>
                            <input id="photo" name="photo" type="file" class="sr-only" accept="image/jpeg,image/png,image/jpg" required
                                @change="
                                    const file = $event.target.files[0];
                                    fileError = '';
                                    if (file) {
                                        if (file.size > 5 * 1024 * 1024) {
                                            fileError = 'Ukuran foto melebihi batas 5MB.';
                                            $event.target.value = '';
                                            fileName = '';
                                        } else {
                                            fileName = file.name;
                                        }
                                    } else {
                                        fileName = '';
                                    }
                                " />
                        </label>
                        <p class="text-xs text-rose-600 mt-1" x-show="fileError" x-text="fileError"></p>
                        <x-input-error :messages="$errors->get('photo')" class="mt-1" />
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="button" :disabled="isUploading"
                        @click="
                            if(!$refs.form.reportValidity()) return;
                            isUploading = true;
                            
                            let formData = new FormData($refs.form);
                            let xhr = new XMLHttpRequest();
                            xhr.open('POST', $refs.form.action);
                            
                            xhr.upload.addEventListener('progress', (e) => {
                                if (e.lengthComputable) {
                                    let loadedMB = (e.loaded / (1024*1024)).toFixed(2);
                                    let totalMB = (e.total / (1024*1024)).toFixed(2);
                                    let percent = Math.round((e.loaded / e.total) * 100);
                                    uploadProgressText = `${loadedMB}MB / ${totalMB}MB (${percent}%)`;
                                }
                            });

                            xhr.onload = () => {
                                if (xhr.status >= 200 && xhr.status < 300) {
                                    if(xhr.responseURL && xhr.responseURL !== window.location.href) {
                                        window.location.href = xhr.responseURL;
                                    } else {
                                        document.open();
                                        document.write(xhr.responseText);
                                        document.close();
                                    }
                                } else {
                                    isUploading = false;
                                    fileError = 'Gagal menyimpan jurnal. Silakan coba lagi.';
                                }
                            };

                            xhr.onerror = () => {
                                isUploading = false;
                                fileError = 'Terjadi kesalahan jaringan.';
                            };

                            xhr.send(formData);
                        "
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#FD366E] hover:bg-[#E0285D] active:bg-[#C92051] text-white text-xs font-semibold uppercase tracking-wider rounded-lg transition shadow-none disabled:opacity-50 cursor-pointer"
                    >
                        <svg x-show="isUploading" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="isUploading ? 'Menyimpan... ' + (uploadProgressText || '') : 'Simpan Jurnal KBM'"></span>
                    </button>
                    <a href="{{ route('journals.index') }}" x-show="!isUploading" class="text-xs font-semibold text-[#97979B] hover:text-[#EDEDF0]">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>