@props(['totalExpr' => 'totalMatches'])

<div
    x-show="{{ $totalExpr }} > pageSize"
    x-cloak
    role="navigation"
    aria-label="Navigasi Halaman"
    class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2"
>
    <p class="text-xs text-zinc-600 dark:text-[#B4B6C2] tabular-nums">
        Menampilkan
        <span class="font-semibold text-zinc-900 dark:text-[#F1F1F4]" x-text="((currentPage - 1) * pageSize) + 1"></span>
        &ndash;
        <span class="font-semibold text-zinc-900 dark:text-[#F1F1F4]" x-text="Math.min(currentPage * pageSize, {{ $totalExpr }})"></span>
        dari
        <span class="font-semibold text-zinc-900 dark:text-[#F1F1F4]" x-text="{{ $totalExpr }}"></span>
        data
    </p>

    <div class="inline-flex items-center gap-1.5">
        <button
            type="button"
            @click="if (currentPage > 1) currentPage--"
            :disabled="currentPage <= 1"
            class="inline-flex items-center justify-center min-h-[44px] sm:min-h-[36px] px-3 py-1.5 text-xs font-semibold rounded-lg border border-zinc-300 dark:border-[#2E3140] bg-white dark:bg-[#1D1D22] text-zinc-800 dark:text-[#F1F1F4] hover:bg-zinc-100 dark:hover:bg-[#262832] disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
        >
            &laquo; Sebelumnya
        </button>

        <span class="px-2.5 text-xs font-mono text-zinc-600 dark:text-[#B4B6C2] tabular-nums">
            <span x-text="currentPage"></span> / <span x-text="totalPages"></span>
        </span>

        <button
            type="button"
            @click="if (currentPage < totalPages) currentPage++"
            :disabled="currentPage >= totalPages"
            class="inline-flex items-center justify-center min-h-[44px] sm:min-h-[36px] px-3 py-1.5 text-xs font-semibold rounded-lg border border-zinc-300 dark:border-[#2E3140] bg-white dark:bg-[#1D1D22] text-zinc-800 dark:text-[#F1F1F4] hover:bg-zinc-100 dark:hover:bg-[#262832] disabled:opacity-40 disabled:cursor-not-allowed transition cursor-pointer"
        >
            Berikutnya &raquo;
        </button>
    </div>
</div>
