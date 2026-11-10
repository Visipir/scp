<x-guest-layout>
    <div class="mb-6">
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/30 text-[11px] font-mono font-semibold mb-3">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
            Aktivasi Akun Siswa
        </div>
        <h2 class="text-xl font-bold tracking-tight auth-header-title">Ganti Password Wajib</h2>
        <p class="text-xs text-muted-token mt-1 leading-relaxed">
            Halo <span class="font-semibold">{{ $user->fullname }}</span> (NIS: <span class="font-mono font-bold">{{ $user->username }}</span>). Karena ini adalah login pertama Anda dengan kata sandi bawaan, silakan buat kata sandi baru untuk mengamankan akun Anda.
        </p>
    </div>

    {{-- Error messages --}}
    @if ($errors->any())
        <div class="mb-4 p-3 rounded-md bg-rose-500/10 border border-rose-500/30 text-xs text-rose-700 dark:text-rose-300">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.first-change.update') }}" class="space-y-4">
        @csrf

        {{-- New Password --}}
        <div>
            <x-input-label for="password" value="Kata Sandi Baru" />
            <x-text-input 
                id="password" 
                type="password" 
                name="password" 
                required 
                autofocus 
                placeholder="Minimal 6 karakter"
                autocomplete="new-password" 
            />
            <p class="text-[11px] text-muted-token font-mono mt-1">
                &bull; Tidak boleh sama dengan NIS Anda.
            </p>
        </div>

        {{-- Confirm Password --}}
        <div>
            <x-input-label for="password_confirmation" value="Konfirmasi Kata Sandi Baru" />
            <x-text-input 
                id="password_confirmation" 
                type="password" 
                name="password_confirmation" 
                required 
                placeholder="Ulangi kata sandi baru"
                autocomplete="new-password" 
            />
        </div>

        <div class="pt-2 space-y-3">
            <x-primary-button class="w-full justify-center py-2.5">
                Simpan Password &amp; Masuk Dashboard
            </x-primary-button>
        </div>
    </form>

    <div class="mt-6 pt-4 border-t auth-footer flex items-center justify-between text-xs">
        <span class="font-mono text-[11px]">Akun: {{ $user->username }}</span>
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="text-muted-token hover:underline font-medium cursor-pointer">
                Keluar / Logout
            </button>
        </form>
    </div>
</x-guest-layout>
