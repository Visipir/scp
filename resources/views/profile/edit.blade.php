<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#EDEDF0]">
                    Pengaturan Profil
                </h1>
                <p class="text-xs text-[#97979B] mt-0.5">
                    Kelola data pribadi, informasi akun, dan kredensial akses Anda.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-[#232327] border border-[#2B2B30] text-xs font-semibold text-[#EDEDF0]">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    {{ $user->role->role_name ?? 'User' }}
                </span>
            </div>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            {{-- Left Column: User Identity Card --}}
            <div class="lg:col-span-4 bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-6 space-y-6">
                <!-- User Avatar & Core Info -->
                <div class="flex flex-col items-center text-center">
                    <div class="w-20 h-20 rounded-2xl bg-[#232327] border border-[#2B2B30] flex items-center justify-center text-2xl font-bold font-mono text-[#EDEDF0] shadow-none">
                        {{ strtoupper(substr($user->fullname, 0, 1)) }}
                    </div>
                    <h2 class="text-lg font-bold text-[#EDEDF0] mt-4 tracking-tight">
                        {{ $user->fullname }}
                    </h2>
                    <span class="font-mono text-xs text-[#97979B] px-2.5 py-0.5 rounded-md bg-[#19191C] border border-[#2B2B30] mt-1.5">
                        {{ $user->username }}
                    </span>
                </div>

                <div class="border-t border-[#2B2B30]"></div>

                <!-- Detailed Account Metadata -->
                <div class="space-y-3.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-[#97979B]">Peran Akun</span>
                        <span class="font-medium text-[#EDEDF0]">{{ $user->role->role_name ?? 'Pengguna' }}</span>
                    </div>

                    @if($user->class_id && $user->schoolClass)
                        <div class="flex items-center justify-between">
                            <span class="text-[#97979B]">{{ $user->role_id === 3 ? 'Kelas Binaan' : 'Kelas' }}</span>
                            <span class="font-medium text-[#EDEDF0]">{{ $user->schoolClass->class_name }}</span>
                        </div>
                    @endif

                    @if($user->attendance_number)
                        <div class="flex items-center justify-between">
                            <span class="text-[#97979B]">No. Absen</span>
                            <span class="font-mono text-[#EDEDF0] font-medium">{{ $user->attendance_number }}</span>
                        </div>
                    @endif

                    @if($user->gender)
                        <div class="flex items-center justify-between">
                            <span class="text-[#97979B]">Jenis Kelamin</span>
                            <span class="font-medium text-[#EDEDF0]">
                                {{ $user->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}
                            </span>
                        </div>
                    @endif

                    <div class="flex items-center justify-between">
                        <span class="text-[#97979B]">Status Akses</span>
                        <span class="inline-flex items-center gap-1.5 text-emerald-400 font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            Aktif
                        </span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-[#97979B]">Terdaftar Sejak</span>
                        <span class="font-mono text-[#EDEDF0] text-[11px]">
                            {{ $user->created_at?->translatedFormat('d M Y') ?? '-' }}
                        </span>
                    </div>
                </div>

                <div class="p-3 bg-[#19191C] border border-[#2B2B30] rounded-lg text-[11px] text-[#97979B] leading-relaxed">
                    Perubahan wewenang hak akses atau penugasan kelas diatur langsung oleh administrator kurikulum sekolah.
                </div>
            </div>

            {{-- Right Column: Settings Forms --}}
            <div class="lg:col-span-8 space-y-6">
                <!-- Update Profile Information Form -->
                <div class="bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-6 sm:p-7">
                    @include('profile.partials.update-profile-information-form')
                </div>

                <!-- Update Password Form -->
                <div class="bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-6 sm:p-7">
                    @include('profile.partials.update-password-form')
                </div>

                <!-- Delete Account Form -->
                <div class="bg-rose-500/[0.03] border border-rose-500/20 rounded-xl p-6 sm:p-7">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
