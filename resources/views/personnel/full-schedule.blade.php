<x-app-layout>
    @php
    $currentUserId = auth()->id();
    $isAdmin = auth()->user()->role === 'admin';
    @endphp

    <div class="min-h-screen bg-slate-50 px-3 py-6 sm:px-6 sm:py-8 lg:px-10"
        x-data="{ 
            enrollModal: false, 
            rosterModal: false, 
            targetSubject: '', 
            targetInstructor: '',
            canManageRoster: false,
            rosterTab: 'paste',
            currentRoster: [],
            rosterSearch: '',
            allSubjects: @js($labs->flatMap->schedules->pluck('subject_code')->unique()->values()),
            openEnroll(subject = '', teacher = '') {
                this.targetSubject = subject;
                this.targetInstructor = teacher;
                this.enrollModal = true;
            },
            openRoster(subject, rosterData, canManage = false) {
                this.targetSubject = subject;
                this.canManageRoster = Boolean(canManage);
                this.currentRoster = typeof rosterData === 'string' ? JSON.parse(rosterData) : (rosterData || []);
                this.rosterSearch = '';
                this.rosterModal = true;
            }
         }">
        <div class="mx-auto max-w-7xl space-y-6 sm:space-y-8">

            {{-- Header --}}
            <div class="flex flex-col gap-4 rounded-2xl sm:rounded-[2rem] border border-slate-200/80 bg-white p-5 sm:p-6 shadow-sm sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="size-2 rounded-full bg-[#D4AF37] animate-pulse"></span>
                        <p class="text-[9px] sm:text-[10px] font-black uppercase tracking-[0.3em] sm:tracking-[0.4em] text-[#D4AF37]">
                            Master Lab Allocation
                        </p>
                    </div>
                    <h2 class="mt-1 sm:mt-2 text-2xl font-black uppercase tracking-tight text-slate-900 sm:text-3xl md:text-4xl">
                        Schedule <span class="text-[#D4AF37]">& Rosters</span>
                    </h2>
                    <p class="mt-1 text-xs sm:text-sm text-slate-500 font-medium">
                        Monitor active bookings and authorize student enrollments in advance for any day of the week.
                    </p>
                </div>

                {{-- Action Group --}}
                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <a href="{{ route('personnel.index') }}"
                        class="inline-flex items-center justify-center rounded-xl sm:rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 sm:px-5 sm:py-3 text-[10px] font-black uppercase tracking-[0.2em] text-slate-700 transition hover:bg-slate-100 shrink-0">
                        <svg class="mr-2 h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Labs
                    </a>
                </div>
            </div>

            {{-- Flash Notifications --}}
            @if(session('success'))
            <div id="success-banner" class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 shadow-sm flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="size-2 rounded-full bg-emerald-500 animate-ping"></div>
                    <p class="text-xs font-black uppercase tracking-wider text-emerald-900">
                        {{ session('success') }}
                    </p>
                </div>
                <button type="button" onclick="document.getElementById('success-banner').remove()" class="text-emerald-500 hover:text-emerald-700 p-1">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            @endif

            @if(session('error'))
            <div id="error-banner" class="rounded-2xl border border-rose-200 bg-rose-50/80 p-4 shadow-sm flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="size-2 rounded-full bg-rose-500 animate-ping"></div>
                    <p class="text-xs font-black uppercase tracking-wider text-rose-900">
                        {{ session('error') }}
                    </p>
                </div>
                <button type="button" onclick="document.getElementById('error-banner').remove()" class="text-rose-500 hover:text-rose-700 p-1">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            @endif

            {{-- Schedule Matrix --}}
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

                <div class="overflow-hidden rounded-2xl sm:rounded-[2.5rem] border border-slate-200/80 bg-white shadow-sm hover:border-slate-300 transition-all">

                    {{-- Lab Header --}}
                    <div class="flex items-center justify-between border-b border-slate-800 bg-slate-900 px-5 py-4 sm:px-8 sm:py-5">
                        <div class="flex items-center gap-3.5">
                            <div class="size-3 rounded-full bg-[#D4AF37] shadow-[0_0_12px_rgba(212,175,55,0.8)] shrink-0"></div>
                            <div>
                                <h3 class="text-base sm:text-lg font-black uppercase tracking-tight text-white">{{ $labName }}</h3>
                                <p class="text-[9px] sm:text-[10px] font-mono font-bold uppercase tracking-[0.2em] text-slate-400">
                                    Facility Zone #00{{ $lab->id }}
                                </p>
                            </div>
                        </div>

                        <span class="rounded-xl border border-slate-700 bg-slate-800 px-3 py-1.5 text-[9px] sm:text-[10px] font-mono font-black uppercase tracking-widest text-[#D4AF37]">
                            {{ $lab->schedules->count() }} {{ Str::plural('Slot', $lab->schedules->count()) }}
                        </span>
                    </div>

                    {{-- Desktop 7-Day Grid View (XL+) --}}
                    <div class="hidden xl:block overflow-x-auto custom-scrollbar">
                        <div class="min-w-[1100px] border-b border-slate-100">
                            {{-- Day Headers --}}
                            <div class="grid grid-cols-7 bg-slate-50 border-b border-slate-200/60">
                                @foreach($days as $day)
                                <div class="border-r border-slate-200/60 px-3 py-3.5 text-center last:border-r-0">
                                    <span class="text-[10px] font-black uppercase tracking-[0.2em] {{ $currentDay == $day ? 'text-[#D4AF37]' : 'text-slate-500' }}">
                                        {{ $day }}
                                    </span>
                                    @if($currentDay == $day)
                                    <span class="mt-1 inline-block size-1.5 rounded-full bg-[#D4AF37] animate-pulse"></span>
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

                                <div class="min-h-[280px] border-r border-slate-200/60 bg-white p-3 last:border-r-0 {{ $currentDay == $day ? 'bg-[#D4AF37]/[0.02]' : '' }}">
                                    <div class="space-y-3">
                                        @forelse($daySchedules as $sched)
                                        @php
                                        $startTime = \Carbon\Carbon::parse($sched->start_time)->format('H:i:s');
                                        $endTime = \Carbon\Carbon::parse($sched->end_time)->format('H:i:s');
                                        $isNow = ($currentDay == $day) && ($currentTime >= $startTime) && ($currentTime <= $endTime);
                                            $targetDate=$currentDay==$day ? $currentDate : now()->startOfWeek(\Carbon\CarbonInterface::MONDAY)->modify("next {$day}")->toDateString();

                                            $logCount = \App\Models\LabSession::where('lab_id', $lab->id)
                                            ->where('teacher_id', $sched->user_id)
                                            ->whereDate('time_in', $targetDate)
                                            ->whereTime('time_in', '>=', $startTime)
                                            ->whereTime('time_in', '<=', $endTime)
                                                ->count();

                                                $enrolledStudents = \App\Models\SubjectEnrollment::where('subject_code', $sched->subject_code)->get();
                                                $enrolledCount = $enrolledStudents->count();
                                                $isOpenLab = str_contains(strtoupper($sched->subject_code), 'OPEN') || str_contains(strtoupper($sched->subject_code), 'FREE');
                                                $canManageThisClass = ($currentUserId == $sched->user_id) || $isAdmin;
                                                @endphp

                                                <div class="rounded-2xl border p-3.5 transition-all duration-200 {{ $isNow ? 'border-[#D4AF37] bg-[#FFFDF5] shadow-md shadow-[#D4AF37]/10' : 'border-slate-200/80 bg-slate-50/70 hover:border-slate-300 hover:bg-white' }}">

                                                    {{-- Time & Active Tag --}}
                                                    <div class="mb-2 flex items-start justify-between gap-1">
                                                        <span class="text-[8px] font-mono font-bold uppercase tracking-wider text-slate-400">
                                                            {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($sched->end_time)->format('h:i A') }}
                                                        </span>

                                                        @if($isNow)
                                                        <span class="inline-flex items-center gap-1 rounded-md bg-[#D4AF37] px-1.5 py-0.5 text-[7px] font-black uppercase tracking-wider text-slate-950 shadow-xs">
                                                            <span class="size-1 rounded-full bg-slate-950 animate-ping"></span> Live
                                                        </span>
                                                        @endif
                                                    </div>

                                                    {{-- Subject Code & Open Lab Badge --}}
                                                    <div class="flex items-center justify-between gap-1">
                                                        <p class="text-xs font-black uppercase leading-tight text-slate-900">
                                                            {{ $sched->subject_code }}
                                                        </p>
                                                        @if($isOpenLab)
                                                        <span class="px-1.5 py-0.5 rounded text-[7px] font-black uppercase bg-emerald-100 text-emerald-800">
                                                            Open Access
                                                        </span>
                                                        @endif
                                                    </div>

                                                    <p class="mt-1 text-[9px] font-bold uppercase tracking-wider text-slate-500 truncate">
                                                        {{ $sched->user->name ?? 'Unassigned' }}
                                                    </p>

                                                    {{-- Enrolled Students Badge (Only for Structured Classes) --}}
                                                    @if(!$isOpenLab)
                                                    <div class="mt-2.5">
                                                        <button type="button"
                                                            @click="openRoster('{{ $sched->subject_code }}', @js($enrolledStudents), {{ $canManageThisClass ? 'true' : 'false' }})"
                                                            class="inline-flex items-center gap-1.5 rounded-lg bg-white border border-slate-200/80 px-2 py-1 text-[8px] font-black uppercase text-slate-700 hover:border-[#D4AF37] hover:text-[#D4AF37] transition cursor-pointer shadow-2xs">
                                                            <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                                            </svg>
                                                            {{ $enrolledCount }} Enrolled
                                                        </button>
                                                    </div>
                                                    @else
                                                    <div class="mt-2.5">
                                                        <span class="inline-flex items-center gap-1 text-[8px] font-bold uppercase text-slate-400">
                                                            <span class="size-1.5 rounded-full bg-emerald-400"></span> No Enrollment Required
                                                        </span>
                                                    </div>
                                                    @endif

                                                    {{-- Controls --}}
                                                    @if($canManageThisClass)
                                                    <div class="mt-3 flex items-center justify-between border-t border-slate-200/70 pt-2.5">
                                                        <div>
                                                            <p class="text-[7px] font-black uppercase tracking-widest text-slate-400">Attendance</p>
                                                            <p class="text-xs font-black font-mono {{ $logCount > 0 ? 'text-slate-900' : 'text-slate-400' }}">
                                                                {{ $logCount }}
                                                            </p>
                                                        </div>

                                                        <div class="flex items-center gap-1.5">
                                                            {{-- + Enroll Button (Hidden if Open Lab) --}}
                                                            @if(!$isOpenLab)
                                                            <button type="button"
                                                                @click="openEnroll('{{ $sched->subject_code }}', '{{ $sched->user->name ?? '' }}')"
                                                                class="inline-flex items-center gap-1 rounded-lg border border-[#D4AF37]/40 bg-[#D4AF37]/10 px-2.5 py-1 text-[8px] font-black uppercase tracking-wider text-[#D4AF37] transition hover:bg-[#D4AF37] hover:text-slate-950 cursor-pointer shadow-xs active:scale-95">
                                                                + Enroll
                                                            </button>
                                                            @endif

                                                            {{-- CSV Export --}}
                                                            @if($logCount > 0)
                                                            <a href="{{ route('personnel.export', ['schedule' => $sched->id, 'date' => $targetDate]) }}"
                                                                class="rounded-lg bg-slate-900 p-1.5 text-[#D4AF37] transition hover:bg-[#D4AF37] hover:text-slate-950 shadow-xs"
                                                                title="Download Attendance CSV">
                                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                </svg>
                                                            </a>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    @endif
                                                </div>
                                                @empty
                                                <div class="flex min-h-[160px] flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200/80 bg-slate-50/50 p-3 text-center">
                                                    <p class="text-[8px] font-black uppercase tracking-[0.2em] text-slate-300">
                                                        No Class Slots
                                                    </p>
                                                </div>
                                                @endforelse
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Mobile View (< XL) --}}
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
                                <span class="text-[9px] font-mono font-bold uppercase tracking-wider text-slate-400">
                                    {{ $daySchedules->count() }} {{ Str::plural('session', $daySchedules->count()) }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @forelse($daySchedules as $sched)
                                @php
                                $startTime = \Carbon\Carbon::parse($sched->start_time)->format('H:i:s');
                                $endTime = \Carbon\Carbon::parse($sched->end_time)->format('H:i:s');
                                $isNow = ($currentDay == $day) && ($currentTime >= $startTime) && ($currentTime <= $endTime);
                                    $targetDate=$currentDay==$day ? $currentDate : now()->startOfWeek(\Carbon\CarbonInterface::MONDAY)->modify("next {$day}")->toDateString();

                                    $logCount = \App\Models\LabSession::where('lab_id', $lab->id)
                                    ->where('teacher_id', $sched->user_id)
                                    ->whereDate('time_in', $targetDate)
                                    ->whereTime('time_in', '>=', $startTime)
                                    ->whereTime('time_in', '<=', $endTime)
                                        ->count();

                                        $enrolledStudents = \App\Models\SubjectEnrollment::where('subject_code', $sched->subject_code)->get();
                                        $enrolledCount = $enrolledStudents->count();
                                        $isOpenLab = str_contains(strtoupper($sched->subject_code), 'OPEN') || str_contains(strtoupper($sched->subject_code), 'FREE');
                                        $canManageThisClass = ($currentUserId == $sched->user_id) || $isAdmin;
                                        @endphp

                                        <div class="rounded-2xl border p-4 transition-all {{ $isNow ? 'border-[#D4AF37] bg-[#FFFDF5] shadow-md' : 'border-slate-200 bg-slate-50/70' }}">
                                            <div class="mb-2 flex items-center justify-between">
                                                <span class="text-[9px] font-mono font-bold uppercase tracking-[0.2em] text-slate-400">
                                                    {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($sched->end_time)->format('h:i A') }}
                                                </span>
                                                @if($isNow)
                                                <span class="rounded-full bg-[#D4AF37] px-2 py-0.5 text-[8px] font-black uppercase tracking-widest text-slate-950">
                                                    Live Now
                                                </span>
                                                @endif
                                            </div>

                                            <div class="flex items-center justify-between gap-1">
                                                <h4 class="text-sm font-black uppercase text-slate-900 leading-snug">
                                                    {{ $sched->subject_code }}
                                                </h4>
                                                @if($isOpenLab)
                                                <span class="px-1.5 py-0.5 rounded text-[7px] font-black uppercase bg-emerald-100 text-emerald-800">
                                                    Open Access
                                                </span>
                                                @endif
                                            </div>

                                            <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                                                {{ $sched->user->name ?? 'Unassigned' }}
                                            </p>

                                            {{-- Enrolled Students Badge Mobile --}}
                                            @if(!$isOpenLab)
                                            <div class="mt-2.5">
                                                <button type="button"
                                                    @click="openRoster('{{ $sched->subject_code }}', @js($enrolledStudents), {{ $canManageThisClass ? 'true' : 'false' }})"
                                                    class="inline-flex items-center gap-1.5 rounded-lg bg-white border border-slate-200/80 px-2.5 py-1 text-[8px] font-black uppercase text-slate-700 cursor-pointer">
                                                    👥 {{ $enrolledCount }} Enrolled
                                                </button>
                                            </div>
                                            @else
                                            <div class="mt-2.5">
                                                <span class="text-[8px] font-bold uppercase text-slate-400">
                                                    Open Access Station
                                                </span>
                                            </div>
                                            @endif

                                            @if($canManageThisClass)
                                            <div class="mt-3 flex items-center justify-between border-t border-slate-200/80 pt-2.5">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-400">Logs:</span>
                                                    <span class="text-xs font-mono font-black {{ $logCount > 0 ? 'text-slate-900' : 'text-slate-400' }}">
                                                        {{ $logCount }}
                                                    </span>
                                                </div>

                                                <div class="flex items-center gap-1.5">
                                                    @if(!$isOpenLab)
                                                    <button type="button"
                                                        @click="openEnroll('{{ $sched->subject_code }}', '{{ $sched->user->name ?? '' }}')"
                                                        class="inline-flex items-center gap-1 rounded-lg border border-[#D4AF37]/30 bg-[#D4AF37]/10 px-2.5 py-1 text-[9px] font-black uppercase text-[#D4AF37] hover:bg-[#D4AF37] hover:text-slate-950 transition cursor-pointer">
                                                        + Enroll
                                                    </button>
                                                    @endif

                                                    @if($logCount > 0)
                                                    <a href="{{ route('personnel.export', ['schedule' => $sched->id, 'date' => $targetDate]) }}"
                                                        class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-2.5 py-1.5 text-[9px] font-black uppercase text-[#D4AF37] hover:bg-[#D4AF37] hover:text-slate-950 transition">
                                                        Export
                                                    </a>
                                                    @endif
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                        @empty
                                        <div class="col-span-full rounded-2xl border border-dashed border-slate-200 bg-slate-50/50 p-4 text-center">
                                            <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">No scheduled sessions for {{ $day }}.</p>
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

        {{-- ========================================================================= --}}
        {{-- MODAL 1: ADVANCE MASS ENROLLMENT --}}
        {{-- ========================================================================= --}}
        <div x-show="enrollModal"
            x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-md"
            @keydown.escape.window="enrollModal = false">

            <div class="w-full max-w-lg rounded-3xl border border-slate-100 bg-white p-6 sm:p-8 shadow-2xl"
                @click.away="enrollModal = false">

                <div class="flex items-center justify-between mb-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="size-1.5 rounded-full bg-[#D4AF37]"></span>
                            <p class="text-[9px] font-black uppercase tracking-[0.25em] text-[#D4AF37]">
                                Class Roster Authorization
                            </p>
                        </div>
                        <h3 class="text-lg sm:text-xl font-black uppercase tracking-tight text-slate-900 mt-0.5">
                            Authorize Student Access
                        </h3>
                    </div>
                    <button type="button" @click="enrollModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Subject Target Selector / Banner --}}
                <div class="mb-5 rounded-2xl border border-[#D4AF37]/30 bg-[#FFFDF5] p-4">
                    <template x-if="targetSubject">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[8px] font-black uppercase tracking-widest text-slate-400">Target Subject</p>
                                <p class="text-base font-black text-slate-900 uppercase" x-text="targetSubject"></p>
                            </div>
                            <span class="rounded-xl bg-[#D4AF37] px-2.5 py-1 text-[8px] font-black uppercase tracking-widest text-slate-950">
                                Active Subject
                            </span>
                        </div>
                    </template>
                    <template x-if="!targetSubject">
                        <div>
                            <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1.5">Select Subject Code to Authorize</label>
                            <input type="text"
                                x-model="targetSubject"
                                placeholder="E.g. IT-402, CS-101"
                                class="w-full rounded-xl border border-slate-300 bg-white p-2.5 text-xs font-black uppercase text-slate-900 focus:border-[#D4AF37] focus:ring-2 focus:ring-[#D4AF37]/30">
                        </div>
                    </template>
                </div>

                {{-- Tab Switcher --}}
                <div class="flex rounded-xl bg-slate-100 p-1 mb-5">
                    <button type="button"
                        @click="rosterTab = 'paste'"
                        :class="rosterTab === 'paste' ? 'bg-white text-slate-950 shadow-xs font-black' : 'text-slate-500 font-bold'"
                        class="flex-1 rounded-lg py-2 text-[10px] uppercase tracking-wider transition-all">
                        📋 Copy-Paste List
                    </button>
                    <button type="button"
                        @click="rosterTab = 'file'"
                        :class="rosterTab === 'file' ? 'bg-white text-slate-950 shadow-xs font-black' : 'text-slate-500 font-bold'"
                        class="flex-1 rounded-lg py-2 text-[10px] uppercase tracking-wider transition-all">
                        📁 Upload CSV / File
                    </button>
                </div>

                <form action="{{ route('personnel.enroll') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="subject_code" :value="targetSubject">

                    {{-- TAB 1: BULK PASTE --}}
                    <div x-show="rosterTab === 'paste'">
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1.5">
                            Student Email Addresses / IDs
                        </label>
                        <textarea name="emails"
                            rows="5"
                            placeholder="Paste email addresses separated by lines, spaces, or commas:&#10;juan@school.edu&#10;maria@school.edu"
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 p-3.5 text-xs font-mono text-slate-800 focus:border-[#D4AF37] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#D4AF37]/20"></textarea>
                        <p class="mt-1.5 text-[8px] font-bold uppercase text-slate-400">
                            💡 Tip: Students enrolled now can immediately log into terminals on class day.
                        </p>
                    </div>

                    {{-- TAB 2: FILE UPLOAD --}}
                    <div x-show="rosterTab === 'file'" style="display: none;">
                        <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-1.5">
                            Upload Roster Spreadsheet (.csv or .txt)
                        </label>
                        <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/60 p-6 text-center hover:bg-slate-50 transition">
                            <svg class="mx-auto h-8 w-8 text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <input type="file" name="file" accept=".csv, .txt" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[9px] file:font-black file:uppercase file:bg-slate-900 file:text-[#D4AF37] hover:file:bg-[#D4AF37] hover:file:text-slate-950 cursor-pointer">
                        </div>
                    </div>

                    <div class="flex gap-3 pt-3">
                        <button type="button"
                            @click="enrollModal = false"
                            class="flex-1 rounded-2xl border border-slate-200 bg-slate-50 py-3.5 text-[10px] font-black uppercase tracking-widest text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit"
                            class="flex-1 rounded-2xl bg-slate-900 py-3.5 text-[10px] font-black uppercase tracking-widest text-[#D4AF37] hover:bg-[#D4AF37] hover:text-slate-950 transition shadow-lg shadow-slate-900/10 cursor-pointer">
                            Authorize Roster
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- MODAL 2: ROSTER INSPECTOR WITH REVOKE PERMISSION CHECK --}}
        {{-- ========================================================================= --}}
        <div x-show="rosterModal"
            x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-md"
            @keydown.escape.window="rosterModal = false">

            <div class="w-full max-w-lg rounded-3xl border border-slate-100 bg-white p-6 sm:p-8 shadow-2xl"
                @click.away="rosterModal = false">

                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-[9px] font-black uppercase tracking-[0.25em] text-[#D4AF37]">
                            Authorized Roster
                        </p>
                        <h3 class="text-lg sm:text-xl font-black uppercase tracking-tight text-slate-900">
                            <span x-text="targetSubject"></span> Students
                        </h3>
                    </div>
                    <button type="button" @click="rosterModal = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Live Filter in Roster --}}
                <div class="mb-4">
                    <input type="text"
                        x-model="rosterSearch"
                        placeholder="Filter enrolled emails in this subject..."
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 p-2.5 text-xs font-medium text-slate-800 placeholder:text-slate-400 focus:border-[#D4AF37] focus:ring-2 focus:ring-[#D4AF37]/20">
                </div>

                {{-- Enrolled List --}}
                <div class="max-h-[300px] overflow-y-auto space-y-2 pr-1 custom-scrollbar">
                    <template x-if="currentRoster.length === 0">
                        <div class="p-8 text-center border-2 border-dashed border-slate-200 rounded-2xl">
                            <p class="text-xs font-bold uppercase text-slate-400 tracking-wider">
                                No students authorized for <span x-text="targetSubject"></span> yet.
                            </p>
                        </div>
                    </template>

                    <template x-for="item in currentRoster.filter(i => !rosterSearch || i.email.toLowerCase().includes(rosterSearch.toLowerCase()))" :key="item.id">
                        <div class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200/80 rounded-xl hover:bg-slate-100/60 transition">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <div class="size-1.5 rounded-full bg-emerald-500 shrink-0"></div>
                                <span class="text-xs font-mono font-bold text-slate-800 truncate" x-text="item.email"></span>
                            </div>

                            {{-- Revoke Action: Only allowed if instructor owns class or is Admin --}}
                            <template x-if="canManageRoster">
                                <form :action="'/terminal/unenroll/' + item.id" method="POST" onsubmit="return confirm('Remove authorization for this student?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-[9px] font-black uppercase text-rose-500 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-2.5 py-1 rounded-lg transition cursor-pointer">
                                        Revoke
                                    </button>
                                </form>
                            </template>
                            <template x-if="!canManageRoster">
                                <span class="text-[8px] font-black uppercase tracking-wider text-slate-400 bg-slate-100 px-2 py-0.5 rounded-md">
                                    Enrolled
                                </span>
                            </template>
                        </div>
                    </template>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 flex justify-between items-center">
                    {{-- Bulk Clear Roster: Only allowed if instructor owns class or is Admin --}}
                    <div>
                        <template x-if="canManageRoster && currentRoster.length > 0">
                            <form action="{{ route('personnel.clear-roster') }}" method="POST" onsubmit="return confirm('Clear ALL enrolled students for this subject?')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="subject_code" :value="targetSubject">
                                <button type="submit" class="text-[9px] font-black uppercase text-rose-500 hover:bg-rose-50 px-3 py-2 rounded-xl transition cursor-pointer">
                                    Clear Roster
                                </button>
                            </form>
                        </template>
                    </div>

                    <button type="button" @click="rosterModal = false" class="px-5 py-2.5 rounded-xl bg-slate-900 text-white text-[10px] font-black uppercase tracking-widest cursor-pointer">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>