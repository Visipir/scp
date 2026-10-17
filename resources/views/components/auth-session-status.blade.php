@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-mono text-xs text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-3.5 py-2.5 rounded-lg mb-4']) }}>
        {{ $status }}
    </div>
@endif
