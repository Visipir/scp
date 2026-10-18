<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-bold text-zinc-900 leading-tight flex items-center gap-2">
            <svg class="w-5 h-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1v2H9V7zm0 4h1v2H9v-2zm0 4h1v2H9v-2zm3-8h1v2h-1V7zm0 4h1v2h-1v-2zm0 4h1v2h-1v-2zm3-8h1v2h-1V7zm0 4h1v2h-1v-2zm0 4h1v2h-1v-2z" />
            </svg>
            Jadwal Ruangan & Lab
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
            
            <!-- Filter Header -->
            <div class="bg-white p-4 rounded-xl border border-zinc-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
                <form method="GET" action="{{ route('room-schedules.index') }}" class="flex flex-col md:flex-row items-start md:items-center gap-4 w-full" id="roomFilterForm">
                    
                    <!-- Room Selector -->
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <label for="room_id" class="text-xs font-bold font-mono uppercase tracking-wider text-zinc-400">Ruangan:</label>
                        <select name="room_id" id="room_id" onchange="document.getElementById('roomFilterForm').submit()" class="block w-full md:w-48 text-sm rounded-md border-zinc-300 py-1.5 pl-3 pr-8 focus:border-zinc-500 focus:ring-zinc-500 font-bold text-zinc-900">
                            @foreach($rooms as $r)
                                <option value="{{ $r->id }}" {{ $roomId == $r->id ? 'selected' : '' }}>{{ $r->room_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="hidden md:block h-5 w-px bg-zinc-200"></div>

                    <!-- Cycle Toggle -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold font-mono uppercase tracking-wider text-zinc-400">Siklus:</span>
                        <div class="inline-flex rounded-md border border-zinc-200 p-0.5 bg-zinc-100/70 text-xs font-semibold">
                            @foreach(['Putaran 1', 'Putaran 2'] as $cycle)
                                <button 
                                    type="submit" 
                                    name="cycle" 
                                    value="{{ $cycle }}" 
                                    class="px-3.5 py-1.5 rounded transition cursor-pointer {{ $cycleFilter === $cycle ? 'bg-zinc-900 text-white shadow-xs' : 'text-zinc-600 hover:text-zinc-900 hover:bg-[#26262A]' }}"
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
                <div class="bg-white p-8 rounded-xl border border-zinc-200 text-center">
                    <p class="text-zinc-500 text-sm">Silakan pilih ruangan terlebih dahulu.</p>
                </div>
            @else
                <!-- Desktop Timetable Matrix (lg+) -->
                <div class="hidden lg:block bg-white rounded-xl border border-zinc-200 shadow-sm overflow-hidden">
                    <div class="grid grid-cols-5 divide-x divide-zinc-200">
                        @foreach($days as $day)
                            <div class="bg-zinc-50/50 p-3 text-center border-b border-zinc-200">
                                <h3 class="font-bold text-zinc-900 text-sm uppercase tracking-wide">{{ $day }}</h3>
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
                                            <div class="p-2 rounded-lg border border-indigo-200 bg-indigo-50/50 relative overflow-hidden group">
                                                <div class="absolute top-0 left-0 w-1 h-full bg-indigo-500"></div>
                                                <div class="pl-2">
                                                    <div class="text-[10px] font-mono text-indigo-500 font-semibold mb-0.5">
                                                        P{{ $period['period'] }} &bull; {{ $period['start_time'] }}-{{ $period['end_time'] }}
                                                    </div>
                                                    <div class="text-xs font-bold text-zinc-900 leading-tight">
                                                        {{ $occupiedBy->subject }}
                                                    </div>
                                                    <div class="mt-1 flex flex-wrap items-center gap-1">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-indigo-100 text-indigo-700">
                                                            {{ $occupiedBy->schoolClass->class_name ?? 'Kelas' }}
                                                        </span>
                                                        <span class="text-[10px] text-zinc-600 truncate">
                                                            {{ $occupiedBy->teacher->fullname ?? 'Guru' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <!-- Empty Slot -->
                                            <div class="p-2 rounded-lg border border-dashed border-zinc-200 bg-zinc-50/30 flex flex-col justify-center items-center py-3">
                                                <div class="text-[10px] font-mono text-zinc-400 font-medium mb-0.5">
                                                    P{{ $period['period'] }} &bull; {{ $period['start_time'] }}-{{ $period['end_time'] }}
                                                </div>
                                                <span class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">Kosong / Tersedia</span>
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
                                :class="activeTab === '{{ $day }}' ? 'bg-zinc-900 text-white shadow' : 'bg-white text-zinc-600 border border-zinc-200'"
                                class="px-4 py-2 text-xs font-bold rounded-lg transition-colors whitespace-nowrap shrink-0"
                            >
                                {{ $day }}
                            </button>
                        @endforeach
                    </div>

                    <div class="bg-white rounded-xl border border-zinc-200 shadow-sm p-3">
                        @foreach($days as $day)
                            <div x-show="activeTab === '{{ $day }}'" class="space-y-2">
                                <h3 class="font-bold text-zinc-900 text-sm mb-3 border-b border-zinc-100 pb-2">{{ $day }}</h3>
                                
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
                                        
                                        <div class="flex items-start gap-3 p-2.5 rounded-lg border {{ $occupiedBy ? 'border-indigo-100 bg-indigo-50/30' : 'border-dashed border-zinc-200 bg-zinc-50/50' }}">
                                            <div class="shrink-0 w-16 text-center">
                                                <div class="text-[10px] font-bold text-zinc-400">P{{ $period['period'] }}</div>
                                                <div class="text-xs font-mono font-medium {{ $occupiedBy ? 'text-indigo-600' : 'text-zinc-500' }}">
                                                    {{ $period['start_time'] }}
                                                    <br>
                                                    {{ $period['end_time'] }}
                                                </div>
                                            </div>
                                            
                                            <div class="flex-1 min-w-0 border-l {{ $occupiedBy ? 'border-indigo-200' : 'border-zinc-200' }} pl-3">
                                                @if($occupiedBy)
                                                    <div class="text-sm font-bold text-zinc-900 leading-tight">
                                                        {{ $occupiedBy->subject }}
                                                    </div>
                                                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700">
                                                            {{ $occupiedBy->schoolClass->class_name ?? 'Kelas' }}
                                                        </span>
                                                        <span class="text-xs text-zinc-600">
                                                            {{ $occupiedBy->teacher->fullname ?? 'Guru' }}
                                                        </span>
                                                    </div>
                                                @else
                                                    <div class="h-full flex items-center">
                                                        <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-1 rounded border border-emerald-100">Kosong / Tersedia</span>
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
