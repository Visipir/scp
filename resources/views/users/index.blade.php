<x-app-layout>
    <x-slot name="header">
        <x-page-header 
            title="Manajemen Pengguna" 
            subtitle="Pengelolaan akun guru, staf, siswa, dan hak akses sistem"
        >
            <div class="flex flex-wrap items-center gap-2">
                @if(auth()->user()?->hasTechnicalAccess())
                    <form method="POST" action="{{ route('users.bulk-reset-passwords') }}" class="inline" data-confirm="Reset password massal untuk semua akun Guru menjadi password sementara acak? Semua sesi/token aktif guru akan dicabut." data-confirm-title="Reset Password Massal Guru">
                        @csrf
                        <button 
                            type="submit" 
                            class="inline-flex items-center justify-center gap-2 min-h-[44px] sm:min-h-0 px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg transition shadow-none shrink-0 cursor-pointer"
                        >
                            <span>Reset PW Massal Guru</span>
                        </button>
                    </form>
                @endif
                <button 
                    type="button" 
                    @click="$dispatch('open-add-user-modal')" 
                    class="inline-flex items-center justify-center gap-2 min-h-[44px] sm:min-h-0 px-4 py-2 bg-[#0073B1] hover:bg-[#005D8F] active:bg-[#004A73] text-white text-xs font-semibold rounded-lg transition shadow-none shrink-0 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    <span>Tambah Pengguna</span>
                </button>
            </div>
        </x-page-header>
    </x-slot>

    <div class="space-y-6"
        x-data="{ 
            addModalOpen: false, 
            editModalOpen: false, 
            resetModalOpen: false,
            editUser: { id: null, fullname: '', username: '', email: '', role_id: '', class_id: '' },
            resetUser: { id: null, fullname: '', username: '' },
            resetMode: 'default',
            resetPassword: '',
            lastFocused: null,
            openAddModal(trigger) {
                this.lastFocused = trigger || document.activeElement;
                this.addModalOpen = true;
                this.$nextTick(() => document.getElementById('add_fullname')?.focus());
            },
            openEditModal(user, trigger) {
                this.lastFocused = trigger || document.activeElement;
                this.editUser = user;
                this.editModalOpen = true;
                this.$nextTick(() => document.getElementById('edit_fullname')?.focus());
            },
            openResetModal(user, trigger) {
                this.lastFocused = trigger || document.activeElement;
                this.resetUser = user;
                this.resetMode = 'default';
                this.resetPassword = '';
                this.resetModalOpen = true;
                this.$nextTick(() => document.getElementById('reset_mode_default')?.focus());
            },
            closeModals() {
                const wasOpen = this.addModalOpen || this.editModalOpen || this.resetModalOpen;
                this.addModalOpen = false;
                this.editModalOpen = false;
                this.resetModalOpen = false;
                if (wasOpen && this.lastFocused && typeof this.lastFocused.focus === 'function') {
                    this.$nextTick(() => this.lastFocused.focus());
                }
            }
        }"
        @open-add-user-modal.window="openAddModal($event.detail?.trigger)"
        @keydown.escape.window="closeModals()"
    >

        {{-- Alerts --}}
        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if(session('reset_credentials'))
            <div class="bg-amber-50 dark:bg-[#282115] border border-amber-300 dark:border-amber-700/60 rounded-xl p-4 text-xs space-y-2">
                <div class="font-semibold text-amber-900 dark:text-amber-300">
                    Kredensial Sementara (Tampil Sekali — Tersimpan di <code>storage/app/private/{{ session('reset_csv_path') }}</code>)
                </div>
                <div class="max-h-48 overflow-y-auto border border-amber-200/70 dark:border-amber-800/50 rounded-lg bg-white dark:bg-[#1A1A1D] p-2.5 font-mono text-[11px]">
                    @foreach(session('reset_credentials') as $cred)
                        <div class="py-0.5 text-zinc-800 dark:text-zinc-200">
                            {{ $cred['fullname'] }} ({{ $cred['username'] }}) &rarr; <span class="font-bold text-amber-700 dark:text-amber-400">{{ $cred['temporary_password'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        @if(session('error'))
            <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
        @endif
        @if($errors->any())
            <x-alert-banner type="danger">{{ $errors->first() }}</x-alert-banner>
        @endif

        {{-- Top Control Bar --}}
        <search class="block bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-4 sm:p-5 min-w-0 max-w-full space-y-4">
            @if(in_array((int) auth()->user()->role_id, [1, 5], true))
                <div class="flex flex-wrap items-center gap-2 border-b border-zinc-200 dark:border-[#2B2B30] pb-3.5" role="group" aria-label="Filter cepat peran pengguna">
                    <a
                        href="{{ route('users.index', array_filter(['class_id' => $classFilter, 'search' => $search])) }}"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg border transition {{ empty($roleFilter) ? 'bg-[#0073B1] text-white border-[#0073B1]' : 'bg-zinc-50 dark:bg-[#26262A] text-zinc-700 dark:text-[#B4B6C2] border-zinc-200 dark:border-[#2B2B30] hover:text-zinc-900 dark:hover:text-white' }}"
                    >
                        Semua
                    </a>
                    <a
                        href="{{ route('users.index', array_filter(['role_id' => 2, 'search' => $search])) }}"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg border transition {{ (string) $roleFilter === '2' ? 'bg-[#0073B1] text-white border-[#0073B1]' : 'bg-zinc-50 dark:bg-[#26262A] text-zinc-700 dark:text-[#B4B6C2] border-zinc-200 dark:border-[#2B2B30] hover:text-zinc-900 dark:hover:text-white' }}"
                    >
                        Guru
                    </a>
                    <a
                        href="{{ route('users.index', array_filter(['role_id' => 4, 'class_id' => $classFilter, 'search' => $search])) }}"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg border transition {{ (string) $roleFilter === '4' ? 'bg-[#0073B1] text-white border-[#0073B1]' : 'bg-zinc-50 dark:bg-[#26262A] text-zinc-700 dark:text-[#B4B6C2] border-zinc-200 dark:border-[#2B2B30] hover:text-zinc-900 dark:hover:text-white' }}"
                    >
                        Siswa
                    </a>
                </div>
            @endif

            <form method="GET" action="{{ route('users.index') }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 w-full">
                <div class="flex flex-col sm:flex-row sm:items-center flex-wrap gap-3 w-full">
                    <label for="role_id_filter" class="text-xs font-semibold uppercase tracking-wider text-zinc-600 dark:text-[#9496A1] shrink-0">
                        Filter:
                    </label>
                    
                    <select 
                        id="role_id_filter" 
                        name="role_id" 
                        class="w-full sm:w-auto rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2 text-xs font-medium text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0073B1] focus:border-[#0073B1] shadow-none transition cursor-pointer"
                    >
                        <option value="">Semua Peran</option>
                        @foreach($roles as $id => $name)
                            <option value="{{ $id }}" @selected($roleFilter == $id)>{{ $name }}</option>
                        @endforeach
                    </select>

                    <label for="class_id_filter" class="sr-only">Filter Kelas</label>
                    <select
                        id="class_id_filter"
                        name="class_id"
                        class="w-full sm:w-auto rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2 text-xs font-medium text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0073B1] focus:border-[#0073B1] shadow-none transition cursor-pointer"
                    >
                        <option value="">Semua Kelas</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" @selected($classFilter == $c->id)>{{ $c->class_name }}</option>
                        @endforeach
                    </select>

                    <div class="relative w-full sm:w-64">
                        <label for="search-users" class="sr-only">Cari Pengguna</label>
                        <input 
                            id="search-users"
                            type="search" 
                            name="search" 
                            enterkeyhint="search"
                            value="{{ $search }}" 
                            placeholder="Cari nama, username, email…" 
                            class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0073B1] focus:border-[#0073B1] shadow-none transition"
                        >
                    </div>
                    
                    <button 
                        type="submit" 
                        class="min-h-[44px] sm:min-h-0 px-3.5 py-2 bg-[#0073B1] hover:bg-[#005D8F] text-white text-xs font-semibold rounded-lg transition cursor-pointer"
                    >
                        Terapkan
                    </button>

                    @if(!empty($roleFilter) || !empty($classFilter) || !empty($search))
                        <a href="{{ route('users.index') }}" class="px-2.5 py-2 text-xs text-zinc-600 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-white underline">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </search>

        {{-- Tabel Data Pengguna --}}
        <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl overflow-hidden shadow-xs min-w-0 max-w-full">
            <div tabindex="0" role="region" aria-label="Daftar Pengguna Sistem" class="overflow-x-auto table-scroll-region min-w-0 w-full">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-[#2B2B30] text-left tabular-nums">
                    <caption class="sr-only">Daftar Akun Pengguna <span translate="no">School Class Progress</span></caption>
                    <thead class="bg-zinc-50 dark:bg-[#232327] text-zinc-600 dark:text-[#9496A1] text-xs font-semibold uppercase tracking-wider">
                        <tr>
                            <th scope="col" class="px-5 py-3">Nama Lengkap</th>
                            <th scope="col" class="px-5 py-3">Username</th>
                            <th scope="col" class="px-5 py-3">Peran (Role)</th>
                            <th scope="col" class="px-5 py-3">Kelas</th>
                            <th scope="col" class="px-5 py-3 text-right">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-[#2B2B30] text-xs">
                        @forelse($users as $user)
                            <tr class="hover:bg-zinc-50/70 dark:hover:bg-[#26262A]/40 transition-colors">
                                <td class="px-5 py-3.5 font-semibold text-zinc-900 dark:text-[#EDEDF0]">
                                    {{ $user->fullname }}
                                </td>
                                <td class="px-5 py-3.5 text-zinc-600 dark:text-[#97979B]">
                                    <div class="font-mono">{{ $user->username }}</div>
                                    @if($user->email)
                                        <div class="text-[11px] text-zinc-500 dark:text-[#97979B] flex items-center gap-1 mt-0.5">
                                            <span>{{ $user->email }}</span>
                                            @if($user->email_verified_at)
                                                <span class="text-emerald-600 font-semibold" title="Email terverifikasi">✓</span>
                                            @else
                                                <span class="text-amber-500 font-normal" title="Belum diverifikasi">(belum verif)</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold 
                                        {{ $user->role_id == 5 ? 'bg-purple-100 text-purple-800 dark:bg-[#241B35] dark:text-purple-300' : '' }}
                                        {{ $user->role_id == 1 ? 'bg-indigo-100 text-indigo-800 dark:bg-[#1D1E36] dark:text-indigo-300' : '' }}
                                        {{ $user->role_id == 2 ? 'bg-emerald-100 text-emerald-800 dark:bg-[#16281D] dark:text-emerald-300' : '' }}
                                        {{ $user->role_id == 3 ? 'bg-amber-100 text-amber-800 dark:bg-[#282115] dark:text-amber-300' : '' }}
                                        {{ $user->role_id == 4 ? 'bg-zinc-100 text-zinc-700 dark:bg-[#232327] dark:text-zinc-300' : '' }}
                                    ">
                                        {{ $roleLabels[$user->role_id] ?? 'Unknown' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    @if(in_array($user->role_id, [3, 4]) && $user->schoolClass)
                                        <span class="inline-block px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 font-mono text-[11px] font-semibold">
                                            {{ $user->schoolClass->class_name }}
                                        </span>
                                    @else
                                        <span class="text-zinc-500 dark:text-[#9496A1]">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right font-medium">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button 
                                            type="button" 
                                            @click="openEditModal({ id: {{ $user->id }}, fullname: '{{ addslashes($user->fullname) }}', username: '{{ addslashes($user->username) }}', email: '{{ addslashes($user->email ?? '') }}', role_id: '{{ $user->role_id }}', class_id: '{{ $user->class_id ?? '' }}' }, $el)"
                                            class="px-2.5 py-1.5 text-xs text-zinc-900 dark:text-[#EDEDF0] hover:text-[#0073B1] dark:hover:text-[#38BDF8] hover:bg-zinc-100 dark:hover:bg-[#26262A] rounded-md font-semibold transition cursor-pointer min-h-[44px] sm:min-h-[36px] flex items-center"
                                        >
                                            Edit
                                        </button>
                                        
                                        <button
                                            type="button"
                                            @click="openResetModal({ id: {{ $user->id }}, fullname: '{{ addslashes($user->fullname) }}', username: '{{ addslashes($user->username) }}' }, $el)"
                                            class="px-2.5 py-1.5 text-xs text-indigo-700 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 rounded-md font-semibold transition cursor-pointer min-h-[44px] sm:min-h-[36px] flex items-center whitespace-nowrap"
                                        >
                                            Reset PW
                                        </button>

                                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline" data-confirm="Yakin ingin menghapus {{ $user->fullname }}?" data-confirm-title="Hapus Pengguna">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-2.5 py-1.5 text-xs text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 rounded-md font-semibold transition cursor-pointer min-h-[44px] sm:min-h-[36px] flex items-center disabled:opacity-40" {{ auth()->id() === $user->id ? 'disabled' : '' }}>
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-xs text-zinc-600 dark:text-[#9496A1]">
                                    Tidak ada data pengguna yang ditemukan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div class="pt-2 text-xs">
            {{ $users->links() }}
        </div>

        {{-- MODAL: Tambah Pengguna --}}
        <dialog 
            closedby="any"
            x-effect="addModalOpen ? ($el.open || $el.showModal()) : ($el.open && $el.close())"
            @close="closeModals()"
            @cancel.prevent="closeModals()"
            aria-labelledby="add-user-modal-title"
            class="m-auto w-[calc(100%-2rem)] sm:max-w-lg bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl text-left overflow-y-auto shadow-xl p-6 sm:p-8"
        >
                    <div class="border-b border-zinc-200 dark:border-[#2B2B30] pb-4 mb-5 flex items-center justify-between gap-4">
                        <h2 id="add-user-modal-title" class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">Tambah Pengguna Baru</h2>
                        <button type="button" @click="closeModals()" aria-label="Tutup modal" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[32px] sm:min-w-[32px] rounded-lg text-zinc-500 hover:text-zinc-900 dark:text-[#9496A1] dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('users.store') }}" class="space-y-4" x-data="{ role: '{{ in_array((string) $roleFilter, ['1', '2', '3', '4'], true) ? $roleFilter : '4' }}' }">
                        @csrf
                        <div>
                            <x-input-label for="add_fullname" value="Nama Lengkap" />
                            <x-text-input id="add_fullname" name="fullname" type="text" autocomplete="name" required />
                        </div>
                        <div>
                            <x-input-label for="add_username" value="Username (Unik)" />
                            <x-text-input id="add_username" name="username" type="text" autocomplete="username" autocorrect="off" autocapitalize="none" spellcheck="false" required />
                        </div>
                        <div>
                            <x-input-label for="add_email" value="Alamat Email (Opsional)" />
                            <x-text-input id="add_email" name="email" type="email" autocomplete="email" inputmode="email" placeholder="nama@example.test" />
                        </div>
                        <div>
                            <x-input-label for="add_password" value="Password Awal" />
                            <x-password-input id="add_password" name="password" autocomplete="new-password" required />
                        </div>
                        <div>
                            <x-input-label for="add_role_id" value="Peran (Role)" />
                            <select id="add_role_id" name="role_id" x-model="role" required class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0073B1] focus:border-[#0073B1] shadow-none transition">
                                @foreach($roles as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="role == '4' || role == '3'">
                            <label for="add_class_id" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5" x-text="role == '3' ? 'Kelas Perwalian (kosongkan untuk Guru BK)' : 'Kelas (Khusus Siswa)'"></label>
                            <select id="add_class_id" name="class_id" :required="role == '4'" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0073B1] focus:border-[#0073B1] shadow-none transition">
                                <option value="">-- Pilih Kelas --</option>
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}">{{ $c->class_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-[#2B2B30]">
                            <button type="button" @click="closeModals()" class="px-4 py-2 min-h-[44px] sm:min-h-0 text-xs font-semibold text-zinc-600 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-[#EDEDF0] transition">Batal</button>
                            <x-primary-button>Simpan</x-primary-button>
                        </div>
                    </form>
        </dialog>

        {{-- MODAL: Edit Pengguna --}}
        <dialog 
            closedby="any"
            x-effect="editModalOpen ? ($el.open || $el.showModal()) : ($el.open && $el.close())"
            @close="closeModals()"
            @cancel.prevent="closeModals()"
            aria-labelledby="edit-user-modal-title"
            class="m-auto w-[calc(100%-2rem)] sm:max-w-lg bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl text-left overflow-y-auto shadow-xl p-6 sm:p-8"
        >
                    <div class="border-b border-zinc-200 dark:border-[#2B2B30] pb-4 mb-5 flex items-center justify-between gap-4">
                        <h2 id="edit-user-modal-title" class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">Edit Pengguna</h2>
                        <button type="button" @click="closeModals()" aria-label="Tutup modal" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[32px] sm:min-w-[32px] rounded-lg text-zinc-500 hover:text-zinc-900 dark:text-[#9496A1] dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form :action="'{{ url('users') }}/' + editUser.id" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <x-input-label for="edit_fullname" value="Nama Lengkap" />
                            <x-text-input id="edit_fullname" name="fullname" type="text" x-model="editUser.fullname" autocomplete="name" required />
                        </div>
                        <div>
                            <x-input-label for="edit_username" value="Username (Unik)" />
                            <x-text-input id="edit_username" name="username" type="text" x-model="editUser.username" autocomplete="username" autocorrect="off" autocapitalize="none" spellcheck="false" required />
                        </div>
                        <div>
                            <x-input-label for="edit_email" value="Alamat Email (Opsional)" />
                            <x-text-input id="edit_email" name="email" type="email" x-model="editUser.email" autocomplete="email" inputmode="email" placeholder="nama@example.test" />
                        </div>
                        <div>
                            <x-input-label for="edit_role_id" value="Peran (Role)" />
                            <select id="edit_role_id" name="role_id" x-model="editUser.role_id" required class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0073B1] focus:border-[#0073B1] shadow-none transition">
                                @foreach($roles as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="editUser.role_id == '4' || editUser.role_id == '3'">
                            <label for="edit_class_id" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5" x-text="editUser.role_id == '3' ? 'Kelas Perwalian (kosongkan untuk Guru BK)' : 'Kelas (Khusus Siswa)'"></label>
                            <select id="edit_class_id" name="class_id" x-model="editUser.class_id" :required="editUser.role_id == '4'" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0073B1] focus:border-[#0073B1] shadow-none transition">
                                <option value="">-- Pilih Kelas --</option>
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}">{{ $c->class_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-[#2B2B30]">
                            <button type="button" @click="closeModals()" class="px-4 py-2 min-h-[44px] sm:min-h-0 text-xs font-semibold text-zinc-600 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-[#EDEDF0] transition">Batal</button>
                            <x-primary-button>Perbarui</x-primary-button>
                        </div>
                    </form>
        </dialog>

        {{-- MODAL: Reset Password Pengguna --}}
        <dialog
            closedby="any"
            x-effect="resetModalOpen ? ($el.open || $el.showModal()) : ($el.open && $el.close())"
            @close="closeModals()"
            @cancel.prevent="closeModals()"
            aria-labelledby="reset-pw-modal-title"
            class="m-auto w-[calc(100%-2rem)] sm:max-w-md bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl text-left overflow-y-auto shadow-xl p-6 sm:p-8"
        >
                    <div class="border-b border-zinc-200 dark:border-[#2B2B30] pb-4 mb-5 flex items-center justify-between gap-4">
                        <div>
                            <h2 id="reset-pw-modal-title" class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">Reset Password Pengguna</h2>
                            <p class="text-xs text-zinc-600 dark:text-[#9496A1] mt-0.5">
                                <span class="font-semibold text-zinc-900 dark:text-[#EDEDF0]" x-text="resetUser.fullname"></span>
                                (<span class="font-mono" x-text="resetUser.username"></span>)
                            </p>
                        </div>
                        <button type="button" @click="closeModals()" aria-label="Tutup modal" class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] sm:min-h-[32px] sm:min-w-[32px] rounded-lg text-zinc-500 hover:text-zinc-900 dark:text-[#9496A1] dark:hover:text-[#EDEDF0] hover:bg-zinc-100 dark:hover:bg-[#26262A] transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form :action="'{{ url('users') }}/' + resetUser.id + '/reset-password'" method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        <div class="space-y-2.5">
                            <span class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Metode Reset Password</span>

                            <label
                                for="reset_mode_default"
                                class="flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition"
                                :class="resetMode === 'default' ? 'border-[#0073B1] bg-sky-50/40 dark:bg-[#16222F]' : 'border-zinc-200 dark:border-[#2B2B30] bg-zinc-50/50 dark:bg-[#19191C]'"
                            >
                                <input id="reset_mode_default" type="radio" name="reset_mode" value="default" x-model="resetMode" class="mt-0.5 text-[#0073B1] focus:ring-[#0073B1]" />
                                <div class="text-xs">
                                    <span class="font-semibold text-zinc-900 dark:text-[#EDEDF0] block">Gunakan Password Default (Acak Sementara)</span>
                                    <span class="text-zinc-600 dark:text-[#9496A1] block mt-0.5">Sistem membuat password acak 12 karakter, menampilkan sekali, dan mengekspor CSV kredensial.</span>
                                </div>
                            </label>

                            @if(in_array((int) auth()->user()->role_id, [1, 5], true))
                                <label
                                    for="reset_mode_custom"
                                    class="flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition"
                                    :class="resetMode === 'custom' ? 'border-[#0073B1] bg-sky-50/40 dark:bg-[#16222F]' : 'border-zinc-200 dark:border-[#2B2B30] bg-zinc-50/50 dark:bg-[#19191C]'"
                                >
                                    <input id="reset_mode_custom" type="radio" name="reset_mode" value="custom" x-model="resetMode" class="mt-0.5 text-[#0073B1] focus:ring-[#0073B1]" />
                                    <div class="text-xs">
                                        <span class="font-semibold text-zinc-900 dark:text-[#EDEDF0] block">Masukkan Password Baru Sendiri</span>
                                        <span class="text-zinc-600 dark:text-[#9496A1] block mt-0.5">Tetapkan password awal secara manual (minimal 6 karakter). Pengguna wajib ganti saat login.</span>
                                    </div>
                                </label>
                            @endif
                        </div>

                        @if(in_array((int) auth()->user()->role_id, [1, 5], true))
                            <div x-show="resetMode === 'custom'" x-cloak>
                                <x-input-label for="reset_custom_password" value="Password Baru" />
                                <x-password-input id="reset_custom_password" name="password" x-model="resetPassword" x-bind:required="resetMode === 'custom'" autocomplete="new-password" placeholder="Minimal 6 karakter…" />
                            </div>
                        @endif

                        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-[#2B2B30]">
                            <button type="button" @click="closeModals()" class="px-4 py-2 min-h-[44px] sm:min-h-0 text-xs font-semibold text-zinc-600 dark:text-[#9496A1] hover:text-zinc-900 dark:hover:text-[#EDEDF0] transition cursor-pointer">Batal</button>
                            <x-primary-button>Reset Password</x-primary-button>
                        </div>
                    </form>
        </dialog>

    </div>
</x-app-layout>
