<x-app-layout>
    <x-slot name="header">
        <x-page-header 
            title="Pengaturan Email" 
            subtitle="Konfigurasi server email SMTP dan kredensial pengirim notifikasi sistem"
        >
            <a href="{{ route('waka.settings') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium text-zinc-700 dark:text-zinc-200 hover:text-zinc-900 dark:hover:text-white bg-white dark:bg-[#1E1E22] rounded-lg border border-zinc-200 dark:border-[#2B2B30] hover:bg-zinc-50 dark:hover:bg-[#26262A] transition">
                &larr; Kembali ke Pengaturan Kurikulum
            </a>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">

        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if(session('error'))
            <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            {{-- KOLOM 1: FORM UTAMA KONFIGURASI SMTP (col-span-8) --}}
            <div class="lg:col-span-8 bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl overflow-hidden divide-y divide-zinc-200 dark:divide-[#2B2B30]">
                <div class="p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">
                        Server SMTP &amp; Pengirim
                    </h2>
                    <p class="text-xs text-zinc-500 dark:text-[#97979B] mt-1">
                        Konfigurasi server email untuk pengiriman kode reset kata sandi dan notifikasi sistem.
                    </p>
                </div>

                <form method="POST" action="{{ route('system-configuration.email.update') }}" class="p-5 sm:p-6 space-y-5">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label for="mail_host" class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                SMTP Host
                            </label>
                            <input
                                type="text"
                                name="mail_host"
                                id="mail_host"
                                value="{{ old('mail_host', $settings['mail_host']) }}"
                                placeholder="misal: smtp.gmail.com atau mail.sekolah.sch.id"
                                class="w-full text-xs rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] px-3.5 py-2.5 shadow-none transition"
                            >
                            @error('mail_host')
                                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="mail_port" class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Port
                            </label>
                            <input
                                type="number"
                                name="mail_port"
                                id="mail_port"
                                value="{{ old('mail_port', $settings['mail_port']) }}"
                                placeholder="587"
                                class="w-full text-xs rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] px-3.5 py-2.5 shadow-none transition font-mono"
                            >
                            @error('mail_port')
                                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="mail_encryption" class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Enkripsi
                            </label>
                            <select
                                name="mail_encryption"
                                id="mail_encryption"
                                class="w-full text-xs rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] px-3.5 py-2.5 shadow-none transition cursor-pointer"
                            >
                                <option value="tls" {{ old('mail_encryption', $settings['mail_encryption']) === 'tls' ? 'selected' : '' }}>TLS (Direkomendasikan untuk port 587)</option>
                                <option value="ssl" {{ old('mail_encryption', $settings['mail_encryption']) === 'ssl' ? 'selected' : '' }}>SSL (Port 465)</option>
                                <option value="none" {{ old('mail_encryption', $settings['mail_encryption']) === 'none' ? 'selected' : '' }}>None (Tanpa enkripsi)</option>
                            </select>
                            @error('mail_encryption')
                                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="mail_username" class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Username SMTP
                            </label>
                            <input
                                type="text"
                                name="mail_username"
                                id="mail_username"
                                value="{{ old('mail_username', $settings['mail_username']) }}"
                                placeholder="user@example.test"
                                class="w-full text-xs rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] px-3.5 py-2.5 shadow-none transition"
                            >
                            @error('mail_username')
                                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="mail_password" class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Password SMTP
                        </label>
                        <input
                            type="password"
                            name="mail_password"
                            id="mail_password"
                            placeholder="{{ !empty($settings['mail_password']) ? '•••••• (Biarkan kosong jika tidak diubah)' : 'Password SMTP / App Password' }}"
                            class="w-full text-xs rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] px-3.5 py-2.5 shadow-none transition font-mono"
                        >
                        <p class="text-[11px] text-zinc-500 dark:text-[#97979B] mt-1">
                            Password dienkripsi secara aman dengan Laravel Crypt. Untuk Gmail, gunakan <em>App Password</em> 16 digit.
                        </p>
                        @error('mail_password')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-zinc-100 dark:border-[#2B2B30]">
                        <div>
                            <label for="mail_from_address" class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Email Pengirim (From Address)
                            </label>
                            <input
                                type="email"
                                name="mail_from_address"
                                id="mail_from_address"
                                value="{{ old('mail_from_address', $settings['mail_from_address']) }}"
                                placeholder="noreply@example.test"
                                class="w-full text-xs rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] px-3.5 py-2.5 shadow-none transition"
                            >
                            @error('mail_from_address')
                                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="mail_from_name" class="block text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Nama Pengirim (From Name)
                            </label>
                            <input
                                type="text"
                                name="mail_from_name"
                                id="mail_from_name"
                                value="{{ old('mail_from_name', $settings['mail_from_name']) }}"
                                placeholder="SCP - SMKN 1 Bantul"
                                class="w-full text-xs rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] px-3.5 py-2.5 shadow-none transition"
                            >
                            @error('mail_from_name')
                                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-2 border-t border-zinc-100 dark:border-[#2B2B30]">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                name="mail_ignore_cert"
                                value="1"
                                {{ old('mail_ignore_cert', $settings['mail_ignore_cert'] ?? false) ? 'checked' : '' }}
                                class="rounded border-zinc-300 dark:border-zinc-700 text-[#0093dd] focus:ring-[#0093dd] bg-white dark:bg-[#26262A]"
                            >
                            <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                Abaikan verifikasi sertifikat SSL (self-signed)
                            </span>
                        </label>
                        <p class="text-[11px] text-zinc-500 dark:text-[#97979B] mt-0.5 ml-6">
                            Centang opsi ini jika server mail lokal / sekolah menggunakan sertifikat self-signed tanpa validasi CA publik.
                        </p>
                    </div>

                    <div class="pt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-zinc-100 dark:border-[#2B2B30]">
                        <span class="text-xs text-zinc-500 dark:text-[#97979B]">
                            Biarkan kosong untuk menggunakan konfigurasi bawaan .env
                        </span>
                        <x-primary-button>
                            Simpan Pengaturan
                        </x-primary-button>
                    </div>
                </form>
            </div>

            {{-- KOLOM 2: UJI PENGIRIMAN & PANDUAN (col-span-4) --}}
            <div class="lg:col-span-4 space-y-6">
                {{-- Uji Coba Pengiriman Email --}}
                <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-5 sm:p-6 space-y-4">
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-[#EDEDF0]">Uji Coba Pengiriman Email</h3>
                        <p class="text-xs text-zinc-500 dark:text-[#97979B] mt-1 leading-relaxed">
                            Kirim email tes menggunakan konfigurasi SMTP di samping ke alamat email akun Anda:
                        </p>
                        <div class="mt-2 text-xs">
                            @if(empty(auth()->user()->email))
                                <span class="text-rose-500 font-semibold">(Email belum diisi pada profil Anda)</span>
                            @elseif(empty(auth()->user()->email_verified_at))
                                <span class="font-mono text-zinc-800 dark:text-zinc-200">{{ auth()->user()->email }}</span>
                                <span class="text-amber-500 font-semibold block mt-0.5">(Belum diverifikasi di profil)</span>
                            @else
                                <strong class="font-mono text-zinc-800 dark:text-zinc-200">{{ auth()->user()->email }}</strong>
                                <span class="text-emerald-600 font-semibold block mt-0.5">(Terverifikasi)</span>
                            @endif
                        </div>
                    </div>

                    <form method="POST" action="{{ route('system-configuration.email.test') }}">
                        @csrf
                        <button
                            type="submit"
                            class="w-full px-4 py-2.5 text-xs font-semibold rounded-lg border border-zinc-300 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 bg-zinc-50 dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                            {{ (empty(auth()->user()->email) || empty(auth()->user()->email_verified_at)) ? 'disabled title="Harap verifikasi email di profil terlebih dahulu"' : '' }}
                        >
                            Kirim Email Tes
                        </button>
                    </form>
                </div>

                {{-- Panduan Singkat --}}
                <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-5 sm:p-6 space-y-3">
                    <h4 class="text-xs font-semibold text-zinc-900 dark:text-[#EDEDF0] uppercase tracking-wider">
                        Panduan SMTP
                    </h4>
                    <ul class="text-xs text-zinc-500 dark:text-[#97979B] space-y-2 leading-relaxed list-disc list-inside">
                        <li><strong>Gmail:</strong> Host <code class="text-zinc-800 dark:text-zinc-300">smtp.gmail.com</code>, Port 587, Enkripsi TLS, gunakan App Password 16 karakter.</li>
                        <li><strong>Mailtrap / Sandbox:</strong> Gunakan kredensial inbox testing untuk lingkungan staging.</li>
                        <li><strong>Domain Sekolah:</strong> Pastikan SPF dan DKIM aktif di server hosting sekolah.</li>
                    </ul>
                </div>
            </div>

        </div>

        {{-- PANEL RIWAYAT PENGIRIMAN EMAIL (20 Terakhir) --}}
        <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl overflow-hidden divide-y divide-zinc-200 dark:divide-[#2B2B30]">
            <div class="p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">
                        Riwayat Pengiriman Email
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-[#97979B] mt-0.5">
                        Log 20 pengiriman email terakhir (alamat penerima disamarkan demi privasi).
                    </p>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-mono font-medium bg-zinc-100 dark:bg-[#26262A] text-zinc-700 dark:text-zinc-300">
                    {{ count($mailLogs ?? []) }} Catatan
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs divide-y divide-zinc-200 dark:divide-[#2B2B30]">
                    <thead class="bg-zinc-50 dark:bg-[#26262A] text-zinc-500 dark:text-[#97979B] font-mono text-[11px] uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3 font-semibold">Waktu</th>
                            <th class="px-5 py-3 font-semibold">Tipe Email</th>
                            <th class="px-5 py-3 font-semibold">Penerima</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 font-semibold">Keterangan / Error</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-[#2B2B30] font-sans">
                        @forelse($mailLogs ?? [] as $log)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-[#26262A]/50 transition">
                                <td class="px-5 py-3 font-mono text-zinc-600 dark:text-zinc-400 whitespace-nowrap">
                                    {{ $log->created_at ? $log->created_at->setTimezone('Asia/Jakarta')->format('d M Y, H:i:s') : '-' }}
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    @php
                                        $typeLabels = [
                                            'test'               => ['label' => 'Tes Email', 'color' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border-blue-200 dark:border-blue-800'],
                                            'password_reset'     => ['label' => 'Reset Kata Sandi', 'color' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border-amber-200 dark:border-amber-800'],
                                            'email_verification' => ['label' => 'Verifikasi Email', 'color' => 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 border-purple-200 dark:border-purple-800'],
                                        ];
                                        $typeInfo = $typeLabels[$log->mail_type] ?? ['label' => ucfirst($log->mail_type), 'color' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700'];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium border {{ $typeInfo['color'] }}">
                                        {{ $typeInfo['label'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 font-mono text-zinc-800 dark:text-[#EDEDF0] whitespace-nowrap">
                                    {{ $log->recipient }}
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    @if($log->status === 'success')
                                        <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400 font-semibold text-[11px]">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Terkirim
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-rose-600 dark:text-rose-400 font-semibold text-[11px]">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Gagal
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-zinc-600 dark:text-zinc-400 max-w-md truncate" title="{{ $log->error_message }}">
                                    {{ $log->error_message ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-zinc-400 dark:text-zinc-500">
                                    Belum ada riwayat pengiriman email yang tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-app-layout>
