@props(['type' => 'info'])

@php
$borderAccent = match($type) {
    'danger' => 'border-l-rose-500',
    'warning' => 'border-l-amber-400',
    'success' => 'border-l-emerald-400',
    default => 'border-l-[#0093dd]',
};
$tagBg = match($type) {
    'danger' => 'bg-rose-100 dark:bg-[#2D1618] text-rose-800 dark:text-rose-300 border-rose-300 dark:border-rose-700/60',
    'warning' => 'bg-amber-100 dark:bg-[#282115] text-amber-800 dark:text-amber-300 border-amber-300 dark:border-amber-700/60',
    'success' => 'bg-emerald-100 dark:bg-[#16281D] text-emerald-800 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700/60',
    default => 'bg-sky-100 dark:bg-[#16222F] text-sky-800 dark:text-sky-300 border-sky-300 dark:border-sky-700/60',
};
$label = match($type) {
    'danger' => 'ERROR',
    'warning' => 'WARNING',
    'success' => 'SUCCESS',
    default => 'INFO',
};
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg border border-zinc-200 dark:border-[#2B2B30] border-l-2 bg-white dark:bg-[#1D1D21] p-4 text-zinc-800 dark:text-[#EDEDF0] shadow-xs dark:shadow-none {$borderAccent}"]) }} role="alert">
    <div class="flex items-start gap-3">
        <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded font-mono shrink-0 border mt-0.5 {{ $tagBg }}">
            {{ $label }}
        </span>
        <div class="flex-1 text-xs sm:text-sm font-medium leading-relaxed text-zinc-800 dark:text-[#EDEDF0]">{{ $slot }}</div>
    </div>
</div>
