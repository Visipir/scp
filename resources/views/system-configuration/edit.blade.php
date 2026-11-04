<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="text-xs font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider">Sistem Konfigurasi</div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0] mt-0.5">
                    Pengaturan Sistem
                </h1>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6 max-w-3xl">

        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if(session('error'))
            <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
        @endif

        {{-- Configuration Main Panel (Swiss Minimalist) --}}
        <div class="bg-white border border-zinc-200/80 rounded-lg divide-y divide-zinc-200/80">
            <div class="p-6 sm:p-8">
                <h2 class="text-base font-bold text-zinc-900 tracking-tight">
                    Kontrol Putaran &amp; Jadwal
                </h2>
                <p class="text-xs text-zinc-500 mt-1">
                    Kelola putaran blok dan kunci akses jadwal pembelajaran.
                </p>
            </div>

            {{-- Cycle Toggle --}}
            <div class="p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div class="space-y-1">
                    <label class="text-sm font-bold text-zinc-900 tracking-tight block">
                        Putaran Block Schedule Aktif
                    </label>
                    <p class="text-xs text-zinc-500 max-w-sm leading-relaxed">
                        Mengubah siklus putaran 2-mingguan sistem.
                    </p>
                </div>

                <form method="POST" action="{{ route('system-configuration.toggle-cycle') }}" class="shrink-0">
                    @csrf
                    @method('PATCH')
                    
                    <div class="inline-flex p-1 bg-zinc-100 rounded-md border border-zinc-200">
                        <button
                            type="submit"
                            name="cycle"
                            value="Putaran 1"
                            class="px-4 py-2 text-xs font-bold font-mono uppercase tracking-wider rounded transition duration-150 {{ ($config->active_cycle ?? 'Putaran 1') === 'Putaran 1' ? 'bg-zinc-900 text-white shadow-xs' : 'text-zinc-500 hover:text-zinc-900' }}"
                        >
                            Putaran 1
                        </button>
                        <button
                            type="submit"
                            name="cycle"
                            value="Putaran 2"
                            class="px-4 py-2 text-xs font-bold font-mono uppercase tracking-wider rounded transition duration-150 {{ ($config->active_cycle ?? 'Putaran 1') === 'Putaran 2' ? 'bg-zinc-900 text-white shadow-xs' : 'text-zinc-500 hover:text-zinc-900' }}"
                        >
                            Putaran 2
                        </button>
                    </div>
                </form>
            </div>

            {{-- Lock Schedule Toggle --}}
            <div class="p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div class="space-y-1">
                    <label class="text-sm font-bold text-zinc-900 tracking-tight block">
                        Kunci Jadwal Mengajar
                    </label>
                    <p class="text-xs text-zinc-500 max-w-sm leading-relaxed">
                        Kunci jadwal agar guru tidak dapat mengubah jadwal secara sepihak.
                    </p>
                </div>

                <form method="POST" action="{{ route('system-configuration.toggle-lock') }}" class="shrink-0">
                    @csrf
                    @method('PATCH')
                    <button
                        type="submit"
                        class="px-4 py-2 text-xs font-bold font-mono uppercase tracking-wider rounded transition duration-150 border {{ ($config->is_schedule_locked ?? false) ? 'bg-rose-50 border-rose-300 text-rose-700 hover:bg-rose-100' : 'bg-emerald-50 border-emerald-300 text-emerald-700 hover:bg-emerald-100' }}"
                    >
                        {{ ($config->is_schedule_locked ?? false) ? 'Jadwal Terkunci' : 'Kunci Jadwal' }}
                    </button>
                </form>
            </div>

            {{-- Email Configuration Link --}}
            <div class="p-6 sm:p-8 flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                <div class="space-y-1">
                    <label class="text-sm font-bold text-zinc-900 tracking-tight block">
                        Pengaturan Email (SMTP)
                    </label>
                    <p class="text-xs text-zinc-500 max-w-sm leading-relaxed">
                        Konfigurasi server SMTP untuk pengiriman reset password dan email tes.
                    </p>
                </div>

                <a
                    href="{{ route('system-configuration.email') }}"
                    class="px-4 py-2 text-xs font-bold font-mono uppercase tracking-wider rounded transition duration-150 border border-blue-300 bg-blue-50 text-blue-700 hover:bg-blue-100 text-center shrink-0"
                >
                    Buka Pengaturan Email &rarr;
                </a>
            </div>
        </div>

        <div class="bg-zinc-50 border border-zinc-200/80 rounded-lg p-5 font-mono text-xs text-zinc-500">
            Terakhir diubah oleh: <span class="text-zinc-800 font-semibold">{{ $config->updatedByUser->fullname ?? 'Sistem' }}</span>
            &bull; {{ $config->updated_at?->diffForHumans() ?? '-' }}
        </div>

    </div>
</x-app-layout>
