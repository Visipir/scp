<x-guest-layout>
    <div class="mb-4">
        <h2 class="text-lg font-bold auth-header-title">Lupa Kata Sandi?</h2>
        <p class="text-sm text-muted-token mt-1">
            Masukkan alamat email terdaftar atau Nama Pengguna / NIS akun Anda. Jika akun memiliki email terverifikasi, kami akan mengirimkan kode verifikasi 6 digit untuk mengatur ulang kata sandi.
        </p>
    </div>

    <!-- Alert untuk siswa/ortu tanpa email -->
    <div class="mb-4 p-3 bg-amber-50 dark:bg-[#282115] border border-amber-300 dark:border-amber-700/60 rounded-lg text-xs text-amber-800 dark:text-amber-300 leading-relaxed">
        <strong>Informasi Siswa / Orang Tua:</strong> Bagi siswa atau orang tua yang belum mendaftarkan email di akun SCP, silakan hubungi wali kelas atau Waka Kurikulum untuk bantuan reset kata sandi.
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <!-- Email or Username/NIS -->
        <div>
            <x-input-label for="identifier" value="Email atau Nama Pengguna / NIS" />
            <x-text-input 
                id="identifier" 
                type="text" 
                name="identifier" 
                :value="old('identifier', old('email'))" 
                required 
                autofocus 
                placeholder="email@sekolah.sch.id atau NIS/NIP"
            />
            <x-input-error :messages="$errors->get('identifier') ?: $errors->get('email')" class="mt-1.5" />
        </div>

        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('login') }}" class="text-xs text-muted-token hover:underline transition">
                &larr; Kembali ke login
            </a>

            <x-primary-button>
                Kirim Kode Reset
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
