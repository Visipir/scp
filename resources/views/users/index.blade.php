<x-app-layout>
    <x-slot name="header">
        <x-page-header 
            title="Manajemen Pengguna" 
            subtitle="Pengelolaan akun guru, staf, siswa, dan hak akses sistem"
        >
            <div class="flex flex-wrap items-center gap-2">
                @if(auth()->user()?->role_id == 1)
                    <form method="POST" action="{{ route('users.bulk-reset-passwords') }}" class="inline" onsubmit="return confirm('Reset password massal untuk semua akun Guru menjadi password sementara acak? Semua sesi/token aktif guru akan dicabut.')">
                        @csrf
                        <button 
                            type="submit" 
                            class="inline-flex items-center gap-2 px-3.5 py-2 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded-lg transition shadow-none shrink-0 cursor-pointer"
                        >
                            <span>Reset PW Massal Guru</span>
                        </button>
                    </form>
                @endif
                <button 
                    type="button" 
                    @click="addModalOpen = true" 
                    class="inline-flex items-center gap-2 px-4 py-2 bg-[#0093dd] hover:bg-[#0082c4] active:bg-[#006fa7] text-white text-xs font-semibold rounded-lg transition shadow-none shrink-0 cursor-pointer"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    <span>Tambah Pengguna</span>
                </button>
            </div>
        </x-page-header>
    </x-slot>

    <div class="space-y-6" x-data="{ 
        addModalOpen: false, 
        editModalOpen: false, 
        editUser: { id: null, fullname: '', username: '', email: '', role_id: '', class_id: '' }
    }">

        {{-- Alerts --}}
        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if(session('reset_credentials'))
            <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/60 rounded-xl p-4 text-xs space-y-2">
                <div class="font-semibold text-amber-900 dark:text-amber-300">
                    Kredensial Sementara (Tampil Sekali — Tersimpan di <code>storage/app/private/{{ session('reset_csv_path') }}</code>)
                </div>
                <div class="max-h-48 overflow-y-auto border border-amber-200/70 dark:border-amber-800/50 rounded-lg bg-white dark:bg-[#1E1E22] p-2.5 font-mono text-[11px]">
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
            <x-alert-banner type="danger">Ada kesalahan pada data yang diinput.</x-alert-banner>
        @endif

        {{-- Top Control Bar --}}
        <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-4 sm:p-5">
            <form method="GET" action="{{ route('users.index') }}" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 w-full">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full">
                    <label for="role_id_filter" class="text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-[#97979B] shrink-0">
                        Filter:
                    </label>
                    
                    <select 
                        id="role_id_filter" 
                        name="role_id" 
                        onchange="this.form.submit()" 
                        class="w-full sm:w-auto rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2 text-xs font-medium text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] shadow-none transition cursor-pointer"
                    >
                        <option value="">Semua Peran</option>
                        @foreach($roles as $id => $name)
                            <option value="{{ $id }}" @selected($roleFilter == $id)>{{ $name }}</option>
                        @endforeach
                    </select>

                    <div class="relative w-full sm:w-64">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ $search }}" 
                            placeholder="Cari nama, username, email..." 
                            class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] shadow-none transition"
                        >
                    </div>
                    
                    <button 
                        type="submit" 
                        class="px-3.5 py-2 bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-200 text-xs font-semibold rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-700 transition"
                    >
                        Cari
                    </button>

                    @if(!empty($roleFilter) || !empty($search))
                        <a href="{{ route('users.index') }}" class="px-2.5 py-2 text-xs text-zinc-500 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-white underline">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        {{-- Tabel Data Pengguna --}}
        <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl overflow-hidden shadow-xs">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-[#2B2B30] text-left">
                    <thead class="bg-zinc-50 dark:bg-[#232327] text-zinc-500 dark:text-[#97979B] text-xs font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="px-5 py-3">Nama Lengkap</th>
                            <th class="px-5 py-3">Username</th>
                            <th class="px-5 py-3">Peran (Role)</th>
                            <th class="px-5 py-3">Kelas</th>
                            <th class="px-5 py-3 text-right">Tindakan</th>
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
                                        {{ $user->role_id == 1 ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-400' : '' }}
                                        {{ $user->role_id == 2 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400' : '' }}
                                        {{ $user->role_id == 3 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400' : '' }}
                                        {{ $user->role_id == 4 ? 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' : '' }}
                                    ">
                                        {{ $roles[$user->role_id] ?? 'Unknown' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5">
                                    @if(in_array($user->role_id, [3, 4]) && $user->schoolClass)
                                        <span class="inline-block px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 font-mono text-[11px] font-semibold">
                                            {{ $user->schoolClass->class_name }}
                                        </span>
                                    @else
                                        <span class="text-zinc-400 dark:text-[#97979B]">-</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 text-right font-medium">
                                    <div class="inline-flex items-center gap-3">
                                        <button 
                                            type="button" 
                                            @click="editUser = { id: {{ $user->id }}, fullname: '{{ addslashes($user->fullname) }}', username: '{{ addslashes($user->username) }}', email: '{{ addslashes($user->email ?? '') }}', role_id: '{{ $user->role_id }}', class_id: '{{ $user->class_id ?? '' }}' }; editModalOpen = true"
                                            class="text-zinc-900 dark:text-[#EDEDF0] hover:text-[#0093dd] dark:hover:text-[#0093dd] font-semibold transition"
                                        >
                                            Edit
                                        </button>
                                        
                                        <form method="POST" action="{{ route('users.reset-password', $user) }}" class="inline" onsubmit="return confirm('Reset password ke username untuk {{ $user->fullname }}?')">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold transition">
                                                Reset PW
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline" onsubmit="return confirm('Yakin ingin menghapus {{ $user->fullname }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 dark:text-rose-400 hover:underline font-semibold transition" {{ auth()->id() === $user->id ? 'disabled' : '' }}>
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-10 text-center text-xs text-zinc-400 dark:text-[#97979B]">
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
        <div 
            x-show="addModalOpen" 
            x-cloak 
            style="display: none;"
            class="fixed inset-0 z-50 overflow-y-auto"
        >
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div 
                    x-show="addModalOpen"
                    x-transition.opacity
                    class="fixed inset-0 bg-zinc-900/60 backdrop-blur-xs" 
                    @click="addModalOpen = false"
                ></div>

                <div 
                    x-show="addModalOpen"
                    x-transition
                    class="relative bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg w-full p-6 sm:p-8"
                >
                    <div class="border-b border-zinc-200 dark:border-[#2B2B30] pb-4 mb-5">
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">Tambah Pengguna Baru</h3>
                    </div>

                    <form method="POST" action="{{ route('users.store') }}" class="space-y-4" x-data="{ role: '4' }">
                        @csrf
                        <div>
                            <x-input-label for="add_fullname" value="Nama Lengkap" />
                            <x-text-input id="add_fullname" name="fullname" type="text" required />
                        </div>
                        <div>
                            <x-input-label for="add_username" value="Username (Unik)" />
                            <x-text-input id="add_username" name="username" type="text" required />
                        </div>
                        <div>
                            <x-input-label for="add_email" value="Alamat Email (Opsional)" />
                            <x-text-input id="add_email" name="email" type="email" placeholder="nama@example.test" />
                        </div>
                        <div>
                            <x-input-label for="add_password" value="Password Awal" />
                            <x-text-input id="add_password" name="password" type="password" required />
                        </div>
                        <div>
                            <x-input-label for="add_role_id" value="Peran (Role)" />
                            <select id="add_role_id" name="role_id" x-model="role" required class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] shadow-none transition">
                                @foreach($roles as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="role == '4' || role == '3'">
                            <label for="add_class_id" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5" x-text="role == '3' ? 'Kelas Perwalian (kosongkan untuk Guru BK)' : 'Kelas (Khusus Siswa)'"></label>
                            <select id="add_class_id" name="class_id" :required="role == '4'" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] shadow-none transition">
                                <option value="">-- Pilih Kelas --</option>
                                @php $allClasses = \App\Models\SchoolClass::orderBy('class_name')->get(); @endphp
                                @foreach($allClasses as $c)
                                    <option value="{{ $c->id }}">{{ $c->class_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-[#2B2B30]">
                            <button type="button" @click="addModalOpen = false" class="px-4 py-2 text-xs font-semibold text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] transition">Batal</button>
                            <x-primary-button>Simpan</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL: Edit Pengguna --}}
        <div 
            x-show="editModalOpen" 
            x-cloak 
            style="display: none;"
            class="fixed inset-0 z-50 overflow-y-auto"
        >
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div 
                    x-show="editModalOpen"
                    x-transition.opacity
                    class="fixed inset-0 bg-zinc-900/60 backdrop-blur-xs" 
                    @click="editModalOpen = false"
                ></div>

                <div 
                    x-show="editModalOpen"
                    x-transition
                    class="relative bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg w-full p-6 sm:p-8"
                >
                    <div class="border-b border-zinc-200 dark:border-[#2B2B30] pb-4 mb-5">
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">Edit Pengguna</h3>
                    </div>

                    <form :action="'{{ url('users') }}/' + editUser.id" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <x-input-label for="edit_fullname" value="Nama Lengkap" />
                            <x-text-input id="edit_fullname" name="fullname" type="text" x-model="editUser.fullname" required />
                        </div>
                        <div>
                            <x-input-label for="edit_username" value="Username (Unik)" />
                            <x-text-input id="edit_username" name="username" type="text" x-model="editUser.username" required />
                        </div>
                        <div>
                            <x-input-label for="edit_email" value="Alamat Email (Opsional)" />
                            <x-text-input id="edit_email" name="email" type="email" x-model="editUser.email" placeholder="nama@example.test" />
                        </div>
                        <div>
                            <x-input-label for="edit_role_id" value="Peran (Role)" />
                            <select id="edit_role_id" name="role_id" x-model="editUser.role_id" required class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] shadow-none transition">
                                @foreach($roles as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="editUser.role_id == '4' || editUser.role_id == '3'">
                            <label for="edit_class_id" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5" x-text="editUser.role_id == '3' ? 'Kelas Perwalian (kosongkan untuk Guru BK)' : 'Kelas (Khusus Siswa)'"></label>
                            <select id="edit_class_id" name="class_id" x-model="editUser.class_id" :required="editUser.role_id == '4'" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#26262A] px-3.5 py-2.5 text-xs text-zinc-900 dark:text-[#EDEDF0] focus:ring-1 focus:ring-[#0093dd] focus:border-[#0093dd] shadow-none transition">
                                <option value="">-- Pilih Kelas --</option>
                                @foreach($allClasses as $c)
                                    <option value="{{ $c->id }}">{{ $c->class_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100 dark:border-[#2B2B30]">
                            <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-xs font-semibold text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] transition">Batal</button>
                            <x-primary-button>Perbarui</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
