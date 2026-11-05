<!-- Mobile Top Header Bar -->
<div class="md:hidden sticky top-0 z-40 flex items-center justify-between h-14 px-4 bg-white dark:bg-[#19191C] border-b border-zinc-200 dark:border-[#2B2B30] transition-colors duration-150">
    <div class="flex items-center gap-3">
        <!-- Hamburger Menu Button -->
        <button 
            type="button" 
            @click="sidebarOpen = true" 
            class="p-2 -ml-2 text-zinc-700 dark:text-[#EDEDF0] hover:text-zinc-900 dark:hover:text-[#EDEDF0] rounded-lg hover:bg-zinc-100 dark:hover:bg-[#26262A] focus:outline-none focus:ring-2 focus:ring-[#0093dd] transition cursor-pointer"
            aria-label="Buka Menu"
        >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        <!-- Brand Name -->
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-sans min-w-0">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-6 h-6 object-contain shrink-0">
            <span class="font-bold text-sm tracking-tight text-zinc-900 dark:text-white truncate">School Class Progress</span>
        </a>
    </div>

    <!-- Mobile Right Profile & Theme Toggle -->
    <div class="flex items-center gap-2">
        <x-theme-dropdown align="right" />

        <a href="{{ route('profile.edit') }}" class="w-7 h-7 rounded-lg bg-zinc-100 dark:bg-[#232327] border border-zinc-200 dark:border-[#2B2B30] flex items-center justify-center text-zinc-800 dark:text-[#EDEDF0] font-bold text-xs font-mono">
            {{ strtoupper(substr(Auth::user()->fullname, 0, 1)) }}
        </a>
    </div>
</div>

<!-- Mobile Off-Canvas Sidebar Drawer (Backdrop & Drawer) -->
<div 
    x-show="sidebarOpen" 
    x-cloak 
    class="relative z-50 md:hidden" 
    role="dialog" 
    aria-modal="true"
>
    <!-- Backdrop Overlay -->
    <div 
        x-show="sidebarOpen"
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-[#09090B]/80 backdrop-blur-xs" 
        @click="sidebarOpen = false"
    ></div>

    <!-- Slide-over Drawer Panel -->
    <div class="fixed inset-0 flex">
        <div 
            x-show="sidebarOpen"
            x-transition:enter="transition ease-in-out duration-200 transform"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in-out duration-150 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="relative flex-1 flex flex-col max-w-xs w-full bg-white dark:bg-[#19191C] border-r border-zinc-200 dark:border-[#2B2B30]"
        >
            <!-- Drawer Close Button -->
            <div class="absolute top-3 right-3">
                <button 
                    type="button" 
                    @click="sidebarOpen = false" 
                    class="p-1.5 rounded-lg text-zinc-500 hover:text-zinc-800 hover:bg-zinc-100 dark:text-[#97979B] dark:hover:text-[#EDEDF0] dark:hover:bg-[#26262A] transition"
                >
                    <span class="sr-only">Tutup menu</span>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Drawer Content (h-14 to match navbar height) -->
            <div class="h-14 flex items-center px-4 border-b border-zinc-200 dark:border-[#2B2B30] shrink-0">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-6 h-6 object-contain shrink-0">
                    <span class="text-sm font-bold text-zinc-900 dark:text-white tracking-tight">School Class Progress</span>
                </a>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-6 overflow-y-auto">
                <!-- Navigation Items Loop -->
                @include('layouts.sidebar-nav-links')
            </nav>

            <!-- Mobile Drawer User Profile Footer -->
            <div class="p-3 border-t border-zinc-200 dark:border-[#2B2B30] bg-zinc-50/70 dark:bg-[#19191C]">
                @include('layouts.sidebar-user-footer')
            </div>
        </div>
    </div>
</div>

<!-- Desktop Fixed Sidebar -->
<aside class="hidden md:flex flex-col w-64 xl:w-72 fixed inset-y-0 left-0 bg-white dark:bg-[#19191C] border-r border-zinc-200 dark:border-[#2B2B30] z-30 select-none transition-colors duration-150">
    <!-- Brand / Monogram Header (h-14 to align precisely with top navbar border) -->
    <div class="h-14 flex items-center px-6 border-b border-zinc-200 dark:border-[#2B2B30] shrink-0">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-7 h-7 object-contain shrink-0">
            <span class="text-sm font-bold text-zinc-900 dark:text-white tracking-tight group-hover:text-[#0093dd] transition-colors">School Class Progress</span>
        </a>
    </div>

    <!-- Navigation List Area -->
    <div class="flex-1 overflow-y-auto px-3.5 py-5 space-y-6">
        @include('layouts.sidebar-nav-links')
    </div>

    <!-- Sidebar Bottom Profile Section -->
    <div class="p-3 border-t border-zinc-200 dark:border-[#2B2B30] bg-zinc-50/70 dark:bg-[#19191C] transition-colors duration-150">
        @include('layouts.sidebar-user-footer')
    </div>
</aside>
