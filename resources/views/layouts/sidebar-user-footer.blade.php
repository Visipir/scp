<!-- User Profile Footer Component -->
<div class="flex items-center justify-between gap-3">
    <!-- User Ident & Name -->
    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 min-w-0 flex-1 group">
        <div class="w-8 h-8 rounded-lg bg-[#232327] border border-[#2B2B30] text-white flex items-center justify-center font-bold text-xs font-mono shrink-0 group-hover:border-[#3A3A3F] transition">
            {{ strtoupper(substr(Auth::user()->fullname, 0, 1)) }}
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold text-[#EDEDF0] truncate leading-tight">
                {{ Auth::user()->fullname }}
            </p>
            <p class="text-[10px] font-mono text-[#97979B] uppercase tracking-wider truncate mt-0.5">
                {{ Auth::user()->role->role_name ?? 'Pengguna' }}
            </p>
        </div>
    </a>

    <!-- Action Buttons (Dark Mode Toggle, Profile & Logout) -->
    <div class="flex items-center gap-1 shrink-0">
        <!-- Dark Mode Toggle Button (Sidebar) -->
        <button 
            type="button" 
            @click="toggleDarkMode()" 
            class="p-1.5 text-[#97979B] hover:text-[#EDEDF0] rounded-lg hover:bg-[#26262A] transition cursor-pointer"
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

        <a 
            href="{{ route('profile.edit') }}" 
            title="Edit Profil"
            class="p-1.5 text-[#97979B] hover:text-[#EDEDF0] rounded-lg hover:bg-[#26262A] transition"
        >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
        </a>

        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button 
                type="submit" 
                title="Keluar dari Sistem"
                class="p-1.5 text-[#97979B] hover:text-rose-500 rounded-lg hover:bg-rose-500/10 transition cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                </svg>
            </button>
        </form>
    </div>
</div>
