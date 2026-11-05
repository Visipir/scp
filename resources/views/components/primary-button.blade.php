<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-[#0093dd] hover:bg-[#0082c4] active:bg-[#006fa7] text-white border border-transparent rounded-lg font-semibold text-xs uppercase tracking-wider focus:outline-none focus:ring-2 focus:ring-[#0093dd] focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-[#19191C] transition-colors duration-150 shadow-xs disabled:opacity-50 cursor-pointer']) }}>
    {{ $slot }}
</button>
