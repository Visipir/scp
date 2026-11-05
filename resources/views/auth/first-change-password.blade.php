<x-guest-layout>
    <div class="mb-6">
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-[11px] font-mono font-semibold mb-3">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
            Aktivasi Akun Siswa
        </div>
        <h2 class="text-xl font-bold tracking-tight text-zinc-900">Ganti Password Wajib</h2>
        <p class="text-xs text-zinc-500 mt-1 leading-relaxed">
            Halo <span class="font-semibold text-zinc-800">{{ $user->fullname }}</span> (NIS: <span class="font-mono font-bold text-zinc-900">{{ $user->username }}</span>). Karena ini adalah login pertama Anda dengan kata sandi bawaan, silakan buat kata sandi baru untuk mengamankan akun Anda.
        </p>
    </div>

    {{-- Error messages --}}
    @if ($errors->any())
        <div class="mb-4 p-3 rounded-md bg-rose-50 border border-rose-200 text-xs text-rose-700">
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
            <p class="text-[11px] text-zinc-400 font-mono mt-1">
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

    <div class="mt-6 pt-4 border-t border-zinc-100 flex items-center justify-between text-xs text-zinc-400">
        <span class="font-mono text-[11px]">Akun: {{ $user->username }}</span>
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="text-zinc-500 hover:text-zinc-900 underline font-medium cursor-pointer">
                Keluar / Logout
            </button>
        </form>
    </div>
</x-guest-layout>
