<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="text-xs font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider">Kesiswaan</div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0] mt-0.5">
                    Pengajuan Izin / Sakit Siswa
                </h1>
            </div>
            <a href="{{ route('leave-requests.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-[#1E1E22] border border-zinc-300 dark:border-[#2C2C30] text-xs font-semibold uppercase tracking-wider text-zinc-700 dark:text-[#EDEDF0] hover:text-zinc-900 dark:hover:text-white rounded-md hover:bg-zinc-50 dark:hover:bg-[#26262A] transition">
                &larr; Batal &amp; Kembali
            </a>
        </div>
    </x-slot>

    <div class="max-w-2xl">
        <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200/80 dark:border-[#2C2C30] rounded-lg p-6 sm:p-8 space-y-6">
            <div class="border-b border-zinc-200/80 dark:border-[#2C2C30] pb-4">
                <h2 class="text-base font-bold text-zinc-900 dark:text-[#EDEDF0] tracking-tight">
                    Formulir Permohonan Ketidakhadiran
                </h2>
                <p class="text-xs text-zinc-500 dark:text-[#97979B] mt-0.5">
                    Cantumkan rentang tanggal izin beserta alasan lengkap dan lampiran surat dokter jika ada.
                </p>
            </div>

            <form method="POST" action="{{ route('leave-requests.store') }}" enctype="multipart/form-data" class="space-y-5" x-data="{ docName: '', docError: '' }">
                @csrf

                {{-- Student Selection: show selector if parent has children, otherwise hidden self --}}
                @if(isset($children) && $children->count() > 0)
                    <div>
                        <x-input-label for="student_id" value="Pilih Siswa" />
                        <select
                            id="student_id"
                            name="student_id"
                            required
                            class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-2 text-sm text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none transition"
                        >
                            @if(auth()->user()->class_id)
                                <option value="{{ auth()->id() }}" @selected(old('student_id') == auth()->id())>
                                    {{ auth()->user()->fullname }} (Diri Sendiri)
                                </option>
                            @endif
                            @foreach($children as $child)
                                <option value="{{ $child->id }}" @selected(old('student_id') == $child->id)>
                                    {{ $child->fullname }} &mdash; {{ $child->schoolClass->class_name ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('student_id')" class="mt-1.5" />
                    </div>
                @else
                    <input type="hidden" name="student_id" value="{{ auth()->id() }}">
                @endif

                {{-- Dates Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="start_date" value="Tanggal Mulai" />
                        <input
                            id="start_date"
                            name="start_date"
                            type="date"
                            value="{{ old('start_date', now()->format('Y-m-d')) }}"
                            required
                            class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-2 text-sm font-mono text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none transition"
                        />
                        <x-input-error :messages="$errors->get('start_date')" class="mt-1.5" />
                    </div>

                    <div>
                        <x-input-label for="end_date" value="Tanggal Selesai" />
                        <input
                            id="end_date"
                            name="end_date"
                            type="date"
                            value="{{ old('end_date', now()->format('Y-m-d')) }}"
                            required
                            class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] px-3 py-2 text-sm font-mono text-zinc-900 dark:text-[#EDEDF0] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none transition"
                        />
                        <x-input-error :messages="$errors->get('end_date')" class="mt-1.5" />
                    </div>
                </div>

                {{-- Reason Textarea --}}
                <div>
                    <x-input-label for="reason" value="Alasan Izin / Sakit" />
                    <textarea
                        id="reason"
                        name="reason"
                        rows="4"
                        required
                        placeholder="Tuliskan keterangan lengkap alasan ketidakhadiran siswa..."
                        class="w-full rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] p-3 text-sm text-zinc-900 dark:text-[#EDEDF0] placeholder:text-zinc-400 dark:placeholder:text-[#5B5B61] focus:border-[#0093dd] focus:ring-1 focus:ring-[#0093dd] shadow-none transition resize-y"
                    >{{ old('reason') }}</textarea>
                    <x-input-error :messages="$errors->get('reason')" class="mt-1.5" />
                </div>

                {{-- Document Upload Dropzone --}}
                <div>
                    <x-input-label for="document_path" value="Unggah Dokumen Bukti / Surat Dokter (Opsional)" />

                    <div class="relative">
                        <label for="document_path" class="group flex flex-col items-center justify-center w-full min-h-[110px] rounded-md border border-dashed border-zinc-300 dark:border-[#2B2B30] bg-zinc-50/50 dark:bg-[#19191C] hover:bg-zinc-100/50 dark:hover:bg-[#26262A] hover:border-zinc-400 dark:hover:border-[#3A3A3F] transition cursor-pointer p-4 text-center">
                            
                            <div class="flex flex-col items-center">
                                <svg class="w-6 h-6 text-zinc-400 dark:text-[#5B5B61] mb-2" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                <p class="text-xs font-semibold text-zinc-700 dark:text-[#EDEDF0]" x-text="docName || 'Pilih atau drop file surat dokter / izin'"></p>
                                <p class="mt-1 text-[11px] font-mono text-zinc-400 dark:text-[#5B5B61]">Format: PDF, JPG, PNG (Maks. 2MB)</p>
                            </div>

                            <input
                                id="document_path"
                                name="document"
                                type="file"
                                accept=".pdf,.jpg,.jpeg,.png"
                                class="sr-only"
                                @change="
                                    const file = $event.target.files[0];
                                    docError = '';
                                    if (file) {
                                        if (file.size > 2 * 1024 * 1024) {
                                            docError = 'Ukuran file terlalu besar! Maksimal 2MB.';
                                            $event.target.value = '';
                                            docName = '';
                                        } else {
                                            docName = file.name;
                                        }
                                    }
                                "
                            />
                        </label>
                    </div>
                    <p x-show="docError" x-text="docError" class="text-xs text-rose-600 mt-1 font-medium"></p>
                    <x-input-error :messages="$errors->get('document')" class="mt-1.5" />
                </div>

                {{-- Submit Button --}}
                <div class="pt-3">
                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 bg-zinc-900 hover:bg-black dark:bg-[#0093dd] dark:hover:bg-[#0082c4] text-white font-semibold text-xs uppercase tracking-wider rounded-md transition shadow-none focus:outline-none focus:ring-2 focus:ring-[#0093dd] focus:ring-offset-2">
                        Kirim Permohonan Izin
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
