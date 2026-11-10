<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-lg text-zinc-900 dark:text-[#EDEDF0] leading-tight">
                    Import Penugasan Wali Kelas &amp; Guru BK
                </h2>
                <p class="text-xs text-zinc-500 dark:text-[#97979B]">
                    Unggah berkas Excel penugasan Wali Kelas dan Guru BK untuk pemetaan hak supervisi kelas dan presensi.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-6 space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="p-4 rounded-lg bg-emerald-50 dark:bg-[#16281D] border border-emerald-300 dark:border-emerald-700/60 text-emerald-800 dark:text-emerald-300 text-xs font-medium">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="p-4 rounded-lg bg-rose-50 dark:bg-[#2D1618] border border-rose-300 dark:border-rose-700/60 text-rose-800 dark:text-rose-300 text-xs font-medium">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2C2C30] rounded-xl p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-[#EDEDF0] mb-2">Petunjuk Import</h3>
            <ul class="text-xs text-zinc-600 dark:text-[#97979B] space-y-1 list-disc list-inside mb-6">
                <li>Kolom yang dibaca: <code class="font-mono text-xs">No, Kelas, Nama Wali, Nama Guru BK</code>.</li>
                <li>Nama kelas akan dicocokkan otomatis (misal: <em>X AK 2</em> akan terhubung ke <em>X AK 2</em> di sistem).</li>
                <li>Nama guru akan dinormalisasi dan dicocokkan ke akun guru aktif di sistem.</li>
                <li>Proses melalui 2 tahap: <strong>Preview</strong> (tanpa mengubah database) lalu <strong>Commit</strong> setelah konfirmasi.</li>
            </ul>

            <form action="{{ route('waka.import-wali-bk.preview') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-zinc-700 dark:text-[#EDEDF0] mb-1">
                        Pilih Berkas Excel (.xlsx, .xls)
                    </label>
                    <input type="file" name="file" accept=".xlsx,.xls" required
                           class="block w-full text-xs text-zinc-500 dark:text-[#97979B] file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-[#0093dd] file:text-white hover:file:bg-[#007bb8] cursor-pointer">
                    @error('file')
                        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-2">
                    <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-[#0093dd] hover:bg-[#007bb8] rounded-lg transition-colors">
                        Unggah &amp; Tampilkan Preview
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
