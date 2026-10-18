<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="text-[10px] font-bold font-mono text-[#97979B] uppercase tracking-widest">Detail Dokumen</div>
                <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-[#EDEDF0] mt-0.5">
                    Permohonan Izin Siswa
                </h1>
            </div>
            <a href="{{ route('leave-requests.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent border border-[#2B2B30] text-xs font-semibold uppercase tracking-wider text-[#EDEDF0] rounded-lg hover:bg-[#26262A] transition">
                &larr; Kembali ke Daftar
            </a>
        </div>
    </x-slot>

    <div class="space-y-6 max-w-3xl">

        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif

        {{-- Main Detail Card --}}
        <div class="bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-6 sm:p-8 space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-[#2B2B30]">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-[#EDEDF0]">
                        {{ $leaveRequest->student->fullname }}
                    </h2>
                    <p class="text-xs font-mono text-[#97979B] mt-0.5">
                        Kelas: {{ $leaveRequest->student->schoolClass->class_name ?? '-' }} &bull; Diajukan oleh: {{ $leaveRequest->applicant->fullname }}
                    </p>
                </div>
                <div class="shrink-0">
                    <x-status-badge :status="$leaveRequest->status_approval" />
                </div>
            </div>

            {{-- Metadata Grid --}}
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="p-3.5 bg-[#232327] rounded-lg border border-[#2B2B30]">
                    <dt class="font-mono text-[#97979B] uppercase tracking-wider text-[10px]">Tanggal Mulai</dt>
                    <dd class="font-bold text-[#EDEDF0] mt-1 font-mono text-sm">{{ $leaveRequest->start_date->translatedFormat('d F Y') }}</dd>
                </div>
                <div class="p-3.5 bg-[#232327] rounded-lg border border-[#2B2B30]">
                    <dt class="font-mono text-[#97979B] uppercase tracking-wider text-[10px]">Tanggal Selesai</dt>
                    <dd class="font-bold text-[#EDEDF0] mt-1 font-mono text-sm">{{ $leaveRequest->end_date->translatedFormat('d F Y') }}</dd>
                </div>
            </dl>

            <div>
                <dt class="text-[10px] font-bold font-mono text-[#97979B] uppercase tracking-wider mb-1">Alasan Ketidakhadiran</dt>
                <dd class="p-4 bg-[#232327] border border-[#2B2B30] rounded-lg text-xs text-[#EDEDF0] leading-relaxed whitespace-pre-line">
                    {{ $leaveRequest->reason }}
                </dd>
            </div>

            @if($leaveRequest->document_path)
                <div class="pt-4 border-t border-[#2B2B30]">
                    <dt class="text-[10px] font-bold font-mono text-[#97979B] uppercase tracking-wider mb-2">Dokumen Bukti / Lampiran</dt>
                    <a href="{{ route('leave-requests.document', $leaveRequest) }}" target="_blank" class="inline-flex items-center gap-2 px-3.5 py-2 bg-[#232327] hover:bg-[#26262A] text-xs font-semibold font-mono text-[#EDEDF0] rounded-lg border border-[#2B2B30] hover:border-[#3A3A3F] transition cursor-pointer">
                        <svg class="w-4 h-4 text-[#0093dd]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                        <span>Buka Dokumen Bukti Lampiran</span>
                        <span class="text-[10px] text-[#97979B] font-mono">&nearr;</span>
                    </a>
                </div>
            @endif

            @if($leaveRequest->verifier)
                <div class="pt-4 border-t border-[#2B2B30] text-xs font-mono text-[#97979B]">
                    {{ $leaveRequest->status_approval === 'Approved' ? 'Disetujui' : 'Ditolak' }} oleh: <span class="font-bold text-[#EDEDF0]">{{ $leaveRequest->verifier->fullname }}</span>
                </div>
            @endif
        </div>

        {{-- Verification Action (Tim Kesiswaan / Waka) --}}
        @if(in_array(auth()->user()->role_id, [1, 3]) && $leaveRequest->status_approval === 'Pending')
            <div class="bg-[#1D1D21] border border-[#2B2B30] rounded-xl p-6 sm:p-8 space-y-4">
                <div>
                    <h3 class="text-sm font-bold text-[#EDEDF0] tracking-tight">Keputusan Verifikasi Kesiswaan</h3>
                    <p class="text-xs text-[#97979B] mt-0.5">Tentukan persetujuan izin siswa ini. Status yang disetujui akan otomatis tersinkronisasi pada absensi kelas.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                    <form method="POST" action="{{ route('leave-requests.update-status', $leaveRequest) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status_approval" value="Approved">
                        <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-500/15 hover:bg-emerald-500/25 active:bg-emerald-500/35 border border-emerald-500/30 text-emerald-400 text-xs font-semibold uppercase tracking-wider rounded-lg transition shadow-none cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            Setujui Permohonan
                        </button>
                    </form>

                    <form method="POST" action="{{ route('leave-requests.update-status', $leaveRequest) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status_approval" value="Rejected">
                        <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-rose-500/15 hover:bg-rose-500/25 active:bg-rose-500/35 border border-rose-500/30 text-rose-400 text-xs font-semibold uppercase tracking-wider rounded-lg transition shadow-none cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Tolak Permohonan
                        </button>
                    </form>
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
