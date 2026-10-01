@props(['status'])

@php
$classes = match($status) {
    'Approved', 'Hadir' => 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800/80',
    'Rejected', 'Alpa'  => 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800/80',
    'Pending', 'Menunggu' => 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800/80',
    'Izin'              => 'bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-800/80',
    'Sakit'             => 'bg-purple-50 text-purple-800 border-purple-200 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800/80',
    'Terlambat'         => 'bg-orange-50 text-orange-800 border-orange-200 dark:bg-orange-950/60 dark:text-orange-300 dark:border-orange-800/80',
    default             => 'bg-zinc-100 text-zinc-800 border-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:border-zinc-700',
};
$dot = match($status) {
    'Approved', 'Hadir' => 'bg-emerald-600 dark:bg-emerald-400',
    'Rejected', 'Alpa'  => 'bg-rose-600 dark:bg-rose-400',
    'Pending', 'Menunggu' => 'bg-amber-500 dark:bg-amber-400',
    'Izin'              => 'bg-blue-600 dark:bg-blue-400',
    'Sakit'             => 'bg-purple-600 dark:bg-purple-400',
    'Terlambat'         => 'bg-orange-500 dark:bg-orange-400',
    default             => 'bg-zinc-500 dark:bg-zinc-400',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-semibold border tracking-wide uppercase font-mono {$classes}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>
    {{ $status }}
</span>
