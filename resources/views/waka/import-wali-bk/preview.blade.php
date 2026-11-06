<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-lg text-zinc-900 dark:text-[#EDEDF0] leading-tight">
                    Preview Penugasan Wali Kelas &amp; Guru BK
                </h2>
                <p class="text-xs text-zinc-500 dark:text-[#97979B]">
                    Verifikasi pemetaan kelas dan guru sebelum disimpan ke database.
                </p>
            </div>
            <a href="{{ route('waka.import-wali-bk.index') }}" class="px-3 py-1.5 text-xs text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-white bg-zinc-100 dark:bg-[#2C2C30] rounded-lg">
                &larr; Batalkan
            </a>
        </div>
    </x-slot>

    <div class="py-6 space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
            <div class="p-3 bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2C2C30] rounded-xl text-center">
                <span class="text-[10px] uppercase font-mono text-zinc-500 dark:text-[#97979B]">Total Baris</span>
                <p class="text-lg font-bold text-zinc-900 dark:text-[#EDEDF0]">{{ $preview['summary']['total_rows'] }}</p>
            </div>
            <div class="p-3 bg-white dark:bg-[#1E1E22] border border-emerald-500/30 rounded-xl text-center">
                <span class="text-[10px] uppercase font-mono text-emerald-400">Kelas Cocok</span>
                <p class="text-lg font-bold text-emerald-400">{{ $preview['summary']['classes_matched'] }}</p>
            </div>
            <div class="p-3 bg-white dark:bg-[#1E1E22] border border-rose-500/30 rounded-xl text-center">
                <span class="text-[10px] uppercase font-mono text-rose-400">Kelas Luput</span>
                <p class="text-lg font-bold text-rose-400">{{ $preview['summary']['classes_unmatched'] }}</p>
            </div>
            <div class="p-3 bg-white dark:bg-[#1E1E22] border border-emerald-500/30 rounded-xl text-center">
                <span class="text-[10px] uppercase font-mono text-emerald-400">Wali Cocok</span>
                <p class="text-lg font-bold text-emerald-400">{{ $preview['summary']['wali_matched'] }}</p>
            </div>
            <div class="p-3 bg-white dark:bg-[#1E1E22] border border-amber-500/30 rounded-xl text-center">
                <span class="text-[10px] uppercase font-mono text-amber-400">Wali Fuzzy</span>
                <p class="text-lg font-bold text-amber-400">{{ $preview['summary']['wali_fuzzy'] }}</p>
            </div>
            <div class="p-3 bg-white dark:bg-[#1E1E22] border border-emerald-500/30 rounded-xl text-center">
                <span class="text-[10px] uppercase font-mono text-emerald-400">BK Cocok</span>
                <p class="text-lg font-bold text-emerald-400">{{ $preview['summary']['bk_matched'] }}</p>
            </div>
            <div class="p-3 bg-white dark:bg-[#1E1E22] border border-amber-500/30 rounded-xl text-center">
                <span class="text-[10px] uppercase font-mono text-amber-400">BK Fuzzy</span>
                <p class="text-lg font-bold text-amber-400">{{ $preview['summary']['bk_fuzzy'] }}</p>
            </div>
        </div>

        <form action="{{ route('waka.import-wali-bk.commit') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $preview['token'] }}">

            <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2C2C30] rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-zinc-50 dark:bg-[#252529] border-b border-zinc-200 dark:border-[#2C2C30] text-[10px] uppercase tracking-wider font-mono text-zinc-500 dark:text-[#97979B]">
                                <th class="py-2.5 px-3">No</th>
                                <th class="py-2.5 px-3">Kelas (Excel)</th>
                                <th class="py-2.5 px-3">Kelas Database</th>
                                <th class="py-2.5 px-3">Wali Kelas (Excel)</th>
                                <th class="py-2.5 px-3">Pencocokan Wali</th>
                                <th class="py-2.5 px-3">Guru BK (Excel)</th>
                                <th class="py-2.5 px-3">Pencocokan Guru BK</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-[#2C2C30]">
                            @foreach($preview['rows'] as $idx => $r)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-[#26262B]">
                                    <td class="py-2 px-3 font-mono text-zinc-400">{{ $r['no'] ?: ($idx + 1) }}</td>
                                    <td class="py-2 px-3 font-semibold text-zinc-900 dark:text-[#EDEDF0]">{{ $r['class_raw'] }}</td>
                                    <td class="py-2 px-3">
                                        @if($r['class'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                                {{ $r['class']['class_name'] }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                                TIDAK DITEMUKAN
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-3 text-zinc-700 dark:text-zinc-300">{{ $r['wali_raw'] ?: '-' }}</td>
                                    <td class="py-2 px-3">
                                        @if($r['wali_status'] === 'matched' && $r['wali_user'])
                                            <span class="text-emerald-400 font-medium">{{ $r['wali_user']['fullname'] }}</span>
                                            <span class="font-mono text-zinc-400 text-[10px]">({{ $r['wali_user']['username'] }})</span>
                                        @elseif($r['wali_status'] === 'fuzzy' || $r['wali_status'] === 'ambiguous')
                                            <select name="confirmed_wali[{{ $idx }}]" class="text-xs bg-zinc-50 dark:bg-[#252529] border border-zinc-300 dark:border-[#3A3A3F] rounded px-2 py-1 text-zinc-800 dark:text-[#EDEDF0]">
                                                <option value="" {{ $r['wali_status'] === 'ambiguous' ? 'selected' : '' }}>-- {{ $r['wali_status'] === 'ambiguous' ? 'Pilih Kandidat (Wajib Konfirmasi)' : 'Lewati / Belum Ada' }} --</option>
                                                @foreach($r['wali_cands'] as $cand)
                                                    <option value="{{ $cand['user']['id'] }}" {{ $r['wali_status'] === 'fuzzy' ? 'selected' : '' }}>
                                                        {{ $cand['user']['fullname'] }} [NIP: ...{{ $cand['nip_last4'] ?? '-' }}] ({{ $cand['subject'] ?? 'Guru' }}) - {{ $cand['score'] }}%
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <span class="text-zinc-400 italic">Tidak ditemukan</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-3 text-zinc-700 dark:text-zinc-300">{{ $r['bk_raw'] ?: '-' }}</td>
                                    <td class="py-2 px-3">
                                        @if($r['bk_status'] === 'matched' && $r['bk_user'])
                                            <span class="text-emerald-400 font-medium">{{ $r['bk_user']['fullname'] }}</span>
                                            <span class="font-mono text-zinc-400 text-[10px]">({{ $r['bk_user']['username'] }})</span>
                                        @elseif($r['bk_status'] === 'fuzzy' || $r['bk_status'] === 'ambiguous')
                                            <select name="confirmed_bk[{{ $idx }}]" class="text-xs bg-zinc-50 dark:bg-[#252529] border border-zinc-300 dark:border-[#3A3A3F] rounded px-2 py-1 text-zinc-800 dark:text-[#EDEDF0]">
                                                <option value="" {{ $r['bk_status'] === 'ambiguous' ? 'selected' : '' }}>-- {{ $r['bk_status'] === 'ambiguous' ? 'Pilih Kandidat (Wajib Konfirmasi)' : 'Lewati / Belum Ada' }} --</option>
                                                @foreach($r['bk_cands'] as $cand)
                                                    <option value="{{ $cand['user']['id'] }}" {{ $r['bk_status'] === 'fuzzy' ? 'selected' : '' }}>
                                                        {{ $cand['user']['fullname'] }} [NIP: ...{{ $cand['nip_last4'] ?? '-' }}] ({{ $cand['subject'] ?? 'Guru' }}) - {{ $cand['score'] }}%
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <span class="text-zinc-400 italic">Tidak ditemukan</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-zinc-50 dark:bg-[#252529] border-t border-zinc-200 dark:border-[#2C2C30] flex items-center justify-between">
                    <p class="text-xs text-zinc-500 dark:text-[#97979B]">
                        Konfirmasi ini akan menetapkan penugasan Wali Kelas dan Guru BK ke tabel akademik.
                    </p>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('waka.import-wali-bk.index') }}" class="px-4 py-2 text-xs text-zinc-600 dark:text-[#97979B] hover:text-white bg-zinc-200 dark:bg-[#323237] rounded-lg">
                            Batal
                        </a>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 rounded-lg shadow-sm transition-colors">
                            Simpan &amp; Terapkan Penugasan
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
