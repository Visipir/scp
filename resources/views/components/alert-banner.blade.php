@props(['type' => 'info'])

@php
$classes = match($type) {
    'danger' => 'bg-rose-50/70 border-rose-200 text-rose-900 dark:bg-rose-950/40 dark:border-rose-800/80 dark:text-rose-200',
    'warning' => 'bg-amber-50/70 border-amber-200 text-amber-900 dark:bg-amber-950/40 dark:border-amber-800/80 dark:text-amber-200',
    'success' => 'bg-emerald-50/70 border-emerald-200 text-emerald-900 dark:bg-emerald-950/40 dark:border-emerald-800/80 dark:text-emerald-200',
    default => 'bg-zinc-50 border-zinc-200 text-zinc-900 dark:bg-zinc-900/60 dark:border-zinc-800 dark:text-zinc-200',
};
$tag = match($type) {
    'danger' => 'bg-rose-600 text-white dark:bg-rose-500',
    'warning' => 'bg-amber-600 text-white dark:bg-amber-500',
    'success' => 'bg-emerald-600 text-white dark:bg-emerald-500',
    default => 'bg-zinc-800 text-white dark:bg-zinc-700',
};
$label = match($type) {
    'danger' => 'Error',
    'warning' => 'Perhatian',
    'success' => 'Berhasil',
    default => 'Info',
};
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg border p-4 {$classes}"]) }} role="alert">
    <div class="flex items-start gap-3">
        <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded font-mono shrink-0 mt-0.5 {{ $tag }}">
            {{ $label }}
        </span>
        <div class="flex-1 text-xs sm:text-sm font-medium leading-relaxed">{{ $slot }}</div>
    </div>
</div>
