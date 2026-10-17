<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-rose-500/10 hover:bg-rose-500/20 active:bg-rose-500/30 text-rose-400 border border-rose-500/30 hover:border-rose-500/50 rounded-lg font-semibold text-xs uppercase tracking-wider focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 focus:ring-offset-[#19191C] transition-colors duration-150 shadow-none cursor-pointer']) }}>
    {{ $slot }}
</button>
