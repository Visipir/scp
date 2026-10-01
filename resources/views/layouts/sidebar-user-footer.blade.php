<!-- Swiss Minimalist User Profile Footer Component -->
<div class="flex items-center justify-between gap-3">
    <!-- User Ident & Name -->
    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2.5 min-w-0 flex-1 group">
        <div class="w-8 h-8 rounded bg-zinc-900 text-white flex items-center justify-center font-bold text-xs font-mono shrink-0 group-hover:bg-black transition">
            {{ strtoupper(substr(Auth::user()->fullname, 0, 1)) }}
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-xs font-bold text-zinc-900 truncate leading-tight group-hover:text-black">
                {{ Auth::user()->fullname }}
            </p>
            <p class="text-[10px] font-mono text-zinc-400 uppercase tracking-wider truncate mt-0.5">
                {{ Auth::user()->role->role_name ?? 'Pengguna' }}
            </p>
        </div>
    </a>

    <!-- Action Buttons (Profile & Logout) -->
    <div class="flex items-center gap-1 shrink-0">
        <a 
            href="{{ route('profile.edit') }}" 
            title="Edit Profil"
            class="p-1.5 text-zinc-400 hover:text-zinc-900 rounded hover:bg-zinc-200/60 transition"
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
                class="p-1.5 text-zinc-400 hover:text-rose-600 rounded hover:bg-rose-50 transition"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                </svg>
            </button>
        </form>
    </div>
</div>
