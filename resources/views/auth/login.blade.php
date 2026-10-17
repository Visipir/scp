<x-guest-layout>
    <!-- Brand / Eyebrow Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <div class="font-black text-2xl tracking-tight text-white flex items-center">
                <span>SCP</span><span class="text-[#FD366E]">.</span>
            </div>
            <span class="text-[10px] font-mono text-[#97979B] uppercase tracking-wider px-2.5 py-0.5 rounded-md bg-[#232327] border border-[#2B2B30]">
                LOGIN
            </span>
        </div>
        <h2 class="text-lg font-bold tracking-tight text-[#EDEDF0]">Masuk ke Konsol</h2>
        <p class="text-xs text-[#97979B] mt-1">Silakan masukkan username dan kata sandi akun Anda</p>
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

        <!-- Remember Me -->
        <div class="flex items-center justify-between pt-1">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input 
                    id="remember_me" 
                    type="checkbox" 
                    class="rounded border-[#2B2B30] bg-[#19191C] text-[#FD366E] shadow-none focus:ring-[#FD366E] focus:ring-offset-2 focus:ring-offset-[#19191C] cursor-pointer" 
                    name="remember"
                >
                <span class="ms-2 text-xs text-[#97979B] hover:text-[#EDEDF0] select-none">Ingat Saya</span>
            </label>
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full justify-center py-2.5 text-xs font-semibold tracking-wider">
                Masuk ke Konsol
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 pt-4 border-t border-[#2B2B30] text-center">
        <p class="text-[11px] font-mono text-[#5B5B61]">
            Teaching Factory &bull; Siklus Blok 2 Mingguan
        </p>
    </div>
</x-guest-layout>
