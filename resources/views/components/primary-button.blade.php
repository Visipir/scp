<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-zinc-900 hover:bg-black active:bg-zinc-800 text-white dark:bg-zinc-100 dark:hover:bg-white dark:active:bg-zinc-200 dark:text-zinc-950 border border-transparent rounded-md font-semibold text-xs uppercase tracking-wider focus:outline-none focus:ring-2 focus:ring-zinc-900 dark:focus:ring-zinc-100 focus:ring-offset-2 transition-colors duration-150 shadow-none']) }}>
    {{ $slot }}
</button>
