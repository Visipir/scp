<section class="space-y-6">
    <div>
        <div class="mb-4">
            <h2 class="text-base font-bold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">
                Data Pribadi
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
    </div>

    <div class="border-t border-zinc-200 dark:border-[#2B2B30] pt-6" x-data="{ mode: 'default', showRemoveModal: false }">
        <div class="mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">
                        Email Akun
                    </h2>
                    <p class="text-xs text-zinc-500 dark:text-[#97979B] mt-0.5">
                        Alamat email digunakan untuk verifikasi dan pemulihan kata sandi saat Anda lupa kata sandi.
                    </p>
                </div>

                @if($user->email)
                    @if($user->email_verified_at)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Terverifikasi
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-400">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            Belum Diverifikasi
                        </span>
                    @endif
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="mb-4 p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs text-emerald-800 dark:text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        {{-- Case 1: Verified Email --}}
        @if($user->email && $user->email_verified_at)
            <div x-show="mode === 'default'" class="space-y-4">
                <div class="p-3.5 bg-zinc-50 dark:bg-[#19191C] border border-zinc-200 dark:border-[#2B2B30] rounded-lg flex items-center justify-between">
                    <div>
                        <div class="text-[11px] text-zinc-500 dark:text-[#97979B]">Email Terdaftar:</div>
                        <div class="font-mono text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $user->email }}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button 
                            type="button" 
                            @click="mode = 'change'" 
                            class="px-3 py-1.5 text-xs font-semibold rounded-md bg-white dark:bg-[#232327] border border-zinc-300 dark:border-[#2B2B30] text-zinc-700 dark:text-zinc-200 hover:bg-zinc-100 transition"
                        >
                            Ubah Email
                        </button>
                        <button 
                            type="button" 
                            @click="showRemoveModal = true" 
                            class="px-3 py-1.5 text-xs font-semibold rounded-md bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-400 hover:bg-rose-100 transition"
                        >
                            Hapus
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Case 2: Unverified Email (Pending Code) --}}
        @if($user->email && ! $user->email_verified_at)
            <div class="space-y-4 p-4 bg-amber-50/70 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800/60 rounded-xl">
                <div>
                    <div class="text-xs text-amber-900 dark:text-amber-200 font-medium">Email Menunggu Verifikasi:</div>
                    <div class="font-mono text-sm font-bold text-amber-950 dark:text-amber-100 mt-0.5">{{ $user->email }}</div>
                    <p class="text-[11px] text-amber-700 dark:text-amber-300 mt-1">
                        Masukkan kode 6 digit yang telah kami kirimkan ke email ini untuk menyelesaikan verifikasi.
                    </p>
                </div>

                {{-- Verify Code Form --}}
                <form method="POST" action="{{ route('profile.email.verify') }}" class="space-y-3">
                    @csrf
                    <div>
                        <x-input-label for="verify_code" value="Kode Verifikasi 6 Digit" />
                        <div class="flex items-center gap-2 mt-1">
                            <x-text-input 
                                id="verify_code" 
                                name="code" 
                                type="text" 
                                maxlength="6" 
                                class="w-40 text-center font-mono tracking-widest text-base font-bold" 
                                placeholder="123456" 
                                required 
                            />
                            <x-primary-button>
                                Verifikasi
                            </x-primary-button>
                        </div>
                        <x-input-error class="mt-1" :messages="$errors->get('code')" />
                    </div>
                </form>

                <div class="flex items-center gap-4 pt-1 text-xs">
                    {{-- Resend Code --}}
                    <form method="POST" action="{{ route('profile.email.request') }}">
                        @csrf
                        <input type="hidden" name="email" value="{{ $user->email }}">
                        <button type="submit" class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold">
                            Kirim Ulang Kode
                        </button>
                    </form>

                    <button type="button" @click="showRemoveModal = true" class="text-rose-600 dark:text-rose-400 hover:underline font-semibold">
                        Batalkan / Hapus Email
                    </button>
                </div>
            </div>
        @endif

        {{-- Case 3: No Email or Changing Email Form --}}
        <div x-show="mode === 'change' || {{ empty($user->email) ? 'true' : 'false' }}" class="space-y-4 {{ $user->email ? 'mt-4 pt-4 border-t border-zinc-200 dark:border-[#2B2B30]' : '' }}">
            <form method="POST" action="{{ route('profile.email.request') }}" class="space-y-3">
                @csrf
                <div>
                    <x-input-label for="new_email" value="{{ $user->email ? 'Alamat Email Baru' : 'Daftarkan Alamat Email' }}" />
                    <x-text-input 
                        id="new_email" 
                        name="email" 
                        type="email" 
                        class="mt-1 block w-full text-xs" 
                        placeholder="nama@example.test" 
                        required 
                    />
                    <p class="mt-1 text-[11px] text-zinc-500 dark:text-[#97979B]">
                        Kami akan mengirimkan kode 6 digit ke alamat email ini untuk memastikan kepemilikan Anda.
                    </p>
                    <x-input-error class="mt-1" :messages="$errors->get('email')" />
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <x-primary-button>
                        Kirim Kode Verifikasi
                    </x-primary-button>
                    @if($user->email)
                        <button 
                            type="button" 
                            @click="mode = 'default'" 
                            class="px-3 py-2 text-xs font-semibold rounded-md border border-zinc-300 dark:border-[#2B2B30] text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 transition"
                        >
                            Batal
                        </button>
                    @endif
                </div>
            </form>
        </div>

        {{-- Remove Email Modal / Prompt --}}
        <div 
            x-show="showRemoveModal" 
            x-cloak 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            @keydown.escape.window="showRemoveModal = false"
        >
            <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl max-w-md w-full p-6 space-y-4 shadow-xl">
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Hapus Alamat Email?</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Setelah dihapus, Anda tidak dapat memulihkan kata sandi secara mandiri via email. Masukkan kata sandi saat ini untuk mengonfirmasi.
                    </p>
                </div>

                <form method="POST" action="{{ route('profile.email.remove') }}" class="space-y-4">
                    @csrf
                    @method('DELETE')

                    <div>
                        <x-input-label for="current_password" value="Kata Sandi Saat Ini" />
                        <x-text-input 
                            id="current_password" 
                            name="current_password" 
                            type="password" 
                            class="mt-1 block w-full text-xs" 
                            placeholder="Kata sandi akun Anda" 
                            required 
                        />
                        <x-input-error class="mt-1" :messages="$errors->get('current_password')" />
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button 
                            type="button" 
                            @click="showRemoveModal = false" 
                            class="px-3 py-1.5 text-xs font-semibold rounded-md border border-zinc-300 dark:border-[#2B2B30] text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 transition"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit" 
                            class="px-3 py-1.5 text-xs font-semibold rounded-md bg-rose-600 text-white hover:bg-rose-700 transition"
                        >
                            Hapus Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
