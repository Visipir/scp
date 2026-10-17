<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-[#FD366E] hover:bg-[#E0285D] active:bg-[#C92051] text-white border border-transparent rounded-lg font-semibold text-xs uppercase tracking-wider focus:outline-none focus:ring-2 focus:ring-[#FD366E] focus:ring-offset-2 focus:ring-offset-[#19191C] transition-colors duration-150 shadow-none disabled:opacity-50 cursor-pointer']) }}>
    {{ $slot }}
</button>
