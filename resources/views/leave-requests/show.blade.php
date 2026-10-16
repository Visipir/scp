<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="text-[10px] font-bold font-mono text-zinc-400 uppercase tracking-widest">Detail Dokumen</div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900 mt-0.5">
                    Permohonan Izin Siswa
                </h1>
            </div>
            <a href="{{ route('leave-requests.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-zinc-300 text-xs font-semibold uppercase tracking-wider text-zinc-700 rounded-md hover:bg-zinc-50 transition">
                &larr; Kembali ke Daftar
            </a>
        </div>
    </x-slot>

    <div class="space-y-6 max-w-3xl">

        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif

        {{-- Main Detail Card --}}
        <div class="bg-white border border-zinc-200/80 rounded-lg p-6 sm:p-8 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-zinc-200/80">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-zinc-900">
                        {{ $leaveRequest->student->fullname }}
                    </h2>
                    <p class="text-xs font-mono text-zinc-500 mt-0.5">
                        Kelas: {{ $leaveRequest->student->schoolClass->class_name ?? '-' }} &bull; Diajukan oleh: {{ $leaveRequest->applicant->fullname }}
                    </p>
                </div>
                <div class="shrink-0">
                    <x-status-badge :status="$leaveRequest->status_approval" />
                </div>
            </div>

            {{-- Metadata Grid --}}
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="p-3 bg-zinc-50 rounded border border-zinc-200/70">
                    <dt class="font-mono text-zinc-400 uppercase tracking-wider text-[10px]">Tanggal Mulai</dt>
                    <dd class="font-bold text-zinc-900 mt-1 font-mono text-sm">{{ $leaveRequest->start_date->translatedFormat('d F Y') }}</dd>
                </div>
                <div class="p-3 bg-zinc-50 rounded border border-zinc-200/70">
                    <dt class="font-mono text-zinc-400 uppercase tracking-wider text-[10px]">Tanggal Selesai</dt>
                    <dd class="font-bold text-zinc-900 mt-1 font-mono text-sm">{{ $leaveRequest->end_date->translatedFormat('d F Y') }}</dd>
                </div>
            </dl>

            <div>
                <dt class="text-[10px] font-bold font-mono text-zinc-400 uppercase tracking-wider mb-1">Alasan Ketidakhadiran</dt>
                <dd class="p-4 bg-zinc-50 border border-zinc-200/70 rounded text-xs text-zinc-800 leading-relaxed whitespace-pre-line">
                    {{ $leaveRequest->reason }}
                </dd>
            </div>

            @if($leaveRequest->document_path)
                <div class="pt-4 border-t border-zinc-200/80">
                    <dt class="text-[10px] font-bold font-mono text-zinc-400 uppercase tracking-wider mb-2">Dokumen Bukti / Lampiran</dt>
                    <a href="{{ route('leave-requests.document', $leaveRequest) }}" target="_blank" class="inline-flex items-center gap-2 px-3 py-2 bg-zinc-100 hover:bg-zinc-200 text-xs font-semibold font-mono text-zinc-900 rounded border border-zinc-200 transition">
                        <svg class="w-4 h-4 text-zinc-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                        Buka Dokumen Bukti Lampiran
                    </a>
                </div>
            @endif

            @if($leaveRequest->verifier)
                <div class="pt-4 border-t border-zinc-200/80 text-xs font-mono text-zinc-500">
                    {{ $leaveRequest->status_approval === 'Approved' ? 'Disetujui' : 'Ditolak' }} oleh: <span class="font-bold text-zinc-900">{{ $leaveRequest->verifier->fullname }}</span>
                </div>
            @endif
        </div>

        {{-- Verification Action (Tim Kesiswaan / Waka) --}}
        @if(in_array(auth()->user()->role_id, [1, 3]) && $leaveRequest->status_approval === 'Pending')
            <div class="bg-white border border-zinc-200/80 rounded-lg p-6 sm:p-8 space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-zinc-900 tracking-tight">Keputusan Verifikasi Kesiswaan</h3>
                    <p class="text-xs text-zinc-500 mt-0.5">Tentukan persetujuan izin siswa ini. Status yang disetujui akan otomatis tersinkronisasi pada absensi kelas.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                    <form method="POST" action="{{ route('leave-requests.update-status', $leaveRequest) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status_approval" value="Approved">
                        <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-zinc-900 hover:bg-black text-white text-xs font-semibold uppercase tracking-wider rounded-md transition shadow-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            Setujui Permohonan
                        </button>
                    </form>

                    <form method="POST" action="{{ route('leave-requests.update-status', $leaveRequest) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status_approval" value="Rejected">
                        <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-rose-300 hover:bg-rose-50 text-rose-700 text-xs font-semibold uppercase tracking-wider rounded-md transition shadow-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Tolak Permohonan
                        </button>
                    </form>
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
