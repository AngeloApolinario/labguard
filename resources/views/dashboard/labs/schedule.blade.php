<x-app-layout>
    {{-- Convert server validation errors to your application toast --}}
    @if ($errors->any())
    @php
    session()->now('toast', [
    'type' => 'danger',
    'title' => 'Input Error',
    'message' => $errors->first()
    ]);
    @endphp
    @endif

    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .custom-scroll::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        .custom-scroll::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.4);
            border-radius: 9999px;
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
            scrollbar-color: #334155 rgba(15, 23, 42, 0.4);
        }
    </style>

    {{-- TOP COMMAND BAR --}}
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[9px] font-black uppercase tracking-[0.3em] text-[#D4AF37]">Facility Space Allocation</span>
                </div>
                <h2 class="font-black text-2xl sm:text-4xl text-slate-900 tracking-tight uppercase">
                    {{ $lab->name }} <span class="text-[#D4AF37]">Occupancy</span>
                </h2>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-3 bg-white px-4 py-2.5 rounded-2xl border border-slate-200/80 shadow-xs">
                    <div class="size-2 rounded-full bg-[#D4AF37]"></div>
                    <div class="text-left">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">System Calendar</p>
                        <p class="text-xs font-black text-slate-800 font-mono">{{ now()->format('D, M d, Y') }}</p>
                    </div>
                </div>
                <a href="{{ route('dashboard.labs') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-[10px] font-black uppercase tracking-wider rounded-xl transition-all shadow-md active:scale-95 cursor-pointer">
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span>Back</span>
                </a>
            </div>
        </div>
    </x-slot>

    {{-- MAIN INTERFACE WRAPPER --}}
    <div x-data="{ 
            mode: '{{ old('schedule_type', 'class') }}',
            subjectCode: '{{ old('subject_code', '') }}',
            eventDate: '{{ old('event_date', now()->toDateString()) }}',
            checkingOverlap: false,
            conflictModal: false,
            archiveModal: false,
            purgeModal: false,
            errorModal: false,
            errorTitle: '',
            errorMessage: '',
            purgeType: 'day',
            conflict: null,
            confirmOverlap: false,
            activeDay: new URLSearchParams(window.location.search).get('day') || 'All',

            init() {
                this.$watch('conflictModal || archiveModal || purgeModal || errorModal', value => {
                    document.body.classList.toggle('overflow-hidden', value);
                });
            },

            showError(title, message) {
                this.errorTitle = title;
                this.errorMessage = message;
                this.errorModal = true;
            },

            get calculatedDay() {
                if (!this.eventDate) return 'Monday';
                const parts = this.eventDate.split('-');
                if (parts.length !== 3) return 'Monday';
                const d = new Date(parts[0], parts[1] - 1, parts[2]);
                const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                return days[d.getDay()] || 'Monday';
            },

            setDay(day) {
                this.activeDay = day;
                const url = new URL(window.location);
                url.searchParams.set('day', day);
                window.history.replaceState({}, '', url);
            },

            openPurgeModal(type) {
                this.purgeType = type;
                this.purgeModal = true;
            },

            async handleFormSubmit(event) {
                const form = event.target;

                // 1. Client-side Validation Checks
                if (this.mode === 'class') {
                    const teacherSelect = form.querySelector('select[name=\'user_id\']');
                    if (!teacherSelect || !teacherSelect.value) {
                        this.showError('Instructor Required', 'Please assign an authorized instructor for this regular class slot.');
                        return;
                    }
                } else if (this.mode === 'event') {
                    const speakerInput = form.querySelector('input[name=\'speaker_name\']');
                    if (!speakerInput || !speakerInput.value.trim()) {
                        this.showError('Speaker / Host Required', 'Please provide a speaker, host, or organization name for this event.');
                        return;
                    }
                    const dateInput = form.querySelector('input[name=\'event_date\']');
                    if (!dateInput || !dateInput.value) {
                        this.showError('Event Date Required', 'Please select a valid date for this event.');
                        return;
                    }
                }

                if (!this.subjectCode.trim()) {
                    this.showError('Subject / Title Required', 'Please enter a course code or event title.');
                    return;
                }

                const startTime = form.querySelector('input[name=\'start_time\']')?.value;
                const endTime = form.querySelector('input[name=\'end_time\']')?.value;
                if (!startTime || !endTime) {
                    this.showError('Time Window Required', 'Please specify both a start time and an end time.');
                    return;
                }
                if (startTime >= endTime) {
                    this.showError('Invalid Time Range', 'End time must be later than start time.');
                    return;
                }

                // 2. If overlap was already confirmed for an Event, proceed directly
                if (this.confirmOverlap && this.mode === 'event') {
                    form.submit();
                    return;
                }

                // 3. Conflict Check via Backend
                this.checkingOverlap = true;
                const formData = new FormData(form);

                try {
                    const response = await fetch('{{ route('dashboard.labs.schedule.checkConflict', $lab->id) }}', {
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
                // Only allow force-proceeding if it is an EVENT
                if (this.mode === 'event') {
                    this.confirmOverlap = true;
                    this.conflictModal = false;
                    this.$nextTick(() => {
                        document.getElementById('scheduleEntryForm').submit();
                    });
                }
            }
        }"
        @keydown.escape.window="conflictModal = false; archiveModal = false; purgeModal = false; errorModal = false"
        class="relative py-6 sm:py-10 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto min-h-screen bg-[#F8FAFC]">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-8 items-start">

            {{-- 1. ENTRY FORM --}}
            <div class="bg-white p-6 sm:p-7 rounded-[2rem] border border-slate-200/80 shadow-xl shadow-slate-200/50 sticky top-6">

                {{-- Mode Switcher --}}
                <div class="flex p-1 bg-slate-100 rounded-2xl mb-6 border border-slate-200/80">
                    <button type="button"
                        @click="mode = 'class'; confirmOverlap = false;"
                        :class="mode === 'class' ? 'bg-white text-slate-900 shadow-sm font-black' : 'text-slate-500 font-bold hover:text-slate-900'"
                        class="flex-1 py-2.5 rounded-xl text-[10px] uppercase tracking-wider transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <span>Class Slot</span>
                    </button>
                    <button type="button"
                        @click="mode = 'event'; confirmOverlap = false;"
                        :class="mode === 'event' ? 'bg-slate-900 text-[#D4AF37] shadow-md font-black' : 'text-slate-500 font-bold hover:text-slate-900'"
                        class="flex-1 py-2.5 rounded-xl text-[10px] uppercase tracking-wider transition-all flex items-center justify-center gap-1.5 cursor-pointer">
                        <span>One-Time Event</span>
                        <span class="size-1.5 rounded-full bg-[#D4AF37] animate-pulse"></span>
                    </button>
                </div>

                <form id="scheduleEntryForm" action="{{ route('dashboard.labs.schedule.store', $lab->id) }}" method="POST" @submit.prevent="handleFormSubmit" class="space-y-4">
                    @csrf
                    <input type="hidden" name="schedule_type" :value="mode">
                    <input type="hidden" name="confirm_overlap" :value="confirmOverlap ? 1 : 0">
                    <input type="hidden" name="is_event" :value="mode === 'event' ? 1 : 0">

                    {{-- Class: Instructor Selection --}}
                    <div x-show="mode === 'class'" x-transition>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-2 mb-1.5 block">Instructor Assigned <span class="text-rose-500">*</span></label>
                        <select name="user_id" :disabled="mode === 'event'" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-xs sm:text-sm py-3 px-4 font-bold text-slate-800 focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] transition-all">
                            <option value="" disabled selected>Select Instructor...</option>
                            @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ old('user_id') == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Event: Guest Speaker / Host --}}
                    <div x-show="mode === 'event'" x-transition style="display: none;">
                        <label class="text-[9px] font-black text-[#D4AF37] uppercase tracking-widest ml-2 mb-1.5 block">Guest Speaker / Host <span class="text-rose-500">*</span></label>
                        <input type="text"
                            name="speaker_name"
                            :disabled="mode === 'class'"
                            value="{{ old('speaker_name') }}"
                            placeholder="E.g. Engr. Maria Santos (Keynote)"
                            class="w-full rounded-2xl border-amber-200/80 bg-amber-50/20 text-xs sm:text-sm py-3 px-4 font-bold text-slate-900 focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] transition-all">
                    </div>

                    {{-- Class: Day Selection --}}
                    <div x-show="mode === 'class'" x-transition>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-2 mb-1.5 block">Weekly Recurring Day</label>
                        <select name="day" :disabled="mode === 'event'" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-xs sm:text-sm py-3 px-4 font-bold text-slate-800 focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] transition-all">
                            @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day)
                            <option value="{{ $day }}" {{ old('day') == $day ? 'selected' : '' }}>Every {{ $day }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Event: Event Date Selection --}}
                    <div x-show="mode === 'event'" x-transition style="display: none;">
                        <label class="text-[9px] font-black text-[#D4AF37] uppercase tracking-widest ml-2 mb-1.5 block">Event Date (Masks Regular Classes) <span class="text-rose-500">*</span></label>
                        <input type="date"
                            name="event_date"
                            x-model="eventDate"
                            :disabled="mode === 'class'"
                            min="{{ now()->toDateString() }}"
                            class="w-full rounded-2xl border-amber-200/80 bg-amber-50/20 text-xs sm:text-sm py-3 px-4 font-mono font-bold text-slate-900 focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] transition-all [color-scheme:light]">

                        <input type="hidden" name="day" :value="calculatedDay" :disabled="mode !== 'event'">
                    </div>

                    {{-- Subject / Title Input --}}
                    <div>
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-2 mb-1.5 block" x-text="mode === 'class' ? 'Course / Subject Code *' : 'Event Title / Topic *'"></label>
                        <input type="text"
                            name="subject_code"
                            x-model="subjectCode"
                            :placeholder="mode === 'class' ? 'E.g. IT-402' : 'E.g. SEMINAR: CYBER DEFENSE'"
                            required
                            class="w-full rounded-2xl border-slate-200 bg-slate-50 text-xs sm:text-sm py-3 px-4 font-black uppercase text-slate-900 focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] transition-all">

                        {{-- Quick Presets --}}
                        <div class="flex items-center gap-1.5 mt-2.5 ml-1">
                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-wider">Presets:</span>
                            <template x-if="mode === 'class'">
                                <div class="flex items-center gap-1.5">
                                    <button type="button" @click="subjectCode = 'OPEN LAB'" class="px-2 py-0.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[8px] font-black uppercase tracking-wider transition cursor-pointer">OPEN LAB</button>
                                    <button type="button" @click="subjectCode = 'FREE LAB'" class="px-2 py-0.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[8px] font-black uppercase tracking-wider transition cursor-pointer">FREE LAB</button>
                                </div>
                            </template>
                            <template x-if="mode === 'event'">
                                <div class="flex items-center gap-1.5">
                                    <button type="button" @click="subjectCode = 'WORKSHOP: AI'" class="px-2 py-0.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 text-[8px] font-black uppercase tracking-wider transition cursor-pointer">WORKSHOP</button>
                                    <button type="button" @click="subjectCode = 'HACKATHON'" class="px-2 py-0.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 text-[8px] font-black uppercase tracking-wider transition cursor-pointer">HACKATHON</button>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Time Window --}}
                    <div class="grid grid-cols-2 gap-3.5">
                        <div>
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-2 mb-1.5 block">Start Time <span class="text-rose-500">*</span></label>
                            <input type="time" name="start_time" value="{{ old('start_time') }}" required class="w-full rounded-2xl border-slate-200 bg-slate-50 text-xs sm:text-sm py-3 px-3.5 font-mono font-bold text-slate-800 focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] transition-all">
                        </div>
                        <div>
                            <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-2 mb-1.5 block">End Time <span class="text-rose-500">*</span></label>
                            <input type="time" name="end_time" value="{{ old('end_time') }}" required class="w-full rounded-2xl border-slate-200 bg-slate-50 text-xs sm:text-sm py-3 px-3.5 font-mono font-bold text-slate-800 focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] transition-all">
                        </div>
                    </div>

                    <button type="submit"
                        :disabled="checkingOverlap"
                        class="w-full py-4 mt-4 text-white text-[11px] font-black uppercase rounded-2xl shadow-xl transition-all active:scale-[0.98] cursor-pointer flex items-center justify-center gap-2"
                        :class="mode === 'class' ? 'bg-gradient-to-r from-slate-900 to-slate-800 hover:bg-black shadow-slate-900/10' : 'bg-gradient-to-r from-amber-600 to-[#D4AF37] hover:from-amber-500 hover:to-yellow-500 text-slate-950 font-black shadow-amber-500/20'">
                        <span x-show="checkingOverlap" class="size-3.5 rounded-full border-2 border-white/30 border-t-white animate-spin"></span>
                        <span x-text="checkingOverlap ? 'Validating Conflicts...' : (mode === 'class' ? 'Confirm Class Slot' : 'Confirm One-Time Event')"></span>
                    </button>
                </form>
            </div>

            {{-- 2. COMMAND MATRIX & ROSTER DISPLAY --}}
            <div class="lg:col-span-2 bg-slate-900 rounded-[2.5rem] p-6 sm:p-8 shadow-2xl flex flex-col border border-slate-800/80">

                {{-- Header & Controls --}}
                <div class="mb-6 pb-6 border-b border-slate-800/80 flex flex-col gap-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="size-2 rounded-full bg-[#D4AF37]"></span>
                                <h4 class="text-white font-black uppercase tracking-tight text-lg sm:text-xl">Allocated <span class="text-[#D4AF37]">Roster</span></h4>
                            </div>
                            <p class="text-[9px] font-mono text-slate-400 mt-1 uppercase">Filtering View: <span class="text-[#D4AF37]" x-text="activeDay"></span></p>
                        </div>

                        {{-- Action Controls --}}
                        <div class="flex items-center flex-wrap gap-2">
                            {{-- Event Archive --}}
                            <button type="button"
                                @click="archiveModal = true"
                                class="px-3.5 py-2.5 bg-[#D4AF37]/10 hover:bg-[#D4AF37] text-[#D4AF37] hover:text-slate-950 border border-[#D4AF37]/30 rounded-xl text-[9px] font-black uppercase tracking-wider transition-all flex items-center gap-1.5 shadow-sm active:scale-95 cursor-pointer">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                                </svg>
                                <span>Archive ({{ count($pastEvents) }})</span>
                            </button>

                            {{-- Revoke Day --}}
                            <template x-if="activeDay !== 'All'">
                                <button type="button"
                                    @click="openPurgeModal('day')"
                                    class="px-3.5 py-2.5 bg-rose-500/10 hover:bg-rose-600 text-rose-400 hover:text-white border border-rose-500/30 rounded-xl text-[9px] font-black uppercase tracking-wider transition-all flex items-center gap-1.5 shadow-sm active:scale-95 cursor-pointer">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span>Revoke <span x-text="activeDay"></span></span>
                                </button>
                            </template>

                            {{-- Revoke All --}}
                            <button type="button"
                                @click="openPurgeModal('all')"
                                class="px-3.5 py-2.5 bg-slate-800 hover:bg-rose-950/40 text-slate-400 hover:text-rose-400 border border-slate-700 rounded-xl text-[9px] font-black uppercase tracking-wider transition-all flex items-center gap-1.5 shadow-sm active:scale-95 cursor-pointer">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span>Revoke All</span>
                            </button>
                        </div>
                    </div>

                    {{-- Day Filter Chips --}}
                    <div class="w-full overflow-x-auto no-scrollbar py-1">
                        <div class="flex items-center gap-1.5 min-w-max">
                            <template x-for="day in ['All', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']" :key="day">
                                <button
                                    @click="setDay(day)"
                                    :class="activeDay === day ? 'bg-[#D4AF37] text-slate-950 font-black shadow-lg shadow-[#D4AF37]/20' : 'bg-slate-800 text-slate-400 hover:bg-slate-700/60 border border-slate-700/50'"
                                    class="px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all shrink-0 cursor-pointer"
                                    x-text="day">
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Desktop Table View --}}
                <div class="hidden sm:block overflow-y-auto max-h-[520px] pr-2 custom-scroll relative">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 bg-slate-900/95 backdrop-blur-md z-10">
                            <tr class="text-[9px] font-black text-slate-400 uppercase tracking-[0.25em] border-b border-slate-800">
                                <th class="pb-3 pl-1">Host & Slot Assignment</th>
                                <th class="pb-3">Day / Date</th>
                                <th class="pb-3">Window</th>
                                <th class="pb-3 text-right pr-1">Action / Attendance</th>
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

                                <td class="py-4 pl-1 font-black text-white text-sm uppercase">
                                    <div class="flex flex-col">
                                        @if($isEvent)
                                        <div class="flex items-center gap-1.5 text-[#D4AF37]">
                                            <svg class="size-3.5 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h7.5M12 15.75a3 3 0 01-3-3V4.5a3 3 0 116 0v8.25a3 3 0 01-3 3z" />
                                            </svg>
                                            <span>{{ $entry->speaker_name ?? 'Guest Speaker' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase bg-[#D4AF37]/15 text-[#D4AF37] border border-[#D4AF37]/30">
                                                🎙️ One-Time Event
                                            </span>
                                            <span class="text-[10px] font-mono text-slate-200">{{ $entry->subject_code }}</span>
                                        </div>
                                        @else
                                        <span>{{ $entry->user->name ?? 'Instructor' }}</span>
                                        @if($isOpenOrFree)
                                        <span class="inline-flex items-center gap-1.5 text-[8px] font-black uppercase tracking-wider px-2 py-0.5 bg-emerald-500/10 text-emerald-400 border border-emerald-500/25 rounded-md mt-1 w-max">
                                            <span class="size-1 rounded-full bg-emerald-400 animate-ping"></span>
                                            {{ $entry->subject_code }} • Open Access
                                        </span>
                                        @else
                                        <span class="text-[9px] text-[#D4AF37] tracking-widest font-mono font-bold uppercase mt-0.5">{{ $entry->subject_code }}</span>
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
                                    <span class="px-3 py-1.5 bg-slate-800/90 rounded-lg border border-slate-700/60 inline-block">
                                        {{ date('h:i A', strtotime($entry->start_time)) }} — {{ date('h:i A', strtotime($entry->end_time)) }}
                                    </span>
                                </td>

                                <td class="py-4 text-right pr-1">
                                    <div class="flex items-center justify-end gap-2">
                                        @if($isEvent)
                                        <a href="{{ route('dashboard.labs.schedule.exportEvent', $entry->id) }}"
                                            title="Export Event Attendance CSV"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-[#D4AF37] to-amber-600 hover:brightness-110 text-slate-950 rounded-xl text-[9px] font-black uppercase tracking-wider shadow-sm transition active:scale-95 cursor-pointer">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                            </svg>
                                            <span>Roster ({{ $entry->attendees_count ?? 0 }})</span>
                                        </a>
                                        @endif

                                        <form action="{{ route('dashboard.labs.schedule.destroy', $entry->id) }}" method="POST" onsubmit="return confirm('Revoke this slot?')">
                                            @csrf @method('DELETE')
                                            <input type="hidden" name="day" :value="activeDay">
                                            <button class="text-rose-400 hover:text-rose-300 text-[9px] font-black uppercase tracking-widest border border-rose-500/20 px-3 py-1.5 rounded-xl hover:bg-rose-500/10 active:scale-95 transition-all cursor-pointer">
                                                Revoke
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-16 text-center text-slate-600 font-black uppercase tracking-widest text-[10px]">No active schedules found for this laboratory</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Card List --}}
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
                                <div class="flex items-center gap-1.5 text-[#D4AF37] text-sm font-black">
                                    <span>🎙️ {{ $entry->speaker_name ?? 'Guest Speaker' }}</span>
                                </div>
                                <p class="text-[9px] text-slate-300 font-bold uppercase mt-0.5">{{ $entry->subject_code }}</p>
                                @else
                                <h5 class="text-white font-black text-sm uppercase">{{ $entry->user->name ?? 'Instructor' }}</h5>
                                <p class="text-[9px] text-[#D4AF37] font-bold uppercase mt-0.5">{{ $entry->subject_code }}</p>
                                @endif
                            </div>

                            <span class="px-2.5 py-1 bg-slate-700/80 rounded-lg text-[9px] font-black uppercase text-slate-300">
                                {{ $isEvent ? \Carbon\Carbon::parse($entry->event_date)->format('M d') : $entry->day }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-slate-700/40">
                            <span class="text-slate-300 font-mono text-xs font-bold">
                                {{ date('h:i A', strtotime($entry->start_time)) }} - {{ date('h:i A', strtotime($entry->end_time)) }}
                            </span>

                            <div class="flex items-center gap-1.5">
                                @if($isEvent)
                                <a href="{{ route('dashboard.labs.schedule.exportEvent', $entry->id) }}" class="px-2.5 py-1 bg-[#D4AF37] text-slate-950 text-[8px] font-black uppercase rounded-lg">
                                    CSV ({{ $entry->attendees_count ?? 0 }})
                                </a>
                                @endif
                                <form action="{{ route('dashboard.labs.schedule.destroy', $entry->id) }}" method="POST" onsubmit="return confirm('Revoke this slot?')">
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
        {{-- PROPERLY SCOPED MODALS (Inside x-data, fixed at root with z-[9999]) --}}
        {{-- ========================================================================= --}}

        {{-- 1. CONFLICT MODAL (Differentiates between Event Overwrite vs Class Overlap Rejection) --}}
        <div x-cloak x-show="conflictModal"
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
            role="dialog" aria-modal="true">

            {{-- Backdrop --}}
            <div x-show="conflictModal" x-transition.opacity.duration.300ms
                class="fixed inset-0 bg-slate-950/85 backdrop-blur-md"
                @click="conflictModal = false"></div>

            {{-- Dialog Box --}}
            <div x-show="conflictModal" x-transition.scale.duration.300ms
                class="relative transform overflow-hidden rounded-[2.5rem] bg-slate-900 shadow-2xl p-6 sm:p-8 text-left text-white max-w-md w-full z-10"
                :class="mode === 'event' ? 'border border-amber-500/40' : 'border border-rose-500/40'">

                {{-- Event: Conflict Can Be Overridden --}}
                <template x-if="mode === 'event'">
                    <div>
                        <div class="flex items-center gap-3.5 mb-5">
                            <div class="size-12 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 shrink-0">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-black uppercase text-white">Event Overlap Detected</h3>
                                <p class="text-[9px] font-mono uppercase text-amber-400">Class Will Be Temporarily Masked</p>
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700 mb-5 space-y-2">
                            <span class="text-[8px] font-black uppercase text-slate-400">Current Slot Occupant</span>
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-black text-[#D4AF37]" x-text="conflict ? conflict.subject_code : ''"></h4>
                                <span class="text-xs font-mono font-bold text-slate-300" x-text="conflict ? conflict.time_window : ''"></span>
                            </div>
                            <p class="text-[10px] text-slate-400" x-text="'Instructor: ' + (conflict ? conflict.host_name : '')"></p>
                        </div>

                        <p class="text-xs text-slate-300 leading-relaxed mb-6 font-medium">
                            This event will overlap with <strong class="text-white" x-text="conflict ? conflict.subject_code : ''"></strong> on <span class="text-amber-400 font-mono font-bold" x-text="eventDate"></span>. The regular class will <strong class="text-amber-400">temporarily disappear for this date</strong> and return next week. Proceed?
                        </p>

                        <div class="flex gap-2.5">
                            <button type="button" @click="forceProceed()" class="flex-1 py-3 bg-gradient-to-r from-amber-500 to-[#D4AF37] text-slate-950 font-black text-xs uppercase rounded-xl active:scale-95 transition cursor-pointer">
                                Confirm & Override
                            </button>
                            <button type="button" @click="conflictModal = false" class="flex-1 py-3 bg-slate-800 text-slate-300 font-bold text-xs uppercase rounded-xl border border-slate-700 hover:bg-slate-700 transition cursor-pointer">
                                Cancel
                            </button>
                        </div>
                    </div>
                </template>

                {{-- Class: Strict Conflict (NO OVERRIDE ALLOWED) --}}
                <template x-if="mode === 'class'">
                    <div>
                        <div class="flex items-center gap-3.5 mb-5">
                            <div class="size-12 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400 shrink-0">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-black uppercase text-white">Class Schedule Conflict</h3>
                                <p class="text-[9px] font-mono uppercase text-rose-400">Overwriting Regular Classes Is Not Permitted</p>
                            </div>
                        </div>

                        <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700 mb-5 space-y-2">
                            <span class="text-[8px] font-black uppercase text-slate-400">Occupied Slot</span>
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-black text-rose-400" x-text="conflict ? conflict.subject_code : ''"></h4>
                                <span class="text-xs font-mono font-bold text-slate-300" x-text="conflict ? conflict.time_window : ''"></span>
                            </div>
                            <p class="text-[10px] text-slate-400" x-text="'Assigned to: ' + (conflict ? conflict.host_name : '')"></p>
                        </div>

                        <p class="text-xs text-slate-300 leading-relaxed mb-6 font-medium">
                            This time slot is already assigned to <strong class="text-white" x-text="conflict ? conflict.subject_code : ''"></strong>. Regular class schedules <strong class="text-rose-400">cannot overwrite each other</strong>. Please select another time window or laboratory.
                        </p>

                        <button type="button" @click="conflictModal = false" class="w-full py-3 bg-slate-800 hover:bg-slate-700 text-white font-black text-xs uppercase rounded-xl border border-slate-700 transition cursor-pointer">
                            Dismiss & Adjust Slot
                        </button>
                    </div>
                </template>

            </div>
        </div>

        {{-- 2. VALIDATION ERROR MODAL (Pops up when fields like Instructor are missing) --}}
        <div x-cloak x-show="errorModal"
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
            role="dialog" aria-modal="true">

            <div x-show="errorModal" x-transition.opacity.duration.300ms
                class="fixed inset-0 bg-slate-950/85 backdrop-blur-md"
                @click="errorModal = false"></div>

            <div x-show="errorModal" x-transition.scale.duration.300ms
                class="relative transform overflow-hidden rounded-[2.5rem] bg-slate-900 border border-rose-500/40 shadow-2xl p-6 sm:p-8 text-left text-white max-w-md w-full z-10">
                <div class="flex items-center gap-3.5 mb-4">
                    <div class="size-12 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400 shrink-0">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-[9px] font-mono uppercase tracking-widest text-rose-400 font-bold">Input Incomplete</span>
                        <h3 class="text-base sm:text-lg font-black uppercase text-white" x-text="errorTitle"></h3>
                    </div>
                </div>

                <p class="text-xs text-slate-300 leading-relaxed mb-6 font-medium" x-text="errorMessage"></p>

                <button type="button" @click="errorModal = false" class="w-full py-3 bg-slate-800 hover:bg-slate-700 text-white font-black text-xs uppercase rounded-xl border border-slate-700 transition cursor-pointer">
                    Understood
                </button>
            </div>
        </div>

        {{-- 3. PURGE MODAL --}}
        <div x-cloak x-show="purgeModal"
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
            role="dialog" aria-modal="true">

            <div x-show="purgeModal" x-transition.opacity.duration.300ms
                class="fixed inset-0 bg-slate-950/85 backdrop-blur-md"
                @click="purgeModal = false"></div>

            <div x-show="purgeModal" x-transition.scale.duration.300ms
                class="relative transform overflow-hidden rounded-[2.5rem] bg-slate-900 border border-rose-500/40 shadow-2xl p-6 sm:p-8 text-left text-white max-w-md w-full z-10">

                <div class="flex items-center gap-3.5 mb-5">
                    <div class="size-12 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400 shrink-0">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-[9px] font-mono uppercase tracking-widest text-rose-400 font-bold">Destructive Action</span>
                        <h3 class="text-base sm:text-lg font-black uppercase text-white" x-text="purgeType === 'all' ? 'Purge Entire Roster' : 'Revoke ' + activeDay + ' Slots'"></h3>
                    </div>
                </div>

                <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700 mb-5 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[8px] font-black uppercase text-slate-400">Target Facility</span>
                        <span class="text-xs font-black text-[#D4AF37]">{{ $lab->name }}</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-slate-700/60 pt-2">
                        <span class="text-[8px] font-black uppercase text-slate-400">Scope</span>
                        <span class="text-xs font-mono font-bold text-white" x-text="purgeType === 'all' ? 'All Days (Mon — Sat)' : activeDay + ' Allocations'"></span>
                    </div>
                </div>

                <p class="text-xs text-slate-300 leading-relaxed mb-6 font-medium">
                    <template x-if="purgeType === 'all'">
                        <span>This will <strong class="text-rose-400">permanently delete every recurring schedule and event</strong> across all days. This cannot be undone.</span>
                    </template>
                    <template x-if="purgeType === 'day'">
                        <span>This will <strong class="text-rose-400">remove all slots assigned on <span x-text="activeDay"></span></strong>. Other days remain unaffected.</span>
                    </template>
                </p>

                <form action="{{ route('dashboard.labs.schedule.destroyByDay', $lab->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="day" :value="purgeType === 'all' ? 'All' : activeDay">

                    <div class="flex gap-2.5">
                        <button type="submit" class="flex-1 py-3 bg-gradient-to-r from-rose-600 to-red-700 hover:from-rose-500 hover:to-red-600 text-white font-black text-xs uppercase rounded-xl active:scale-95 transition cursor-pointer">
                            Confirm Revoke
                        </button>
                        <button type="button" @click="purgeModal = false" class="flex-1 py-3 bg-slate-800 text-slate-300 font-bold text-xs uppercase rounded-xl border border-slate-700 hover:bg-slate-700 transition cursor-pointer">
                            Abort
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- 4. PAST EVENTS ARCHIVE MODAL --}}
        <div x-cloak x-show="archiveModal"
            class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
            role="dialog" aria-modal="true">

            <div x-show="archiveModal" x-transition.opacity.duration.300ms
                class="fixed inset-0 bg-slate-950/85 backdrop-blur-md"
                @click="archiveModal = false"></div>

            <div x-show="archiveModal" x-transition.scale.duration.300ms
                class="relative transform overflow-hidden rounded-[2.5rem] bg-slate-900 border border-slate-700 shadow-2xl p-6 sm:p-8 text-left text-white max-w-2xl w-full z-10">
                <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-5">
                    <div>
                        <h3 class="text-lg font-black uppercase tracking-tight text-white">Event Attendance Archive</h3>
                        <p class="text-[9px] font-mono uppercase tracking-widest text-[#D4AF37]">Historical completed events & CSV exports</p>
                    </div>
                    <button type="button" @click="archiveModal = false" class="text-slate-400 hover:text-white p-1 cursor-pointer">✕</button>
                </div>

                <div class="max-h-96 overflow-y-auto space-y-3 custom-scroll pr-1">
                    @forelse($pastEvents as $pe)
                    <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700/70 flex items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="text-sm font-black text-white uppercase">{{ $pe->subject_code }}</h4>
                                <span class="px-2 py-0.5 rounded text-[8px] font-bold uppercase bg-slate-700 text-slate-300">
                                    {{ \Carbon\Carbon::parse($pe->event_date)->format('M d, Y') }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">Speaker: <strong class="text-[#D4AF37]">{{ $pe->speaker_name ?? 'Guest Speaker' }}</strong></p>
                            <p class="text-[9px] font-mono text-slate-500 mt-0.5">{{ date('h:i A', strtotime($pe->start_time)) }} — {{ date('h:i A', strtotime($pe->end_time)) }}</p>
                        </div>

                        <a href="{{ route('dashboard.labs.schedule.exportEvent', $pe->id) }}"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gradient-to-r from-[#D4AF37] to-amber-600 hover:brightness-110 text-slate-950 font-black text-[9px] uppercase tracking-wider rounded-xl shadow-sm shrink-0 active:scale-95 transition cursor-pointer">
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            <span>Export CSV ({{ $pe->attendees_count }})</span>
                        </a>
                    </div>
                    @empty
                    <div class="py-12 text-center text-slate-500 text-xs font-bold uppercase tracking-wider">
                        No past completed events recorded for this facility yet.
                    </div>
                    @endforelse
                </div>

                <div class="mt-6 pt-4 border-t border-slate-800 text-right">
                    <button type="button" @click="archiveModal = false" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white text-[10px] font-black uppercase rounded-xl cursor-pointer">
                        Close
                    </button>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>