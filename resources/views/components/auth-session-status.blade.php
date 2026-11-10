@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-mono text-xs text-emerald-800 dark:text-emerald-300 bg-emerald-50 dark:bg-[#16281D] border border-emerald-300 dark:border-emerald-700/60 px-3.5 py-2.5 rounded-lg mb-4']) }}>
        {{ $status }}
    </div>
@endif
