@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2.5 border-l-2 border-[#0073B1] text-start text-xs font-semibold text-[#0073B1] bg-sky-50 dark:text-white dark:bg-[#3A3A3F] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0073B1] transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2.5 border-l-2 border-transparent text-start text-xs font-medium text-zinc-700 hover:text-zinc-900 hover:bg-zinc-100 dark:text-[#9496A1] dark:hover:text-[#EDEDF0] dark:hover:bg-[#26262A] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0073B1] transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
