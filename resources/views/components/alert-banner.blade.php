@props(['type' => 'info'])

@php
$classes = match($type) {
    'danger' => 'bg-rose-50/70 border-rose-200 text-rose-900',
    'warning' => 'bg-amber-50/70 border-amber-200 text-amber-900',
    'success' => 'bg-emerald-50/70 border-emerald-200 text-emerald-900',
    default => 'bg-zinc-50 border-zinc-200 text-zinc-900',
};
$tag = match($type) {
    'danger' => 'bg-rose-600 text-white',
    'warning' => 'bg-amber-600 text-white',
    'success' => 'bg-emerald-600 text-white',
    default => 'bg-zinc-800 text-white',
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
