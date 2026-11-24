<x-guest-layout :title="'Verifikasi Email'">
    <div class="mb-4 text-sm text-muted-token">
        Terima kasih telah mendaftar! Sebelum memulai, mohon verifikasi alamat email Anda dengan mengklik tautan yang baru saja kami kirimkan ke email Anda. Jika belum menerima email, kami siap mengirimkan ulang.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-emerald-600 dark:text-emerald-400">
            Tautan verifikasi baru telah dikirimkan ke alamat email yang Anda daftarkan.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    Kirim Ulang Email Verifikasi
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline text-sm text-muted-token hover:underline rounded-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0073B1]">
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>
