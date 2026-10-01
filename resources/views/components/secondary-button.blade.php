<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-white hover:bg-zinc-50 active:bg-zinc-100 border border-zinc-300 rounded-md font-semibold text-xs text-zinc-800 uppercase tracking-wider focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2 disabled:opacity-25 transition-colors duration-150 shadow-none']) }}>
    {{ $slot }}
</button>
