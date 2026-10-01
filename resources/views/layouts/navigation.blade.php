<!-- Mobile Top Header Bar (Swiss Minimalist) -->
<div class="md:hidden sticky top-0 z-40 flex items-center justify-between h-14 px-4 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 transition-colors duration-150">
    <div class="flex items-center gap-3">
        <!-- Hamburger Menu Button -->
        <button 
            type="button" 
            @click="sidebarOpen = true" 
            class="p-2 -ml-2 text-zinc-700 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-800 focus:outline-none focus:ring-1 focus:ring-zinc-900 dark:focus:ring-zinc-100 transition"
            aria-label="Buka Menu"
        >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        <!-- Brand Name -->
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
            <div class="w-7 h-7 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-950 flex items-center justify-center font-black text-xs rounded tracking-tighter">
                SCP
            </div>
            <span class="font-bold text-base tracking-tight text-zinc-900 dark:text-white">SCP<span class="text-rose-600">.</span></span>
        </a>
    </div>

    <!-- Mobile Right Profile & Dark Mode Toggle -->
    <div class="flex items-center gap-2">
        <button 
            type="button" 
            @click="toggleDarkMode()" 
            class="p-1.5 text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-800 transition cursor-pointer"
            :title="darkMode ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
            aria-label="Toggle Dark Mode"
        >
            <svg x-show="darkMode" x-cloak class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
            </svg>
            <svg x-show="!darkMode" class="w-4 h-4 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
            </svg>
        </button>

        <a href="{{ route('profile.edit') }}" class="w-7 h-7 rounded bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 flex items-center justify-center text-zinc-800 dark:text-zinc-200 font-bold text-xs font-mono">
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
        class="fixed inset-0 bg-zinc-950/70 backdrop-blur-xs" 
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
            class="relative flex-1 flex flex-col max-w-xs w-full bg-white dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-800"
        >
            <!-- Drawer Close Button -->
            <div class="absolute top-3 right-3">
                <button 
                    type="button" 
                    @click="sidebarOpen = false" 
                    class="p-1.5 rounded-md text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition"
                >
                    <span class="sr-only">Tutup menu</span>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Drawer Content (h-14 to match navbar height) -->
            <div class="h-14 flex items-center px-4 border-b border-zinc-200/80 dark:border-zinc-800 shrink-0">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <div class="w-7 h-7 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-950 flex items-center justify-center font-black text-xs rounded tracking-tighter">
                        SCP
                    </div>
                    <div>
                        <div class="font-bold text-xs text-zinc-900 dark:text-white tracking-tight leading-none">School Class Progress<span class="text-rose-600">.</span></div>
                        <div class="text-[9px] text-zinc-400 dark:text-zinc-500 font-mono mt-0.5 uppercase tracking-wider">SMK Negeri 1 Bantul</div>
                    </div>
                </a>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-6 overflow-y-auto">
                <!-- Navigation Items Loop -->
                @include('layouts.sidebar-nav-links')
            </nav>

            <!-- Mobile Drawer User Profile Footer -->
            <div class="p-3 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                @include('layouts.sidebar-user-footer')
            </div>
        </div>
    </div>
</div>

<!-- Desktop Fixed Swiss Minimalist Sidebar -->
<aside class="hidden md:flex flex-col w-64 xl:w-72 fixed inset-y-0 left-0 bg-white dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-800 z-30 select-none transition-colors duration-150">
    <!-- Brand / Monogram Header (h-14 to align precisely with top navbar border) -->
    <div class="h-14 flex items-center px-6 border-b border-zinc-200/80 dark:border-zinc-800 shrink-0">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
            <div class="w-7 h-7 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-950 flex items-center justify-center font-black text-xs rounded tracking-tighter group-hover:bg-black dark:group-hover:bg-white transition">
                SCP
            </div>
            <div>
                <div class="font-bold text-xs tracking-tight text-zinc-900 dark:text-white flex items-center gap-0.5 leading-none">
                    SCP<span class="text-rose-600 font-black">.</span>
                    <span class="text-[10px] font-normal text-zinc-400 dark:text-zinc-500 ml-1">Class Progress</span>
                </div>
                <div class="text-[9px] text-zinc-400 dark:text-zinc-500 font-mono tracking-widest uppercase mt-0.5">SMK Negeri 1 Bantul</div>
            </div>
        </a>
    </div>

    <!-- Navigation List Area -->
    <div class="flex-1 overflow-y-auto px-4 py-6 space-y-6">
        @include('layouts.sidebar-nav-links')
    </div>

    <!-- Sidebar Bottom Profile Section -->
    <div class="p-3 border-t border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/60 dark:bg-zinc-900/50 transition-colors duration-150">
        @include('layouts.sidebar-user-footer')
    </div>
</aside>
