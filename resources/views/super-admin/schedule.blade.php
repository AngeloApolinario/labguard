<x-app-layout>
    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .custom-scroll::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        .custom-scroll::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.6);
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 9999px;
        }

        .custom-scroll::-webkit-scrollbar-thumb:hover {
            background: #D4AF37;
        }

        .custom-scroll {
            scrollbar-width: thin;
            scrollbar-color: #334155 rgba(15, 23, 42, 0.6);
        }
    </style>

    @php
    // Dynamically detects if accessed from super-admin or dashboard
    $routePrefix = request()->is('super-admin*') ? 'super-admin' : 'dashboard';
    @endphp

    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-black text-2xl sm:text-4xl text-slate-800 tracking-tighter uppercase">
                    {{ $lab->name }} <span class="text-[#D4AF37]">Occupancy</span>
                </h2>
                <div class="flex items-center space-x-2 mt-1">
                    <div class="size-2 bg-emerald-500 rounded-full animate-pulse"></div>
                    <p class="text-[9px] sm:text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] sm:tracking-[0.3em]">
                        Super Admin Master Schedule & Event Allocation
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden sm:block bg-white px-5 py-2 rounded-2xl border border-slate-100 shadow-sm text-right">
                    <p class="text-[9px] font-black text-[#D4AF37] uppercase tracking-widest">System Date</p>
                    <p class="text-xs sm:text-sm font-black text-slate-700 uppercase">{{ now()->format('D, M d, Y') }}</p>
                </div>
                <a href="{{ Route::has($routePrefix . '.labs') ? route($routePrefix . '.labs') : route('dashboard.labs') }}" class="px-5 py-2.5 bg-slate-800 text-white text-[10px] font-black uppercase rounded-xl hover:bg-slate-700 active:scale-95 transition-all shadow-sm">
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    {{-- Toast Notifications --}}
    <div class="fixed top-4 right-4 left-4 sm:left-auto sm:top-10 sm:right-10 z-[100] flex flex-col gap-3 sm:w-80 pointer-events-none">
        @if(session('success'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show"
            x-transition
            class="pointer-events-auto bg-slate-900/95 backdrop-blur-xl border border-emerald-500/30 shadow-2xl p-4 rounded-2xl flex items-center space-x-3 text-white">
            <div class="bg-emerald-500/20 border border-emerald-500/30 p-2 rounded-xl shrink-0">
                <svg class="size-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black uppercase tracking-widest text-emerald-400">Entry Verified</p>
                <p class="text-[11px] font-medium text-slate-200 leading-tight mt-0.5 truncate">{{ session('success') }}</p>
            </div>
        </div>
        @endif

        @if(session('error') || $errors->any())
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 8000)" x-show="show"
            x-transition
            class="pointer-events-auto bg-slate-900/95 backdrop-blur-xl border border-rose-500/30 shadow-2xl p-4 rounded-2xl flex items-start space-x-3 text-white">
            <div class="bg-rose-500/20 border border-rose-500/30 p-2 rounded-xl shrink-0">
                <svg class="size-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black uppercase tracking-widest text-rose-400">System Conflict</p>
                <p class="text-[11px] font-medium text-slate-200 leading-tight mt-0.5 break-words">
                    {{ session('error') ?? $errors->first() }}
                </p>
            </div>
        </div>
        @endif
    </div>

    {{-- Main Container with Conflict Detector & Archive Modal State --}}
    <div x-data="{ 
            mode: '{{ old('schedule_type', 'class') }}',
            subjectCode: '{{ old('subject_code', '') }}',
            checkingOverlap: false,
            conflictModal: false,
            archiveModal: false,
            conflict: null,
            confirmOverlap: false,

            async handleFormSubmit(event) {
                if (this.confirmOverlap) {
                    event.target.submit();
                    return;
                }

                this.checkingOverlap = true;
                const form = event.target;
                const formData = new FormData(form);

                try {
                    const checkUrl = '{{ Route::has($routePrefix . '.labs.schedule.checkConflict') ? route($routePrefix . '.labs.schedule.checkConflict', $lab->id) : route('dashboard.labs.schedule.checkConflict', $lab->id) }}';
                    const response = await fetch(checkUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: formData
                    });

                    const data = await response.json();
                    this.checkingOverlap = false;

                    if (data.has_conflict) {
                        this.conflict = data.conflict;
                        this.conflictModal = true;
                    } else {
                        form.submit();
                    }
                } catch (e) {
                    this.checkingOverlap = false;
                    form.submit();
                }
            },

            forceProceed() {
                this.confirmOverlap = true;
                this.conflictModal = false;
                this.$nextTick(() => {
                    document.getElementById('scheduleEntryForm').submit();
                });
            }
        }"
        @keydown.escape.window="conflictModal = false; archiveModal = false"
        class="py-6 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto min-h-screen bg-[#F8FAFC]">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-10 items-start">

            {{-- 1. ENTRY FORM --}}
            <div class="bg-white p-6 sm:p-8 rounded-3xl sm:rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/50 sticky top-6">

                {{-- Mode Switcher Tabs --}}
                <div class="flex p-1 bg-slate-100 rounded-2xl mb-6 border border-slate-200/70">
                    <button type="button"
                        @click="mode = 'class'"
                        :class="mode === 'class' ? 'bg-white text-slate-900 shadow-xs font-black' : 'text-slate-500 font-bold'"
                        class="flex-1 py-2.5 rounded-xl text-[9px] uppercase tracking-wider transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <span>Class Slot</span>
                    </button>
                    <button type="button"
                        @click="mode = 'event'"
                        :class="mode === 'event' ? 'bg-slate-900 text-[#D4AF37] shadow-sm font-black' : 'text-slate-500 font-bold'"
                        class="flex-1 py-2.5 rounded-xl text-[9px] uppercase tracking-wider transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <span>One-Time Event</span>
                        <span class="size-1.5 rounded-full bg-[#D4AF37] animate-pulse"></span>
                    </button>
                </div>

                <form id="scheduleEntryForm"
                    action="{{ Route::has($routePrefix . '.labs.schedule.store') ? route($routePrefix . '.labs.schedule.store', $lab->id) : route('dashboard.labs.schedule.store', $lab->id) }}"
                    method="POST"
                    @submit.prevent="handleFormSubmit"
                    class="space-y-4">
                    @csrf
                    <input type="hidden" name="schedule_type" :value="mode">
                    <input type="hidden" name="confirm_overlap" :value="confirmOverlap ? 1 : 0">

                    {{-- Class: Instructor Selection --}}
                    <div x-show="mode === 'class'">
                        <label class="text-[8px] font-black text-slate-400 uppercase ml-2 mb-1 block">Authorized Teacher</label>
                        <select name="user_id" class="w-full rounded-2xl border-slate-200/80 bg-slate-50 text-xs sm:text-sm py-3 px-4 focus:ring-[#D4AF37] focus:border-[#D4AF37] transition-all">
                            <option value="" disabled selected>Select Instructor...</option>
                            @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ old('user_id') == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Event: Guest Speaker --}}
                    <div x-show="mode === 'event'" style="display: none;">
                        <label class="text-[8px] font-black text-slate-400 uppercase ml-2 mb-1 block">Speaker / Host (No Teacher Required)</label>
                        <input type="text"
                            name="speaker_name"
                            value="{{ old('speaker_name') }}"
                            placeholder="E.g. Engr. Maria Santos (Keynote Speaker)"
                            class="w-full rounded-2xl border-slate-200/80 bg-slate-50 text-xs sm:text-sm py-3 px-4 font-bold text-slate-800 focus:ring-[#D4AF37] focus:border-[#D4AF37] transition-all">
                    </div>

                    {{-- Class: Day Selection --}}
                    <div x-show="mode === 'class'">
                        <label class="text-[8px] font-black text-slate-400 uppercase ml-2 mb-1 block">Recurring Day</label>
                        <select name="day" class="w-full rounded-2xl border-slate-200/80 bg-slate-50 text-xs sm:text-sm py-3 px-4 focus:ring-[#D4AF37] focus:border-[#D4AF37] transition-all">
                            @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day)
                            <option value="{{ $day }}" {{ old('day') == $day ? 'selected' : '' }}>Every {{ $day }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Event: Calendar Date Input --}}
                    <div x-show="mode === 'event'" style="display: none;">
                        <label class="text-[8px] font-black text-slate-400 uppercase ml-2 mb-1 block">Event Date (Masks Class on this Day)</label>
                        <input type="date"
                            name="event_date"
                            value="{{ old('event_date', now()->toDateString()) }}"
                            min="{{ now()->toDateString() }}"
                            class="w-full rounded-2xl border-slate-200/80 bg-slate-50 text-xs sm:text-sm py-3 px-4 font-mono font-bold text-slate-800 focus:ring-[#D4AF37] focus:border-[#D4AF37] transition-all [color-scheme:light]">
                    </div>

                    {{-- Subject / Title Input --}}
                    <div>
                        <label class="text-[8px] font-black text-slate-400 uppercase ml-2 mb-1 block" x-text="mode === 'class' ? 'Subject Code' : 'Event Topic / Seminar Title'"></label>
                        <input type="text"
                            name="subject_code"
                            x-model="subjectCode"
                            :placeholder="mode === 'class' ? 'E.g. IT-402' : 'E.g. SEMINAR: CYBERSECURITY'"
                            required
                            class="w-full rounded-2xl border-slate-200/80 bg-slate-50 text-xs sm:text-sm py-3 px-4 uppercase font-bold focus:ring-[#D4AF37] focus:border-[#D4AF37] transition-all">

                        <div class="flex items-center gap-1.5 mt-2 ml-1">
                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-wider">Presets:</span>
                            <template x-if="mode === 'class'">
                                <div class="flex items-center gap-1.5">
                                    <button type="button" @click="subjectCode = 'OPEN LAB'" class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-600 text-[8px] font-black uppercase tracking-wider hover:bg-[#D4AF37]/20 hover:text-[#D4AF37] border border-slate-200">OPEN LAB</button>
                                    <button type="button" @click="subjectCode = 'FREE LAB'" class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-600 text-[8px] font-black uppercase tracking-wider hover:bg-[#D4AF37]/20 hover:text-[#D4AF37] border border-slate-200">FREE LAB</button>
                                </div>
                            </template>
                            <template x-if="mode === 'event'">
                                <div class="flex items-center gap-1.5">
                                    <button type="button" @click="subjectCode = 'WORKSHOP: AI 101'" class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-600 text-[8px] font-black uppercase tracking-wider hover:bg-purple-100 hover:text-purple-700 border border-slate-200">WORKSHOP</button>
                                    <button type="button" @click="subjectCode = 'HACKATHON'" class="px-2 py-0.5 rounded-lg bg-slate-100 text-slate-600 text-[8px] font-black uppercase tracking-wider hover:bg-purple-100 hover:text-purple-700 border border-slate-200">HACKATHON</button>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Time Window --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[8px] font-black text-slate-400 uppercase ml-2 mb-1 block">Start Time</label>
                            <input type="time" name="start_time" value="{{ old('start_time') }}" required class="w-full rounded-2xl border-slate-200/80 bg-slate-50 text-xs sm:text-sm py-3 px-3 sm:px-4 focus:ring-[#D4AF37] focus:border-[#D4AF37] transition-all">
                        </div>
                        <div>
                            <label class="text-[8px] font-black text-slate-400 uppercase ml-2 mb-1 block">End Time</label>
                            <input type="time" name="end_time" value="{{ old('end_time') }}" required class="w-full rounded-2xl border-slate-200/80 bg-slate-50 text-xs sm:text-sm py-3 px-3 sm:px-4 focus:ring-[#D4AF37] focus:border-[#D4AF37] transition-all">
                        </div>
                    </div>

                    <button type="submit"
                        :disabled="checkingOverlap"
                        class="w-full py-4 mt-4 text-white text-[10px] font-black uppercase rounded-2xl shadow-lg transition-all active:scale-[0.98] cursor-pointer flex items-center justify-center gap-2"
                        :class="mode === 'class' ? 'bg-gradient-to-r from-[#D4AF37] to-amber-600 hover:from-amber-500 shadow-[#D4AF37]/20' : 'bg-gradient-to-r from-slate-900 to-indigo-950 hover:bg-slate-800 shadow-slate-900/20'">
                        <span x-show="checkingOverlap" class="size-3 rounded-full border-2 border-white/30 border-t-white animate-spin"></span>
                        <span x-text="checkingOverlap ? 'Checking Conflicts...' : (mode === 'class' ? 'Establish Class Slot' : 'Confirm One-Time Event')"></span>
                    </button>
                </form>

                {{-- ========================================================================= --}}
                {{-- CONFLICT OVERRIDE MODAL --}}
                {{-- ========================================================================= --}}
                <div x-cloak x-show="conflictModal" class="fixed inset-0 z-[150] overflow-y-auto" role="dialog" aria-modal="true">
                    <div x-show="conflictModal" x-transition class="fixed inset-0 bg-slate-950/80 backdrop-blur-md"></div>
                    <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                        <div x-show="conflictModal" x-transition class="relative transform overflow-hidden rounded-[2.5rem] bg-slate-900 border border-amber-500/40 shadow-2xl p-6 sm:p-8 text-left text-white max-w-md w-full">
                            <div class="flex items-center gap-3.5 mb-4">
                                <div class="size-12 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0">
                                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base sm:text-lg font-black uppercase text-white">Schedule Overlap Detected</h3>
                                    <p class="text-[9px] font-mono uppercase text-amber-400">Class Will Be Temporarily Masked</p>
                                </div>
                            </div>

                            <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700/60 mb-4 space-y-2">
                                <span class="text-[8px] font-black uppercase text-slate-400">Current Occupant</span>
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-black text-[#D4AF37]" x-text="conflict?.subject_code"></h4>
                                    <span class="text-xs font-mono font-bold text-slate-300" x-text="conflict?.time_window"></span>
                                </div>
                                <p class="text-[10px] text-slate-400" x-text="'Instructor / Host: ' + conflict?.host_name"></p>
                            </div>

                            <p class="text-xs text-slate-300 leading-relaxed mb-6 font-medium">
                                This event will overlap with <strong class="text-white" x-text="conflict?.subject_code"></strong>. The regular schedule will <strong class="text-amber-400">disappear for this date only</strong>, and return automatically next week. Are you sure you want to proceed?
                            </p>

                            <div class="flex gap-2.5">
                                <button type="button" @click="forceProceed()" class="flex-1 py-3 bg-gradient-to-r from-amber-500 to-[#D4AF37] text-slate-950 font-black text-xs uppercase rounded-xl active:scale-95 transition">
                                    Confirm & Override
                                </button>
                                <button type="button" @click="conflictModal = false" class="flex-1 py-3 bg-slate-800 text-slate-300 font-bold text-xs uppercase rounded-xl border border-slate-700 hover:bg-slate-700 transition">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- 2. ACTIVE ROSTER & ATTENDANCE EXPORT DISPLAY --}}
            <div class="lg:col-span-2 bg-slate-900 rounded-3xl sm:rounded-[2.5rem] p-6 sm:p-8 shadow-2xl flex flex-col border border-slate-800/80 overflow-hidden"
                x-data="{ 
                    activeDay: new URLSearchParams(window.location.search).get('day') || 'All',
                    setDay(day) {
                        this.activeDay = day;
                        const url = new URL(window.location);
                        url.searchParams.set('day', day);
                        window.history.replaceState({}, '', url);
                    }
                 }">

                {{-- Header & Archive Modal Trigger --}}
                <div class="mb-6 pb-6 border-b border-slate-800/80 flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-white font-black uppercase tracking-tighter text-lg sm:text-xl">Active <span class="text-[#D4AF37]">Roster</span></h4>
                            <p class="text-[8px] font-bold text-slate-500 uppercase tracking-widest mt-0.5">Filtering by: <span class="text-[#D4AF37]" x-text="activeDay"></span></p>
                        </div>

                        {{-- Event Archive & Past Attendance Trigger --}}
                        <button type="button"
                            @click="archiveModal = true"
                            class="px-4 py-2 bg-[#D4AF37]/10 hover:bg-[#D4AF37] text-[#D4AF37] hover:text-slate-950 border border-[#D4AF37]/30 rounded-xl text-[9px] font-black uppercase tracking-wider transition-all flex items-center gap-1.5 shadow-sm active:scale-95 cursor-pointer">
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                            </svg>
                            <span>Past Events & Attendance ({{ count($pastEvents ?? []) }})</span>
                        </button>
                    </div>

                    {{-- Day Filter Chips --}}
                    <div class="w-full overflow-x-auto no-scrollbar py-1">
                        <div class="flex items-center gap-1.5 min-w-max">
                            <template x-for="day in ['All', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']" :key="day">
                                <button
                                    @click="setDay(day)"
                                    :class="activeDay === day ? 'bg-[#D4AF37] text-slate-950 font-black shadow-lg shadow-[#D4AF37]/20' : 'bg-slate-800 text-slate-400 hover:bg-slate-700 border border-slate-700/50'"
                                    class="px-3.5 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all shrink-0 cursor-pointer"
                                    x-text="day">
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Desktop View --}}
                <div class="hidden sm:block overflow-y-auto max-h-[520px] pr-2 custom-scroll">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 bg-slate-900 z-10">
                            <tr class="text-[9px] font-black text-slate-500 uppercase tracking-[0.3em] border-b border-slate-800">
                                <th class="pb-3 bg-slate-900">Host & Subject / Event</th>
                                <th class="pb-3 bg-slate-900">Schedule</th>
                                <th class="pb-3 bg-slate-900">Time Window</th>
                                <th class="pb-3 text-right bg-slate-900">Action / Attendance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/80">
                            @forelse($schedules as $entry)
                            @php
                            $isEvent = (bool)($entry->is_event ?? false);
                            $isOpenOrFree = str_contains(strtoupper($entry->subject_code), 'OPEN') || str_contains(strtoupper($entry->subject_code), 'FREE');
                            @endphp
                            <tr class="group hover:bg-white/[0.02] transition-colors"
                                x-show="activeDay === 'All' || activeDay === '{{ $entry->day }}'"
                                x-transition>

                                <td class="py-4 font-black text-white text-sm uppercase">
                                    <div class="flex flex-col">
                                        @if($isEvent)
                                        <div class="flex items-center gap-1.5 text-purple-300">
                                            <svg class="size-3.5 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
                                            </svg>
                                            <span>{{ $entry->speaker_name ?? 'Guest Speaker' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="px-2 py-0.5 rounded text-[7px] font-black uppercase bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                                Event (Masking Slot)
                                            </span>
                                            <span class="text-[9px] font-mono text-[#D4AF37]">{{ $entry->subject_code }}</span>
                                        </div>
                                        @else
                                        <span>{{ $entry->user->name ?? 'Instructor' }}</span>
                                        @if($isOpenOrFree)
                                        <span class="inline-flex items-center gap-1.5 text-[8px] font-black uppercase tracking-wider px-2 py-0.5 bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 rounded-md mt-1 w-max shadow-2xs">
                                            <span class="size-1 rounded-full bg-emerald-400 animate-ping"></span>
                                            {{ $entry->subject_code }} • Open Access
                                        </span>
                                        @else
                                        <span class="text-[9px] text-[#D4AF37] tracking-widest italic font-bold uppercase mt-0.5">{{ $entry->subject_code }}</span>
                                        @endif
                                        @endif
                                    </div>
                                </td>

                                <td class="py-4 font-bold text-xs uppercase tracking-widest">
                                    @if($isEvent && $entry->event_date)
                                    <span class="text-[#D4AF37] font-mono block">{{ \Carbon\Carbon::parse($entry->event_date)->format('M d, Y') }}</span>
                                    <span class="text-[8px] text-slate-500">{{ $entry->day }}</span>
                                    @else
                                    <span :class="activeDay !== 'All' ? 'text-[#D4AF37]' : 'text-slate-400'">{{ $entry->day }}</span>
                                    @endif
                                </td>

                                <td class="py-4 font-mono font-bold text-white text-xs">
                                    <span class="px-3 py-1.5 bg-slate-800/90 rounded-lg border border-slate-700/60 inline-block shadow-inner">
                                        {{ date('h:i A', strtotime($entry->start_time)) }} — {{ date('h:i A', strtotime($entry->end_time)) }}
                                    </span>
                                </td>

                                {{-- Action / Event Attendance Export --}}
                                <td class="py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($isEvent)
                                        <a href="{{ Route::has($routePrefix . '.labs.schedule.exportEvent') ? route($routePrefix . '.labs.schedule.exportEvent', $entry->id) : route('dashboard.labs.schedule.exportEvent', $entry->id) }}"
                                            title="Download Event Attendance CSV"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-[#D4AF37] to-amber-600 hover:brightness-110 text-slate-950 rounded-xl text-[9px] font-black uppercase tracking-wider shadow-sm transition active:scale-95">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                            </svg>
                                            <span>Attendance ({{ $entry->attendees_count ?? 0 }})</span>
                                        </a>
                                        @endif

                                        <form action="{{ Route::has($routePrefix . '.labs.schedule.destroy') ? route($routePrefix . '.labs.schedule.destroy', $entry->id) : route('dashboard.labs.schedule.destroy', $entry->id) }}" method="POST" onsubmit="return confirm('Revoke this slot?')">
                                            @csrf @method('DELETE')
                                            <input type="hidden" name="day" :value="activeDay">
                                            <button class="text-rose-400 hover:text-rose-300 text-[9px] font-black uppercase tracking-widest border border-rose-500/20 px-3.5 py-1.5 rounded-xl hover:bg-rose-500/10 active:scale-95 transition-all cursor-pointer">
                                                Revoke
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-16 text-center text-slate-600 font-black uppercase tracking-widest text-[10px]">No active schedules found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Mobile View --}}
                <div class="block sm:hidden overflow-y-auto max-h-[480px] space-y-3 pr-1 custom-scroll">
                    @forelse($schedules as $entry)
                    @php
                    $isEvent = (bool)($entry->is_event ?? false);
                    @endphp
                    <div class="p-4 bg-slate-800/60 rounded-2xl border border-slate-700/50 space-y-3"
                        x-show="activeDay === 'All' || activeDay === '{{ $entry->day }}'">
                        <div class="flex items-start justify-between">
                            <div>
                                @if($isEvent)
                                <div class="flex items-center gap-1.5 text-purple-300 text-sm font-black">
                                    <span>🎙️ {{ $entry->speaker_name ?? 'Guest Speaker' }}</span>
                                </div>
                                <p class="text-[9px] text-[#D4AF37] font-bold uppercase mt-0.5">{{ $entry->subject_code }}</p>
                                @else
                                <h5 class="text-white font-black text-sm uppercase">{{ $entry->user->name ?? 'Instructor' }}</h5>
                                <p class="text-[9px] text-[#D4AF37] font-bold uppercase mt-0.5">{{ $entry->subject_code }}</p>
                                @endif
                            </div>

                            <div class="text-right">
                                @if($isEvent && $entry->event_date)
                                <span class="px-2.5 py-1 bg-purple-500/20 text-purple-300 border border-purple-500/30 rounded-lg text-[9px] font-black uppercase tracking-wider block">
                                    {{ \Carbon\Carbon::parse($entry->event_date)->format('M d') }}
                                </span>
                                @else
                                <span class="px-2.5 py-1 bg-slate-700/80 rounded-lg text-[9px] font-black uppercase text-slate-300 tracking-wider block">
                                    {{ $entry->day }}
                                </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-700/40">
                            <span class="text-slate-300 font-mono text-xs font-bold">
                                {{ date('h:i A', strtotime($entry->start_time)) }} - {{ date('h:i A', strtotime($entry->end_time)) }}
                            </span>

                            <div class="flex items-center gap-1.5">
                                @if($isEvent)
                                <a href="{{ Route::has($routePrefix . '.labs.schedule.exportEvent') ? route($routePrefix . '.labs.schedule.exportEvent', $entry->id) : route('dashboard.labs.schedule.exportEvent', $entry->id) }}" class="px-2.5 py-1 bg-[#D4AF37] text-slate-950 text-[8px] font-black uppercase rounded-lg">
                                    CSV ({{ $entry->attendees_count ?? 0 }})
                                </a>
                                @endif
                                <form action="{{ Route::has($routePrefix . '.labs.schedule.destroy') ? route($routePrefix . '.labs.schedule.destroy', $entry->id) : route('dashboard.labs.schedule.destroy', $entry->id) }}" method="POST" onsubmit="return confirm('Revoke this slot?')">
                                    @csrf @method('DELETE')
                                    <input type="hidden" name="day" :value="activeDay">
                                    <button class="text-rose-400 text-[8px] font-black uppercase border border-rose-500/30 px-2.5 py-1 rounded-lg">
                                        Revoke
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="py-12 text-center text-slate-600 font-black uppercase tracking-widest text-[10px]">
                        No scheduled slots found
                    </div>
                    @endforelse
                </div>

            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- 3. PAST EVENTS ARCHIVE & ATTENDANCE DOWNLOAD MODAL --}}
        {{-- ========================================================================= --}}
        <div x-cloak x-show="archiveModal" class="fixed inset-0 z-[160] overflow-y-auto" role="dialog" aria-modal="true">
            <div x-show="archiveModal" x-transition class="fixed inset-0 bg-slate-950/80 backdrop-blur-md" @click="archiveModal = false"></div>
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                <div x-show="archiveModal" x-transition class="relative transform overflow-hidden rounded-[2.5rem] bg-slate-900 border border-slate-700 shadow-2xl p-6 sm:p-8 text-left text-white max-w-2xl w-full">

                    <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-5">
                        <div>
                            <h3 class="text-lg font-black uppercase tracking-tight text-white">Event Attendance Archive</h3>
                            <p class="text-[9px] font-mono uppercase tracking-widest text-slate-400">Download attendance reports for completed events</p>
                        </div>
                        <button type="button" @click="archiveModal = false" class="text-slate-400 hover:text-white p-1">✕</button>
                    </div>

                    <div class="max-h-96 overflow-y-auto space-y-3 custom-scroll pr-1">
                        @forelse($pastEvents ?? [] as $pe)
                        <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700 flex items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-black text-white uppercase">{{ $pe->subject_code }}</h4>
                                    <span class="px-2 py-0.5 rounded text-[8px] font-bold uppercase bg-slate-700 text-slate-300">
                                        {{ \Carbon\Carbon::parse($pe->event_date)->format('M d, Y') }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 mt-1">Speaker: <strong class="text-purple-300">{{ $pe->speaker_name ?? 'Guest Speaker' }}</strong></p>
                                <p class="text-[9px] font-mono text-slate-500 mt-0.5">{{ date('h:i A', strtotime($pe->start_time)) }} — {{ date('h:i A', strtotime($pe->end_time)) }}</p>
                            </div>

                            <a href="{{ Route::has($routePrefix . '.labs.schedule.exportEvent') ? route($routePrefix . '.labs.schedule.exportEvent', $pe->id) : route('dashboard.labs.schedule.exportEvent', $pe->id) }}"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-[#D4AF37] to-amber-600 hover:brightness-110 text-slate-950 font-black text-[9px] uppercase tracking-wider rounded-xl shadow-sm shrink-0 active:scale-95 transition">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                <span>Export CSV ({{ $pe->attendees_count ?? 0 }})</span>
                            </a>
                        </div>
                        @empty
                        <div class="py-12 text-center text-slate-500 text-xs font-bold uppercase tracking-wider">
                            No past events recorded for this laboratory yet.
                        </div>
                        @endforelse
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-800 text-right">
                        <button type="button" @click="archiveModal = false" class="px-5 py-2 bg-slate-800 hover:bg-slate-700 text-white text-[10px] font-black uppercase rounded-xl">
                            Close Archive
                        </button>
                    </div>

                </div>
            </div>
        </div>

    </div>
</x-app-layout>