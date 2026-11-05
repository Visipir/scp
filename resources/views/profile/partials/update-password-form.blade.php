<section>
    <div class="mb-5">
        <h2 class="text-base font-bold text-[#EDEDF0] tracking-tight">
            Keamanan &amp; Kata Sandi
        </h2>
        <p class="text-xs text-[#97979B] mt-0.5">
            Gunakan kombinasi kata sandi yang aman dan tidak mudah ditebak untuk melindungi akun Anda.
        </p>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" value="Kata Sandi Saat Ini" />
            <x-text-input 
                id="update_password_current_password" 
                name="current_password" 
                type="password" 
                class="mt-1 block w-full text-xs" 
                autocomplete="current-password" 
                placeholder="Masukkan kata sandi lama"
            />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1.5" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <x-input-label for="update_password_password" value="Kata Sandi Baru" />
                <x-text-input 
                    id="update_password_password" 
                    name="password" 
                    type="password" 
                    class="mt-1 block w-full text-xs" 
                    autocomplete="new-password" 
                    placeholder="Minimal 8 karakter"
                />
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1.5" />
            </div>

            <div>
                <x-input-label for="update_password_password_confirmation" value="Konfirmasi Kata Sandi Baru" />
                <x-text-input 
                    id="update_password_password_confirmation" 
                    name="password_confirmation" 
                    type="password" 
                    class="mt-1 block w-full text-xs" 
                    autocomplete="new-password" 
                    placeholder="Ulangi kata sandi baru"
                />
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1.5" />
            </div>
        </div>

        <div class="pt-2 flex items-center gap-3">
            <x-primary-button>
                Perbarui Kata Sandi
            </x-primary-button>

            @if (session('status') === 'password-updated')
                <span
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2500)"
                    class="text-xs font-medium text-emerald-400 flex items-center gap-1.5"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                    Kata sandi diperbarui
                </span>
            @endif
        </div>
    </form>
</section>
