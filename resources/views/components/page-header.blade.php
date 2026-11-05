@props([
    'title',
    'subtitle' => null,
])

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0]">
            {{ $title }}
        </h1>
        @if($subtitle)
            <p class="text-xs text-zinc-500 dark:text-[#97979B] mt-0.5">
                {{ $subtitle }}
            </p>
        @endif
    </div>
    @if(isset($actions) || !empty(trim((string)$slot)))
        <div class="flex flex-wrap items-center gap-2 sm:self-center shrink-0">
            {{ $actions ?? $slot }}
        </div>
    @endif
</div>
