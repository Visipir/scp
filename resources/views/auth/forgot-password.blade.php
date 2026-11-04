<x-guest-layout>
    <div class="mb-4">
        <h2 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">Lupa Kata Sandi?</h2>
        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
            Masukkan alamat email yang terdaftar pada akun Anda. Kami akan mengirimkan kode verifikasi 6 digit untuk mengatur ulang kata sandi.
        </p>
    </div>

    <!-- Alert untuk siswa/ortu tanpa email -->
    <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-lg text-xs text-amber-800 dark:text-amber-200 leading-relaxed">
        <strong>Informasi Siswa / Orang Tua:</strong> Bagi siswa atau orang tua yang belum mendaftarkan email di akun SCP, silakan hubungi wali kelas atau Waka Kurikulum untuk bantuan reset kata sandi.
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Alamat Email Terdaftar')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" placeholder="nama@sekolah.sch.id" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <a href="{{ route('login') }}" class="text-xs text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200 transition">
                &larr; Kembali ke Login
            </a>

            <x-primary-button>
                Kirim Kode Reset
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
