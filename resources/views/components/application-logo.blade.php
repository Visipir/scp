<picture>
    <source srcset="{{ asset('images/logo.webp') }}" type="image/webp">
    <img src="{{ asset('images/logo.png') }}" alt="Logo SMKN 1 Bantul" width="300" height="300" {{ $attributes->merge(['class' => 'object-contain']) }}>
</picture>
