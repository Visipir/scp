<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="text-[10px] font-bold font-mono text-zinc-400 uppercase tracking-widest">Master Data</div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 mt-0.5">
                    Manajemen Pengguna
                </h1>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-zinc-100 text-zinc-800 border border-zinc-200 text-xs font-mono font-semibold">
                    <span class="w-1.5 h-1.5 rounded-full bg-zinc-600"></span> Akses: Waka &amp; BK
                </span>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6" x-data="{ 
        addModalOpen: false, 
        editModalOpen: false, 
        editUser: { id: null, fullname: '', username: '', role_id: '', class_id: '' }
    }">

        {{-- Alerts --}}
        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif
        @if(session('error'))
            <x-alert-banner type="danger">{{ session('error') }}</x-alert-banner>
        @endif
        @if($errors->any())
            <x-alert-banner type="danger">Ada kesalahan pada data yang diinput.</x-alert-banner>
        @endif

        {{-- Top Control Bar --}}
        <div class="bg-white border border-zinc-200/80 rounded-lg p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <form method="GET" action="{{ route('users.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-3 w-full md:w-auto">
                <label for="role_id_filter" class="text-xs font-bold font-mono uppercase tracking-wider text-zinc-500 shrink-0">
                    Filter &amp; Cari:
                </label>
                
                <div class="flex flex-col sm:flex-row items-center gap-2 w-full md:w-auto">
                    <select 
                        id="role_id_filter" 
                        name="role_id" 
                        onchange="this.form.submit()" 
                        class="w-full sm:w-auto rounded-md border-zinc-300 bg-white px-3 py-2 text-xs font-semibold text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition cursor-pointer"
                    >
                        <option value="">-- Semua Peran --</option>
                        @foreach($roles as $id => $name)
                            <option value="{{ $id }}" @selected($roleFilter == $id)>{{ $name }}</option>
                        @endforeach
                    </select>

                    <div class="relative w-full sm:w-48">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama/username..." class="w-full rounded-md border-zinc-300 bg-white px-3 py-2 text-xs text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition">
                    </div>
                    
                    <button type="submit" class="w-full sm:w-auto px-3 py-2 bg-zinc-100 border border-zinc-300 text-zinc-700 text-xs font-semibold rounded hover:bg-[#26262A] transition">Cari</button>

                    @if(!empty($roleFilter) || !empty($search))
                        <a href="{{ route('users.index') }}" class="px-2.5 py-2 text-xs font-mono text-zinc-500 hover:text-zinc-900 underline" title="Reset filter">Reset</a>
                    @endif
                </div>
            </form>

            <button 
                type="button" 
                @click="addModalOpen = true" 
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-zinc-900 hover:bg-black text-white text-xs font-semibold uppercase tracking-wider rounded-md transition shadow-none shrink-0 w-full md:w-auto"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Tambah Pengguna
            </button>
        </div>

        {{-- Tabel Data Pengguna --}}
        <div class="bg-white border border-zinc-200/80 rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200/80 text-left">
                <thead class="bg-zinc-50 font-mono text-[10px] text-zinc-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3 font-bold">Nama Lengkap</th>
                        <th class="px-5 py-3 font-bold">Username</th>
                        <th class="px-5 py-3 font-bold">Peran (Role)</th>
                        <th class="px-5 py-3 font-bold">Kelas</th>
                        <th class="px-5 py-3 text-right font-bold">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 text-xs">
                    @forelse($users as $user)
                        <tr class="hover:bg-[#232327] transition-colors">
                            <td class="px-5 py-3.5 font-bold text-zinc-900">
                                {{ $user->fullname }}
                            </td>
                            <td class="px-5 py-3.5 font-mono text-zinc-500">
                                {{ $user->username }}
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider 
                                    {{ $user->role_id == 1 ? 'bg-indigo-100 text-indigo-800' : '' }}
                                    {{ $user->role_id == 2 ? 'bg-emerald-100 text-emerald-800' : '' }}
                                    {{ $user->role_id == 3 ? 'bg-amber-100 text-amber-800' : '' }}
                                    {{ $user->role_id == 4 ? 'bg-zinc-100 text-zinc-600' : '' }}
                                ">
                                    {{ $roles[$user->role_id] ?? 'Unknown' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                @if(in_array($user->role_id, [3, 4]) && $user->schoolClass)
                                    <span class="inline-block px-2 py-0.5 rounded bg-zinc-200/70 text-zinc-800 font-mono text-[11px] font-bold">
                                        {{ $user->schoolClass->class_name }}
                                    </span>
                                @else
                                    <span class="text-zinc-300">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono text-[11px]">
                                <div class="inline-flex items-center gap-3">
                                    <button 
                                        type="button" 
                                        @click="editUser = { id: {{ $user->id }}, fullname: '{{ addslashes($user->fullname) }}', username: '{{ addslashes($user->username) }}', role_id: '{{ $user->role_id }}', class_id: '{{ $user->class_id ?? '' }}' }; editModalOpen = true"
                                        class="text-zinc-900 hover:underline font-semibold"
                                    >
                                        Edit
                                    </button>
                                    
                                    <form method="POST" action="{{ route('users.reset-password', $user) }}" class="inline" onsubmit="return confirm('Reset password ke username untuk {{ $user->fullname }}?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-indigo-600 hover:underline font-semibold">
                                            Reset PW
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline" onsubmit="return confirm('Yakin ingin menghapus {{ $user->fullname }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-600 hover:underline font-semibold" {{ auth()->id() === $user->id ? 'disabled' : '' }}>
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center font-mono text-xs text-zinc-400">
                                Tidak ada data pengguna yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="pt-2 font-mono text-xs">
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
                    class="relative bg-white border border-zinc-200 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg w-full p-6 sm:p-8"
                >
                    <div class="border-b border-zinc-200/80 pb-4 mb-5">
                        <h3 class="text-base font-bold text-zinc-900 tracking-tight">Tambah Pengguna Baru</h3>
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
                            <x-input-label for="add_password" value="Password Awal" />
                            <x-text-input id="add_password" name="password" type="password" required />
                        </div>
                        <div>
                            <x-input-label for="add_role_id" value="Peran (Role)" />
                            <select id="add_role_id" name="role_id" x-model="role" required class="w-full rounded-md border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition">
                                @foreach($roles as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="role == '4' || role == '3'">
                            <label for="add_class_id" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5" x-text="role == '3' ? 'Kelas Perwalian (kosongkan untuk Guru BK)' : 'Kelas (Khusus Siswa)'"></label>
                            <select id="add_class_id" name="class_id" :required="role == '4'" class="w-full rounded-md border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition">
                                <option value="">-- Pilih Kelas --</option>
                                @php $allClasses = \App\Models\SchoolClass::orderBy('class_name')->get(); @endphp
                                @foreach($allClasses as $c)
                                    <option value="{{ $c->id }}">{{ $c->class_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100">
                            <button type="button" @click="addModalOpen = false" class="px-4 py-2 text-xs font-semibold uppercase text-zinc-600 hover:text-zinc-900 transition">Batal</button>
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
                    class="relative bg-white border border-zinc-200 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-lg w-full p-6 sm:p-8"
                >
                    <div class="border-b border-zinc-200/80 pb-4 mb-5">
                        <h3 class="text-base font-bold text-zinc-900 tracking-tight">Edit Pengguna</h3>
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
                            <x-input-label for="edit_role_id" value="Peran (Role)" />
                            <select id="edit_role_id" name="role_id" x-model="editUser.role_id" required class="w-full rounded-md border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition">
                                @foreach($roles as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div x-show="editUser.role_id == '4' || editUser.role_id == '3'">
                            <label for="edit_class_id" class="block text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5" x-text="editUser.role_id == '3' ? 'Kelas Perwalian (kosongkan untuk Guru BK)' : 'Kelas (Khusus Siswa)'"></label>
                            <select id="edit_class_id" name="class_id" x-model="editUser.class_id" :required="editUser.role_id == '4'" class="w-full rounded-md border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-900 focus:ring-1 focus:ring-zinc-900 shadow-none transition">
                                <option value="">-- Pilih Kelas --</option>
                                @foreach($allClasses as $c)
                                    <option value="{{ $c->id }}">{{ $c->class_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t border-zinc-100">
                            <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-xs font-semibold uppercase text-zinc-600 hover:text-zinc-900 transition">Batal</button>
                            <x-primary-button>Perbarui</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
