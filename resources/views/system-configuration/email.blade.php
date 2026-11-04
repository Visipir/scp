<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="text-xs font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider">Sistem Konfigurasi</div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0] mt-0.5">
                    Pengaturan Email (SMTP Server)
                </h1>
            </div>
            <div>
                <a href="{{ route('waka.settings') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white bg-zinc-100 dark:bg-zinc-800 rounded-md border border-zinc-200 dark:border-zinc-700 transition">
                    &larr; Kembali ke Pengaturan Sistem
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6 max-w-3xl">

        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if(session('error'))
            <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
        @endif

        {{-- Configuration Main Form --}}
        <div class="bg-white dark:bg-[#1C1C1E] border border-zinc-200 dark:border-[#2C2C2E] rounded-xl shadow-xs overflow-hidden">
            <div class="p-6 border-b border-zinc-200 dark:border-[#2C2C2E]">
                <h2 class="text-base font-bold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">
                    Server SMTP &amp; Pengirim
                </h2>
                <p class="text-xs text-zinc-500 dark:text-[#97979B] mt-1">
                    Konfigurasi server email untuk pengiriman kode reset kata sandi dan notifikasi sistem.
                </p>
            </div>

            <form method="POST" action="{{ route('system-configuration.email.update') }}" class="p-6 space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label for="mail_host" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            SMTP Host
                        </label>
                        <input
                            type="text"
                            name="mail_host"
                            id="mail_host"
                            value="{{ old('mail_host', $settings['mail_host']) }}"
                            placeholder="misal: smtp.gmail.com atau mail.sekolah.sch.id"
                            class="w-full text-sm rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#2C2C2E] text-zinc-900 dark:text-zinc-100 focus:ring-blue-500 focus:border-blue-500"
                        >
                        @error('mail_host')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="mail_port" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Port
                        </label>
                        <input
                            type="number"
                            name="mail_port"
                            id="mail_port"
                            value="{{ old('mail_port', $settings['mail_port']) }}"
                            placeholder="587"
                            class="w-full text-sm rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#2C2C2E] text-zinc-900 dark:text-zinc-100 focus:ring-blue-500 focus:border-blue-500"
                        >
                        @error('mail_port')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="mail_encryption" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Enkripsi
                        </label>
                        <select
                            name="mail_encryption"
                            id="mail_encryption"
                            class="w-full text-sm rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#2C2C2E] text-zinc-900 dark:text-zinc-100 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="tls" {{ old('mail_encryption', $settings['mail_encryption']) === 'tls' ? 'selected' : '' }}>TLS (Direkomendasikan - Port 587)</option>
                            <option value="ssl" {{ old('mail_encryption', $settings['mail_encryption']) === 'ssl' ? 'selected' : '' }}>SSL (Port 465)</option>
                            <option value="none" {{ old('mail_encryption', $settings['mail_encryption']) === 'none' ? 'selected' : '' }}>Tanpa Enkripsi (None)</option>
                        </select>
                        @error('mail_encryption')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="mail_username" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            SMTP Username
                        </label>
                        <input
                            type="text"
                            name="mail_username"
                            id="mail_username"
                            value="{{ old('mail_username', $settings['mail_username']) }}"
                            placeholder="admin@sekolah.sch.id"
                            class="w-full text-sm rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#2C2C2E] text-zinc-900 dark:text-zinc-100 focus:ring-blue-500 focus:border-blue-500"
                        >
                        @error('mail_username')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="mail_password" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                        SMTP Password / App Password
                    </label>
                    <input
                        type="password"
                        name="mail_password"
                        id="mail_password"
                        placeholder="{{ !empty($settings['mail_password']) ? '•••••• (Kosongkan jika tidak ingin mengubah)' : 'Masukkan password SMTP' }}"
                        class="w-full text-sm rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#2C2C2E] text-zinc-900 dark:text-zinc-100 focus:ring-blue-500 focus:border-blue-500"
                    >
                    <p class="mt-1 text-xs text-zinc-500 dark:text-[#97979B]">
                        Password disimpan secara aman dengan enkripsi (Crypt). Kosongkan input ini jika tidak ingin mengubah kata sandi tersimpan.
                    </p>
                    @error('mail_password')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                    <div>
                        <label for="mail_from_address" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Email Pengirim (From Address)
                        </label>
                        <input
                            type="email"
                            name="mail_from_address"
                            id="mail_from_address"
                            value="{{ old('mail_from_address', $settings['mail_from_address']) }}"
                            placeholder="noreply@sekolah.sch.id"
                            class="w-full text-sm rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#2C2C2E] text-zinc-900 dark:text-zinc-100 focus:ring-blue-500 focus:border-blue-500"
                        >
                        @error('mail_from_address')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="mail_from_name" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Nama Pengirim (From Name)
                        </label>
                        <input
                            type="text"
                            name="mail_from_name"
                            id="mail_from_name"
                            value="{{ old('mail_from_name', $settings['mail_from_name']) }}"
                            placeholder="SCP - SMKN 1 Bantul"
                            class="w-full text-sm rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#2C2C2E] text-zinc-900 dark:text-zinc-100 focus:ring-blue-500 focus:border-blue-500"
                        >
                        @error('mail_from_name')
                            <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-between border-t border-zinc-100 dark:border-zinc-800">
                    <span class="text-xs text-zinc-500 dark:text-[#97979B]">
                        Biarkan kosong untuk menggunakan konfigurasi bawaan file .env
                    </span>
                    <button
                        type="submit"
                        class="px-5 py-2 text-xs font-bold uppercase tracking-wider text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-xs transition"
                    >
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>

        {{-- Test Mail Box --}}
        <div class="bg-white dark:bg-[#1C1C1E] border border-zinc-200 dark:border-[#2C2C2E] rounded-xl shadow-xs p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-zinc-900 dark:text-[#EDEDF0]">Uji Coba Pengiriman Email</h3>
                <p class="text-xs text-zinc-500 dark:text-[#97979B] mt-0.5">
                    Kirim email tes menggunakan konfigurasi di atas ke alamat email akun Anda: 
                    @if(empty(auth()->user()->email))
                        <span class="text-rose-500 font-semibold">(Email belum diisi pada profil Anda)</span>
                    @elseif(empty(auth()->user()->email_verified_at))
                        <span class="font-mono text-zinc-800 dark:text-zinc-200">{{ auth()->user()->email }}</span>
                        <span class="text-amber-500 font-semibold">(Belum diverifikasi di profil)</span>
                    @else
                        <strong class="font-mono text-zinc-800 dark:text-zinc-200">{{ auth()->user()->email }}</strong>
                        <span class="text-emerald-600 font-semibold">(Terverifikasi)</span>
                    @endif
                </p>
            </div>
            <form method="POST" action="{{ route('system-configuration.email.test') }}" class="shrink-0">
                @csrf
                <button
                    type="submit"
                    class="px-4 py-2 text-xs font-bold uppercase tracking-wider rounded-lg border border-zinc-300 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 bg-zinc-50 dark:bg-zinc-800 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition disabled:opacity-50 disabled:cursor-not-allowed"
                    {{ (empty(auth()->user()->email) || empty(auth()->user()->email_verified_at)) ? 'disabled title="Harap verifikasi email di profil terlebih dahulu"' : '' }}
                >
                    Kirim Email Tes
                </button>
            </form>
        </div>

    </div>
</x-app-layout>
