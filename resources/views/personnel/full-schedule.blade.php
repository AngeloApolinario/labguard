<x-app-layout>
    <div class="min-h-screen bg-slate-50 px-4 py-10 md:px-8 lg:px-10">
        <div class="mx-auto max-w-7xl space-y-8">

            {{-- Header --}}
            <div class="flex flex-col gap-4 rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.4em] text-[#D4AF37]">
                        Personnel Dashboard
                    </p>
                    <h2 class="mt-2 text-3xl font-black uppercase tracking-tight text-slate-900 md:text-4xl">
                        Master <span class="text-[#D4AF37]">Schedule</span>
                    </h2>
                    <p class="mt-2 text-sm text-slate-500">
                        Monitor all lab bookings, active sessions, and export attendance logs from a single view.
                    </p>
                </div>

                <a href="{{ route('personnel.index') }}"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 px-5 py-3 text-[10px] font-black uppercase tracking-[0.25em] text-slate-700 transition hover:bg-slate-100">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Labs
                </a>
            </div>

            {{-- Schedule Grid --}}
            <div class="space-y-8">
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

                <div class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm">
                    {{-- Lab Header --}}
                    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-900 px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="h-3 w-3 rounded-full bg-[#D4AF37] shadow-[0_0_12px_rgba(212,175,55,0.8)]"></div>
                            <div>
                                <h3 class="text-lg font-black uppercase tracking-tight text-white">{{ $labName }}</h3>
                                <p class="text-[10px] font-bold uppercase tracking-[0.25em] text-slate-400">
                                    Facility ID: #00{{ $lab->id }}
                                </p>
                            </div>
                        </div>

                        <span class="rounded-full border border-slate-700 bg-slate-800 px-3 py-1 text-[10px] font-black uppercase tracking-[0.25em] text-slate-300">
                            {{ $lab->schedules->count() }} Bookings
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <div class="min-w-[1200px] border-b border-slate-100">
                            {{-- Day Headers --}}
                            <div class="grid grid-cols-7 bg-slate-50/80">
                                @foreach($days as $day)
                                <div class="border-r border-slate-100 px-4 py-4 text-center last:border-r-0">
                                    <span class="text-[10px] font-black uppercase tracking-[0.25em] {{ $currentDay == $day ? 'text-[#D4AF37]' : 'text-slate-400' }}">
                                        {{ $day }}
                                    </span>
                                    @if($currentDay == $day)
                                    <p class="mt-1 text-[8px] font-bold uppercase tracking-[0.25em] text-[#D4AF37]/70">
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

                                <div class="min-h-[280px] border-r border-slate-100 bg-white p-3 last:border-r-0 {{ $currentDay == $day ? 'bg-[#D4AF37]/[0.02]' : '' }}">
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

                                                <div class="rounded-[1.25rem] border p-4 transition-all duration-200 {{ $isNow ? 'border-[#D4AF37] bg-[#FFFDF5] shadow-[0_10px_30px_rgba(212,175,55,0.12)]' : 'border-slate-200 bg-slate-50/70 hover:border-slate-300 hover:bg-white' }}">
                                                    <div class="mb-3 flex items-start justify-between gap-3">
                                                        <span class="text-[9px] font-black uppercase tracking-[0.25em] text-slate-400">
                                                            {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($sched->end_time)->format('h:i A') }}
                                                        </span>

                                                        @if($isNow)
                                                        <span class="rounded-full bg-[#D4AF37] px-2 py-1 text-[8px] font-black uppercase tracking-[0.25em] text-white">
                                                            Active
                                                        </span>
                                                        @endif
                                                    </div>

                                                    <p class="text-sm font-black uppercase leading-tight text-slate-900">
                                                        {{ $sched->subject_code }}
                                                    </p>

                                                    <p class="mt-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-500">
                                                        {{ $sched->user->name ?? 'Unassigned' }}
                                                    </p>

                                                    @if(auth()->id() == $sched->user_id || auth()->user()->role == 'admin')
                                                    <div class="mt-4 flex items-center justify-between border-t border-slate-200 pt-3">
                                                        <div>
                                                            <p class="text-[8px] font-black uppercase tracking-[0.25em] text-slate-400">
                                                                Session Logs
                                                            </p>
                                                            <p class="text-sm font-black {{ $logCount > 0 ? 'text-slate-900' : 'text-rose-500' }}">
                                                                {{ $logCount }}
                                                            </p>
                                                        </div>

                                                        @if($logCount > 0)
                                                        <a href="{{ route('personnel.export', ['schedule' => $sched->id, 'date' => $targetDate]) }}"
                                                            class="rounded-xl bg-slate-900 p-2 text-[#D4AF37] transition hover:bg-[#D4AF37] hover:text-white"
                                                            title="Download Attendance CSV">
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                            </svg>
                                                        </a>
                                                        @else
                                                        <button type="button"
                                                            onclick="alert('No attendance logs have been recorded during this specific class session timeframe.')"
                                                            class="cursor-not-allowed rounded-xl bg-slate-100 p-2 text-slate-300 transition hover:bg-rose-50 hover:text-rose-400">
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                            </svg>
                                                        </button>
                                                        @endif
                                                    </div>
                                                    @endif
                                                </div>
                                                @empty
                                                <div class="flex min-h-[180px] flex-col items-center justify-center rounded-[1.25rem] border border-dashed border-slate-200 bg-slate-50/60 p-4 text-center">
                                                    <p class="text-[9px] font-black uppercase tracking-[0.25em] text-slate-300">
                                                        No Sessions
                                                    </p>
                                                    <p class="mt-1 text-[10px] text-slate-400">
                                                        No class bookings for this day.
                                                    </p>
                                                </div>
                                                @endforelse
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            height: 8px;
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