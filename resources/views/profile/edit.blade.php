<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="text-[10px] font-bold font-mono text-zinc-400 uppercase tracking-widest">Akun &amp; Keamanan</div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 mt-0.5">
                    Pengaturan Profil
                </h1>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6 max-w-4xl">
        <div class="p-6 sm:p-8 bg-white border border-zinc-200/80 rounded-lg">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="p-6 sm:p-8 bg-white border border-zinc-200/80 rounded-lg">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="p-6 sm:p-8 bg-white border border-zinc-200/80 rounded-lg">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
