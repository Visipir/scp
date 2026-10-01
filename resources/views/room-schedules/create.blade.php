<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('room-schedules.index') }}" class="text-zinc-400 hover:text-indigo-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <h2 class="font-bold text-xl text-zinc-900 leading-tight">
                Tambah Jadwal Ruangan
            </h2>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <x-auth-session-status class="mb-4" :status="session('success')" />
            
            @if ($errors->any())
                <div class="mb-4 p-4 rounded-xl bg-red-50 border border-red-100">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Terdapat kesalahan pengisian:</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc pl-5 space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('room-schedules.store') }}" class="bg-white rounded-xl shadow-sm border border-zinc-200 overflow-hidden">
                @csrf
                <div class="p-6 space-y-6">
                    
                    <!-- Ruangan & Kelas -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="room_id" value="Ruangan / Lab *" />
                            <select id="room_id" name="room_id" class="mt-1 block w-full border-zinc-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">-- Pilih Ruangan --</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>{{ $room->room_name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('room_id')" class="mt-2" />
                        </div>
                        
                        <div>
                            <x-input-label for="class_id" value="Kelas *" />
                            <select id="class_id" name="class_id" class="mt-1 block w-full border-zinc-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="">-- Pilih Kelas --</option>
                                @foreach($classes as $c)
                                    <option value="{{ $c->id }}" {{ old('class_id') == $c->id ? 'selected' : '' }}>{{ $c->class_name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('class_id')" class="mt-2" />
                        </div>
                    </div>

                    <!-- Guru & Mapel -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="teacher_id" value="Guru (Opsional)" />
                            <select id="teacher_id" name="teacher_id" class="mt-1 block w-full border-zinc-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="">-- Pilih Guru --</option>
                                @foreach($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>{{ $teacher->fullname }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('teacher_id')" class="mt-2" />
                        </div>
                        
                        <div>
                            <x-input-label for="subject" value="Mata Pelajaran (Opsional)" />
                            <x-text-input id="subject" class="block mt-1 w-full" type="text" name="subject" :value="old('subject')" placeholder="Cth: Pemrograman Web" />
                            <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                        </div>
                    </div>

                    <hr class="border-zinc-100">

                    <!-- Waktu -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div>
                            <x-input-label for="day_of_week" value="Hari *" />
                            <select id="day_of_week" name="day_of_week" class="mt-1 block w-full border-zinc-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $day)
                                    <option value="{{ $day }}" {{ old('day_of_week') == $day ? 'selected' : '' }}>{{ $day }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('day_of_week')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="period" value="Jam Ke- *" />
                            <select id="period" name="period" class="mt-1 block w-full border-zinc-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}" {{ old('period') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                            <x-input-error :messages="$errors->get('period')" class="mt-2" />
                        </div>
                        
                        <div>
                            <x-input-label for="cycle_type" value="Putaran *" />
                            <select id="cycle_type" name="cycle_type" class="mt-1 block w-full border-zinc-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                <option value="Putaran 1" {{ old('cycle_type') == 'Putaran 1' ? 'selected' : '' }}>Putaran 1 (35m)</option>
                                <option value="Putaran 2" {{ (old('cycle_type') ?? 'Putaran 2') == 'Putaran 2' ? 'selected' : '' }}>Putaran 2 (40m/45m)</option>
                            </select>
                            <x-input-error :messages="$errors->get('cycle_type')" class="mt-2" />
                        </div>
                    </div>

                </div>
                
                <div class="bg-zinc-50 px-6 py-4 flex items-center justify-end">
                    <x-primary-button>
                        Simpan Jadwal
                    </x-primary-button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
