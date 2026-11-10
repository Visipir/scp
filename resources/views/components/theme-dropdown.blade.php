@props(['align' => 'right', 'dropup' => false, 'size' => 'default'])

@php
$alignmentClasses = match ($align) {
    'left' => 'left-0 origin-top-left',
    default => 'right-0 origin-top-right',
};

$verticalClasses = $dropup 
    ? 'bottom-full mb-2 origin-bottom' 
    : 'top-full mt-2 origin-top';

$buttonSizeClass = match ($size) {
    'mobile' => 'w-11 h-11',
    default  => 'w-9 h-9 sm:w-8 sm:h-8',
};
@endphp

<div class="relative inline-block text-left" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <!-- Trigger Button (Responsive Tap Target) -->
    <button 
        type="button" 
        id="theme-toggle-btn"
        @click="open = !open" 
        class="{{ $buttonSizeClass }} text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] rounded-lg bg-zinc-100 dark:bg-[#232327] hover:bg-zinc-200 dark:hover:bg-[#26262A] border border-zinc-200 dark:border-[#2B2B30] transition-colors duration-150 cursor-pointer flex items-center justify-center shadow-2xs focus:outline-none focus:ring-2 focus:ring-[#0093dd] shrink-0"
        :title="$store.theme ? ($store.theme.mode === 'system' ? 'Tema: Otomatis (Sistem)' : ($store.theme.mode === 'light' ? 'Tema: Terang' : 'Tema: Gelap')) : 'Pengaturan Tema'"
        aria-label="Pilih Tema Tampilan"
        aria-haspopup="true"
        aria-pressed="false"
        :aria-expanded="open"
        :aria-pressed="$store.theme ? ($store.theme.isDark ? 'true' : 'false') : (document.documentElement.classList.contains('dark') ? 'true' : 'false')"
    >
        <!-- Sun icon when light mode selected -->
        <svg x-show="$store.theme?.mode === 'light'" x-cloak class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
        </svg>

        <!-- Moon icon when dark mode selected -->
        <svg x-show="$store.theme?.mode === 'dark'" x-cloak class="w-4 h-4 text-sky-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
        </svg>

        <!-- System / Monitor icon when system selected or initial load -->
        <svg x-show="!$store.theme || $store.theme?.mode === 'system'" class="w-4 h-4 text-zinc-600 dark:text-[#97979B]" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3" />
        </svg>
    </button>

    <!-- Dropdown Menu -->
    <div 
        x-show="open" 
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 w-44 rounded-xl border border-zinc-200 dark:border-[#2B2B30] bg-white dark:bg-[#1D1D21] p-1.5 shadow-xl dark:shadow-2xl {{ $verticalClasses }} {{ $alignmentClasses }}"
        style="display: none;"
    >
        <div class="px-2 py-1 text-[10px] font-mono font-bold uppercase tracking-wider text-zinc-400 dark:text-[#5B5B61]">
            Pilih Tema
        </div>

        <div class="space-y-0.5">
            <!-- 1. Terang (Light) -->
            <button 
                type="button" 
                @click="$store.theme?.set('light'); open = false" 
                class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors duration-150 cursor-pointer"
                :class="$store.theme?.mode === 'light' ? 'bg-sky-50 text-[#0093dd] dark:bg-[#26262A] dark:text-white font-semibold' : 'text-zinc-700 dark:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A]'"
            >
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                    </svg>
                    <span>Terang</span>
                </div>
                <template x-if="$store.theme?.mode === 'light'">
                    <svg class="w-3.5 h-3.5 text-[#0093dd] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </template>
            </button>

            <!-- 2. Gelap (Dark) -->
            <button 
                type="button" 
                @click="$store.theme?.set('dark'); open = false" 
                class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors duration-150 cursor-pointer"
                :class="$store.theme?.mode === 'dark' ? 'bg-sky-50 text-[#0093dd] dark:bg-[#26262A] dark:text-white font-semibold' : 'text-zinc-700 dark:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A]'"
            >
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-sky-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                    </svg>
                    <span>Gelap</span>
                </div>
                <template x-if="$store.theme?.mode === 'dark'">
                    <svg class="w-3.5 h-3.5 text-[#0093dd] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </template>
            </button>

            <!-- 3. Sistem / Otomatis (Device) -->
            <button 
                type="button" 
                @click="$store.theme?.set('system'); open = false" 
                class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors duration-150 cursor-pointer"
                :class="(!$store.theme || $store.theme?.mode === 'system') ? 'bg-sky-50 text-[#0093dd] dark:bg-[#26262A] dark:text-white font-semibold' : 'text-zinc-700 dark:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A]'"
            >
                <div class="flex items-center gap-2.5">
                    <svg class="w-4 h-4 text-zinc-500 dark:text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3" />
                    </svg>
                    <div class="flex flex-col text-left">
                        <span>Sistem</span>
                        <span class="text-[10px] text-zinc-400 dark:text-[#97979B] font-mono leading-tight">Ikuti Perangkat</span>
                    </div>
                </div>
                <template x-if="!$store.theme || $store.theme?.mode === 'system'">
                    <svg class="w-3.5 h-3.5 text-[#0093dd] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                    </svg>
                </template>
            </button>
        </div>
    </div>
</div>
