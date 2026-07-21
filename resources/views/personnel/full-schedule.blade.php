<x-app-layout>
    <div class="py-12 px-4 md:px-8 min-h-screen bg-[#F8FAFC]">
        <div class="max-w-[1600px] mx-auto">

            {{-- Header Section --}}
            <div class="mb-10 flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                <div>
                    <h2 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tighter uppercase leading-none">
                        Master <span class="text-[#D4AF37]">Schedule</span>
                    </h2>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.3em] mt-3 flex items-center gap-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-[#D4AF37] animate-ping"></span>
                        Cross-Facility Occupancy Overview
                    </p>
                </div>
                <a href="{{ route('personnel.index') }}" class="inline-flex items-center justify-center px-6 py-3 bg-white border border-slate-200 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Selection
                </a>
            </div>

            {{-- The Master Grid Container --}}
            <div class="space-y-12">
                @foreach($labs as $lab)
                <div class="bg-white rounded-[3rem] border border-slate-100 shadow-sm overflow-hidden transition-all hover:shadow-md">

                    {{-- Lab Title Ribbon --}}
                    <div class="px-8 py-5 bg-slate-900 flex items-center justify-between sticky left-0 z-20">
                        <div class="flex items-center gap-4">
                            <div class="size-3 bg-[#D4AF37] rounded-full shadow-[0_0_10px_rgba(212,175,55,0.8)]"></div>
                            <h3 class="text-lg font-black text-white uppercase tracking-tight">{{ $lab->lab_name }}</h3>
                        </div>
                        <span class="hidden sm:inline text-[9px] font-bold text-slate-500 uppercase tracking-[0.2em]">Facility ID: #00{{ $lab->id }}</span>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <div class="min-w-[1200px] grid grid-cols-7 border-b border-slate-50">

                            {{-- Day Headers --}}
                            @php $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']; @endphp
                            @foreach($days as $day)
                            <div class="p-5 bg-slate-50/50 border-r border-slate-100 last:border-0 text-center">
                                <span class="text-[10px] font-black uppercase {{ now()->format('l') == $day ? 'text-[#D4AF37]' : 'text-slate-400' }}">
                                    {{ $day }}
                                </span>
                                @if(now()->format('l') == $day)
                                <p class="text-[8px] font-bold text-[#D4AF37]/60 uppercase tracking-tighter">Current Day</p>
                                @endif
                            </div>
                            @endforeach

                            {{-- Schedule Slots --}}
                            @foreach($days as $day)
                            @php
                            // Calculate the calendar date for the specific column day
                            $targetDate = now()->startOfWeek(\Carbon\CarbonInterface::MONDAY)->modify("next $day")->toDateString();

                            if ($day === 'Monday' && now()->format('l') === 'Monday') {
                            $targetDate = now()->toDateString();
                            } elseif ($day === now()->format('l')) {
                            $targetDate = now()->toDateString();
                            }
                            @endphp
                            <div class="p-4 border-r border-slate-50 last:border-0 {{ now()->format('l') == $day ? 'bg-[#D4AF37]/[0.02]' : '' }}">
                                <div class="space-y-4">
                                    @php
                                    $daySchedules = $lab->schedules->where('day', $day)->sortBy('start_time');
                                    @endphp

                                    @forelse($daySchedules as $sched)
                                    @php
                                    $isNow = (now()->format('l') == $day && now()->between($sched->start_time, $sched->end_time));

                                    // FIXED: Restrict log count to the exact session time frame (start_time to end_time)
                                    $logCount = \App\Models\LabSession::where('lab_id', $lab->id)
                                    ->where('teacher_id', $sched->user_id)
                                    ->whereDate('time_in', $targetDate)
                                    ->whereTime('time_in', '>=', $sched->start_time)
                                    ->whereTime('time_in', '<=', $sched->end_time)
                                        ->count();
                                        @endphp

                                        <div class="group relative p-5 rounded-3xl border transition-all duration-300
                                        {{ $isNow 
                                            ? 'bg-white border-[#D4AF37] shadow-lg shadow-[#D4AF37]/10 z-10 scale-[1.02]' 
                                            : 'bg-slate-50/50 border-slate-100 hover:border-slate-300 hover:bg-white' }}">

                                            <div class="flex justify-between items-start mb-3">
                                                <span class="text-[9px] font-black text-slate-400 uppercase font-mono group-hover:text-[#D4AF37]">
                                                    {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($sched->end_time)->format('h:i A') }}
                                                </span>
                                                @if($isNow)
                                                <div class="flex gap-1 items-center">
                                                    <span class="text-[8px] font-black text-[#D4AF37] uppercase">Active</span>
                                                    <div class="size-2 bg-[#D4AF37] rounded-full animate-pulse"></div>
                                                </div>
                                                @endif
                                            </div>

                                            <p class="text-[12px] font-black text-slate-800 uppercase leading-tight">
                                                {{ $sched->subject_code }}
                                            </p>
                                            <p class="text-[9px] font-bold text-slate-400 uppercase mt-1">
                                                {{ $sched->user->name }}
                                            </p>

                                            {{-- Export Authorization --}}
                                            @if(auth()->id() == $sched->user_id || auth()->user()->role == 'admin')
                                            <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between">
                                                <div class="flex flex-col">
                                                    {{-- Visual wording fixed to match true session reality --}}
                                                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-tighter">Session Logs</span>
                                                    <span class="text-[10px] font-black {{ $logCount > 0 ? 'text-slate-900' : 'text-rose-500' }}">
                                                        {{ $logCount }}
                                                    </span>
                                                </div>

                                                @if($logCount > 0)
                                                <a href="{{ route('personnel.export', ['schedule' => $sched->id, 'date' => $targetDate]) }}"
                                                    class="p-2 bg-slate-900 text-[#D4AF37] rounded-xl hover:bg-[#D4AF37] hover:text-white transition-all shadow-sm active:scale-95"
                                                    title="Download Attendance CSV">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </a>
                                                @else
                                                <button type="button"
                                                    onclick="alert('No attendance logs have been recorded during this specific class session timeframe.')"
                                                    class="p-2 bg-slate-100 text-slate-300 rounded-xl cursor-not-allowed group-hover:bg-rose-50 group-hover:text-rose-300 transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                </button>
                                                @endif
                                            </div>
                                            @endif
                                        </div>
                                        @empty
                                        <div class="py-12 flex flex-col items-center justify-center border-2 border-dashed border-slate-100 rounded-[2rem] bg-slate-50/30">
                                            <p class="text-[9px] font-bold text-slate-300 uppercase tracking-widest">No Sessions</p>
                                        </div>
                                        @endforelse
                                </div>
                            </div>
                            @endforeach

                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            height: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #D4AF37;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</x-app-layout>