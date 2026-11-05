@props(['status'])

@php
$classes = match($status) {
    'Approved', 'Hadir' => 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-500/30',
    'Rejected', 'Alpa'  => 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-500/15 dark:text-rose-400 dark:border-rose-500/30',
    'Pending', 'Menunggu' => 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30',
    'Izin'              => 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-500/15 dark:text-sky-400 dark:border-sky-500/30',
    'Sakit'             => 'bg-amber-50 text-amber-800 border-amber-200 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-500/30',
    'Terlambat'         => 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-500/15 dark:text-purple-400 dark:border-purple-500/30',
    default             => 'bg-zinc-100 text-zinc-600 border-zinc-200 dark:bg-[#232327] dark:text-[#97979B] dark:border-[#2B2B30]',
};
$dot = match($status) {
    'Approved', 'Hadir' => 'bg-emerald-500 dark:bg-emerald-400',
    'Rejected', 'Alpa'  => 'bg-rose-500 dark:bg-rose-400',
    'Pending', 'Menunggu' => 'bg-amber-500 dark:bg-amber-400',
    'Izin'              => 'bg-sky-500 dark:bg-sky-400',
    'Sakit'             => 'bg-amber-500 dark:bg-amber-400',
    'Terlambat'         => 'bg-purple-500 dark:bg-purple-400',
    default             => 'bg-zinc-400 dark:bg-[#97979B]',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-semibold border tracking-wide uppercase font-mono {$classes}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>
    {{ $status }}
</span>
