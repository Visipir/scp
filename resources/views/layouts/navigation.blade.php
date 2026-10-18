<!-- Mobile Top Header Bar -->
<div class="md:hidden sticky top-0 z-40 flex items-center justify-between h-14 px-4 bg-[#19191C] border-b border-[#2B2B30] transition-colors duration-150">
    <div class="flex items-center gap-3">
        <!-- Hamburger Menu Button -->
        <button 
            type="button" 
            @click="sidebarOpen = true" 
            class="p-2 -ml-2 text-[#EDEDF0] hover:text-white rounded-lg hover:bg-[#26262A] focus:outline-none focus:ring-2 focus:ring-[#FD366E] transition cursor-pointer"
            aria-label="Buka Menu"
        >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        <!-- Brand Name -->
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 font-sans">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-6 h-6 object-contain shrink-0">
            <span class="font-black text-lg tracking-tight text-white">SCP<span class="text-[#FD366E]">.</span></span>
        </a>
    </div>

    <!-- Mobile Right Profile & Dark Mode Toggle -->
    <div class="flex items-center gap-2">
        <button 
            type="button" 
            @click="toggleDarkMode()" 
            class="p-1.5 text-[#97979B] hover:text-[#EDEDF0] rounded-lg hover:bg-[#26262A] border border-[#2B2B30] transition cursor-pointer"
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

        <a href="{{ route('profile.edit') }}" class="w-7 h-7 rounded-lg bg-[#232327] border border-[#2B2B30] flex items-center justify-center text-[#EDEDF0] font-bold text-xs font-mono">
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
            class="relative flex-1 flex flex-col max-w-xs w-full bg-[#19191C] border-r border-[#2B2B30]"
        >
            <!-- Drawer Close Button -->
            <div class="absolute top-3 right-3">
                <button 
                    type="button" 
                    @click="sidebarOpen = false" 
                    class="p-1.5 rounded-lg text-[#97979B] hover:text-[#EDEDF0] hover:bg-[#26262A] transition"
                >
                    <span class="sr-only">Tutup menu</span>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Drawer Content (h-14 to match navbar height) -->
            <div class="h-14 flex items-center px-4 border-b border-[#2B2B30] shrink-0">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-6 h-6 object-contain shrink-0">
                    <span class="font-black text-lg tracking-tight text-white leading-none">SCP<span class="text-[#FD366E]">.</span></span>
                    <span class="text-xs text-[#97979B] font-medium">School Class Progress</span>
                </a>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-6 overflow-y-auto">
                <!-- Navigation Items Loop -->
                @include('layouts.sidebar-nav-links')
            </nav>

            <!-- Mobile Drawer User Profile Footer -->
            <div class="p-3 border-t border-[#2B2B30] bg-[#19191C]">
                @include('layouts.sidebar-user-footer')
            </div>
        </div>
    </div>
</div>

<!-- Desktop Fixed Sidebar -->
<aside class="hidden md:flex flex-col w-64 xl:w-72 fixed inset-y-0 left-0 bg-[#19191C] border-r border-[#2B2B30] z-30 select-none transition-colors duration-150">
    <!-- Brand / Monogram Header (h-14 to align precisely with top navbar border) -->
    <div class="h-14 flex items-center px-6 border-b border-[#2B2B30] shrink-0">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
            <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-7 h-7 object-contain shrink-0">
            <div class="font-black text-xl tracking-tight text-white flex items-center leading-none">
                <span>SCP</span><span class="text-[#FD366E]">.</span>
            </div>
            <div class="h-4 w-px bg-[#2B2B30]"></div>
            <span class="text-xs font-medium text-[#97979B] tracking-tight group-hover:text-[#EDEDF0] transition-colors">School Class Progress</span>
        </a>
    </div>

    <!-- Navigation List Area -->
    <div class="flex-1 overflow-y-auto px-3.5 py-5 space-y-6">
        @include('layouts.sidebar-nav-links')
    </div>

    <!-- Sidebar Bottom Profile Section -->
    <div class="p-3 border-t border-[#2B2B30] bg-[#19191C] transition-colors duration-150">
        @include('layouts.sidebar-user-footer')
    </div>
</aside>
