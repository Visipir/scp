<x-app-layout>
    <x-slot name="header">
        <x-page-header 
            title="Izin Siswa" 
            subtitle="Daftar pengajuan izin, sakit, dan verifikasi kehadiran siswa"
        >
            @if(Auth::user()->role_id === 4)
                <x-slot name="actions">
                    <a href="{{ route('leave-requests.create') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-[#0093dd] hover:bg-[#0082c4] active:bg-[#006fa7] text-white text-xs font-semibold rounded-lg transition shadow-none shrink-0 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        <span>Ajukan Izin</span>
                    </a>
                </x-slot>
            @endif
        </x-page-header>
    </x-slot>

    <div class="space-y-6">

        @if(session('success'))
            <x-alert-banner type="success">{{ session('success') }}</x-alert-banner>
        @endif

        {{-- Status Filter (Segmented Bar) --}}
        <div class="flex items-center justify-between bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-2 overflow-x-auto">
            <div class="inline-flex p-1 bg-zinc-100 dark:bg-[#19191C] rounded-lg border border-zinc-200 dark:border-[#2B2B30] gap-1">
                <a href="{{ route('leave-requests.index') }}" 
                   class="px-3.5 py-1.5 rounded-md text-xs font-medium transition whitespace-nowrap {{ !request('status') ? 'bg-[#0093dd] text-white shadow-none' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-200/60 dark:text-[#97979B] dark:hover:text-[#EDEDF0] dark:hover:bg-[#26262A]' }}">
                    Semua
                </a>
                <a href="{{ route('leave-requests.index', ['status' => 'Pending']) }}" 
                   class="px-3.5 py-1.5 rounded-md text-xs font-medium transition whitespace-nowrap {{ request('status') === 'Pending' ? 'bg-[#0093dd] text-white shadow-none' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-200/60 dark:text-[#97979B] dark:hover:text-[#EDEDF0] dark:hover:bg-[#26262A]' }}">
                    Pending
                </a>
                <a href="{{ route('leave-requests.index', ['status' => 'Approved']) }}" 
                   class="px-3.5 py-1.5 rounded-md text-xs font-medium transition whitespace-nowrap {{ request('status') === 'Approved' ? 'bg-[#0093dd] text-white shadow-none' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-200/60 dark:text-[#97979B] dark:hover:text-[#EDEDF0] dark:hover:bg-[#26262A]' }}">
                    Disetujui
                </a>
                <a href="{{ route('leave-requests.index', ['status' => 'Rejected']) }}" 
                   class="px-3.5 py-1.5 rounded-md text-xs font-medium transition whitespace-nowrap {{ request('status') === 'Rejected' ? 'bg-[#0093dd] text-white shadow-none' : 'text-zinc-600 hover:text-zinc-900 hover:bg-zinc-200/60 dark:text-[#97979B] dark:hover:text-[#EDEDF0] dark:hover:bg-[#26262A]' }}">
                    Ditolak
                </a>
            </div>
        </div>

        {{-- List of Requests --}}
        <div class="space-y-3">
            @forelse($leaveRequests as $leave)
                <a href="{{ route('leave-requests.show', $leave) }}" class="block bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-5 hover:border-[#0093dd]/50 hover:bg-zinc-50/50 dark:hover:bg-[#232327]/40 transition-colors duration-150 group">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="font-bold text-sm text-zinc-900 dark:text-[#EDEDF0] truncate">
                                    {{ $leave->student->fullname }}
                                </h3>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-[#232327] border border-zinc-200 dark:border-[#2B2B30] text-[11px] font-mono text-zinc-600 dark:text-[#97979B]">
                                    {{ $leave->student->schoolClass->class_name ?? '-' }}
                                </span>
                            </div>
                            <p class="text-xs font-mono text-zinc-500 dark:text-[#97979B]">
                                Periode: {{ $leave->start_date->format('d/m/Y') }} &ndash; {{ $leave->end_date->format('d/m/Y') }}
                            </p>
                        </div>
                        <div class="shrink-0 flex flex-col items-end gap-1">
                            <x-status-badge :status="$leave->status_approval" />
                            @if($leave->verifier && $leave->status_approval !== 'Pending')
                                <span class="text-[9px] font-mono text-zinc-500 dark:text-[#97979B] uppercase">Oleh: {{ $leave->verifier->fullname }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-[#2B2B30] flex items-center justify-between">
                        <p class="text-xs text-zinc-500 dark:text-[#97979B] line-clamp-1 leading-relaxed max-w-2xl">
                            <span class="font-semibold text-zinc-800 dark:text-[#EDEDF0]">Keterangan:</span> {{ $leave->reason }}
                        </p>
                    </div>
                </a>
            @empty
                <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-12 text-center">
                    <div class="w-10 h-10 mx-auto rounded-lg bg-zinc-100 dark:bg-[#232327] text-zinc-400 dark:text-[#97979B] flex items-center justify-center mb-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
                    </div>
                    <p class="text-xs font-medium text-zinc-600 dark:text-[#97979B]">Belum ada permohonan izin dalam kategori ini.</p>
                    @if(Auth::user()->role_id === 4)
                        <div class="mt-3">
                            <a href="{{ route('leave-requests.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-[#0093dd] hover:bg-[#0082c4] rounded-lg transition">
                                Ajukan Izin Baru
                            </a>
                        </div>
                    @endif
                </div>
            @endforelse
        </div>

        <div class="pt-2 font-mono text-xs">
            {{ $leaveRequests->appends(request()->query())->links() }}
        </div>

    </div>
</x-app-layout>
