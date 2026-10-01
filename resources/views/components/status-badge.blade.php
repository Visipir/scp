@props(['status'])

@php
$classes = match($status) {
    'Approved', 'Hadir' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
    'Rejected', 'Alpa'  => 'bg-rose-50 text-rose-800 border-rose-200',
    'Pending'           => 'bg-amber-50 text-amber-800 border-amber-200',
    'Izin'              => 'bg-blue-50 text-blue-800 border-blue-200',
    'Sakit'             => 'bg-purple-50 text-purple-800 border-purple-200',
    'Terlambat'         => 'bg-orange-50 text-orange-800 border-orange-200',
    default             => 'bg-zinc-100 text-zinc-800 border-zinc-200',
};
$dot = match($status) {
    'Approved', 'Hadir' => 'bg-emerald-600',
    'Rejected', 'Alpa'  => 'bg-rose-600',
    'Pending'           => 'bg-amber-500',
    'Izin'              => 'bg-blue-600',
    'Sakit'             => 'bg-purple-600',
    'Terlambat'         => 'bg-orange-500',
    default             => 'bg-zinc-500',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-semibold border tracking-wide uppercase font-mono {$classes}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>
    {{ $status }}
</span>
