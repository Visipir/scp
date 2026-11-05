<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ $originUrl }}" class="p-2 -ml-2 rounded-lg text-zinc-400 hover:text-zinc-900 hover:bg-[#26262A] transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            <div>
                <div class="text-xs font-semibold text-zinc-500 dark:text-[#97979B] uppercase tracking-wider">
                    AI Importer Preview
                </div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 dark:text-[#EDEDF0] mt-0.5">
                    Preview Data {{ $type }}
                </h1>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <div class="bg-white border border-zinc-200/90 rounded-xl overflow-hidden shadow-sm">
            <div class="p-5 sm:p-7 space-y-6">
                <p class="text-sm text-zinc-600">
                    Berikut adalah data yang berhasil diekstrak oleh Google Gemini AI. Silakan periksa kembali sebelum menyimpan ke dalam database.
                </p>

                <div class="overflow-x-auto rounded-lg border border-zinc-200">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm">
                        <thead class="bg-zinc-50">
                            <tr>
                                @if(!empty($data) && count($data) > 0)
                                    @foreach(array_keys($data[0]) as $key)
                                        <th class="px-4 py-3 text-left text-xs font-bold text-zinc-700 uppercase tracking-wider">{{ str_replace('_', ' ', $key) }}</th>
                                    @endforeach
                                @endif
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-zinc-200">
                            @forelse($data as $row)
                                <tr class="hover:bg-[#232327] transition">
                                    @foreach($row as $value)
                                        <td class="px-4 py-3 whitespace-nowrap text-zinc-600">{{ is_array($value) ? json_encode($value) : (string)$value }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="100%" class="px-4 py-8 text-center text-zinc-500">Data kosong atau gagal di-parsing.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ $originUrl }}" class="px-4 py-2 text-xs font-semibold text-zinc-600 hover:text-zinc-900 hover:bg-[#26262A] rounded-lg transition">
                        Batal
                    </a>
                    <form action="{{ route('ai-importer.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="cache_key" value="{{ $cacheKey }}">
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 bg-zinc-900 hover:bg-black text-white text-xs font-bold uppercase tracking-wider rounded-lg transition shadow-xs">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            Simpan ke Database
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
