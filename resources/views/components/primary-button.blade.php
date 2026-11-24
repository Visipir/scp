<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center min-h-[44px] sm:min-h-[38px] px-4 py-2.5 bg-[#0073B1] hover:bg-[#005D8F] active:bg-[#004A73] text-white border border-transparent rounded-lg font-semibold text-xs uppercase tracking-wider focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0073B1] focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-[#19191C] transition-colors duration-150 shadow-xs disabled:opacity-50 cursor-pointer']) }}>
    {{ $slot }}
</button>
