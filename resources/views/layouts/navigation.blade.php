<!-- Mobile Top Header Bar (Swiss Minimalist) -->
<div class="md:hidden sticky top-0 z-40 flex items-center justify-between h-14 px-4 bg-white border-b border-zinc-200">
    <div class="flex items-center gap-3">
        <!-- Hamburger Menu Button -->
        <button 
            type="button" 
            @click="sidebarOpen = true" 
            class="p-2 -ml-2 text-zinc-700 hover:text-zinc-900 rounded-md hover:bg-zinc-100 focus:outline-none focus:ring-1 focus:ring-zinc-900 transition"
            aria-label="Buka Menu"
        >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        <!-- Brand Name -->
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
            <div class="w-7 h-7 bg-zinc-900 text-white flex items-center justify-center font-black text-xs rounded tracking-tighter">
                SCP
            </div>
            <span class="font-bold text-base tracking-tight text-zinc-900">SCP<span class="text-rose-600">.</span></span>
        </a>
    </div>

    <!-- Mobile Right Profile Initials -->
    <div class="flex items-center gap-2">
        <a href="{{ route('profile.edit') }}" class="w-7 h-7 rounded bg-zinc-100 border border-zinc-200 flex items-center justify-center text-zinc-800 font-bold text-xs font-mono">
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
        class="fixed inset-0 bg-zinc-900/60 backdrop-blur-xs" 
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
            class="relative flex-1 flex flex-col max-w-xs w-full bg-white border-r border-zinc-200"
        >
            <!-- Drawer Close Button -->
            <div class="absolute top-3 right-3">
                <button 
                    type="button" 
                    @click="sidebarOpen = false" 
                    class="p-1.5 rounded-md text-zinc-400 hover:text-zinc-900 hover:bg-zinc-100 transition"
                >
                    <span class="sr-only">Tutup menu</span>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Drawer Content (h-14 to match navbar height) -->
            <div class="h-14 flex items-center px-4 border-b border-zinc-200/80 shrink-0">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                    <div class="w-7 h-7 bg-zinc-900 text-white flex items-center justify-center font-black text-xs rounded tracking-tighter">
                        SCP
                    </div>
                    <div>
                        <div class="font-bold text-xs text-zinc-900 tracking-tight leading-none">School Class Progress<span class="text-rose-600">.</span></div>
                        <div class="text-[9px] text-zinc-400 font-mono mt-0.5 uppercase tracking-wider">SMK Negeri 1 Bantul</div>
                    </div>
                </a>
            </div>

            <nav class="flex-1 px-3 py-4 space-y-6 overflow-y-auto">
                <!-- Navigation Items Loop -->
                @include('layouts.sidebar-nav-links')
            </nav>

            <!-- Mobile Drawer User Profile Footer -->
            <div class="p-3 border-t border-zinc-200 bg-zinc-50/50">
                @include('layouts.sidebar-user-footer')
            </div>
        </div>
    </div>
</div>

<!-- Desktop Fixed Swiss Minimalist Sidebar -->
<aside class="hidden md:flex flex-col w-64 xl:w-72 fixed inset-y-0 left-0 bg-white border-r border-zinc-200 z-30 select-none">
    <!-- Brand / Monogram Header (h-14 to align precisely with top navbar border) -->
    <div class="h-14 flex items-center px-6 border-b border-zinc-200/80 shrink-0">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group">
            <div class="w-7 h-7 bg-zinc-900 text-white flex items-center justify-center font-black text-xs rounded tracking-tighter group-hover:bg-black transition">
                SCP
            </div>
            <div>
                <div class="font-bold text-xs tracking-tight text-zinc-900 flex items-center gap-0.5 leading-none">
                    SCP<span class="text-rose-600 font-black">.</span>
                    <span class="text-[10px] font-normal text-zinc-400 ml-1">Class Progress</span>
                </div>
                <div class="text-[9px] text-zinc-400 font-mono tracking-widest uppercase mt-0.5">SMK Negeri 1 Bantul</div>
            </div>
        </a>
    </div>

    <!-- Navigation List Area -->
    <div class="flex-1 overflow-y-auto px-4 py-6 space-y-6">
        @include('layouts.sidebar-nav-links')
    </div>

    <!-- Sidebar Bottom Profile Section -->
    <div class="p-3 border-t border-zinc-200/80 bg-zinc-50/60">
        @include('layouts.sidebar-user-footer')
    </div>
</aside>
