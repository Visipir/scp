@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi Halaman" class="flex flex-col sm:flex-row items-center justify-between gap-3">
        {{-- Mobile Compact Prev/Next --}}
        <div class="flex w-full gap-2 items-center justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center justify-center min-h-[44px] px-4 py-2 text-xs font-semibold text-zinc-500 dark:text-[#9496A1] bg-white dark:bg-[#15161D] border border-zinc-300 dark:border-[#2E3140] cursor-not-allowed rounded-lg">
                    &laquo; Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2 text-xs font-semibold text-zinc-800 dark:text-[#F1F1F4] bg-white dark:bg-[#1D1D22] border border-zinc-300 dark:border-[#2E3140] rounded-lg hover:bg-zinc-100 dark:hover:bg-[#262832] transition-colors">
                    &laquo; Sebelumnya
                </a>
            @endif

            <span class="text-xs font-mono text-zinc-600 dark:text-[#B4B6C2] tabular-nums">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center justify-center min-h-[44px] px-4 py-2 text-xs font-semibold text-zinc-800 dark:text-[#F1F1F4] bg-white dark:bg-[#1D1D22] border border-zinc-300 dark:border-[#2E3140] rounded-lg hover:bg-zinc-100 dark:hover:bg-[#262832] transition-colors">
                    Berikutnya &raquo;
                </a>
            @else
                <span class="inline-flex items-center justify-center min-h-[44px] px-4 py-2 text-xs font-semibold text-zinc-500 dark:text-[#9496A1] bg-white dark:bg-[#15161D] border border-zinc-300 dark:border-[#2E3140] cursor-not-allowed rounded-lg">
                    Berikutnya &raquo;
                </span>
            @endif
        </div>

        {{-- Desktop Full Info & Page Numbers --}}
        <div class="hidden sm:flex sm:flex-1 sm:gap-4 sm:items-center sm:justify-between">
            <div>
                <p class="text-xs text-zinc-600 dark:text-[#B4B6C2] leading-5 tabular-nums">
                    Menampilkan
                    @if ($paginator->firstItem())
                        <span class="font-semibold text-zinc-900 dark:text-[#F1F1F4]">{{ $paginator->firstItem() }}</span>
                        &ndash;
                        <span class="font-semibold text-zinc-900 dark:text-[#F1F1F4]">{{ $paginator->lastItem() }}</span>
                    @else
                        <span class="font-semibold text-zinc-900 dark:text-[#F1F1F4]">{{ $paginator->count() }}</span>
                    @endif
                    dari
                    <span class="font-semibold text-zinc-900 dark:text-[#F1F1F4]">{{ $paginator->total() }}</span>
                    data
                </p>
            </div>

            <div>
                <span class="inline-flex rtl:flex-row-reverse rounded-lg overflow-hidden border border-zinc-300 dark:border-[#2E3140]">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="Halaman sebelumnya">
                            <span class="inline-flex items-center justify-center px-2.5 py-2 min-h-[36px] text-xs font-medium text-zinc-500 dark:text-[#9496A1] bg-zinc-50 dark:bg-[#15161D] cursor-not-allowed" aria-hidden="true">
                                <svg aria-hidden="true" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center justify-center px-2.5 py-2 min-h-[36px] text-xs font-medium text-zinc-700 dark:text-[#F1F1F4] bg-white dark:bg-[#1D1D22] hover:bg-zinc-100 dark:hover:bg-[#262832] transition-colors" aria-label="Halaman sebelumnya">
                            <svg aria-hidden="true" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="inline-flex items-center px-3 py-2 min-h-[36px] border-l border-zinc-300 dark:border-[#2E3140] text-xs font-medium text-zinc-600 dark:text-[#B4B6C2] bg-white dark:bg-[#1D1D22] cursor-default">{{ $element }}</span>
                            </span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="inline-flex items-center px-3.5 py-2 min-h-[36px] border-l border-[#0073B1] text-xs font-bold text-white bg-[#0073B1] cursor-default tabular-nums">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="inline-flex items-center px-3.5 py-2 min-h-[36px] border-l border-zinc-300 dark:border-[#2E3140] text-xs font-medium text-zinc-700 dark:text-[#F1F1F4] bg-white dark:bg-[#1D1D22] hover:bg-zinc-100 dark:hover:bg-[#262832] transition-colors tabular-nums" aria-label="Ke halaman {{ $page }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center justify-center px-2.5 py-2 min-h-[36px] border-l border-zinc-300 dark:border-[#2E3140] text-xs font-medium text-zinc-700 dark:text-[#F1F1F4] bg-white dark:bg-[#1D1D22] hover:bg-zinc-100 dark:hover:bg-[#262832] transition-colors" aria-label="Halaman berikutnya">
                            <svg aria-hidden="true" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="Halaman berikutnya">
                            <span class="inline-flex items-center justify-center px-2.5 py-2 min-h-[36px] border-l border-zinc-300 dark:border-[#2E3140] text-xs font-medium text-zinc-500 dark:text-[#9496A1] bg-zinc-50 dark:bg-[#15161D] cursor-not-allowed" aria-hidden="true">
                                <svg aria-hidden="true" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
