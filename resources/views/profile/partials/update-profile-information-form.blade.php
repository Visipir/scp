<section>
    <div class="mb-5">
        <h2 class="text-base font-bold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">
            Data Profil
        </h2>
        <p class="text-xs text-zinc-500 dark:text-[#97979B] mt-0.5">
            Perbarui nama lengkap akun Anda.
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
            <x-input-label for="email" value="Alamat Email (Untuk Reset Kata Sandi)" />
            <x-text-input 
                id="email" 
                name="email" 
                type="email" 
                class="mt-1 block w-full text-xs" 
                :value="old('email', $user->email)" 
                placeholder="nama@sekolah.sch.id"
            />
            <p class="mt-1.5 text-[11px] text-zinc-500 dark:text-[#97979B]">
                Digunakan untuk menerima kode verifikasi jika Anda lupa kata sandi.
            </p>
            <x-input-error class="mt-1.5" :messages="$errors->get('email')" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="username" value="Username / NIP / NIS" />
                <span class="text-[10px] text-zinc-500 dark:text-[#97979B] font-mono flex items-center gap-1">
                    <svg class="w-3 h-3 text-zinc-400 dark:text-[#5B5B61]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    Terkunci
                </span>
            </div>
            <x-text-input 
                id="username" 
                type="text" 
                class="mt-1 block w-full text-xs font-mono bg-zinc-100 text-zinc-500 border-zinc-200 dark:bg-[#19191C] dark:text-[#97979B] dark:border-[#2B2B30] cursor-not-allowed select-none focus:border-zinc-200 dark:focus:border-[#2B2B30] focus:ring-0" 
                :value="$user->username" 
                disabled 
                readonly
            />
            <p class="mt-1.5 text-[11px] text-zinc-500 dark:text-[#97979B]">
                Username / NIP / NIS telah dikunci oleh sistem dan tidak dapat diubah secara mandiri.
            </p>
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
                    class="text-xs font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5"
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
