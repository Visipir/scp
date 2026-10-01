<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-wider focus:outline-none focus:ring-2 focus:ring-rose-600 focus:ring-offset-2 transition-colors duration-150 shadow-none']) }}>
    {{ $slot }}
</button>
