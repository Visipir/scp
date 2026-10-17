@props(['status'])

@php
$classes = match($status) {
    'Approved', 'Hadir' => 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30',
    'Rejected', 'Alpa'  => 'bg-[#FD366E]/15 text-[#FD366E] border-[#FD366E]/30',
    'Pending', 'Menunggu' => 'bg-amber-500/15 text-amber-400 border-amber-500/30',
    'Izin'              => 'bg-sky-500/15 text-sky-400 border-sky-500/30',
    'Sakit'             => 'bg-amber-500/15 text-amber-400 border-amber-500/30',
    'Terlambat'         => 'bg-purple-500/15 text-purple-400 border-purple-500/30',
    default             => 'bg-[#232327] text-[#97979B] border-[#2B2B30]',
};
$dot = match($status) {
    'Approved', 'Hadir' => 'bg-emerald-400',
    'Rejected', 'Alpa'  => 'bg-[#FD366E]',
    'Pending', 'Menunggu' => 'bg-amber-400',
    'Izin'              => 'bg-sky-400',
    'Sakit'             => 'bg-amber-400',
    'Terlambat'         => 'bg-purple-400',
    default             => 'bg-[#97979B]',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-semibold border tracking-wide uppercase font-mono {$classes}"]) }}>
    <span class="w-1.5 h-1.5 rounded-full {{ $dot }}"></span>
    {{ $status }}
</span>
