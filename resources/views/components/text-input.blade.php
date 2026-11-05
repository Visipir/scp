@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-lg border border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3.5 py-2 text-sm text-zinc-900 dark:text-[#EDEDF0] placeholder:text-zinc-400 dark:placeholder:text-[#5B5B61] focus:border-[#0093dd] focus:ring-2 focus:ring-[#0093dd] focus:outline-none disabled:bg-zinc-100 disabled:text-zinc-400 disabled:border-zinc-200 dark:disabled:bg-[#151518] dark:disabled:text-[#5B5B61] dark:disabled:border-[#222226] shadow-none transition-colors duration-150']) }}>
