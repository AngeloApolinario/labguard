<x-app-layout>
    <div class="min-h-screen bg-slate-50 px-3 py-6 sm:px-6 sm:py-8 lg:px-10">
        <div class="mx-auto max-w-7xl space-y-6 sm:space-y-8">

            {{-- Header --}}
            <div class="flex flex-col gap-4 rounded-2xl sm:rounded-[2rem] border border-slate-200 bg-white p-5 sm:p-6 shadow-sm sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-[9px] sm:text-[10px] font-black uppercase tracking-[0.3em] sm:tracking-[0.4em] text-[#D4AF37]">
                        Personnel Dashboard
                    </p>
                    <h2 class="mt-1 sm:mt-2 text-2xl font-black uppercase tracking-tight text-slate-900 sm:text-3xl md:text-4xl">
                        Master <span class="text-[#D4AF37]">Schedule</span>
                    </h2>
                    <p class="mt-1 sm:mt-2 text-xs sm:text-sm text-slate-500">
                        Monitor all lab bookings, active sessions, and export attendance logs from a single view.
                    </p>
                </div>

                <a href="{{ route('personnel.index') }}"
                    class="inline-flex items-center justify-center rounded-xl sm:rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 sm:px-5 sm:py-3 text-[10px] font-black uppercase tracking-[0.2em] sm:tracking-[0.25em] text-slate-700 transition hover:bg-slate-100 shrink-0">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Labs
                </a>
            </div>

            {{-- Schedule Grid --}}
            <div class="space-y-6 sm:space-y-8">
                @php
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                $currentDay = now()->format('l');
                $currentDate = now()->toDateString();
                $currentTime = now()->format('H:i:s');
                @endphp

                @foreach($labs as $lab)
                @php
                $labName = $lab->name ?? $lab->room_name ?? $lab->lab_name ?? 'Untitled Lab';
                @endphp

                <div class="overflow-hidden rounded-2xl sm:rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                    {{-- Lab Header --}}
                    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-900 px-4 py-3.5 sm:px-6 sm:py-4">
                        <div class="flex items-center gap-3">
                            <div class="h-2.5 w-2.5 sm:h-3 sm:w-3 rounded-full bg-[#D4AF37] shadow-[0_0_12px_rgba(212,175,55,0.8)] shrink-0"></div>
                            <div>
                                <h3 class="text-base sm:text-lg font-black uppercase tracking-tight text-white">{{ $labName }}</h3>
                                <p class="text-[9px] sm:text-[10px] font-bold uppercase tracking-[0.2em] sm:tracking-[0.25em] text-slate-400">
                                    Facility ID: #00{{ $lab->id }}
                                </p>
                            </div>
                        </div>

                        <span class="rounded-full border border-slate-700 bg-slate-800 px-2.5 py-1 text-[9px] sm:text-[10px] font-black uppercase tracking-[0.15em] sm:tracking-[0.25em] text-slate-300 shrink-0">
                            {{ $lab->schedules->count() }} Bookings
                        </span>
                    </div>

                    {{-- Desktop Desktop Grid View (Visible on XL screen sizes and up) --}}
                    <div class="hidden xl:block overflow-x-auto custom-scrollbar">
                        <div class="min-w-[1100px] border-b border-slate-100">
                            {{-- Day Headers --}}
                            <div class="grid grid-cols-7 bg-slate-50/80">
                                @foreach($days as $day)
                                <div class="border-r border-slate-100 px-3 py-3 text-center last:border-r-0">
                                    <span class="text-[10px] font-black uppercase tracking-[0.2em] {{ $currentDay == $day ? 'text-[#D4AF37]' : 'text-slate-400' }}">
                                        {{ $day }}
                                    </span>
                                    @if($currentDay == $day)
                                    <p class="mt-0.5 text-[8px] font-bold uppercase tracking-[0.2em] text-[#D4AF37]/70">
                                        Today
                                    </p>
                                    @endif
                                </div>
                                @endforeach
                            </div>

                            {{-- Schedule Cells --}}
                            <div class="grid grid-cols-7">
                                @foreach($days as $day)
                                @php
                                $daySchedules = $lab->schedules->where('day', $day)->sortBy('start_time');
                                @endphp

                                <div class="min-h-[260px] border-r border-slate-100 bg-white p-3 last:border-r-0 {{ $currentDay == $day ? 'bg-[#D4AF37]/[0.02]' : '' }}">
                                    <div class="space-y-3">
                                        @forelse($daySchedules as $sched)
                                        @php
                                        $startTime = \Carbon\Carbon::parse($sched->start_time)->format('H:i:s');
                                        $endTime = \Carbon\Carbon::parse($sched->end_time)->format('H:i:s');
                                        $isNow = $currentDay == $day && $currentTime >= $startTime && $currentTime <= $endTime;
                                            $targetDate=$currentDay==$day ? $currentDate : now()->startOfWeek(\Carbon\CarbonInterface::MONDAY)->modify("next {$day}")->toDateString();

                                            $logCount = \App\Models\LabSession::where('lab_id', $lab->id)
                                            ->where('teacher_id', $sched->user_id)
                                            ->whereDate('time_in', $targetDate)
                                            ->whereTime('time_in', '>=', $startTime)
                                            ->whereTime('time_in', '<=', $endTime)
                                                ->count();
                                                @endphp

                                                <div class="rounded-[1.25rem] border p-3.5 transition-all duration-200 {{ $isNow ? 'border-[#D4AF37] bg-[#FFFDF5] shadow-[0_10px_30px_rgba(212,175,55,0.12)]' : 'border-slate-200 bg-slate-50/70 hover:border-slate-300 hover:bg-white' }}">
                                                    <div class="mb-2 flex items-start justify-between gap-2">
                                                        <span class="text-[8px] font-black uppercase tracking-[0.15em] text-slate-400">
                                                            {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($sched->end_time)->format('h:i A') }}
                                                        </span>

                                                        @if($isNow)
                                                        <span class="rounded-full bg-[#D4AF37] px-1.5 py-0.5 text-[7px] font-black uppercase tracking-[0.2em] text-white">
                                                            Active
                                                        </span>
                                                        @endif
                                                    </div>

                                                    <p class="text-xs font-black uppercase leading-tight text-slate-900">
                                                        {{ $sched->subject_code }}
                                                    </p>

                                                    <p class="mt-1 text-[9px] font-semibold uppercase tracking-[0.15em] text-slate-500">
                                                        {{ $sched->user->name ?? 'Unassigned' }}
                                                    </p>

                                                    @if(auth()->id() == $sched->user_id || auth()->user()->role == 'admin')
                                                    <div class="mt-3 flex items-center justify-between border-t border-slate-200/80 pt-2.5">
                                                        <div>
                                                            <p class="text-[7px] font-black uppercase tracking-[0.2em] text-slate-400">
                                                                Logs
                                                            </p>
                                                            <p class="text-xs font-black {{ $logCount > 0 ? 'text-slate-900' : 'text-rose-500' }}">
                                                                {{ $logCount }}
                                                            </p>
                                                        </div>

                                                        @if($logCount > 0)
                                                        <a href="{{ route('personnel.export', ['schedule' => $sched->id, 'date' => $targetDate]) }}"
                                                            class="rounded-lg bg-slate-900 p-1.5 text-[#D4AF37] transition hover:bg-[#D4AF37] hover:text-white"
                                                            title="Download Attendance CSV">
                                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                        </a>
                                                        @else
                                                        <button type="button"
                                                            onclick="alert('No attendance logs have been recorded during this specific class session timeframe.')"
                                                            class="cursor-not-allowed rounded-lg bg-slate-100 p-1.5 text-slate-300 transition hover:bg-rose-50 hover:text-rose-400">
                                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                            </svg>
                                                        </button>
                                                        @endif
                                                    </div>
                                                    @endif
                                                </div>
                                                @empty
                                                <div class="flex min-h-[160px] flex-col items-center justify-center rounded-[1.25rem] border border-dashed border-slate-200 bg-slate-50/60 p-3 text-center">
                                                    <p class="text-[8px] font-black uppercase tracking-[0.2em] text-slate-300">
                                                        No Sessions
                                                    </p>
                                                </div>
                                                @endforelse
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Mobile/Tablet Responsive Day Card Accordion Stack (Visible on screen sizes below XL) --}}
                    <div class="block xl:hidden divide-y divide-slate-100">
                        @foreach($days as $day)
                        @php
                        $daySchedules = $lab->schedules->where('day', $day)->sortBy('start_time');
                        $isToday = $currentDay == $day;
                        @endphp

                        <div class="p-4 sm:p-5 {{ $isToday ? 'bg-[#D4AF37]/[0.02]' : '' }}">
                            <div class="mb-3 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-black uppercase tracking-[0.25em] {{ $isToday ? 'text-[#D4AF37]' : 'text-slate-800' }}">
                                        {{ $day }}
                                    </span>
                                    @if($isToday)
                                    <span class="rounded-full bg-[#D4AF37]/10 px-2 py-0.5 text-[8px] font-black uppercase tracking-widest text-[#D4AF37]">
                                        Today
                                    </span>
                                    @endif
                                </div>
                                <span class="text-[9px] font-bold uppercase tracking-wider text-slate-400">
                                    {{ $daySchedules->count() }} {{ Str::plural('session', $daySchedules->count()) }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @forelse($daySchedules as $sched)
                                @php
                                $startTime = \Carbon\Carbon::parse($sched->start_time)->format('H:i:s');
                                $endTime = \Carbon\Carbon::parse($sched->end_time)->format('H:i:s');
                                $isNow = $currentDay == $day && $currentTime >= $startTime && $currentTime <= $endTime;
                                    $targetDate=$currentDay==$day ? $currentDate : now()->startOfWeek(\Carbon\CarbonInterface::MONDAY)->modify("next {$day}")->toDateString();

                                    $logCount = \App\Models\LabSession::where('lab_id', $lab->id)
                                    ->where('teacher_id', $sched->user_id)
                                    ->whereDate('time_in', $targetDate)
                                    ->whereTime('time_in', '>=', $startTime)
                                    ->whereTime('time_in', '<=', $endTime)
                                        ->count();
                                        @endphp

                                        <div class="rounded-2xl border p-4 transition-all {{ $isNow ? 'border-[#D4AF37] bg-[#FFFDF5] shadow-md' : 'border-slate-200 bg-slate-50/70' }}">
                                            <div class="mb-2 flex items-center justify-between">
                                                <span class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">
                                                    {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($sched->end_time)->format('h:i A') }}
                                                </span>
                                                @if($isNow)
                                                <span class="rounded-full bg-[#D4AF37] px-2 py-0.5 text-[8px] font-black uppercase tracking-widest text-white">
                                                    Active Now
                                                </span>
                                                @endif
                                            </div>

                                            <h4 class="text-sm font-black uppercase text-slate-900 leading-snug">
                                                {{ $sched->subject_code }}
                                            </h4>
                                            <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                                                {{ $sched->user->name ?? 'Unassigned' }}
                                            </p>

                                            @if(auth()->id() == $sched->user_id || auth()->user()->role == 'admin')
                                            <div class="mt-3 flex items-center justify-between border-t border-slate-200/80 pt-2.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-400">Session Logs:</span>
                                                    <span class="text-xs font-black {{ $logCount > 0 ? 'text-slate-900' : 'text-rose-500' }}">
                                                        {{ $logCount }}
                                                    </span>
                                                </div>

                                                @if($logCount > 0)
                                                <a href="{{ route('personnel.export', ['schedule' => $sched->id, 'date' => $targetDate]) }}"
                                                    class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-2.5 py-1.5 text-[9px] font-black uppercase text-[#D4AF37] hover:bg-[#D4AF37] hover:text-white transition">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    Export
                                                </a>
                                                @else
                                                <button type="button"
                                                    onclick="alert('No attendance logs have been recorded during this specific class session timeframe.')"
                                                    class="cursor-not-allowed rounded-lg bg-slate-100 p-1.5 text-slate-300">
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                </button>
                                                @endif
                                            </div>
                                            @endif
                                        </div>
                                        @empty
                                        <div class="col-span-full rounded-xl border border-dashed border-slate-200 bg-slate-50/50 p-3 text-center">
                                            <p class="text-[9px] font-semibold uppercase tracking-wider text-slate-400">No class bookings for {{ $day }}.</p>
                                        </div>
                                        @endforelse
                            </div>
                        </div>
                        @endforeach
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
            background: #f1f5f9;
            border-radius: 9999px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #d4af37;
        }
    </style>
</x-app-layout>