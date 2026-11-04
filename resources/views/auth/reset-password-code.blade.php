<x-guest-layout>
    <div class="mb-4">
        <h2 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">Atur Ulang Kata Sandi</h2>
        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
            Masukkan kode verifikasi 6 digit yang dikirimkan ke email Anda dan tentukan kata sandi baru (minimal 8 karakter).
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.reset.update') }}" class="space-y-4">
        @csrf

        <!-- Identifier (Email or Username) -->
        <div>
            <x-input-label for="identifier" value="Email atau Nama Pengguna / NIS" />
            <x-text-input id="identifier" class="block mt-1 w-full" type="text" name="identifier" :value="old('identifier', $identifier ?? ($email ?? ''))" required />
            <x-input-error :messages="$errors->get('identifier') ?: $errors->get('email')" class="mt-1" />
        </div>

        <!-- 6-digit Code -->
        <div>
            <x-input-label for="code" :value="__('Kode Verifikasi 6 Digit')" />
            <x-text-input
                id="code"
                class="block mt-1 w-full font-mono text-center tracking-widest text-lg font-bold"
                type="text"
                name="code"
                maxlength="6"
                placeholder="123456"
                :value="old('code')"
                required
                autofocus
            />
            <p class="text-xs text-zinc-500 mt-1">Kode berlaku selama 15 menit (maksimal 5 kali percobaan).</p>
            <x-input-error :messages="$errors->get('code')" class="mt-1" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Kata Sandi Baru (Min. 8 Karakter)')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Konfirmasi Kata Sandi Baru')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>

        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('password.request') }}" class="text-xs text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200 transition">
                Kirim ulang kode?
            </a>

            <x-primary-button>
                Simpan Kata Sandi Baru
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
