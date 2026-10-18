<section>
    <div class="mb-5">
        <h2 class="text-base font-bold text-[#EDEDF0] tracking-tight">
            Data Profil
        </h2>
        <p class="text-xs text-[#97979B] mt-0.5">
            Perbarui nama lengkap dan username akun yang digunakan untuk login.
        </p>
    </div>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-4">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="fullname" value="Nama Lengkap" />
            <x-text-input 
                id="fullname" 
                name="fullname" 
                type="text" 
                class="mt-1 block w-full text-xs" 
                :value="old('fullname', $user->fullname)" 
                required 
                autofocus 
                autocomplete="name" 
                placeholder="Masukkan nama lengkap"
            />
            <x-input-error class="mt-1.5" :messages="$errors->get('fullname')" />
        </div>

        <div>
            <x-input-label for="username" value="Username / NIP / NIS" />
            <x-text-input 
                id="username" 
                name="username" 
                type="text" 
                class="mt-1 block w-full text-xs font-mono" 
                :value="old('username', $user->username)" 
                required 
                autocomplete="username" 
                placeholder="Masukkan username"
            />
            <x-input-error class="mt-1.5" :messages="$errors->get('username')" />
        </div>

        <div class="pt-2 flex items-center gap-3">
            <x-primary-button>
                Simpan Perubahan
            </x-primary-button>

            @if (session('status') === 'profile-updated')
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
                    Tersimpan
                </span>
            @endif
        </div>
    </form>
</section>
