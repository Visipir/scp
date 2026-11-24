@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl'
])

@php
$maxWidthClass = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

<dialog
    closedby="any"
    x-data="{ show: @js($show) }"
    x-effect="show ? ($el.open || $el.showModal()) : ($el.open && $el.close())"
    x-on:open-modal.window="$event.detail == '{{ $name }}' ? show = true : null"
    x-on:close-modal.window="$event.detail == '{{ $name }}' ? show = false : null"
    x-on:close.stop="show = false"
    @close="show = false"
    @cancel.prevent="show = false"
    aria-label="{{ ucwords(str_replace(['-', '_'], ' ', $name)) }}"
    class="m-auto w-[calc(100%-2rem)] {{ $maxWidthClass }} bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl overflow-hidden shadow-xl dark:shadow-none text-zinc-900 dark:text-[#EDEDF0] p-0"
>
    {{ $slot }}
</dialog>
