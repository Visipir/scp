<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-rose-50 hover:bg-rose-100 active:bg-rose-200 text-rose-700 border border-rose-300 dark:bg-[#2D1618] dark:hover:bg-[#3D1E20] dark:active:bg-[#4D2326] dark:text-rose-300 dark:border-rose-700/60 rounded-lg font-semibold text-xs uppercase tracking-wider focus:outline-none focus:ring-2 focus:ring-rose-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-[#19191C] transition-colors duration-150 shadow-none cursor-pointer']) }}>
    {{ $slot }}
</button>
