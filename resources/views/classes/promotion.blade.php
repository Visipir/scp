<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0] flex items-center gap-2">
                    <svg class="w-6 h-6 text-[#0073B1] dark:text-[#38BDF8]" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                    Simulasi Kenaikan Kelas &amp; Perubahan Penugasan
                </h1>
                <p class="text-xs text-zinc-600 dark:text-[#9496A1] mt-1">
                    Simulasi kenaikan tingkat rombongan belajar, mutasi siswa, serta penataan massal Wali Kelas dan Guru BK.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('classes.index') }}" class="inline-flex items-center min-h-[44px] sm:min-h-0 px-3.5 py-2 text-xs font-semibold text-zinc-700 dark:text-zinc-200 hover:text-zinc-900 dark:hover:text-white bg-zinc-100 dark:bg-[#2C2C30] rounded-lg transition-colors">
                    &larr; Kembali ke Data Kelas
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $teachersJson = $teachers->map(fn($t) => [
            'id' => (int) $t->id,
            'name' => $t->fullname ?? $t->name,
            'label' => trim(($t->fullname ?? $t->name) . ($t->nip ? " ({$t->nip})" : '')),
        ])->values();
    @endphp

    <div 
        class="space-y-6"
        x-data="{
            activeTab: '{{ session('active_tab', request('tab', 'promotion')) }}',
            selectAllStudents: true,
            teachers: {{ Js::from($teachersJson) }},
            toggleAllStudents() {
                const checkboxes = document.querySelectorAll('.student-checkbox');
                checkboxes.forEach(cb => cb.checked = this.selectAllStudents);
            },
            hydrateTeacherSelect(el, includeNip = true) {
                if (!el || el.dataset.hydrated === '1') return;
                el.dataset.hydrated = '1';
                const existing = new Set(Array.from(el.options).map(o => String(o.value)));
                const frag = document.createDocumentFragment();
                for (const t of this.teachers) {
                    const val = String(t.id);
                    if (existing.has(val)) continue;
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.textContent = includeNip ? t.label : t.name;
                    frag.appendChild(opt);
                }
                el.appendChild(frag);
            }
        }"
    >
        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if(session('error'))
            <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
        @endif

        {{-- Tab Navigation --}}
        <div class="border-b border-zinc-200 dark:border-[#2B2B30] flex flex-wrap items-center gap-6">
            <button 
                type="button" 
                @click="activeTab = 'promotion'"
                class="min-h-[44px] sm:min-h-0 pb-3 text-xs font-bold tracking-tight transition-colors border-b-2 cursor-pointer flex items-center gap-2"
                :class="activeTab === 'promotion' ? 'border-[#0073B1] text-[#0073B1] dark:border-[#38BDF8] dark:text-[#38BDF8]' : 'border-transparent text-zinc-600 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-white'"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5" />
                </svg>
                <span>1. Simulasi Kenaikan &amp; Kelulusan Siswa</span>
            </button>

            <button 
                type="button" 
                @click="activeTab = 'assignments'"
                class="min-h-[44px] sm:min-h-0 pb-3 text-xs font-bold tracking-tight transition-colors border-b-2 cursor-pointer flex items-center gap-2"
                :class="activeTab === 'assignments' ? 'border-[#0073B1] text-[#0073B1] dark:border-[#38BDF8] dark:text-[#38BDF8]' : 'border-transparent text-zinc-600 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-white'"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
                <span>2. Penugasan Massal Wali Kelas &amp; Guru BK</span>
            </button>
        </div>

        {{-- TAB 1: Kenaikan Kelas & Kelulusan --}}
        <div x-show="activeTab === 'promotion'" class="space-y-6">
            {{-- Form Parameter Simulasi --}}
            <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-5 shadow-xs">
                <h2 class="text-sm font-bold text-zinc-900 dark:text-[#EDEDF0] mb-3 flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#0073B1]"></span>
                    Pilih Kelas Asal &amp; Tujuan
                </h2>

                <form method="GET" action="{{ route('classes.promotion') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label for="promo_source_class_id" class="block text-xs font-semibold text-zinc-700 dark:text-[#EDEDF0] mb-1">Kelas Asal (Rombel Saat Ini)</label>
                        <select 
                            id="promo_source_class_id"
                            name="source_class_id" 
                            required 
                            class="w-full rounded-lg border-zinc-200 dark:border-[#2B2B30] bg-zinc-50 dark:bg-[#232327] px-3 py-2 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1]"
                        >
                            <option value="">-- Pilih Kelas Asal --</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" {{ (string)$sourceClassId === (string)$c->id ? 'selected' : '' }}>
                                    {{ $c->class_name }} ({{ $c->students_count ?? $c->students->count() }} siswa)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="promo_action" class="block text-xs font-semibold text-zinc-700 dark:text-[#EDEDF0] mb-1">Aksi Rombongan Belajar</label>
                        <select 
                            id="promo_action"
                            name="action" 
                            class="w-full rounded-lg border-zinc-200 dark:border-[#2B2B30] bg-zinc-50 dark:bg-[#232327] px-3 py-2 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1]"
                        >
                            <option value="promote" {{ $action === 'promote' ? 'selected' : '' }}>Naik / Mutasi ke Kelas Baru</option>
                            <option value="graduate" {{ $action === 'graduate' ? 'selected' : '' }}>Kelulusan (Alumni / Tamat)</option>
                        </select>
                    </div>

                    <div>
                        <label for="promo_target_class_id" class="block text-xs font-semibold text-zinc-700 dark:text-[#EDEDF0] mb-1">Kelas Tujuan</label>
                        <select 
                            id="promo_target_class_id"
                            name="target_class_id" 
                            class="w-full rounded-lg border-zinc-200 dark:border-[#2B2B30] bg-zinc-50 dark:bg-[#232327] px-3 py-2 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1]"
                        >
                            <option value="">-- Pilih Kelas Tujuan --</option>
                            @foreach($classes as $c)
                                @if((string)$c->id !== (string)$sourceClassId)
                                    <option value="{{ $c->id }}" {{ (string)$targetClassId === (string)$c->id ? 'selected' : '' }}>
                                        {{ $c->class_name }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <button 
                            type="submit" 
                            class="w-full min-h-[44px] sm:min-h-0 px-4 py-2 bg-[#0073B1] hover:bg-[#005D8F] text-white text-xs font-semibold rounded-lg transition cursor-pointer flex items-center justify-center gap-1.5"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m5.231 13.481L15 17.25m-1.769-2.769a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z" />
                            </svg>
                            <span>Jalankan Simulasi</span>
                        </button>
                    </div>
                </form>
            </div>

            @if($sourceClass)
                {{-- Simulation Preview Cards --}}
                @if($simulation)
                    <div class="bg-sky-50/50 dark:bg-[#16212b] border border-sky-200 dark:border-sky-900/40 rounded-xl p-5 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-[#0073B1] text-white">HASIL SIMULASI</span>
                                <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ $sourceClass->class_name }} &rarr; {{ $action === 'graduate' ? 'Lulus / Alumni' : ($simulation['target_class']?->class_name ?? 'Belum dipilih') }}
                                </h3>
                            </div>
                            <div class="text-xs font-mono text-zinc-600 dark:text-zinc-300">
                                Total Siswa Terpilih: <strong>{{ $simulation['total_students'] }} dari {{ $simulation['source_class_total'] }} siswa</strong>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Table of Students with Action --}}
                <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl overflow-hidden shadow-xs">
                    <form method="POST" action="{{ route('classes.promotion.execute') }}" data-confirm="Apakah Anda yakin ingin menerapkan perubahan kenaikan/kelulusan untuk siswa terpilih?" data-confirm-title="Terapkan Kenaikan Kelas" data-confirm-variant="primary">
                        @csrf
                        <input type="hidden" name="source_class_id" value="{{ $sourceClass->id }}">
                        <input type="hidden" name="action" value="{{ $action }}">
                        <input type="hidden" name="target_class_id" value="{{ $targetClassId }}">

                        <div class="p-4 border-b border-zinc-200 dark:border-[#2B2B30] flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-zinc-50/60 dark:bg-[#232327]">
                            <div class="flex items-center gap-3 flex-wrap">
                                <label for="promo_select_all" class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold text-zinc-700 dark:text-[#EDEDF0]">
                                    <input 
                                        id="promo_select_all"
                                        type="checkbox" 
                                        x-model="selectAllStudents" 
                                        @change="toggleAllStudents()"
                                        checked
                                        class="rounded border-zinc-300 dark:border-[#2B2B30] text-[#0073B1] focus:ring-[#0073B1]"
                                    >
                                    <span>Pilih Semua Siswa</span>
                                </label>
                                <span class="text-xs text-zinc-400">|</span>
                                <span class="text-xs text-zinc-600 dark:text-[#9496A1]">
                                    Daftar Anggota Kelas: <strong>{{ $sourceClass->class_name }}</strong> ({{ $sourceStudents->count() }} siswa)
                                </span>
                            </div>

                            @if(($action === 'promote' && $targetClassId) || $action === 'graduate')
                                <button 
                                    type="submit" 
                                    class="inline-flex items-center justify-center gap-1.5 min-h-[44px] sm:min-h-0 px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-lg transition shadow-xs cursor-pointer"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                    <span>Terapkan Perubahan Kenaikan Kelas</span>
                                </button>
                            @endif
                        </div>

                        <div tabindex="0" role="region" aria-label="Daftar Siswa Simulasi Kenaikan Kelas" class="overflow-x-auto table-scroll-region">
                            <table class="w-full text-left border-collapse tabular-nums">
                                <caption class="sr-only">Daftar Siswa Rombongan Belajar dalam Simulasi Kenaikan atau Kelulusan</caption>
                                <thead>
                                    <tr class="border-b border-zinc-200 dark:border-[#2B2B30] bg-zinc-50 dark:bg-[#1E1E22] text-[11px] font-semibold text-zinc-600 dark:text-[#9496A1] uppercase tracking-wider font-mono">
                                        <th scope="col" class="py-3 px-4 w-12 text-center">Pilih</th>
                                        <th scope="col" class="py-3 px-4 w-16">No</th>
                                        <th scope="col" class="py-3 px-4">NIS</th>
                                        <th scope="col" class="py-3 px-4">Nama Siswa</th>
                                        <th scope="col" class="py-3 px-4">Jenis Kelamin</th>
                                        <th scope="col" class="py-3 px-4">Status Rombel</th>
                                        <th scope="col" class="py-3 px-4">Tujuan / Simulasi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-[#2B2B30] text-xs text-zinc-700 dark:text-[#EDEDF0]">
                                    @forelse($sourceStudents as $index => $stu)
                                        <tr class="hover:bg-zinc-50 dark:hover:bg-[#26262A] transition-colors">
                                            <td class="py-3 px-4 text-center">
                                                <input 
                                                    type="checkbox" 
                                                    name="student_ids[]" 
                                                    value="{{ $stu->id }}" 
                                                    aria-label="Pilih {{ $stu->fullname ?? $stu->name }}"
                                                    checked 
                                                    class="student-checkbox rounded border-zinc-300 dark:border-[#2B2B30] text-[#0073B1] focus:ring-[#0073B1]"
                                                >
                                            </td>
                                            <td class="py-3 px-4 font-mono text-zinc-500 dark:text-[#9496A1]">{{ $index + 1 }}</td>
                                            <td class="py-3 px-4 font-mono font-medium">{{ $stu->username ?? $stu->nis ?? '-' }}</td>
                                            <td class="py-3 px-4 font-semibold text-zinc-900 dark:text-zinc-100">{{ $stu->fullname ?? $stu->name }}</td>
                                            <td class="py-3 px-4">{{ $stu->gender === 'L' ? 'Laki-laki' : ($stu->gender === 'P' ? 'Perempuan' : ($stu->gender ?? '-')) }}</td>
                                            <td class="py-3 px-4">
                                                <span class="px-2 py-0.5 rounded text-[11px] font-mono font-semibold bg-zinc-100 dark:bg-[#28282D] text-zinc-700 dark:text-zinc-300">
                                                    {{ $sourceClass->class_name }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                @if($action === 'graduate')
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 dark:bg-[#16281D] text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                                        &rarr; Lulus / Alumni
                                                    </span>
                                                @elseif($targetClassId && $simulation && $simulation['target_class'])
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded text-[11px] font-semibold bg-sky-50 dark:bg-[#152332] text-[#0073B1] dark:text-[#38BDF8] border border-sky-200 dark:border-sky-800">
                                                        &rarr; {{ $simulation['target_class']->class_name }}
                                                    </span>
                                                @else
                                                    <span class="text-zinc-500 dark:text-[#9496A1] text-xs italic">Pilih kelas tujuan di form atas</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="py-8 text-center text-xs text-zinc-600 dark:text-[#9496A1]">
                                                Tidak ada data siswa terdaftar di rombongan belajar ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            @endif
        </div>

        {{-- TAB 2: Penugasan Massal Wali Kelas & Guru BK (Lazy-mounted & on-demand option hydration for slim DOM) --}}
        <template x-if="activeTab === 'assignments'">
        <div class="space-y-6">
            @if(session('assignment_simulation'))
                @php $asSim = session('assignment_simulation'); @endphp
                <div class="bg-amber-50/60 dark:bg-[#241c10] border border-amber-200 dark:border-amber-800/60 rounded-xl p-5 space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2.5 py-1 text-xs font-bold rounded-md bg-amber-700 text-white">HASIL SIMULASI PENUGASAN</span>
                            <h2 class="text-sm font-bold text-amber-900 dark:text-amber-200">
                                Terdeteksi {{ $asSim['changed_count'] }} perubahan rombel dari total {{ $asSim['total_classes'] }} kelas
                            </h2>
                        </div>
                    </div>

                    @if(!empty($asSim['changes']))
                        <div tabindex="0" role="region" aria-label="Simulasi Perubahan Penugasan Guru" class="overflow-x-auto table-scroll-region">
                            <table class="w-full text-xs text-left tabular-nums">
                                <caption class="sr-only">Perbandingan Wali Kelas dan Guru BK Lama vs Baru</caption>
                                <thead>
                                    <tr class="border-b border-amber-200 dark:border-amber-800 text-[11px] font-mono uppercase text-amber-800 dark:text-amber-300">
                                        <th scope="col" class="py-2 px-3">Kelas</th>
                                        <th scope="col" class="py-2 px-3">Wali Kelas Lama &rarr; Baru</th>
                                        <th scope="col" class="py-2 px-3">Guru BK Lama &rarr; Baru</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-amber-100 dark:divide-amber-900/40">
                                    @foreach($asSim['changes'] as $ch)
                                        <tr>
                                            <td class="py-2 px-3 font-bold text-zinc-900 dark:text-zinc-100">{{ $ch['class_name'] }}</td>
                                            <td class="py-2 px-3">
                                                @if($ch['homeroom_changed'])
                                                    <span class="line-through text-zinc-500 dark:text-[#9496A1]">{{ $ch['current_homeroom'] }}</span>
                                                    &rarr; <span class="font-bold text-amber-800 dark:text-amber-300">{{ $ch['new_homeroom'] }}</span>
                                                @else
                                                    <span class="text-zinc-600 dark:text-[#9496A1]">{{ $ch['current_homeroom'] }} (Tetap)</span>
                                                @endif
                                            </td>
                                            <td class="py-2 px-3">
                                                @if($ch['counselor_changed'])
                                                    <span class="line-through text-zinc-500 dark:text-[#9496A1]">{{ $ch['current_counselors'] }}</span>
                                                    &rarr; <span class="font-bold text-amber-800 dark:text-amber-300">{{ $ch['new_counselors'] }}</span>
                                                @else
                                                    <span class="text-zinc-600 dark:text-[#9496A1]">{{ $ch['current_counselors'] }} (Tetap)</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-xs text-amber-800 dark:text-amber-300 italic">Tidak ada perubahan penugasan dibanding data aktif saat ini.</p>
                    @endif
                </div>
            @endif

            <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl overflow-hidden shadow-xs">
                <form method="POST" action="{{ route('classes.promotion.assignments') }}">
                    @csrf
                    <div class="p-4 border-b border-zinc-200 dark:border-[#2B2B30] flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-zinc-50/60 dark:bg-[#232327]">
                        <div>
                            <h2 class="text-sm font-bold text-zinc-900 dark:text-[#EDEDF0]">Matriks Penugasan Wali Kelas &amp; Guru BK</h2>
                            <p class="text-xs text-zinc-600 dark:text-[#9496A1] mt-0.5">
                                Atur atau rolling guru wali dan konselor BK untuk seluruh rombongan belajar sekaligus.
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button 
                                type="submit" 
                                name="mode" 
                                value="simulate" 
                                class="min-h-[44px] sm:min-h-0 px-3.5 py-2 bg-zinc-100 dark:bg-[#2E2E33] hover:bg-zinc-200 dark:hover:bg-[#38383E] text-zinc-800 dark:text-zinc-200 text-xs font-semibold rounded-lg transition cursor-pointer"
                            >
                                Simulasikan Perubahan
                            </button>
                            <button 
                                type="submit" 
                                name="mode" 
                                value="commit" 
                                data-confirm="Simpan dan terapkan seluruh perubahan Wali Kelas & Guru BK?"
                                data-confirm-title="Terapkan Penugasan Massal"
                                data-confirm-variant="primary"
                                class="min-h-[44px] sm:min-h-0 px-4 py-2 bg-[#0073B1] hover:bg-[#005D8F] text-white text-xs font-bold rounded-lg transition shadow-xs cursor-pointer"
                            >
                                Simpan Penugasan Massal
                            </button>
                        </div>
                    </div>

                    <div tabindex="0" role="region" aria-label="Pemetaan Wali Kelas dan Guru BK" class="overflow-x-auto table-scroll-region">
                        <table class="w-full text-left border-collapse tabular-nums">
                            <caption class="sr-only">Tabel Penugasan Wali Kelas dan Guru BK per Rombel</caption>
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-[#2B2B30] bg-zinc-50 dark:bg-[#1E1E22] text-[11px] font-semibold text-zinc-600 dark:text-[#9496A1] uppercase tracking-wider font-mono">
                                    <th scope="col" class="py-3 px-4 w-12 text-center">No</th>
                                    <th scope="col" class="py-3 px-4 w-40">Rombongan Belajar</th>
                                    <th scope="col" class="py-3 px-4 w-48">Wali Kelas Saat Ini</th>
                                    <th scope="col" class="py-3 px-4">Pilih Wali Kelas Baru</th>
                                    <th scope="col" class="py-3 px-4 w-48">Guru BK Saat Ini</th>
                                    <th scope="col" class="py-3 px-4">Pilih Guru BK Baru</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-[#2B2B30] text-xs text-zinc-700 dark:text-[#EDEDF0]">
                                @foreach($classes as $idx => $cl)
                                    @php
                                        $currentWali = $cl->homeroomTeacher;
                                        $currentBKs = $cl->counselors;
                                    @endphp
                                    <tr class="hover:bg-zinc-50 dark:hover:bg-[#26262A] transition-colors">
                                        <td class="py-3 px-4 text-center font-mono text-zinc-500 dark:text-[#9496A1]">{{ $idx + 1 }}</td>
                                        <td class="py-3 px-4 font-bold text-zinc-900 dark:text-zinc-100">
                                            {{ $cl->class_name }}
                                            <span class="block text-[11px] font-mono text-zinc-600 dark:text-[#9496A1] font-normal">Tahun: {{ $cl->academic_year ?? '2026/2027' }}</span>
                                        </td>
                                        <td class="py-3 px-4 text-zinc-600 dark:text-zinc-300">
                                            {{ $currentWali?->fullname ?? $currentWali?->name ?? '(Belum Ada)' }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <select 
                                                name="assignments[{{ $cl->id }}][homeroom_user_id]" 
                                                aria-label="Wali kelas baru untuk {{ $cl->class_name }}"
                                                @focus="hydrateTeacherSelect($el, true)"
                                                @pointerdown="hydrateTeacherSelect($el, true)"
                                                class="w-full rounded-lg border-zinc-200 dark:border-[#2B2B30] bg-zinc-50 dark:bg-[#19191C] px-3 py-1.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1]"
                                            >
                                                <option value="">-- Tetapkan Kosong --</option>
                                                @if($currentWali)
                                                    <option value="{{ $currentWali->id }}" selected>
                                                        {{ $currentWali->fullname ?? $currentWali->name }} {{ $currentWali->nip ? "({$currentWali->nip})" : '' }}
                                                    </option>
                                                @endif
                                            </select>
                                        </td>
                                        <td class="py-3 px-4 text-zinc-600 dark:text-zinc-300">
                                            {{ $currentBKs->pluck('fullname')->implode(', ') ?: ($currentBKs->pluck('name')->implode(', ') ?: '(Belum Ada)') }}
                                        </td>
                                        <td class="py-3 px-4">
                                            <select 
                                                name="assignments[{{ $cl->id }}][counselor_user_ids][]" 
                                                aria-label="Guru BK baru untuk {{ $cl->class_name }}"
                                                multiple 
                                                size="3" 
                                                @focus="hydrateTeacherSelect($el, false)"
                                                @pointerdown="hydrateTeacherSelect($el, false)"
                                                class="w-full rounded-lg border-zinc-200 dark:border-[#2B2B30] bg-zinc-50 dark:bg-[#19191C] px-3 py-1.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1]"
                                            >
                                                @foreach($currentBKs as $bk)
                                                    <option value="{{ $bk->id }}" selected>
                                                        {{ $bk->fullname ?? $bk->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <span class="text-[11px] text-zinc-600 dark:text-[#9496A1] mt-0.5 block">Klik daftar lalu tahan Ctrl/Cmd untuk memilih lebih dari 1 BK</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
        </template>
    </div>
</x-app-layout>
