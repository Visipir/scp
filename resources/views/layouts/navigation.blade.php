<!-- Mobile Top Header Bar -->
<div class="lg:hidden sticky top-0 z-40 flex items-center justify-between h-14 px-4 pt-[env(safe-area-inset-top,0px)] bg-white dark:bg-[#19191C] border-b border-zinc-200 dark:border-[#2B2B30] transition-colors duration-150">
    <div class="flex items-center gap-2">
        <!-- Hamburger Menu Button (44x44px touch target) -->
        <button 
            type="button" 
            @click="sidebarOpen = true" 
            :aria-expanded="sidebarOpen"
            aria-controls="mobile-nav-drawer"
            class="w-11 h-11 -ml-2 text-zinc-700 dark:text-[#EDEDF0] hover:text-zinc-900 dark:hover:text-[#EDEDF0] rounded-lg hover:bg-zinc-100 dark:hover:bg-[#26262A] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0073B1] transition cursor-pointer flex items-center justify-center shrink-0"
            aria-label="Buka Menu"
        >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        <!-- Brand Name -->
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-sans min-w-0">
            <picture class="shrink-0">
                <source srcset="{{ asset('images/logo.webp') }}" type="image/webp">
                <img src="{{ asset('images/logo.png') }}" alt="" width="24" height="24" class="w-6 h-6 object-contain shrink-0">
            </picture>
            <span translate="no" class="font-bold text-sm tracking-tight text-zinc-900 dark:text-white truncate">School Class Progress</span>
        </a>
    </div>

    <!-- Mobile Right Profile & Theme Toggle (44x44px touch targets) -->
    <div class="flex items-center gap-2">
        <x-theme-dropdown align="right" size="mobile" />

        <a href="{{ route('profile.edit') }}" aria-label="Profil Pengguna" class="w-11 h-11 rounded-lg bg-zinc-100 dark:bg-[#232327] border border-zinc-200 dark:border-[#2B2B30] flex items-center justify-center text-zinc-800 dark:text-[#EDEDF0] font-bold text-sm font-mono shrink-0">
            {{ strtoupper(substr(Auth::user()->fullname, 0, 1)) }}
        </a>
    </div>
</div>

<!-- Native Top-Layer Mobile Sidebar Drawer (<dialog showModal() auto-inert & Esc/backdrop dismiss) -->
<dialog 
    id="mobile-nav-drawer"
    closedby="any"
    x-effect="if (sidebarOpen) { if (!$el.open) { $el.showModal(); $nextTick(() => $refs.mobileCloseBtn?.focus()); } } else if ($el.open) { $el.close(); }"
    @close="sidebarOpen = false"
    @cancel.prevent="sidebarOpen = false"
    aria-label="Menu Navigasi Utama"
    class="fixed inset-y-0 left-0 m-0 h-dvh max-h-none w-80 max-w-[85vw] p-0 bg-white dark:bg-[#19191C] border-r border-zinc-200 dark:border-[#2B2B30] text-zinc-900 dark:text-[#EDEDF0] shadow-xl lg:hidden open:flex flex-col"
>
    <!-- Drawer Header -->
    <div class="h-14 flex items-center justify-between px-4 border-b border-zinc-200 dark:border-[#2B2B30] shrink-0">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
            <picture class="shrink-0">
                <source srcset="{{ asset('images/logo.webp') }}" type="image/webp">
                <img src="{{ asset('images/logo.png') }}" alt="" width="24" height="24" class="w-6 h-6 object-contain shrink-0">
            </picture>
            <span translate="no" class="text-sm font-bold text-zinc-900 dark:text-white tracking-tight">School Class Progress</span>
        </a>

        <button 
            x-ref="mobileCloseBtn"
            type="button" 
            @click="sidebarOpen = false" 
            class="w-11 h-11 -mr-1.5 rounded-lg text-zinc-600 hover:text-zinc-900 hover:bg-zinc-100 dark:text-[#B4B6C2] dark:hover:text-[#F1F1F4] dark:hover:bg-[#262832] transition flex items-center justify-center cursor-pointer shrink-0"
            aria-label="Tutup menu"
        >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav aria-label="Menu Seluler" class="flex-1 px-3 py-4 space-y-6 overflow-y-auto">
        @include('layouts.sidebar-nav-links')
    </nav>

    <!-- Mobile Drawer User Profile Footer -->
    <div class="p-3 pb-[max(0.75rem,env(safe-area-inset-bottom,0px))] border-t border-zinc-200 dark:border-[#2B2B30] bg-zinc-50/70 dark:bg-[#19191C]">
        @include('layouts.sidebar-user-footer')
    </div>
</dialog>

<!-- Desktop Fixed Sidebar -->
<aside class="hidden lg:flex flex-col w-64 xl:w-72 fixed inset-y-0 left-0 bg-white dark:bg-[#19191C] border-r border-zinc-200 dark:border-[#2B2B30] z-30 select-none transition-colors duration-150">
    <!-- Brand / Monogram Header (h-14 to align precisely with top navbar border) -->
    <div class="h-14 flex items-center px-6 border-b border-zinc-200 dark:border-[#2B2B30] shrink-0">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
            <picture class="shrink-0">
                <source srcset="{{ asset('images/logo.webp') }}" type="image/webp">
                <img src="{{ asset('images/logo.png') }}" alt="" width="28" height="28" class="w-7 h-7 object-contain shrink-0">
            </picture>
            <span translate="no" class="text-sm font-bold text-zinc-900 dark:text-white tracking-tight group-hover:text-[#0073B1] transition-colors">School Class Progress</span>
        </a>
    </div>

    <!-- Navigation List Area -->
    <nav aria-label="Menu Utama" class="flex-1 overflow-y-auto px-3.5 py-5 space-y-6">
        @include('layouts.sidebar-nav-links')
    </nav>

    <!-- Sidebar Bottom Profile Section -->
    <div class="p-3 border-t border-zinc-200 dark:border-[#2B2B30] bg-zinc-50/70 dark:bg-[#19191C] transition-colors duration-150">
        @include('layouts.sidebar-user-footer')
    </div>
</aside>
