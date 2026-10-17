<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center px-4 py-2.5 bg-transparent hover:bg-[#26262A] active:bg-[#2F2F34] border border-[#2B2B30] rounded-lg font-semibold text-xs text-[#EDEDF0] uppercase tracking-wider focus:outline-none focus:ring-2 focus:ring-[#FD366E] focus:ring-offset-2 focus:ring-offset-[#19191C] disabled:opacity-30 disabled:text-[#5B5B61] transition-colors duration-150 shadow-none cursor-pointer']) }}>
    {{ $slot }}
</button>
