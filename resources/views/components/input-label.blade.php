@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-xs font-semibold text-[#97979B] uppercase tracking-wider mb-1.5']) }}>
    {{ $value ?? $slot }}
</label>
