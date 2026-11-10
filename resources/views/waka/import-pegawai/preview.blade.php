<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-lg text-zinc-900 dark:text-[#EDEDF0] leading-tight">
                    Preview Import Data Pegawai
                </h2>
                <p class="text-xs text-zinc-500 dark:text-[#97979B]">
                    Verifikasi hasil pencocokan akun guru sebelum disimpan ke database.
                </p>
            </div>
            <a href="{{ route('waka.import-pegawai.index') }}" class="px-3 py-1.5 text-xs text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-white bg-zinc-100 dark:bg-[#2C2C30] rounded-lg">
                &larr; Batalkan
            </a>
        </div>
    </x-slot>

    <div class="py-6 space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="p-3 bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2C2C30] rounded-xl text-center">
                <span class="text-[10px] uppercase font-mono text-zinc-500 dark:text-[#97979B]">Total Baris</span>
                <p class="text-lg font-bold text-zinc-900 dark:text-[#EDEDF0]">{{ $preview['summary']['total_rows'] }}</p>
            </div>
            <div class="p-3 bg-white dark:bg-[#1E1E22] border border-emerald-500/30 rounded-xl text-center">
                <span class="text-[10px] uppercase font-mono text-emerald-400">Matched</span>
                <p class="text-lg font-bold text-emerald-400">{{ $preview['summary']['matched'] }}</p>
            </div>
            <div class="p-3 bg-white dark:bg-[#1E1E22] border border-amber-500/30 rounded-xl text-center">
                <span class="text-[10px] uppercase font-mono text-amber-400">Fuzzy (Konfirmasi)</span>
                <p class="text-lg font-bold text-amber-400">{{ $preview['summary']['fuzzy'] }}</p>
            </div>
            <div class="p-3 bg-white dark:bg-[#1E1E22] border border-sky-500/30 rounded-xl text-center">
                <span class="text-[10px] uppercase font-mono text-sky-400">New Account</span>
                <p class="text-lg font-bold text-sky-400">{{ $preview['summary']['new'] }}</p>
            </div>
            <div class="p-3 bg-white dark:bg-[#1E1E22] border border-rose-500/30 rounded-xl text-center">
                <span class="text-[10px] uppercase font-mono text-rose-400">Ambiguous</span>
                <p class="text-lg font-bold text-rose-400">{{ $preview['summary']['ambiguous'] }}</p>
            </div>
            <div class="p-3 bg-white dark:bg-[#1E1E22] border border-purple-500/30 rounded-xl text-center">
                <span class="text-[10px] uppercase font-mono text-purple-400">Tanpa Email</span>
                <p class="text-lg font-bold text-purple-400">{{ $preview['summary']['no_email'] }}</p>
            </div>
        </div>

        @if($preview['summary']['no_email'] > 0)
            <div class="p-4 rounded-xl bg-purple-50 dark:bg-[#25182D] border border-purple-300 dark:border-purple-700/60 text-xs text-purple-900 dark:text-purple-300">
                <p class="font-bold mb-1">Perhatian: Terdeteksi {{ $preview['summary']['no_email'] }} guru tanpa email.</p>
                <p class="text-purple-800/80 dark:text-purple-300/80">Akun tetap dapat dibuat/diperbarui, namun login dengan OTP email tidak dapat digunakan sampai email diisi.</p>
            </div>
        @endif

        <form action="{{ route('waka.import-pegawai.commit') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $preview['token'] }}">

            <div class="bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2C2C30] rounded-xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-zinc-50 dark:bg-[#252529] border-b border-zinc-200 dark:border-[#2C2C30] text-[10px] uppercase tracking-wider font-mono text-zinc-500 dark:text-[#97979B]">
                                <th class="py-2.5 px-3">No</th>
                                <th class="py-2.5 px-3">NIP</th>
                                <th class="py-2.5 px-3">Nama Pegawai (Excel)</th>
                                <th class="py-2.5 px-3">Mapel</th>
                                <th class="py-2.5 px-3">Email</th>
                                <th class="py-2.5 px-3">Status Pencocokan</th>
                                <th class="py-2.5 px-3">Akun Database Terkait</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-[#2C2C30]">
                            @foreach($preview['rows'] as $idx => $r)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-[#26262B]">
                                    <td class="py-2 px-3 font-mono text-zinc-400">{{ $r['no'] ?: ($idx + 1) }}</td>
                                    <td class="py-2 px-3 font-mono">{{ $r['nip'] ?: '-' }}</td>
                                    <td class="py-2 px-3 font-semibold text-zinc-900 dark:text-[#EDEDF0]">{{ $r['name'] }}</td>
                                    <td class="py-2 px-3 text-zinc-500">{{ $r['subject'] ?: '-' }}</td>
                                    <td class="py-2 px-3 font-mono">
                                        @if($r['email'])
                                            <span class="text-zinc-700 dark:text-zinc-300">{{ $r['email'] }}</span>
                                        @else
                                            <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-mono bg-purple-100 dark:bg-[#25182D] text-purple-800 dark:text-purple-300">tanpa email</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-3">
                                        @if($r['status'] === 'matched')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-emerald-100 dark:bg-[#16281D] text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700/60">MATCHED</span>
                                        @elseif($r['status'] === 'fuzzy')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-amber-100 dark:bg-[#282115] text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700/60">FUZZY ({{ $r['match_score'] }}%)</span>
                                        @elseif($r['status'] === 'new')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-sky-100 dark:bg-[#16222F] text-sky-800 dark:text-sky-300 border border-sky-300 dark:border-sky-700/60">NEW</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold font-mono bg-rose-100 dark:bg-[#2D1618] text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-700/60">AMBIGUOUS</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-3">
                                        @if($r['status'] === 'matched' && $r['matched_user'])
                                            <span class="text-zinc-800 dark:text-zinc-200">{{ $r['matched_user']['fullname'] }}</span>
                                            <span class="font-mono text-zinc-400">({{ $r['matched_user']['username'] }})</span>
                                        @elseif($r['status'] === 'fuzzy')
                                            <select name="confirmed_fuzzy[{{ $idx }}]" class="text-xs bg-zinc-50 dark:bg-[#252529] border border-zinc-300 dark:border-[#3A3A3F] rounded px-2 py-1 text-zinc-800 dark:text-[#EDEDF0]">
                                                <option value="">-- Buat Akun Baru Saja --</option>
                                                @foreach($r['candidate_list'] as $cand)
                                                    <option value="{{ $cand['user']['id'] }}" selected>
                                                        Hubungkan ke: {{ $cand['user']['fullname'] }} ({{ $cand['user']['username'] }}) - {{ $cand['score'] }}%
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if(!empty($r['title_warning']))
                                                <div class="text-[10px] text-amber-500 dark:text-amber-400 mt-1 flex items-center gap-1 font-mono">
                                                    <span>{{ $r['title_warning'] }}</span>
                                                </div>
                                            @endif
                                        @elseif($r['status'] === 'new')
                                            <span class="italic text-zinc-400">Akun baru akan otomatis dibuat</span>
                                        @else
                                            <span class="text-rose-400">Konflik multi-nama, akan dilewati</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-zinc-50 dark:bg-[#252529] border-t border-zinc-200 dark:border-[#2C2C30] flex items-center justify-between">
                    <p class="text-xs text-zinc-500 dark:text-[#97979B]">
                        Konfirmasi di atas akan memutakhirkan profil guru dan membuat akun guru baru.
                    </p>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('waka.import-pegawai.index') }}" class="px-4 py-2 text-xs text-zinc-600 dark:text-[#97979B] hover:text-white bg-zinc-200 dark:bg-[#323237] rounded-lg">
                            Batal
                        </a>
                        <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 rounded-lg shadow-sm transition-colors">
                            Simpan &amp; Terapkan ke Database
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
