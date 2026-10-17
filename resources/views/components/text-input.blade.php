@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-lg border border-[#2B2B30] bg-[#19191C] px-3.5 py-2 text-sm text-[#EDEDF0] placeholder:text-[#5B5B61] focus:border-[#FD366E] focus:ring-2 focus:ring-[#FD366E] focus:outline-none disabled:bg-[#151518] disabled:text-[#5B5B61] disabled:border-[#222226] shadow-none transition-colors duration-150']) }}>
