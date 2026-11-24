<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0] flex items-center gap-2">
                    <svg class="w-6 h-6 text-[#0073B1] dark:text-[#38BDF8] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                    </svg>
                    Daftar Ruangan &amp; Laboratorium
                </h1>
                <p class="text-xs text-zinc-600 dark:text-[#9496A1] mt-1">
                    Master list data ruangan sekolah yang otomatis terhubung ke jadwal pelajaran dan jadwal ruangan.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="{{ route('room-schedules.index') }}" class="inline-flex items-center min-h-[44px] sm:min-h-0 px-3.5 py-2 text-xs font-semibold text-zinc-700 dark:text-zinc-200 hover:text-zinc-900 dark:hover:text-white bg-zinc-100 dark:bg-[#2C2C30] rounded-lg transition-colors">
                    Lihat Matriks Jadwal Ruangan &rarr;
                </a>
                <button 
                    type="button" 
                    @click="$dispatch('open-add-room-modal', { trigger: $el })"
                    class="inline-flex items-center gap-1.5 min-h-[44px] sm:min-h-0 px-3.5 py-2 bg-[#0073B1] hover:bg-[#005D8F] active:bg-[#004A73] text-white text-xs font-semibold rounded-lg transition shadow-xs cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Tambah Ruangan</span>
                </button>
            </div>
        </div>
    </x-slot>

    <div 
        class="space-y-6"
        x-data="{
            addModalOpen: false,
            editModalOpen: false,
            editRoomId: null,
            editRoomName: '',
            lastFocused: null,
            openAdd(trigger) {
                this.lastFocused = trigger || document.activeElement;
                this.addModalOpen = true;
                this.$nextTick(() => document.getElementById('add_room_name')?.focus());
            },
            openEdit(id, name, trigger) {
                this.lastFocused = trigger || document.activeElement;
                this.editRoomId = id;
                this.editRoomName = name;
                this.editModalOpen = true;
                this.$nextTick(() => document.getElementById('edit_room_name')?.focus());
            },
            closeModals() {
                const wasOpen = this.addModalOpen || this.editModalOpen;
                this.addModalOpen = false;
                this.editModalOpen = false;
                if (wasOpen && this.lastFocused && typeof this.lastFocused.focus === 'function') {
                    this.$nextTick(() => this.lastFocused.focus());
                }
            }
        }"
        @open-add-room-modal.window="openAdd($event.detail?.trigger)"
        @keydown.escape.window="closeModals()"
    >
        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if(session('error'))
            <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
        @endif

        {{-- Filter & Search Toolbar --}}
        <search class="block bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-4 shadow-xs">
            <form method="GET" action="{{ route('rooms.index') }}" class="flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="w-full sm:w-80 relative">
                    <label for="search-rooms" class="sr-only">Cari nama ruangan atau laboratorium</label>
                    <input 
                        id="search-rooms"
                        type="search" 
                        enterkeyhint="search"
                        name="search" 
                        value="{{ $search }}" 
                        placeholder="Cari nama ruangan atau lab…" 
                        class="w-full rounded-lg border-zinc-200 dark:border-[#2B2B30] bg-zinc-50/50 dark:bg-[#19191C] px-3 py-2 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1]"
                    >
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                    <button type="submit" class="min-h-[44px] sm:min-h-0 px-3.5 py-2 bg-[#0073B1] hover:bg-[#005D8F] text-white text-xs font-semibold rounded-lg transition shadow-xs cursor-pointer">
                        Cari
                    </button>
                    @if($search)
                        <a href="{{ route('rooms.index') }}" class="inline-flex items-center min-h-[44px] sm:min-h-0 px-3 py-2 text-xs text-zinc-600 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-zinc-200 bg-zinc-100 dark:bg-[#26262A] rounded-lg">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </search>

        {{-- Rooms Table --}}
        <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl overflow-hidden shadow-xs">
            <div tabindex="0" role="region" aria-label="Daftar Master Ruangan" class="overflow-x-auto table-scroll-region">
                <table class="w-full text-left text-xs border-collapse tabular-nums">
                    <caption class="sr-only">Daftar Master Ruangan dan Keterpakaian Jadwal</caption>
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-[#232327] border-b border-zinc-200 dark:border-[#2B2B30] text-[11px] font-mono text-zinc-600 dark:text-[#9496A1] uppercase tracking-wider">
                            <th scope="col" class="py-3 px-4 w-14 text-center">No</th>
                            <th scope="col" class="py-3 px-4">Nama Ruangan / Laboratorium</th>
                            <th scope="col" class="py-3 px-4 text-center">Jadwal KBM <span class="sr-only">(jumlah jadwal mengajar guru)</span></th>
                            <th scope="col" class="py-3 px-4 text-center">Matriks Ruang <span class="sr-only">(jumlah slot matriks jadwal ruangan)</span></th>
                            <th scope="col" class="py-3 px-4 text-center">Status</th>
                            <th scope="col" class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-[#2B2B30]">
                        @forelse($rooms as $index => $room)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-[#232327]/60 transition-colors">
                                <td class="py-3 px-4 text-center font-mono text-zinc-500 dark:text-[#9496A1]">
                                    {{ $rooms->firstItem() + $index }}
                                </td>
                                <td class="py-3 px-4 font-bold text-zinc-900 dark:text-[#EDEDF0]">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full {{ ($room->schedules_count > 0 || $room->room_schedules_count > 0) ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>
                                        <span>{{ $room->room_name }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-center font-mono whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $room->schedules_count > 0 ? 'bg-sky-50 dark:bg-[#16222F] text-[#0073B1] dark:text-[#38BDF8] border border-sky-200 dark:border-sky-800/40' : 'text-zinc-600 dark:text-[#9496A1]' }}">
                                        {{ $room->schedules_count }} jadwal
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center font-mono whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold {{ $room->room_schedules_count > 0 ? 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/40' : 'text-zinc-600 dark:text-[#9496A1]' }}">
                                        {{ $room->room_schedules_count }} slot
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if($room->schedules_count > 0 || $room->room_schedules_count > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-bold bg-emerald-50 dark:bg-[#16281D] text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/40 whitespace-nowrap">
                                            Aktif Digunakan
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-medium text-zinc-600 dark:text-[#9496A1] whitespace-nowrap">
                                            Belum Digunakan
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button 
                                            type="button" 
                                            @click="openEdit({{ $room->id }}, '{{ addslashes($room->room_name) }}', $el)"
                                            class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[32px] sm:min-w-[32px] p-1.5 text-zinc-600 dark:text-[#9496A1] hover:text-[#0073B1] dark:hover:text-[#38BDF8] hover:bg-zinc-100 dark:hover:bg-[#26262A] rounded-lg transition cursor-pointer"
                                            aria-label="Edit nama ruangan {{ $room->room_name }}"
                                        >
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
                                            </svg>
                                        </button>

                                        @if($room->schedules_count === 0 && $room->room_schedules_count === 0)
                                            <form action="{{ route('rooms.destroy', $room) }}" method="POST" data-confirm="Hapus ruangan {{ addslashes($room->room_name) }}?" data-confirm-title="Hapus Ruangan" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[32px] sm:min-w-[32px] p-1.5 text-zinc-600 dark:text-[#9496A1] hover:text-rose-700 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-[#2D1618] rounded-lg transition cursor-pointer" aria-label="Hapus ruangan {{ $room->room_name }}">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-zinc-600 dark:text-[#9496A1]">
                                    Belum ada data ruangan yang terdaftar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-zinc-200 dark:border-[#2B2B30]">
                {{ $rooms->links() }}
            </div>
        </div>

        {{-- Modal Tambah Ruangan --}}
        <dialog 
            closedby="any"
            x-effect="addModalOpen ? ($el.open || $el.showModal()) : ($el.open && $el.close())"
            @close="closeModals()"
            @cancel.prevent="closeModals()"
            aria-labelledby="add-room-modal-title"
            class="m-auto w-[calc(100%-2rem)] max-w-md rounded-xl bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] text-left shadow-xl p-6"
        >
                    <div class="flex items-center justify-between pb-3 border-b border-zinc-200 dark:border-[#2B2B30]">
                        <h2 id="add-room-modal-title" class="text-sm font-bold text-zinc-900 dark:text-[#EDEDF0]">Tambah Ruangan Baru</h2>
                        <button type="button" @click="closeModals()" aria-label="Tutup modal" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[32px] sm:min-w-[32px] rounded-lg text-zinc-500 hover:text-zinc-900 dark:text-[#9496A1] dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-[#26262A]">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('rooms.store') }}" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="add_room_name" class="block text-xs font-bold text-zinc-700 dark:text-[#EDEDF0] mb-1">Nama Ruangan / Lab</label>
                            <input 
                                id="add_room_name"
                                type="text" 
                                name="room_name" 
                                required 
                                placeholder="Contoh: Lab RPL 1, Ruang 15, Aula"
                                class="w-full rounded-lg border-zinc-200 dark:border-[#2B2B30] bg-zinc-50 dark:bg-[#19191C] px-3 py-2 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1]"
                            >
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" @click="closeModals()" class="px-3.5 py-2 min-h-[44px] sm:min-h-0 text-xs font-semibold text-zinc-600 dark:text-[#9496A1] hover:bg-zinc-100 dark:hover:bg-[#26262A] rounded-lg transition">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 min-h-[44px] sm:min-h-0 bg-[#0073B1] hover:bg-[#005D8F] text-white text-xs font-semibold rounded-lg transition">
                                Simpan Ruangan
                            </button>
                        </div>
                    </form>
        </dialog>

        {{-- Modal Edit Ruangan --}}
        <dialog 
            closedby="any"
            x-effect="editModalOpen ? ($el.open || $el.showModal()) : ($el.open && $el.close())"
            @close="closeModals()"
            @cancel.prevent="closeModals()"
            aria-labelledby="edit-master-room-modal-title"
            class="m-auto w-[calc(100%-2rem)] max-w-md rounded-xl bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] text-left shadow-xl p-6"
        >
                    <div class="flex items-center justify-between pb-3 border-b border-zinc-200 dark:border-[#2B2B30]">
                        <h2 id="edit-master-room-modal-title" class="text-sm font-bold text-zinc-900 dark:text-[#EDEDF0]">Edit Nama Ruangan</h2>
                        <button type="button" @click="closeModals()" aria-label="Tutup modal" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[32px] sm:min-w-[32px] rounded-lg text-zinc-500 hover:text-zinc-900 dark:text-[#9496A1] dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-[#26262A]">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form method="POST" :action="'{{ url('/rooms') }}/' + editRoomId" class="mt-4 space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <label for="edit_room_name" class="block text-xs font-bold text-zinc-700 dark:text-[#EDEDF0] mb-1">Nama Ruangan</label>
                            <input 
                                id="edit_room_name"
                                type="text" 
                                name="room_name" 
                                x-model="editRoomName" 
                                required 
                                class="w-full rounded-lg border-zinc-200 dark:border-[#2B2B30] bg-zinc-50 dark:bg-[#19191C] px-3 py-2 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0073B1] focus:ring-1 focus:ring-[#0073B1]"
                            >
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button type="button" @click="closeModals()" class="px-3.5 py-2 min-h-[44px] sm:min-h-0 text-xs font-semibold text-zinc-600 dark:text-[#9496A1] hover:bg-zinc-100 dark:hover:bg-[#26262A] rounded-lg transition">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 min-h-[44px] sm:min-h-0 bg-[#0073B1] hover:bg-[#005D8F] text-white text-xs font-semibold rounded-lg transition">
                                Perbarui Ruangan
                            </button>
                        </div>
                    </form>
        </dialog>

    </div>
</x-app-layout>
