@props([
    'disabled' => false,
    'invalid' => false,
    'id',
    'name',
    'required' => false,
    'autocomplete' => 'current-password',
    'wrapperClass' => '',
])

<div x-data="{ shown: false }" class="relative {{ $wrapperClass }}">
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="password"
        :type="shown ? 'text' : 'password'"
        autocomplete="{{ $autocomplete }}"
        @if($required) required @endif
        @if($invalid) aria-invalid="true" @endif
        @disabled($disabled)
        {{ $attributes->merge([
            'class' => 'w-full rounded-lg border border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] pl-3.5 pr-11 py-2 text-sm text-zinc-900 dark:text-[#EDEDF0] placeholder:text-zinc-400 dark:placeholder:text-[#5B5B61] focus:border-[#0073B1] focus-visible:ring-2 focus-visible:ring-[#0073B1] focus-visible:outline-none disabled:bg-zinc-100 disabled:text-zinc-400 disabled:border-zinc-200 dark:disabled:bg-[#151518] dark:disabled:text-[#5B5B61] dark:disabled:border-[#222226] shadow-none transition-colors duration-150',
        ]) }}
    >
    <button
        type="button"
        @click="shown = !shown"
        :aria-pressed="shown"
        :aria-label="shown ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
        class="absolute inset-y-0 right-0 flex items-center justify-center w-11 text-zinc-500 hover:text-zinc-800 dark:text-[#9496A1] dark:hover:text-[#F1F1F4] rounded-r-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0073B1] transition cursor-pointer"
    >
        <svg x-show="!shown" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
        <svg x-show="shown" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
        </svg>
    </button>
</div>
