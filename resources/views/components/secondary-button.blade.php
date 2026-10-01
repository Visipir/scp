<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 active:bg-zinc-100 dark:active:bg-zinc-600 border border-zinc-300 dark:border-zinc-700 rounded-md font-semibold text-xs text-zinc-800 dark:text-zinc-200 uppercase tracking-wider focus:outline-none focus:ring-2 focus:ring-zinc-900 dark:focus:ring-zinc-100 focus:ring-offset-2 disabled:opacity-25 transition-colors duration-150 shadow-none']) }}>
    {{ $slot }}
</button>
