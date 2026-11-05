@props(['type' => 'info'])

@php
$borderAccent = match($type) {
    'danger' => 'border-l-rose-500',
    'warning' => 'border-l-amber-400',
    'success' => 'border-l-emerald-400',
    default => 'border-l-[#0093dd]',
};
$tagBg = match($type) {
    'danger' => 'bg-rose-500/15 text-rose-400 border-rose-500/30',
    'warning' => 'bg-amber-500/15 text-amber-400 border-amber-500/30',
    'success' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
    default => 'bg-[#0093dd]/15 text-[#0093dd] border-[#0093dd]/30',
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
