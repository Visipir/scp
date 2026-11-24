<x-app-layout>
    <x-slot name="header">
        <x-page-header 
            title="Izin Siswa" 
            subtitle="Daftar pengajuan izin, sakit, dan verifikasi kehadiran siswa"
        >
            @if(Auth::user()->role_id === 4)
                <x-slot name="actions">
                    <a href="{{ route('leave-requests.create') }}" class="inline-flex items-center gap-2 min-h-[44px] sm:min-h-0 px-3.5 py-2 bg-[#0073B1] hover:bg-[#005D8F] active:bg-[#004A73] text-white text-xs font-semibold rounded-lg transition shadow-none shrink-0 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
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

        {{-- Filter Bar --}}
        <search class="block bg-white dark:bg-[#1E1E22] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-4 space-y-3">
            <form method="GET" action="{{ route('leave-requests.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                {{-- Status --}}
                <div>
                    <label for="leave_filter_status" class="block text-[11px] font-mono font-medium text-zinc-600 dark:text-[#9496A1] mb-1">Status</label>
                    <select id="leave_filter_status" name="status" class="w-full text-xs rounded-lg border-zinc-200 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] text-zinc-900 dark:text-[#EDEDF0] focus:ring-[#0073B1] focus:border-[#0073B1]">
                        <option value="">Semua Status</option>
                        <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Menunggu</option>
                        <option value="Approved" {{ request('status') === 'Approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>

                {{-- Kelas (hanya untuk staff/kesiswaan) --}}
                @if($classes->isNotEmpty())
                <div>
                    <label for="leave_filter_class" class="block text-[11px] font-mono font-medium text-zinc-600 dark:text-[#9496A1] mb-1">Kelas</label>
                    <select id="leave_filter_class" name="class_id" class="w-full text-xs rounded-lg border-zinc-200 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] text-zinc-900 dark:text-[#EDEDF0] focus:ring-[#0073B1] focus:border-[#0073B1]">
                        <option value="">Semua Kelas</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ request('class_id') == $c->id ? 'selected' : '' }}>{{ $c->class_name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Tanggal Izin --}}
                <div>
                    <label for="leave_filter_date" class="block text-[11px] font-mono font-medium text-zinc-600 dark:text-[#9496A1] mb-1">Tanggal</label>
                    <input id="leave_filter_date" type="date" name="date" value="{{ request('date') }}" class="w-full text-xs rounded-lg border-zinc-200 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] text-zinc-900 dark:text-[#EDEDF0] focus:ring-[#0073B1] focus:border-[#0073B1]">
                </div>

                {{-- Cari Siswa (Nama / NIS) --}}
                <div>
                    <label for="leave_filter_student" class="block text-[11px] font-mono font-medium text-zinc-600 dark:text-[#9496A1] mb-1">Siswa (Nama / NIS)</label>
                    <div class="flex items-center gap-2">
                        <input id="leave_filter_student" type="search" enterkeyhint="search" name="student" value="{{ request('student', request('search')) }}" placeholder="Cari nama / NIS…" class="w-full text-xs rounded-lg border-zinc-200 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] text-zinc-900 dark:text-[#EDEDF0] focus:ring-[#0073B1] focus:border-[#0073B1]">
                        <button type="submit" class="min-h-[44px] sm:min-h-0 px-3 py-2 bg-[#0073B1] hover:bg-[#005D8F] text-white text-xs font-semibold rounded-lg shrink-0 transition cursor-pointer">
                            Filter
                        </button>
                        @if(request()->hasAny(['status', 'class_id', 'date', 'student', 'search']))
                            <a href="{{ route('leave-requests.index') }}" class="inline-flex items-center min-h-[44px] sm:min-h-0 px-2.5 py-2 bg-zinc-100 hover:bg-zinc-200 text-zinc-700 dark:bg-[#232327] dark:hover:bg-[#2B2B30] dark:text-[#9496A1] text-xs rounded-lg shrink-0 transition">
                                Reset
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </search>

        {{-- List of Requests --}}
        <div class="space-y-3">
            @forelse($leaveRequests as $leave)
                <a href="{{ route('leave-requests.show', $leave) }}" class="block bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-5 hover:border-[#0073B1]/50 hover:bg-zinc-50/50 dark:hover:bg-[#232327]/40 transition-colors duration-150 group">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="space-y-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h2 class="font-bold text-sm text-zinc-900 dark:text-[#EDEDF0] truncate">
                                    {{ $leave->student->fullname }}
                                </h2>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-zinc-100 dark:bg-[#232327] border border-zinc-200 dark:border-[#2B2B30] text-[11px] font-mono text-zinc-700 dark:text-[#9496A1]">
                                    {{ $leave->student->schoolClass->class_name ?? '-' }}
                                </span>
                                @php
                                    $cat = $leave->category ?? $leave->attendance_status;
                                    $catBadge = match($cat) {
                                        'Sakit' => 'bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800/50',
                                        'Dispensasi' => 'bg-purple-50 dark:bg-purple-950/40 text-purple-800 dark:text-purple-300 border-purple-200 dark:border-purple-800/50',
                                        'Lainnya' => 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border-zinc-200 dark:border-zinc-700',
                                        default => 'bg-sky-50 dark:bg-sky-950/40 text-sky-800 dark:text-sky-300 border-sky-200 dark:border-sky-800/50',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider border {{ $catBadge }}">
                                    {{ $cat }}
                                </span>
                            </div>
                            <p class="text-xs font-mono text-zinc-600 dark:text-[#9496A1]">
                                Periode: {{ $leave->start_date->format('d/m/Y') }} &ndash; {{ $leave->end_date->format('d/m/Y') }}
                            </p>
                        </div>
                        <div class="shrink-0 flex flex-col items-end gap-1">
                            <x-status-badge :status="$leave->status_approval" />
                            <span class="text-[11px] font-mono text-zinc-600 dark:text-[#9496A1]">{{ $leave->decisionLabel() }}</span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-[#2B2B30] flex items-center justify-between">
                        <p class="text-xs text-zinc-600 dark:text-[#9496A1] line-clamp-1 leading-relaxed max-w-2xl">
                            <span class="font-semibold text-zinc-800 dark:text-[#EDEDF0]">Keterangan:</span> {{ $leave->reason }}
                        </p>
                    </div>
                </a>
            @empty
                <div class="bg-white dark:bg-[#1D1D21] border border-zinc-200 dark:border-[#2B2B30] rounded-xl p-12 text-center">
                    <div class="w-10 h-10 mx-auto rounded-lg bg-zinc-100 dark:bg-[#232327] text-zinc-500 dark:text-[#9496A1] flex items-center justify-center mb-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/></svg>
                    </div>
                    <p class="text-xs font-medium text-zinc-600 dark:text-[#9496A1]">Belum ada permohonan izin dalam kategori ini.</p>
                    @if(Auth::user()->role_id === 4)
                        <div class="mt-3">
                            <a href="{{ route('leave-requests.create') }}" class="inline-flex items-center gap-1.5 min-h-[44px] sm:min-h-0 px-3 py-1.5 text-xs font-semibold text-white bg-[#0073B1] hover:bg-[#005D8F] rounded-lg transition">
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
