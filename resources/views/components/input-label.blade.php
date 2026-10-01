@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5']) }}>
    {{ $value ?? $slot }}
</label>
