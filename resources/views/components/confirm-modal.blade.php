<dialog
    x-data
    closedby="any"
    x-effect="if ($store.confirmModal.open) { if (!$el.open) { $el.showModal(); $nextTick(() => $refs.cancelBtn?.focus()); } } else if ($el.open) { $el.close(); }"
    @close="if ($store.confirmModal.open) $store.confirmModal.cancel()"
    @cancel.prevent="$store.confirmModal.cancel()"
    aria-labelledby="confirm-modal-title"
    aria-describedby="confirm-modal-message"
    class="m-auto w-[min(28rem,calc(100vw-2rem))] rounded-xl bg-white dark:bg-[#1D1D22] border border-zinc-200 dark:border-[#2E3140] shadow-xl p-6 text-left text-zinc-900 dark:text-[#F1F1F4]"
>
    <div class="space-y-4">
        <div class="flex items-start gap-3.5">
            <div
                class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0 border"
                :class="$store.confirmModal.variant === 'warning'
                    ? 'bg-amber-50 dark:bg-[#282115] border-amber-200 dark:border-amber-700/60 text-amber-700 dark:text-amber-300'
                    : 'bg-rose-50 dark:bg-[#2D1618] border-rose-200 dark:border-rose-700/60 text-rose-700 dark:text-rose-300'"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <h2 id="confirm-modal-title" class="text-base font-bold text-zinc-900 dark:text-[#F1F1F4]" x-text="$store.confirmModal.title"></h2>
                <p id="confirm-modal-message" class="text-sm text-zinc-600 dark:text-[#B4B6C2] mt-1 leading-relaxed" x-text="$store.confirmModal.message"></p>
            </div>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 pt-2">
            <button
                x-ref="cancelBtn"
                type="button"
                @click="$store.confirmModal.cancel()"
                class="w-full sm:w-auto min-h-[44px] sm:min-h-[38px] px-4 py-2 text-xs font-semibold rounded-lg border border-zinc-300 dark:border-[#2E3140] bg-white dark:bg-[#262832] text-zinc-700 dark:text-[#F1F1F4] hover:bg-zinc-100 dark:hover:bg-[#2F323E] transition-colors cursor-pointer"
            >
                Batal
            </button>
            <button
                type="button"
                @click="$store.confirmModal.confirm()"
                class="w-full sm:w-auto min-h-[44px] sm:min-h-[38px] px-4 py-2 text-xs font-semibold rounded-lg text-white transition-colors cursor-pointer"
                :class="$store.confirmModal.variant === 'warning'
                    ? 'bg-amber-700 hover:bg-amber-800'
                    : 'bg-rose-700 hover:bg-rose-800'"
                x-text="$store.confirmModal.confirmLabel"
            ></button>
        </div>
    </div>
</dialog>
