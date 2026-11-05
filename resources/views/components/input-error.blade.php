@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-xs font-mono text-rose-600 dark:text-rose-400 space-y-1 mt-1.5']) }}>
        @foreach ((array) $messages as $message)
            <li class="flex items-center gap-1.5">
                <span class="w-1 h-1 rounded-full bg-rose-600 dark:bg-rose-400"></span>
                <span>{{ $message }}</span>
            </li>
        @endforeach
    </ul>
@endif
