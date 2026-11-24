<x-app-layout>
    <x-slot name="header">
        <x-page-header 
            :title="Auth::user()->role_id === 5 ? 'Pengaturan Sistem & Teknis' : 'Pengaturan Kurikulum'" 
            :subtitle="Auth::user()->role_id === 5 ? 'Pengaturan global siklus jadwal, batas waktu jurnal, integrasi AI, dan server email' : 'Pengaturan global siklus putaran jadwal, status kunci, dan batas waktu pengisian jurnal'" 
        />
    </x-slot>

    <div class="space-y-6">

        {{-- Flash Message --}}
        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if(session('error'))
            <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            
            {{-- Kontrol putaran dan kunci jadwal --}}
            <div class="space-y-6">
                <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl overflow-hidden divide-y divide-zinc-200 dark:divide-[#2B2B30]">
                    <div class="p-5 sm:p-6">
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight flex items-center gap-2">
                            <svg class="w-5 h-5 text-zinc-700 dark:text-zinc-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />
                            </svg>
                            Kontrol Sistem Putaran &amp; Kunci Jadwal
                        </h2>
                        <p class="text-xs text-zinc-500 dark:text-[#97979B] mt-1">
                            Pengaturan global yang mengatur siklus 2-mingguan dan hak akses perubahan jadwal bagi seluruh guru pengajar.
                        </p>
                    </div>

                    {{-- 1. CYCLE TOGGLE (Putaran 1 / Putaran 2) --}}
                    <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <span class="text-sm font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight block">
                                Putaran Block Schedule Aktif
                            </span>
                            <p class="text-xs text-zinc-600 dark:text-[#9496A1] max-w-sm leading-relaxed">
                                Pilih putaran kalender akademik yang aktif saat ini. Seluruh jadwal dan filter akan menyesuaikan putaran terpilih.
                            </p>
                            @if($config->is_schedule_locked ?? false)
                                <p class="text-xs text-rose-700 dark:text-rose-400 font-medium">
                                    Putaran dikunci. Buka kunci jadwal di bawah untuk mengubah putaran.
                                </p>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('system-configuration.toggle-cycle') }}" class="shrink-0">
                            @csrf
                            @method('PATCH')
                            
                            <div class="inline-flex p-1 bg-zinc-100 dark:bg-[#26262A] rounded-lg border border-zinc-200 dark:border-[#2B2B30]">
                                <button
                                    type="submit"
                                    name="cycle"
                                    value="Putaran 1"
                                    @disabled($config->is_schedule_locked ?? false)
                                    class="min-h-[44px] sm:min-h-0 px-3.5 py-1.5 text-xs font-semibold rounded-md transition duration-150 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed {{ ($config->active_cycle ?? 'Putaran 1') === 'Putaran 1' ? 'bg-white dark:bg-[#1E1E22] text-zinc-900 dark:text-[#EDEDF0] shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }}"
                                >
                                    Putaran 1
                                </button>
                                <button
                                    type="submit"
                                    name="cycle"
                                    value="Putaran 2"
                                    @disabled($config->is_schedule_locked ?? false)
                                    class="min-h-[44px] sm:min-h-0 px-3.5 py-1.5 text-xs font-semibold rounded-md transition duration-150 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed {{ ($config->active_cycle ?? 'Putaran 1') === 'Putaran 2' ? 'bg-white dark:bg-[#1E1E22] text-zinc-900 dark:text-[#EDEDF0] shadow-xs' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white' }}"
                                >
                                    Putaran 2
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- 2. LOCK SCHEDULE TOGGLE --}}
                    <div class="p-5 sm:p-6 flex items-start sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <label for="lock-toggle-btn" class="text-sm font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight block">
                                    Kunci Jadwal Mengajar (Lock Schedule)
                                </label>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ ($config->is_schedule_locked ?? false) ? 'bg-rose-100 text-rose-800 dark:bg-[#2D1618] dark:text-rose-300 border border-rose-300 dark:border-rose-700/60' : 'bg-emerald-100 text-emerald-800 dark:bg-[#16281D] dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700/60' }}">
                                    {{ ($config->is_schedule_locked ?? false) ? 'Terkunci' : 'Terbuka' }}
                                </span>
                            </div>
                            <p class="text-xs text-zinc-600 dark:text-[#9496A1] max-w-sm leading-relaxed">
                                Saat terkunci, seluruh guru dilarang menambah, mengubah, atau menghapus jadwal pelajaran demi menjaga stabilitas data.
                            </p>
                            <p class="text-xs text-zinc-600 dark:text-[#9496A1] leading-relaxed">
                                Tukar jadwal guru tetap bisa dilakukan saat jadwal terkunci.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('system-configuration.toggle-lock') }}" class="shrink-0">
                            @csrf
                            @method('PATCH')

                            <div x-data="{ isLocked: {{ ($config->is_schedule_locked ?? false) ? 'true' : 'false' }} }">
                                <button
                                    id="lock-toggle-btn"
                                    type="submit"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0073B1] focus-visible:ring-offset-2 dark:focus-visible:ring-offset-[#1E1E22]"
                                    :class="isLocked ? 'bg-rose-600' : 'bg-zinc-300 dark:bg-zinc-700'"
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

                {{-- Khusus Superadmin: Pengaturan Email (SMTP Server) --}}
                @if(Auth::user()->role_id === 5)
                <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-5 sm:p-6 space-y-5">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="text-sm font-semibold text-zinc-900 dark:text-[#EDEDF0] flex items-center gap-2">
                                    <svg class="w-4 h-4 text-[#0073B1] dark:text-[#38BDF8] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                    </svg>
                                    Pengaturan Email (SMTP Server)
                                </h2>
                                @if(!empty($config->mail_host))
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-[#16281D] dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700/60">
                                        Terkonfigurasi
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-[#282115] dark:text-amber-300 border border-amber-300 dark:border-amber-700/60">
                                        Belum diatur
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-zinc-600 dark:text-[#9496A1] leading-relaxed">
                                Atur host SMTP, port, enkripsi, dan kredensial pengirim untuk pengiriman kode reset kata sandi mandiri.
                            </p>
                        </div>
                        <a 
                            href="{{ route('system-configuration.email') }}" 
                            class="inline-flex items-center justify-center gap-1.5 min-h-[44px] sm:min-h-0 px-3.5 py-2 bg-[#0073B1] hover:bg-[#005D8F] active:bg-[#004A73] text-white text-xs font-semibold rounded-lg transition shrink-0 shadow-none"
                        >
                            <span>Kelola Email SMTP</span>
                        </a>
                    </div>
                </div>
                @endif
            </div>

            {{-- Kolom Kanan: Aturan Jurnal & Teknis AI --}}
            <div class="space-y-6">
                {{-- Pengaturan Waktu Pengisian Jurnal KBM --}}
                <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl overflow-hidden divide-y divide-zinc-200 dark:divide-[#2B2B30]">
                    <div class="p-5 sm:p-6">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-[#0073B1] dark:text-[#38BDF8]" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">
                                Aturan Waktu Pengisian Jurnal KBM
                            </h2>
                        </div>
                        <p class="text-xs text-zinc-600 dark:text-[#9496A1] mt-1 leading-relaxed">
                            Batasi waktu pengisian jurnal pembelajaran harian guru agar jurnal hanya dapat diisi saat atau setelah jam pelajaran berlangsung.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('waka.settings.journal-time') }}" class="p-5 sm:p-6 space-y-5" x-data="{ restricted: {{ ($config->is_journal_time_restricted ?? true) ? 'true' : 'false' }} }">
                        @csrf
                        
                        {{-- Toggle Pembatasan --}}
                        <div class="flex items-start sm:items-center justify-between gap-4">
                            <div>
                                <label for="is_journal_time_restricted" class="text-sm font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight block cursor-pointer">
                                    Aktifkan Pembatasan Waktu
                                </label>
                                <p class="text-xs text-zinc-600 dark:text-[#9496A1] leading-relaxed">
                                    Jika diaktifkan, guru tidak bisa mengisi jurnal sebelum jam pelajaran dimulai.
                                </p>
                            </div>
                            <div class="shrink-0 flex items-center pt-0.5 sm:pt-0">
                                <input
                                    type="checkbox"
                                    switch
                                    id="is_journal_time_restricted"
                                    name="is_journal_time_restricted"
                                    value="1"
                                    x-model="restricted"
                                >
                            </div>
                        </div>

                        {{-- Mode Aturan Waktu --}}
                        <div class="space-y-2">
                            <span class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-[#EDEDF0]">
                                Mode Validasi Waktu
                            </span>
                            <div class="grid grid-cols-1 gap-2.5">
                                <label class="flex items-start gap-3 p-3 rounded-lg border border-zinc-200 dark:border-[#2B2B30] hover:bg-zinc-50 dark:hover:bg-[#25252A] cursor-pointer">
                                    <input type="radio" name="journal_fill_rule" value="during_and_after" class="mt-0.5 text-[#0073B1] focus:ring-[#0073B1]" {{ ($config->journal_fill_rule ?? 'during_and_after') === 'during_and_after' ? 'checked' : '' }}>
                                    <div>
                                        <span class="text-xs font-semibold text-zinc-900 dark:text-[#EDEDF0] block">Mulai Saat KBM Berjalan &amp; Setelahnya (Direkomendasikan)</span>
                                        <span class="text-[11px] text-zinc-600 dark:text-[#9496A1] leading-relaxed block">Guru hanya dapat mengisi setelah jam pelajaran dimulai (start_time) hingga akhir hari (23:59). Tidak bisa mendahului jadwal.</span>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 p-3 rounded-lg border border-zinc-200 dark:border-[#2B2B30] hover:bg-zinc-50 dark:hover:bg-[#25252A] cursor-pointer">
                                    <input type="radio" name="journal_fill_rule" value="during_schedule_only" class="mt-0.5 text-[#0073B1] focus:ring-[#0073B1]" {{ ($config->journal_fill_rule ?? '') === 'during_schedule_only' ? 'checked' : '' }}>
                                    <div>
                                        <span class="text-xs font-semibold text-zinc-900 dark:text-[#EDEDF0] block">Hanya Saat Jam KBM Sedang Berlangsung</span>
                                        <span class="text-[11px] text-zinc-600 dark:text-[#9496A1] leading-relaxed block">Jurnal wajib diisi tepat di antara jam mulai dan jam selesai (+ toleransi 15 menit). Pengisian di luar jam akan ditolak.</span>
                                    </div>
                                </label>

                                <label class="flex items-start gap-3 p-3 rounded-lg border border-zinc-200 dark:border-[#2B2B30] hover:bg-zinc-50 dark:hover:bg-[#25252A] cursor-pointer">
                                    <input type="radio" name="journal_fill_rule" value="anytime" class="mt-0.5 text-[#0073B1] focus:ring-[#0073B1]" {{ ($config->journal_fill_rule ?? '') === 'anytime' ? 'checked' : '' }}>
                                    <div>
                                        <span class="text-xs font-semibold text-zinc-900 dark:text-[#EDEDF0] block">Bebas (Tanpa Pembatasan Waktu)</span>
                                        <span class="text-[11px] text-zinc-600 dark:text-[#9496A1] leading-relaxed block">Guru dapat mengisi jurnal kapan saja tanpa pembatasan jam mulai maupun selesai.</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Toleransi Buka Lebih Awal --}}
                        <div class="space-y-1.5">
                            <label for="journal_early_buffer_minutes" class="text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-[#EDEDF0]">
                                Toleransi Buka Lebih Awal (Menit)
                            </label>
                            <div class="flex items-center gap-3">
                                <input type="number" id="journal_early_buffer_minutes" name="journal_early_buffer_minutes" min="0" max="60" value="{{ $config->journal_early_buffer_minutes ?? 0 }}" class="w-24 px-3 py-1.5 text-xs rounded-lg border border-zinc-200 dark:border-[#2B2B30] bg-zinc-50 dark:bg-[#161619] text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0073B1]">
                                <span class="text-xs text-zinc-600 dark:text-[#9496A1]">menit sebelum jam pelajaran dimulai (0 = tepat waktu jam mulai)</span>
                            </div>
                        </div>

                        <div class="pt-2 flex justify-end">
                            <x-primary-button>Simpan Aturan Jurnal</x-primary-button>
                        </div>
                    </form>
                </div>

                {{-- Khusus Non-Superadmin (Waka): Info Impor Jadwal FET --}}
                @if(Auth::user()->role_id !== 5)
                <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-5 sm:p-6">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-[#EDEDF0] flex items-center gap-2">
                                <svg class="w-4 h-4 text-[#0073B1] dark:text-[#38BDF8] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12-3-3m0 0-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                Impor Jadwal FET
                            </h2>
                            <p class="text-xs text-zinc-600 dark:text-[#9496A1] leading-relaxed">
                                Fitur unggah berkas PDF jadwal FET tersedia langsung pada menu Jadwal Pelajaran untuk siklus Putaran 1 atau Putaran 2.
                            </p>
                        </div>
                        <a 
                            href="{{ route('schedules.index') }}" 
                            class="inline-flex items-center gap-1.5 min-h-[44px] sm:min-h-0 px-3.5 py-2 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-800 dark:text-[#EDEDF0] text-xs font-semibold rounded-lg border border-zinc-200 dark:border-zinc-700 transition shrink-0"
                        >
                            <span>Buka Menu Jadwal</span>
                        </a>
                    </div>
                </div>
                @endif

                {{-- Khusus Superadmin: Konfigurasi Google Gemini AI --}}
                @if(Auth::user()->role_id === 5)
                <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl overflow-hidden divide-y divide-zinc-200 dark:divide-[#2B2B30]">
                    <div class="p-5 sm:p-6">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight flex items-center gap-2">
                                    <svg class="w-5 h-5 text-[#0073B1] dark:text-[#38BDF8]" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                                    </svg>
                                    Konfigurasi Google Gemini AI
                                </h2>
                                <p class="text-xs text-zinc-600 dark:text-[#9496A1] mt-1">
                                    Kunci API dan model kecerdasan buatan untuk ekstraksi otomatis dokumen PDF jadwal FET.
                                </p>
                            </div>
                            @if($config->effective_gemini_api_key)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-emerald-50 dark:bg-[#16281D] text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700/60 text-xs font-semibold shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 dark:bg-emerald-400"></span> AI Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-amber-50 dark:bg-[#282115] text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-700/60 text-xs font-semibold shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600 dark:bg-amber-400"></span> Belum Ada API Key
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="p-5 sm:p-6">
                        <form method="POST" action="{{ route('waka.settings.gemini') }}" class="space-y-5">
                            @csrf

                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <x-input-label for="gemini_api_key" value="Google Gemini API Key" />
                                    @if($config->masked_api_key)
                                        <span class="font-mono text-[11px] text-zinc-600 dark:text-[#9496A1]">
                                            Saat ini: {{ $config->masked_api_key }}
                                        </span>
                                    @endif
                                </div>
                                <x-password-input
                                    id="gemini_api_key"
                                    name="gemini_api_key"
                                    autocomplete="off"
                                    :required="false"
                                    :invalid="$errors->has('gemini_api_key')"
                                    class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] pl-3.5 pr-11 py-2.5 text-xs font-mono text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1] shadow-none transition"
                                    :placeholder="$config->effective_gemini_api_key ? 'Ketik API key baru untuk mengubah…' : 'Masukkan Google Gemini API Key Anda (AIza…)'"
                                />
                                <p class="text-[11px] text-zinc-600 dark:text-[#9496A1] mt-1">
                                    Mendukung model Gemini 3.5 &amp; 3.7. Kunci disimpan terenkripsi di database.
                                </p>
                                <x-input-error :messages="$errors->get('gemini_api_key')" class="mt-1" />
                            </div>

                            <div class="space-y-4">
                                {{-- 1. Model untuk Ekstraksi Jadwal --}}
                                <div>
                                    <x-input-label for="gemini_schedule_model" value="Model AI Ekstraksi Jadwal FET" />
                                    <select
                                        id="gemini_schedule_model"
                                        name="gemini_schedule_model"
                                        class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] pl-3.5 pr-9 py-2.5 text-xs font-normal text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1] shadow-none transition cursor-pointer"
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
                                    <p class="text-[11px] text-zinc-600 dark:text-[#9496A1] mt-1">High Multimodal Reasoning untuk tabel &amp; dokumen PDF.</p>
                                    <x-input-error :messages="$errors->get('gemini_schedule_model')" class="mt-1" />
                                </div>

                                {{-- 2. Model untuk Chatbot Guru --}}
                                <div>
                                    <x-input-label for="gemini_chat_model" value="Model AI Chatbot Guru (SCP Assistant)" />
                                    <select
                                        id="gemini_chat_model"
                                        name="gemini_chat_model"
                                        class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] pl-3.5 pr-9 py-2.5 text-xs font-normal text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1] shadow-none transition cursor-pointer"
                                        required
                                    >
                                        @php
                                            $currentChatModel = old('gemini_chat_model', $config->effective_gemini_chat_model);
                                        @endphp
                                        <option value="gemini-3.5-flash-lite" @selected($currentChatModel === 'gemini-3.5-flash-lite')>
                                            gemini-3.5-flash-lite (Disarankan — Cepat &amp; Hemat Kuota)
                                        </option>
                                        <option value="gemini-3.1-flash-lite" @selected($currentChatModel === 'gemini-3.1-flash-lite')>
                                            gemini-3.1-flash-lite (Alternatif Kuota Tinggi)
                                        </option>
                                        <option value="gemini-3.5-flash" @selected($currentChatModel === 'gemini-3.5-flash')>
                                            gemini-3.5-flash (Respon Lebih Elaboratif)
                                        </option>
                                    </select>
                                    <p class="text-[11px] text-zinc-600 dark:text-[#9496A1] mt-1">Optimasi batas kuota harian &amp; respons interaktif cepat.</p>
                                    <x-input-error :messages="$errors->get('gemini_chat_model')" class="mt-1" />
                                </div>
                            </div>

                            <div class="pt-2 flex justify-end">
                                <x-primary-button>Simpan Konfigurasi AI</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
                @endif

        </div>

    </div>
</x-app-layout>
