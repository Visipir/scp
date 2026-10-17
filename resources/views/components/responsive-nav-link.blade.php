@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-2 border-[#FD366E] text-start text-xs font-semibold text-white bg-[#3A3A3F] focus:outline-none transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-2 border-transparent text-start text-xs font-medium text-[#97979B] hover:text-[#EDEDF0] hover:bg-[#26262A] focus:outline-none transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
