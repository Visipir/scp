<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Jadwal Ruangan" subtitle="Pemetaan pemakaian ruang kelas, laboratorium, dan fasilitas sekolah" />
    </x-slot>

    <div class="space-y-4">
            <!-- Filter Header -->
            <div class="bg-white dark:bg-[#1E1E22] p-4 rounded-xl border border-zinc-200 dark:border-[#2C2C30] shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <form method="GET" action="{{ route('room-schedules.index') }}" class="flex flex-col md:flex-row items-start md:items-center gap-4 w-full" id="roomFilterForm">
                    
                    <!-- Room Selector -->
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <label for="room_id" class="text-xs font-bold font-mono uppercase tracking-wider text-zinc-400 dark:text-[#97979B]">Ruangan:</label>
                        <select name="room_id" id="room_id" onchange="document.getElementById('roomFilterForm').submit()" class="block w-full md:w-48 text-sm rounded-md border-zinc-300 dark:border-[#2B2B30] bg-white dark:bg-[#19191C] py-1.5 pl-3 pr-8 focus:border-[#0093dd] focus:ring-[#0093dd] font-bold text-zinc-900 dark:text-[#EDEDF0]">
                            @foreach($rooms as $r)
                                <option value="{{ $r->id }}" {{ $roomId == $r->id ? 'selected' : '' }}>{{ $r->room_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="hidden md:block h-5 w-px bg-zinc-200 dark:bg-[#2C2C30]"></div>

                    <!-- Cycle Toggle -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold font-mono uppercase tracking-wider text-zinc-400 dark:text-[#97979B]">Siklus:</span>
                        <div class="inline-flex rounded-md border border-zinc-200 dark:border-[#2B2B30] p-0.5 bg-zinc-100/70 dark:bg-[#121214] text-xs font-semibold">
                            @foreach(['Putaran 1', 'Putaran 2'] as $cycle)
                                <button 
                                    type="submit" 
                                    name="cycle" 
                                    value="{{ $cycle }}" 
                                    class="px-3.5 py-1.5 rounded transition cursor-pointer {{ $cycleFilter === $cycle ? 'bg-[#0093dd] text-white shadow-xs font-semibold' : 'text-zinc-600 dark:text-[#97979B] hover:text-zinc-900 dark:hover:text-[#EDEDF0] hover:bg-zinc-200 dark:hover:bg-[#26262A]' }}"
                                >
                                    {{ $cycle }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                </form>
            </div>

            @php
                $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
                $periodsByDay = $scheduleMatrix[$cycleFilter]['periods'] ?? [];
            @endphp

            @if(!$selectedRoom)
                <div class="bg-white dark:bg-[#1E1E22] p-8 rounded-xl border border-zinc-200 dark:border-[#2C2C30] text-center">
                    <p class="text-zinc-500 dark:text-[#97979B] text-sm">Silakan pilih ruangan terlebih dahulu.</p>
                </div>
            @else
                <!-- Desktop Timetable Matrix (lg+) -->
                <div class="hidden lg:block bg-white dark:bg-[#1E1E22] rounded-xl border border-zinc-200 dark:border-[#2C2C30] shadow-sm overflow-hidden">
                    <div class="grid grid-cols-5 divide-x divide-zinc-200 dark:divide-[#2C2C30]">
                        @foreach($days as $day)
                            <div class="bg-zinc-50/50 dark:bg-[#19191C] p-3 text-center border-b border-zinc-200 dark:border-[#2C2C30]">
                                <h3 class="font-bold text-zinc-900 dark:text-[#EDEDF0] text-sm uppercase tracking-wide">{{ $day }}</h3>
                            </div>
                        @endforeach
                        
                        @foreach($days as $day)
                            <div class="p-3 space-y-2">
                                @if(isset($periodsByDay[$day]))
                                    @foreach($periodsByDay[$day] as $period)
                                        @php
                                            // Check if this period is occupied by any schedule for this day
                                            $daySchedules = $schedulesByDay[$day] ?? collect();
                                            $occupiedBy = null;
                                            foreach($daySchedules as $sched) {
                                                // Convert times to comparable formats (H:i)
                                                $schedStart = \Carbon\Carbon::parse($sched->start_time)->format('H:i');
                                                $schedEnd = \Carbon\Carbon::parse($sched->end_time)->format('H:i');
                                                $periodStart = $period['start_time'];
                                                $periodEnd = $period['end_time'];
                                                
                                                // If schedule covers this period
                                                if ($schedStart <= $periodStart && $schedEnd >= $periodEnd) {
                                                    $occupiedBy = $sched;
                                                    break;
                                                }
                                            }
                                        @endphp
                                        
                                        @if($occupiedBy)
                                            <!-- Occupied Slot -->
                                            <div class="p-2.5 rounded-lg border border-[#0093dd]/30 bg-[#0093dd]/5 dark:bg-[#0093dd]/10 relative group transition-colors">
                                                <div class="text-[10px] font-mono text-[#0093dd] font-semibold mb-0.5">
                                                    P{{ $period['period'] }} &bull; {{ $period['start_time'] }}-{{ $period['end_time'] }}
                                                </div>
                                                <div class="text-xs font-bold text-zinc-900 dark:text-[#EDEDF0] leading-tight">
                                                    {{ $occupiedBy->subject }}
                                                </div>
                                                <div class="mt-1.5 flex flex-wrap items-center gap-1">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-[#0093dd]/15 text-[#0093dd]">
                                                        {{ $occupiedBy->schoolClass->class_name ?? 'Kelas' }}
                                                    </span>
                                                    <span class="text-[10px] text-zinc-600 dark:text-[#97979B] truncate">
                                                        {{ $occupiedBy->teacher->fullname ?? 'Guru' }}
                                                    </span>
                                                </div>
                                            </div>
                                        @else
                                            <!-- Empty Slot -->
                                            <div class="p-2 rounded-lg border border-dashed border-zinc-200 dark:border-[#2C2C30] bg-zinc-50/30 dark:bg-[#19191C] flex flex-col justify-center items-center py-3">
                                                <div class="text-[10px] font-mono text-zinc-400 dark:text-[#5B5B61] font-medium mb-0.5">
                                                    P{{ $period['period'] }} &bull; {{ $period['start_time'] }}-{{ $period['end_time'] }}
                                                </div>
                                                <span class="text-[10px] font-semibold text-emerald-800 dark:text-emerald-300 bg-emerald-50 dark:bg-[#16281D] px-1.5 py-0.5 rounded border border-emerald-300 dark:border-emerald-700/60">Kosong / Tersedia</span>
                                            </div>
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Mobile & Tablet View (< lg) using Alpine Tabs -->
                <div class="lg:hidden" x-data="{ activeTab: 'Senin' }">
                    <div class="flex overflow-x-auto hide-scrollbar gap-2 mb-4 pb-1">
                        @foreach($days as $day)
                            <button 
                                @click="activeTab = '{{ $day }}'"
                                :class="activeTab === '{{ $day }}' ? 'bg-[#0093dd] text-white shadow' : 'bg-white dark:bg-[#1E1E22] text-zinc-600 dark:text-[#97979B] border border-zinc-200 dark:border-[#2C2C30] hover:bg-zinc-50 dark:hover:bg-[#26262A]'"
                                class="px-4 py-2 text-xs font-bold rounded-lg transition-colors whitespace-nowrap shrink-0"
                            >
                                {{ $day }}
                            </button>
                        @endforeach
                    </div>

                    <div class="bg-white dark:bg-[#1E1E22] rounded-xl border border-zinc-200 dark:border-[#2C2C30] shadow-sm p-3">
                        @foreach($days as $day)
                            <div x-show="activeTab === '{{ $day }}'" class="space-y-2">
                                <h3 class="font-bold text-zinc-900 dark:text-[#EDEDF0] text-sm mb-3 border-b border-zinc-100 dark:border-[#2C2C30] pb-2">{{ $day }}</h3>
                                
                                @if(isset($periodsByDay[$day]))
                                    @foreach($periodsByDay[$day] as $period)
                                        @php
                                            $daySchedules = $schedulesByDay[$day] ?? collect();
                                            $occupiedBy = null;
                                            foreach($daySchedules as $sched) {
                                                $schedStart = \Carbon\Carbon::parse($sched->start_time)->format('H:i');
                                                $schedEnd = \Carbon\Carbon::parse($sched->end_time)->format('H:i');
                                                $periodStart = $period['start_time'];
                                                $periodEnd = $period['end_time'];
                                                
                                                if ($schedStart <= $periodStart && $schedEnd >= $periodEnd) {
                                                    $occupiedBy = $sched;
                                                    break;
                                                }
                                            }
                                        @endphp
                                        
                                        <div class="flex items-start gap-3 p-2.5 rounded-lg border {{ $occupiedBy ? 'border-[#0093dd]/30 bg-[#0093dd]/5 dark:bg-[#0093dd]/10' : 'border-dashed border-zinc-200 dark:border-[#2C2C30] bg-zinc-50/50 dark:bg-[#19191C]' }}">
                                            <div class="shrink-0 w-16 text-center">
                                                <div class="text-[10px] font-bold text-zinc-400 dark:text-[#5B5B61]">P{{ $period['period'] }}</div>
                                                <div class="text-xs font-mono font-medium {{ $occupiedBy ? 'text-[#0093dd]' : 'text-zinc-500 dark:text-[#97979B]' }}">
                                                    {{ $period['start_time'] }}
                                                    <br>
                                                    {{ $period['end_time'] }}
                                                </div>
                                            </div>
                                            
                                            <div class="flex-1 min-w-0 border-l {{ $occupiedBy ? 'border-[#0093dd]/30' : 'border-zinc-200 dark:border-[#2C2C30]' }} pl-3">
                                                @if($occupiedBy)
                                                    <div class="text-sm font-bold text-zinc-900 dark:text-[#EDEDF0] leading-tight">
                                                        {{ $occupiedBy->subject }}
                                                    </div>
                                                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-[#0093dd]/15 text-[#0093dd]">
                                                            {{ $occupiedBy->schoolClass->class_name ?? 'Kelas' }}
                                                        </span>
                                                        <span class="text-xs text-zinc-600 dark:text-[#97979B]">
                                                            {{ $occupiedBy->teacher->fullname ?? 'Guru' }}
                                                        </span>
                                                    </div>
                                                @else
                                                    <div class="h-full flex items-center">
                                                        <span class="text-xs font-semibold text-emerald-800 dark:text-emerald-300 bg-emerald-50 dark:bg-[#16281D] px-2 py-1 rounded border border-emerald-300 dark:border-emerald-700/60">Kosong / Tersedia</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    
    <style>
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</x-app-layout>
