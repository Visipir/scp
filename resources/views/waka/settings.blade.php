<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="text-[10px] font-bold font-mono text-zinc-400 uppercase tracking-widest">Sistem Konfigurasi</div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 mt-0.5">
                    Pengaturan Kurikulum &amp; Jadwal
                </h1>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-zinc-100 text-zinc-800 border border-zinc-200 text-xs font-mono font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-zinc-600"></span> Waka Kurikulum
                </span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-8 max-w-3xl">

        {{-- Flash Message --}}
        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if(session('error'))
            <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
        @endif

        {{-- SECTION 1: KONTROL SISTEM PUTARAN & KUNCI JADWAL --}}
        <div class="bg-white border border-zinc-200/80 rounded-lg divide-y divide-zinc-200/80">
            <div class="p-6 sm:p-8">
                <h2 class="text-base font-bold text-zinc-900 tracking-tight flex items-center gap-2">
                    <svg class="w-5 h-5 text-zinc-700" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                    </svg>
                    Kontrol Sistem Putaran &amp; Kunci Jadwal
                </h2>
                <p class="text-xs text-zinc-500 mt-1">
                    Pengaturan global yang mengatur siklus 2-mingguan dan hak akses perubahan jadwal bagi seluruh guru pengajar.
                </p>
            </div>

            {{-- 1. CYCLE TOGGLE (Putaran 1 / Putaran 2) --}}
            <div class="p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div class="space-y-1">
                    <label class="text-sm font-bold text-zinc-900 tracking-tight block">
                        Putaran Block Schedule Aktif
                    </label>
                    <p class="text-xs text-zinc-500 max-w-sm leading-relaxed">
                        Pilih putaran kalender akademik yang aktif saat ini. Seluruh jadwal dan filter akan menyesuaikan putaran terpilih.
                    </p>
                </div>

                <form method="POST" action="{{ route('system-configuration.toggle-cycle') }}" class="shrink-0">
                    @csrf
                    @method('PATCH')
                    
                    <div class="inline-flex p-1 bg-zinc-100 rounded-md border border-zinc-200">
                        <button
                            type="submit"
                            name="cycle"
                            value="Putaran 1"
                            class="px-4 py-2 text-xs font-bold font-mono uppercase tracking-wider rounded transition duration-150 {{ ($config->active_cycle ?? 'Putaran 1') === 'Putaran 1' ? 'bg-zinc-900 text-white shadow-xs' : 'text-zinc-500 hover:text-zinc-900' }}"
                        >
                            Putaran 1
                        </button>
                        <button
                            type="submit"
                            name="cycle"
                            value="Putaran 2"
                            class="px-4 py-2 text-xs font-bold font-mono uppercase tracking-wider rounded transition duration-150 {{ ($config->active_cycle ?? 'Putaran 1') === 'Putaran 2' ? 'bg-zinc-900 text-white shadow-xs' : 'text-zinc-500 hover:text-zinc-900' }}"
                        >
                            Putaran 2
                        </button>
                    </div>
                </form>
            </div>

            {{-- 2. LOCK SCHEDULE TOGGLE --}}
            <div class="p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div class="space-y-1">
                    <label for="lock-toggle-btn" class="text-sm font-bold text-zinc-900 tracking-tight block">
                        Kunci Jadwal Mengajar (Lock Schedule)
                    </label>
                    <p class="text-xs text-zinc-500 max-w-sm leading-relaxed">
                        Saat terkunci, seluruh guru dilarang menambah, mengubah, atau menghapus jadwal pelajaran demi menjaga stabilitas data.
                    </p>
                </div>

                <form method="POST" action="{{ route('system-configuration.toggle-lock') }}" class="shrink-0">
                    @csrf
                    @method('PATCH')

                    <div x-data="{ isLocked: {{ ($config->is_schedule_locked ?? false) ? 'true' : 'false' }} }">
                        <button
                            id="lock-toggle-btn"
                            type="submit"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-1 focus:ring-zinc-900 focus:ring-offset-2"
                            :class="isLocked ? 'bg-rose-600' : 'bg-zinc-300'"
                            role="switch"
                            :aria-checked="isLocked"
                        >
                            <span class="sr-only">Toggle Kunci Jadwal</span>
                            <span
                                aria-hidden="true"
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-xs transition duration-200 ease-in-out"
                                :class="isLocked ? 'translate-x-5' : 'translate-x-0'"
                            ></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- SECTION 2: KONFIGURASI GOOGLE GEMINI AI --}}
        <div class="bg-white border border-zinc-200/80 rounded-lg divide-y divide-zinc-200/80">
            <div class="p-6 sm:p-8">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-bold text-zinc-900 tracking-tight flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                            </svg>
                            Konfigurasi Google Gemini AI
                        </h2>
                        <p class="text-xs text-zinc-500 mt-1">
                            Kunci API dan model kecerdasan buatan untuk ekstraksi otomatis dokumen PDF jadwal FET. Tersimpan terenkripsi di basis data.
                        </p>
                    </div>
                    @if($config->effective_gemini_api_key)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-mono font-semibold shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> AI Aktif
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-amber-50 text-amber-700 border border-amber-200 text-[11px] font-mono font-semibold shrink-0">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span> Belum Ada API Key
                        </span>
                    @endif
                </div>
            </div>

            <div class="p-6 sm:p-8">
                <form method="POST" action="{{ route('waka.settings.gemini') }}" class="space-y-5">
                    @csrf

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <x-input-label for="gemini_api_key" value="Google Gemini API Key" />
                            @if($config->masked_api_key)
                                <span class="font-mono text-[11px] text-zinc-400">
                                    Saat ini: {{ $config->masked_api_key }}
                                </span>
                            @endif
                        </div>
                        <div class="relative" x-data="{ showKey: false }">
                            <input
                                id="gemini_api_key"
                                name="gemini_api_key"
                                :type="showKey ? 'text' : 'password'"
                                class="w-full rounded-md border-zinc-300 bg-white px-3 py-2 pr-10 text-sm font-mono text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition"
                                placeholder="{{ $config->effective_gemini_api_key ? 'Ketik API key baru untuk mengubah...' : 'Masukkan Google Gemini API Key Anda (AIza...)' }}"
                            />
                            <button
                                type="button"
                                @click="showKey = !showKey"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-zinc-400 hover:text-zinc-600 focus:outline-none"
                            >
                                <svg x-show="!showKey" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg x-show="showKey" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                                </svg>
                            </button>
                        </div>
                        <p class="text-[11px] text-zinc-400 mt-1.5">
                            Dapatkan API Key gratis di <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener noreferrer" class="text-zinc-900 underline font-semibold">Google AI Studio</a>.
                        </p>
                        <x-input-error :messages="$errors->get('gemini_api_key')" class="mt-1.5" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- 1. Model untuk Ekstraksi Jadwal PDF --}}
                        <div>
                            <x-input-label for="gemini_schedule_model" value="Model Ekstraksi Jadwal PDF" />
                            <select
                                id="gemini_schedule_model"
                                name="gemini_schedule_model"
                                class="w-full rounded-md border-zinc-300 bg-white px-3 py-2 text-xs font-mono text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition"
                                required
                            >
                                @php
                                    $currentScheduleModel = old('gemini_schedule_model', $config->effective_gemini_schedule_model);
                                @endphp
                                <option value="gemini-3.5-flash" @selected($currentScheduleModel === 'gemini-3.5-flash')>
                                    gemini-3.5-flash (Disarankan — Penalaran Multimodal Tinggi)
                                </option>
                                <option value="gemini-3.7-flash" @selected($currentScheduleModel === 'gemini-3.7-flash')>
                                    gemini-3.7-flash (Penalaran Multimodal Ekstra Kuat)
                                </option>
                                <option value="gemini-3.5-flash-lite" @selected($currentScheduleModel === 'gemini-3.5-flash-lite')>
                                    gemini-3.5-flash-lite (Hemat Kuota / Ringan)
                                </option>
                                <option value="gemini-3.1-flash-lite" @selected($currentScheduleModel === 'gemini-3.1-flash-lite')>
                                    gemini-3.1-flash-lite (Alternatif Cepat / Kuota Tinggi)
                                </option>
                            </select>
                            <p class="text-[10px] text-zinc-400 font-mono mt-1">High Multimodal Reasoning untuk tabel & dokumen PDF.</p>
                            <x-input-error :messages="$errors->get('gemini_schedule_model')" class="mt-1" />
                        </div>

                        {{-- 2. Model untuk Chatbot Guru --}}
                        <div>
                            <x-input-label for="gemini_chat_model" value="Model AI Chatbot Guru (SCP Assistant)" />
                            <select
                                id="gemini_chat_model"
                                name="gemini_chat_model"
                                class="w-full rounded-md border-zinc-300 bg-white px-3 py-2 text-xs font-mono text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition"
                                required
                            >
                                @php
                                    $currentChatModel = old('gemini_chat_model', $config->effective_gemini_chat_model);
                                @endphp
                                <option value="gemini-3.5-flash-lite" @selected($currentChatModel === 'gemini-3.5-flash-lite')>
                                    gemini-3.5-flash-lite (Disarankan — 500 RPD &amp; Cepat)
                                </option>
                                <option value="gemini-3.1-flash-lite" @selected($currentChatModel === 'gemini-3.1-flash-lite')>
                                    gemini-3.1-flash-lite (Alternatif Kuota Tinggi)
                                </option>
                                <option value="gemini-3.5-flash" @selected($currentChatModel === 'gemini-3.5-flash')>
                                    gemini-3.5-flash (Respon Lebih Elaboratif)
                                </option>
                            </select>
                            <p class="text-[10px] text-zinc-400 font-mono mt-1">Optimasi batas kuota harian & respon interaktif cepat.</p>
                            <x-input-error :messages="$errors->get('gemini_chat_model')" class="mt-1" />
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <x-primary-button>Simpan Konfigurasi AI</x-primary-button>
                    </div>
                </form>
            </div>
        </div>

        {{-- INFO CALLOUT: IMPORT JADWAL DIPINDAH KE HALAMAN JADWAL --}}
        <div class="bg-white border border-zinc-200/80 rounded-lg p-6 sm:p-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
            <div class="space-y-1 max-w-xl">
                <h3 class="text-sm font-bold text-zinc-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    Impor Jadwal FET PDF Otomatis dengan AI
                </h3>
                <p class="text-xs text-zinc-500 leading-relaxed">
                    Fitur unggah berkas PDF jadwal FET sekarang tersedia langsung di halaman <strong class="text-zinc-800">Jadwal Pelajaran</strong>. Anda dapat mengunggah berkas jadwal sekaligus memilih siklus target (<span class="font-mono font-semibold text-zinc-800">Putaran 1</span> atau <span class="font-mono font-semibold text-zinc-800">Putaran 2</span>) secara langsung.
                </p>
            </div>
            <a 
                href="{{ route('schedules.index') }}" 
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-zinc-900 hover:bg-black text-white text-xs font-semibold rounded-md uppercase tracking-wider transition shrink-0 shadow-none"
            >
                <span>Buka Menu Jadwal</span>
                <span class="font-mono">&rarr;</span>
            </a>
        </div>

        {{-- STATUS SUMMARY CARD --}}
        <div class="bg-zinc-50 border border-zinc-200/80 rounded-lg p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 font-mono text-xs">
            <div class="flex items-center gap-2">
                <span class="text-zinc-500">Status Sistem:</span>
                <span class="font-bold text-zinc-900 uppercase">{{ $config->active_cycle ?? 'Putaran 1' }}</span>
                @if($config->is_schedule_locked ?? false)
                    <span class="text-zinc-300">&bull;</span>
                    <span class="font-bold uppercase text-rose-600">Jadwal Terkunci</span>
                @endif
            </div>
            <div class="text-zinc-400">
                Terakhir diubah: {{ $config->updated_at ? $config->updated_at->diffForHumans() : '-' }}
            </div>
        </div>

    </div>
</x-app-layout>
