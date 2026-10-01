<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-zinc-900 hover:bg-black active:bg-zinc-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-wider focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-2 transition-colors duration-150 shadow-none']) }}>
    {{ $slot }}
</button>
