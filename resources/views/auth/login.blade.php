<x-guest-layout :title="'Masuk'">
    <div class="mb-6">
        <h1 class="text-xl font-bold tracking-tight auth-header-title">Masuk ke Akun</h1>
        <p class="text-xs text-muted-token mt-1">Silakan masukkan username dan kata sandi Anda untuk melanjutkan</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- Username -->
        <div>
            <x-input-label for="username" value="Username" />
            <x-text-input 
                id="username" 
                type="text" 
                name="username" 
                :value="is_string(old('username')) ? old('username') : ''" 
                required 
                data-autofocus-desktop 
                autocomplete="username" 
                autocorrect="off"
                autocapitalize="none"
                spellcheck="false"
                enterkeyhint="next"
                :invalid="$errors->has('username')"
                placeholder="NIP / NIS / Username"
            />
            <x-input-error :messages="$errors->get('username')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Password" />
            <x-password-input 
                id="password" 
                name="password" 
                required 
                autocomplete="current-password" 
                enterkeyhint="done"
                :invalid="$errors->has('password')"
                placeholder="Kata sandi akun"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between pt-1">
            <label for="remember_me" class="inline-flex items-center min-h-[44px] sm:min-h-0 cursor-pointer">
                <input 
                    id="remember_me" 
                    type="checkbox" 
                    class="rounded border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] text-[#0073B1] shadow-none focus:ring-[#0073B1] focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-[#19191C] cursor-pointer" 
                    name="remember"
                >
                <span class="ms-2 text-xs text-zinc-700 dark:text-[#B4B6C2] hover:text-zinc-900 dark:hover:text-[#F1F1F4] select-none">Ingat Saya</span>
            </label>

            @if (Route::has('password.request'))
                <a 
                    href="{{ route('password.request') }}" 
                    class="inline-flex items-center min-h-[44px] sm:min-h-0 px-1 text-xs font-semibold text-[#0073B1] dark:text-[#38BDF8] hover:underline transition-colors"
                >
                    Lupa password?
                </a>
            @endif
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full justify-center py-2.5 text-xs font-semibold tracking-wider">
                Masuk
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
