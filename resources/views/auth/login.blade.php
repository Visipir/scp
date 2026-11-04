<x-guest-layout>
    <!-- Brand Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-9 h-9 object-contain shrink-0">
                <div class="font-bold text-lg tracking-tight text-zinc-900 dark:text-white">
                    School Class Progress
                </div>
            </div>
        </div>
        <h2 class="text-lg font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0]">Masuk ke Akun</h2>
        <p class="text-xs text-zinc-500 dark:text-[#97979B] mt-1">Silakan masukkan username dan kata sandi Anda</p>
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
                :value="old('username')" 
                required 
                autofocus 
                autocomplete="username" 
                placeholder="NIP / NIS / Username"
            />
            <x-input-error :messages="$errors->get('username')" class="mt-1.5" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Password" />
            <x-text-input 
                id="password" 
                type="password" 
                name="password" 
                required 
                autocomplete="current-password" 
                placeholder="Kata sandi akun"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <!-- Remember Me & Forgot Password -->
        <div class="flex items-center justify-between pt-1">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input 
                    id="remember_me" 
                    type="checkbox" 
                    class="rounded border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] text-[#0093dd] shadow-none focus:ring-[#0093dd] focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-[#19191C] cursor-pointer" 
                    name="remember"
                >
                <span class="ms-2 text-xs text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] select-none">Ingat Saya</span>
            </label>

            @if (Route::has('password.request'))
                <a 
                    href="{{ route('password.request') }}" 
                    class="text-xs font-medium text-[#0093dd] hover:underline hover:text-[#007bbd] transition-colors"
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
