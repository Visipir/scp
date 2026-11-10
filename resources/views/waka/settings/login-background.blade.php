<x-app-layout>
    <x-slot name="header">
        <x-page-header 
            title="Tampilan Login" 
            subtitle="Kelola gambar latar belakang halaman login (desktop 2 kolom)" 
        />
    </x-slot>

    <div class="space-y-6">

        {{-- Flash Messages --}}
        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if(session('error'))
            <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
        @endif
        @if($errors->any())
            <div class="p-4 bg-rose-50 dark:bg-[#2D1618] border border-rose-300 dark:border-rose-700/60 rounded-xl text-rose-800 dark:text-rose-300 text-xs font-mono">
                @foreach($errors->all() as $err)
                    <div>&bull; {{ $err }}</div>
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            
            {{-- Form pengaturan gambar latar --}}
            <div class="space-y-6">
                <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl overflow-hidden divide-y divide-zinc-200 dark:divide-[#2B2B30]">
                    <div class="p-5 sm:p-6">
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight flex items-center gap-2">
                            <svg class="w-5 h-5 text-zinc-700 dark:text-zinc-300" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                            Unggah Gambar Latar Belakang Baru
                        </h2>
                        <p class="text-xs text-zinc-500 dark:text-[#97979B] mt-1">
                            Gambar ini akan ditampilkan pada kolom kanan halaman masuk sistem di layar desktop (&ge; 1024px).
                        </p>

                        <form method="POST" action="{{ route('waka.settings.login-background.update') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                            @csrf

                            <div>
                                <label for="background_input" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    Pilih Berkas Gambar
                                </label>
                                <input 
                                    type="file" 
                                    id="background_input" 
                                    name="background" 
                                    accept=".jpg,.jpeg,.png,.webp"
                                    required
                                    class="block w-full text-xs text-zinc-600 dark:text-zinc-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-zinc-100 dark:file:bg-[#2B2B30] file:text-zinc-800 dark:file:text-white hover:file:bg-zinc-200 dark:hover:file:bg-[#34343A] cursor-pointer"
                                />
                                <div class="mt-2 text-[11px] text-zinc-500 dark:text-[#97979B] space-y-1">
                                    <div>&bull; Format yang didukung: <strong>JPG, JPEG, PNG, WebP</strong> (SVG ditolak).</div>
                                    <div>&bull; Ukuran maksimal berkas: <strong>3 MB</strong>.</div>
                                    <div>&bull; Dimensi minimal: <strong>1200 &times; 800 px</strong> (Maksimal 6000 px).</div>
                                    <div>&bull; Otomatis di-reencode ke WebP maksimal lebar 1920 px dan metadata EXIF dibersihkan.</div>
                                </div>
                            </div>

                            {{-- Native FileReader Preview --}}
                            <div id="preview_container" class="hidden mt-4 pt-4 border-t border-zinc-200 dark:border-[#2B2B30]">
                                <div class="text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-2 flex items-center justify-between">
                                    <span>Pratinjau Sebelum Disimpan:</span>
                                    <span id="preview_specs" class="font-mono text-[10px] text-zinc-500 dark:text-zinc-400"></span>
                                </div>
                                <div class="relative w-full aspect-[16/9] rounded-lg overflow-hidden border border-zinc-300 dark:border-[#3A3A3F] bg-black/40">
                                    <img id="preview_img" src="" alt="Pratinjau Gambar" class="w-full h-full object-cover" />
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent flex items-end p-3">
                                        <span class="text-[10px] font-mono text-white/80">Simulasi Overlay Layar Masuk</span>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-3 flex items-center gap-3">
                                <button 
                                    type="submit" 
                                    class="px-4 py-2 bg-[#0093dd] hover:bg-[#0082c5] text-white text-xs font-semibold rounded-lg shadow-sm transition"
                                >
                                    Simpan Latar Belakang
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Kembalikan ke Default --}}
                    <div class="p-5 sm:p-6 bg-zinc-50/50 dark:bg-[#1A1A1D]">
                        <h3 class="text-xs font-semibold text-zinc-900 dark:text-[#EDEDF0]">
                            Kembalikan ke Latar Belakang Default
                        </h3>
                        <p class="text-xs text-zinc-500 dark:text-[#97979B] mt-1">
                            Hapus gambar khusus saat ini dan pulihkan tampilan gradasi warna brand bawaan.
                        </p>
                        <form method="POST" action="{{ route('waka.settings.login-background.destroy') }}" class="mt-3" onsubmit="return confirm('Apakah Anda yakin ingin menghapus gambar latar dan kembali ke default?')">
                            @csrf
                            @method('DELETE')
                            <button 
                                type="submit" 
                                class="px-3.5 py-1.5 bg-zinc-200 dark:bg-[#2B2B30] hover:bg-zinc-300 dark:hover:bg-[#34343A] text-zinc-800 dark:text-zinc-200 text-xs font-medium rounded-lg transition"
                                {{ !$config->login_background_path ? 'disabled' : '' }}
                            >
                                Kembalikan ke Default
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Pratinjau latar aktif --}}
            <div class="space-y-6">
                <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl overflow-hidden divide-y divide-zinc-200 dark:divide-[#2B2B30]">
                    <div class="p-5 sm:p-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">
                                Latar Belakang Aktif
                            </h2>
                            @if($config->login_background_path)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-emerald-100 dark:bg-[#16281D] text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700/60">
                                    GAMBAR KHUSUS
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-zinc-100 dark:bg-[#232327] text-zinc-700 dark:text-[#97979B] border border-zinc-300 dark:border-[#3A3A3F]">
                                    DEFAULT BRAND
                                </span>
                            @endif
                        </div>

                        <div class="mt-4 relative w-full aspect-[16/9] rounded-lg overflow-hidden border border-zinc-200 dark:border-[#2B2B30] bg-black">
                            <img 
                                src="{{ route('login.background', ['v' => $bgTimestamp]) }}" 
                                alt="Latar Belakang Saat Ini" 
                                class="w-full h-full object-cover"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent flex items-end justify-between p-3">
                                <span class="text-[10px] font-mono text-white/90">
                                    {{ $config->login_background_path ? basename($config->login_background_path) : 'Brand Gradient SVG' }}
                                </span>
                                <a 
                                    href="{{ route('login') }}" 
                                    target="_blank" 
                                    class="text-[10px] bg-zinc-800 hover:bg-zinc-700 text-white border border-zinc-600 px-2.5 py-1 rounded transition"
                                >
                                    Buka /login &nearr;
                                </a>
                            </div>
                        </div>

                        <div class="mt-4 text-xs text-zinc-500 dark:text-[#97979B] space-y-1 font-mono text-[11px]">
                            <div>Terakhir diubah: {{ $config->login_background_updated_at ? $config->login_background_updated_at->setTimezone('Asia/Jakarta')->format('d M Y, H:i:s') . ' WIB' : '-' }}</div>
                            <div>Pengubah: {{ $config->updatedByUser ? $config->updatedByUser->fullname : 'Sistem' }}</div>
                            <div>URL Publik: <code class="text-zinc-700 dark:text-zinc-300">GET /login-background</code></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Native FileReader Preview Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const fileInput = document.getElementById('background_input');
            const previewContainer = document.getElementById('preview_container');
            const previewImg = document.getElementById('preview_img');
            const previewSpecs = document.getElementById('preview_specs');

            if (!fileInput) return;

            fileInput.addEventListener('change', function (e) {
                const file = e.target.files && e.target.files[0];
                if (!file) {
                    previewContainer.classList.add('hidden');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (event) {
                    const tempImg = new Image();
                    tempImg.onload = function () {
                        previewImg.src = event.target.result;
                        previewSpecs.textContent = `${tempImg.naturalWidth} \u00D7 ${tempImg.naturalHeight} px | ${(file.size / (1024 * 1024)).toFixed(2)} MB`;
                        previewContainer.classList.remove('hidden');
                    };
                    tempImg.src = event.target.result;
                };
                reader.readAsDataURL(file);
            });
        });
    </script>
</x-app-layout>
