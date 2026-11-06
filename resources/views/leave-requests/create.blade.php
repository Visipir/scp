<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-900 dark:text-[#EDEDF0] leading-tight">Ajukan Permohonan Izin</h2>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2C2C30] shadow-sm sm:rounded-lg p-4 sm:p-6">
                <form method="POST" action="{{ route('leave-requests.store') }}" enctype="multipart/form-data" class="space-y-4" x-data="{ fileName: '', fileError: '' }">
                    @csrf

                    <input type="hidden" name="student_id" value="{{ auth()->id() }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="start_date" value="Tanggal Mulai" />
                            <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date')" required />
                            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="end_date" value="Tanggal Selesai" />
                            <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" :value="old('end_date')" required />
                            <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="reason" value="Alasan Izin" />
                        <textarea id="reason" name="reason" rows="4" class="mt-1 block w-full border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] text-zinc-900 dark:text-[#EDEDF0] placeholder:text-zinc-400 dark:placeholder:text-[#5B5B61] focus:border-[#0093dd] focus:ring-[#0093dd] rounded-md shadow-sm" required placeholder="Jelaskan alasan permohonan izin...">{{ old('reason') }}</textarea>
                        <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                    </div>

                    {{-- Document Upload with Frontend Validation --}}
                    <div>
                        <x-input-label for="document" value="Dokumen Pendukung (Opsional, maks 2MB)" />
                        <div class="mt-1">
                            <label for="document" class="flex flex-col items-center justify-center w-full h-32 border-2 border-zinc-300 dark:border-[#2B2B30] border-dashed rounded-lg cursor-pointer bg-zinc-50 dark:bg-[#19191C] hover:bg-zinc-100 dark:hover:bg-[#26262A] transition">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-8 h-8 text-zinc-400 dark:text-[#5B5B61] mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                    <p class="text-sm text-zinc-500 dark:text-[#EDEDF0]" x-text="fileName || 'Klik untuk upload surat/dokumen'"></p>
                                    <p class="text-xs text-zinc-400 dark:text-[#5B5B61] mt-1">PDF, JPG, PNG</p>
                                </div>
                                <input id="document" name="document" type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png"
                                    @change="
                                        const file = $event.target.files[0];
                                        fileError = '';
                                        if (file) {
                                            if (file.size > 2 * 1024 * 1024) {
                                                fileError = 'Ukuran file maksimal 2MB.';
                                                $event.target.value = '';
                                                fileName = '';
                                            } else {
                                                fileName = file.name;
                                            }
                                        } else {
                                            fileName = '';
                                        }
                                    " />
                            </label>
                            <p class="text-xs text-red-600 mt-1" x-show="fileError" x-text="fileError"></p>
                            <x-input-error :messages="$errors->get('document')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center gap-4 pt-2">
                        <x-primary-button>Kirim Permohonan</x-primary-button>
                        <a href="{{ route('dashboard') }}" class="text-sm text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0]">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
