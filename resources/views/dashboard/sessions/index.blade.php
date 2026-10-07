<x-app-layout>
    {{-- Header --}}
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="size-2 bg-emerald-500 rounded-full animate-pulse"></span>
                    <span class="text-[9px] font-black uppercase tracking-[0.3em] text-[#D4AF37]">Workstation Session Logbook</span>
                </div>
                <h2 class="font-black text-2xl sm:text-3xl md:text-4xl text-slate-800 tracking-tight uppercase">
                    Session <span class="text-[#D4AF37]">History</span>
                </h2>
            </div>

            {{-- Matching Metric Badges --}}
            <div class="grid grid-cols-2 sm:flex gap-3 w-full md:w-auto">
                <div class="bg-white px-5 sm:px-6 py-3.5 sm:py-4 rounded-2xl sm:rounded-3xl border border-slate-100 shadow-sm flex-1 sm:flex-initial">
                    <p class="text-[8px] font-black text-slate-400 uppercase mb-1 tracking-widest">Total Sessions</p>
                    <p class="text-xl sm:text-2xl font-black text-slate-800 font-mono">{{ $totalSessions ?? ($sessions->total() ?? $sessions->count()) }}</p>
                </div>
                <div class="bg-white px-5 sm:px-6 py-3.5 sm:py-4 rounded-2xl sm:rounded-3xl border border-emerald-500/20 shadow-lg shadow-emerald-500/5 flex-1 sm:flex-initial relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 to-teal-500"></div>
                    <p class="text-[8px] font-black text-emerald-600 uppercase mb-1 tracking-widest">Active Now</p>
                    <p class="text-xl sm:text-2xl font-black text-emerald-600 font-mono">{{ $activeSessionsCount ?? $sessions->whereNull('time_out')->count() }}</p>
                </div>
            </div>
        </div>
    </x-slot>

    {{-- Main Container with Alpine Modal State --}}
    <div x-data="{
        modalOpen: false,
        audit: {},
        openAudit(data) {
            this.audit = data;
            this.modalOpen = true;
        }
    }" @keydown.escape.window="modalOpen = false" class="py-6 sm:py-8 md:py-10 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto min-h-screen">

        {{-- ========================================================================= --}}
        {{-- FILTER BAR (Updated with Laboratory Filter)                               --}}
        {{-- ========================================================================= --}}
        <div class="mb-6 sm:mb-8">
            <div class="bg-white border border-slate-200/80 p-5 sm:p-7 rounded-3xl sm:rounded-[2.2rem] shadow-xl shadow-slate-900/5">
                <form action="{{ url()->current() }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">

                    {{-- 1. Student Name --}}
                    <div>
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5 block ml-1">Student Name</label>
                        <input type="text" name="student_name" value="{{ request('student_name') }}" placeholder="Search Student..."
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs font-semibold focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] placeholder:text-slate-400 py-3 px-4 transition-all">
                    </div>

                    {{-- 2. Terminal ID --}}
                    <div>
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5 block ml-1">Terminal ID</label>
                        <input type="text" name="pc_number" value="{{ request('pc_number') }}" placeholder="E.g. PC-01..."
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs font-semibold focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] placeholder:text-slate-400 py-3 px-4 transition-all">
                    </div>

                    {{-- 3. NEW: Laboratory Filter --}}
                    <div>
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5 block ml-1">Laboratory</label>
                        <select name="lab_id" class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs font-semibold focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] py-3 px-3.5 transition-all cursor-pointer">
                            <option value="">All Laboratories</option>
                            @foreach($allLabs ?? $labs ?? \App\Models\Lab::orderBy('name')->get() as $l)
                            <option value="{{ $l->id }}" {{ request('lab_id') == $l->id ? 'selected' : '' }}>
                                {{ $l->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 4. Activity Date --}}
                    <div>
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5 block ml-1">Activity Date</label>
                        <input type="date" name="date" value="{{ request('date') }}"
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs font-semibold focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] [color-scheme:light] py-3 px-4 transition-all cursor-pointer">
                    </div>

                    {{-- 5. Action Buttons --}}
                    <div class="flex items-center gap-2 w-full">
                        <button type="submit" class="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-black uppercase text-[10px] tracking-wider py-3.5 rounded-2xl shadow-md transition-all active:scale-95 cursor-pointer flex items-center justify-center gap-1.5">
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <span>Filter</span>
                        </button>
                        <a href="{{ url()->current() }}" class="px-4 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold uppercase text-[10px] tracking-wider rounded-2xl transition-all text-center shrink-0">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- DESKTOP TABLE VIEW                                                        --}}
        {{-- ========================================================================= --}}
        <div class="hidden md:block bg-white border border-slate-200/80 rounded-[2.5rem] overflow-hidden shadow-2xl shadow-slate-900/5">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[9px] font-black text-slate-400 uppercase tracking-[0.25em] bg-slate-50/70 border-b border-slate-100">
                        <th class="py-5 px-7">Student Node</th>
                        <th class="py-5 px-4">Terminal & Session Context</th>
                        <th class="py-5 px-4">Time In</th>
                        <th class="py-5 px-4">Time Out</th>
                        <th class="py-5 px-4 text-center">Hardware Audit</th>
                        <th class="py-5 px-7 text-right">Total Duration</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($sessions as $session)
                    @php
                    // Context resolution: Resolves Laboratory, Subject, and Teacher gracefully
                    $resolvedLabName = $session->lab->name
                    ?? $session->computer->lab->name
                    ?? $session->lab_name
                    ?? null;

                    $resolvedSubject = $session->subject_code
                    ?? $session->subject
                    ?? null;

                    $resolvedTeacher = $session->teacher->name
                    ?? $session->instructor->name
                    ?? $session->teacher_name
                    ?? null;

                    // Fallback check against Lab Schedule at the time the student logged in
                    if ((!$resolvedSubject || !$resolvedTeacher) && ($session->lab_id || isset($session->computer->lab_id))) {
                    $targetLabId = $session->lab_id ?? $session->computer->lab_id;
                    $activeSched = \App\Models\Schedule::where('lab_id', $targetLabId)
                    ->where('day', \Carbon\Carbon::parse($session->time_in)->format('l'))
                    ->whereTime('start_time', '<=', \Carbon\Carbon::parse($session->time_in)->format('H:i:s'))
                        ->whereTime('end_time', '>=', \Carbon\Carbon::parse($session->time_in)->format('H:i:s'))
                        ->first();

                        if ($activeSched) {
                        $resolvedSubject = $resolvedSubject ?? $activeSched->subject_code;
                        $resolvedTeacher = $resolvedTeacher ?? ($activeSched->is_event
                        ? ($activeSched->speaker_name ?? 'Guest Speaker')
                        : ($activeSched->user->name ?? null));
                        }
                        }
                        @endphp

                        <tr class="group hover:bg-slate-50/50 transition-colors">

                            {{-- 1. Student Node --}}
                            <td class="py-6 px-7">
                                <div class="flex items-center gap-3.5">
                                    <div class="size-10 rounded-2xl bg-[#D4AF37]/10 flex items-center justify-center font-black text-[#D4AF37] text-[10px] border border-[#D4AF37]/25 shrink-0">
                                        {{ strtoupper(substr($session->student_name ?? '??', 0, 2)) }}
                                    </div>
                                    <div>
                                        <span class="font-black text-slate-900 tracking-tight uppercase block leading-tight text-xs">{{ $session->student_name }}</span>
                                        <span class="text-[9px] font-mono font-bold text-slate-400 uppercase tracking-tight mt-0.5 block">{{ $session->student_id_number }}</span>
                                    </div>
                                </div>
                            </td>

                            {{-- 2. Terminal ID & "During Subject • Teacher" Context --}}
                            <td class="py-6 px-4">
                                <div class="flex flex-col items-start gap-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2.5 py-1 bg-slate-900 text-[#D4AF37] rounded-lg font-mono font-black text-[10px] border border-slate-800 uppercase shadow-sm">
                                            {{ $session->computer->pc_number ?? 'PC-??' }}
                                        </span>
                                        @if($resolvedLabName)
                                        <span class="px-1.5 py-0.5 rounded text-[8px] font-mono font-bold uppercase bg-slate-100 text-slate-500 border border-slate-200">
                                            {{ $resolvedLabName }}
                                        </span>
                                        @endif
                                    </div>

                                    {{-- Session Context Micro-Pill --}}
                                    @if($resolvedSubject || $resolvedTeacher)
                                    <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200/80 text-[8px] font-medium text-slate-600 max-w-[200px]" title="During {{ $resolvedSubject ?? 'Class' }} &bull; {{ $resolvedTeacher ?? 'Instructor' }}">
                                        <span class="size-1 rounded-full bg-[#D4AF37] shrink-0"></span>
                                        <span class="font-black font-mono text-slate-800 truncate uppercase">{{ $resolvedSubject ?? 'Active Class' }}</span>
                                        @if($resolvedTeacher)
                                        <span class="text-slate-300 shrink-0">•</span>
                                        <span class="truncate text-slate-500">{{ $resolvedTeacher }}</span>
                                        @endif
                                    </div>
                                    @else
                                    <span class="text-[8px] text-slate-400 font-medium italic">
                                        Open Terminal Session
                                    </span>
                                    @endif
                                </div>
                            </td>

                            {{-- 3. Time In --}}
                            <td class="py-6 px-4 whitespace-nowrap">
                                <div class="text-[10px] font-black uppercase tracking-tight text-slate-900">
                                    <div class="font-mono text-slate-800 mb-0.5">{{ $session->time_in ? \Carbon\Carbon::parse($session->time_in)->format('M d, Y') : 'N/A' }}</div>
                                    <span class="text-slate-400 font-mono">{{ $session->time_in ? \Carbon\Carbon::parse($session->time_in)->format('h:i A') : '--:--' }}</span>
                                </div>
                            </td>

                            {{-- 4. Time Out --}}
                            <td class="py-6 px-4 whitespace-nowrap">
                                <div class="text-[10px] font-black uppercase tracking-tight">
                                    @if($session->time_out)
                                    <div class="font-mono text-slate-800 mb-0.5">{{ \Carbon\Carbon::parse($session->time_out)->format('M d, Y') }}</div>
                                    <span class="text-slate-400 font-mono">{{ \Carbon\Carbon::parse($session->time_out)->format('h:i A') }}</span>
                                    @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200/70 text-[9px] font-black uppercase">
                                        <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span> ACTIVE
                                    </span>
                                    @endif
                                </div>
                            </td>

                            {{-- 5. Hardware Audit Trigger --}}
                            <td class="py-6 px-4 text-center whitespace-nowrap">
                                @if($session->checklist)
                                <button type="button"
                                    @click="openAudit(@js([
                                    'student_name'    => $session->student_name,
                                    'student_id'      => $session->student_id_number,
                                    'pc_number'       => $session->computer->pc_number ?? ($session->checklist->pc_number ?? 'PC-??'),
                                    'lab_name'        => $resolvedLabName ?? ($session->checklist->lab_name ?? 'Default Lab'),
                                    'verified_at'     => optional($session->checklist->verified_at)->format('M d, Y • h:i A') ?? optional($session->checklist->created_at)->format('M d, Y • h:i A'),
                                    'system_unit_ok'  => (bool)($session->checklist->system_unit_ok ?? $session->checklist->pc_case_ok ?? true),
                                    'monitor_ok'      => (bool)$session->checklist->monitor_ok,
                                    'avr_ok'          => (bool)$session->checklist->avr_ok,
                                    'mouse_ok'        => (bool)$session->checklist->mouse_ok,
                                    'keyboard_ok'     => (bool)$session->checklist->keyboard_ok,
                                    'cables_ok'       => (bool)($session->checklist->cables_ok ?? $session->checklist->headset_ok ?? true),
                                    'all_operational' => (bool)$session->checklist->all_operational,
                                ]))"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200/80 text-emerald-700 rounded-xl font-black text-[9px] uppercase tracking-wider transition-all duration-200 active:scale-95 cursor-pointer">
                                    <svg class="size-3.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                    </svg>
                                    <span>Verified</span>
                                    <svg class="size-3 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                </button>
                                @else
                                <span class="inline-flex items-center px-2 py-0.5 bg-slate-100 text-slate-400 rounded-lg font-bold text-[8px] uppercase tracking-wider border border-slate-200/60">
                                    Unlogged
                                </span>
                                @endif
                            </td>

                            {{-- 6. Total Duration --}}
                            <td class="py-6 px-7 text-right">
                                <div class="inline-block bg-slate-50 px-3.5 py-1.5 rounded-xl border border-slate-200/60">
                                    <span class="text-[10px] font-mono font-black text-slate-800 uppercase tracking-tight block">
                                        {{ $session->time_out ? \Carbon\Carbon::parse($session->time_in)->diffForHumans(\Carbon\Carbon::parse($session->time_out), true) : 'Ongoing' }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-24 text-center">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <div class="size-12 rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400 mb-2">
                                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="text-slate-800 text-sm font-black uppercase tracking-wider">No Sessions Found</div>
                                    <p class="text-slate-400 font-medium text-xs">No workstation session records match your specified filter parameters.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                </tbody>
            </table>

            {{-- Desktop Pagination --}}
            @if(method_exists($sessions, 'links') && $sessions->hasPages())
            <div class="p-6 bg-slate-50/50 border-t border-slate-100">
                {{ $sessions->links() }}
            </div>
            @endif
        </div>

        {{-- ========================================================================= --}}
        {{-- MOBILE CARD VIEW (< md screens)                                           --}}
        {{-- ========================================================================= --}}
        <div class="block md:hidden space-y-4">
            @forelse($sessions as $session)
            @php
            $resolvedLabName = $session->lab->name ?? $session->computer->lab->name ?? $session->lab_name ?? null;
            $resolvedSubject = $session->subject_code ?? $session->subject ?? null;
            $resolvedTeacher = $session->teacher->name ?? $session->instructor->name ?? $session->teacher_name ?? null;
            @endphp

            <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-xl shadow-slate-900/5 space-y-3.5">

                {{-- Card Header --}}
                <div class="flex items-start justify-between gap-3 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="size-10 rounded-2xl bg-[#D4AF37]/10 flex items-center justify-center font-black text-[#D4AF37] text-[10px] border border-[#D4AF37]/25 shrink-0">
                            {{ strtoupper(substr($session->student_name ?? '??', 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <span class="font-black text-slate-900 text-sm tracking-tight block uppercase truncate">{{ $session->student_name }}</span>
                            <span class="text-[9px] font-mono font-bold text-slate-400 uppercase tracking-tight block">{{ $session->student_id_number }}</span>
                        </div>
                    </div>

                    {{-- Terminal ID & Lab --}}
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="px-2.5 py-1 bg-slate-900 text-[#D4AF37] rounded-lg font-mono font-black text-[10px] border border-slate-800 uppercase shadow-sm">
                            {{ $session->computer->pc_number ?? 'PC-??' }}
                        </span>
                        @if($resolvedLabName)
                        <span class="px-1.5 py-0.5 rounded text-[8px] font-mono font-bold uppercase bg-slate-100 text-slate-500 border border-slate-200">
                            {{ $resolvedLabName }}
                        </span>
                        @endif
                    </div>
                </div>

                {{-- Session Context Badge (Mobile) --}}
                @if($resolvedSubject || $resolvedTeacher)
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-200 text-[9px] font-medium text-slate-600 w-full">
                    <span class="size-1.5 rounded-full bg-[#D4AF37] shrink-0"></span>
                    <span class="font-black font-mono text-slate-900 uppercase">{{ $resolvedSubject ?? 'Active Class' }}</span>
                    @if($resolvedTeacher)
                    <span class="text-slate-300">•</span>
                    <span class="truncate text-slate-500">{{ $resolvedTeacher }}</span>
                    @endif
                </div>
                @endif

                {{-- Time In & Time Out Grid --}}
                <div class="grid grid-cols-2 gap-3 bg-slate-50/70 p-3 rounded-2xl border border-slate-100">
                    <div>
                        <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block mb-0.5">Time In</span>
                        <div class="text-[10px] font-black uppercase text-slate-900 font-mono">
                            {{ $session->time_in ? \Carbon\Carbon::parse($session->time_in)->format('M d, Y') : 'N/A' }}
                            <span class="text-slate-400 block font-mono">{{ $session->time_in ? \Carbon\Carbon::parse($session->time_in)->format('h:i A') : '--:--' }}</span>
                        </div>
                    </div>
                    <div>
                        <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block mb-0.5">Time Out</span>
                        <div class="text-[10px] font-black uppercase text-slate-700 font-mono">
                            @if($session->time_out)
                            {{ \Carbon\Carbon::parse($session->time_out)->format('M d, Y') }}
                            <span class="text-slate-400 block font-mono">{{ \Carbon\Carbon::parse($session->time_out)->format('h:i A') }}</span>
                            @else
                            <span class="text-emerald-600 font-bold block">ACTIVE NOW</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Footer: Audit Trigger & Duration --}}
                <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                    <div>
                        @if($session->checklist)
                        <button type="button"
                            @click="openAudit(@js([
                                'student_name'    => $session->student_name,
                                'student_id'      => $session->student_id_number,
                                'pc_number'       => $session->computer->pc_number ?? ($session->checklist->pc_number ?? 'PC-??'),
                                'lab_name'        => $resolvedLabName ?? ($session->checklist->lab_name ?? 'Default Lab'),
                                'verified_at'     => optional($session->checklist->verified_at)->format('M d, Y • h:i A') ?? optional($session->checklist->created_at)->format('M d, Y • h:i A'),
                                'system_unit_ok'  => (bool)($session->checklist->system_unit_ok ?? $session->checklist->pc_case_ok ?? true),
                                'monitor_ok'      => (bool)$session->checklist->monitor_ok,
                                'avr_ok'          => (bool)$session->checklist->avr_ok,
                                'mouse_ok'        => (bool)$session->checklist->mouse_ok,
                                'keyboard_ok'     => (bool)$session->checklist->keyboard_ok,
                                'cables_ok'       => (bool)($session->checklist->cables_ok ?? $session->checklist->headset_ok ?? true),
                                'all_operational' => (bool)$session->checklist->all_operational,
                            ]))"
                            class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200/80 rounded-xl text-[9px] font-black uppercase tracking-wider">
                            <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Audit Verified</span>
                        </button>
                        @else
                        <span class="text-[8px] font-bold uppercase text-slate-400 bg-slate-100 px-2 py-0.5 rounded-md">
                            No Checklist
                        </span>
                        @endif
                    </div>

                    <div class="bg-slate-50 px-2.5 py-1 rounded-xl border border-slate-200/60 font-mono text-[9px] font-black text-slate-800">
                        {{ $session->time_out ? \Carbon\Carbon::parse($session->time_in)->diffForHumans(\Carbon\Carbon::parse($session->time_out), true) : 'Ongoing' }}
                    </div>
                </div>

            </div>
            @empty
            <div class="bg-white border border-slate-200/80 rounded-3xl p-12 text-center shadow-xl shadow-slate-900/5">
                <p class="text-slate-400 font-black uppercase tracking-widest text-xs">
                    No session records found
                </p>
            </div>
            @endforelse

            {{-- Mobile Pagination --}}
            @if(method_exists($sessions, 'links') && $sessions->hasPages())
            <div class="pt-2">
                {{ $sessions->links() }}
            </div>
            @endif
        </div>

        {{-- ========================================================================= --}}
        {{-- TELEPORTED HARDWARE AUDIT MODAL (Cannot be overlapped by table headers)    --}}
        {{-- ========================================================================= --}}
        <template x-teleport="body">
            <div x-cloak x-show="modalOpen" class="fixed inset-0 z-[99999] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div x-show="modalOpen"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    @click="modalOpen = false"
                    class="fixed inset-0 bg-slate-950/85 backdrop-blur-md transition-opacity">
                </div>

                <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                    <div x-show="modalOpen"
                        x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                        class="relative transform overflow-hidden rounded-[2.5rem] bg-slate-900 border border-slate-700/80 shadow-2xl transition-all w-full max-w-2xl text-left z-10">

                        {{-- Modal Header --}}
                        <div class="p-6 sm:p-8 bg-gradient-to-b from-slate-800/80 via-slate-850 to-slate-900 border-b border-slate-800 relative">
                            <button type="button" @click="modalOpen = false" class="absolute top-6 right-6 text-slate-400 hover:text-white p-2.5 rounded-xl bg-slate-800/60 hover:bg-slate-800 border border-slate-700/50 transition-colors cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>

                            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#D4AF37]/10 border border-[#D4AF37]/30 text-[#D4AF37] text-[9px] font-black uppercase tracking-widest mb-3">
                                <svg class="w-3.5 h-3.5 text-[#D4AF37]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                </svg>
                                <span>Terminal Hardware Audit Record</span>
                            </div>

                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-1">
                                <div>
                                    <h3 class="text-xl sm:text-2xl font-black text-white uppercase tracking-tight" x-text="audit.student_name"></h3>
                                    <p class="text-xs font-mono text-slate-400 mt-1">
                                        ID: <span class="text-[#D4AF37] font-bold" x-text="audit.student_id"></span>
                                        <span class="mx-2 text-slate-600">•</span>
                                        <span x-text="audit.lab_name"></span>
                                    </p>
                                </div>

                                <span class="inline-flex items-center self-start sm:self-center px-3.5 py-1.5 bg-slate-950 text-[#D4AF37] border border-slate-800 rounded-xl font-mono font-black text-xs uppercase shadow-inner" x-text="audit.pc_number"></span>
                            </div>
                        </div>

                        {{-- 6-Item Inspection Grid --}}
                        <div class="p-6 sm:p-8 space-y-4 bg-slate-900/90">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">

                                {{-- 1. System Unit --}}
                                <div class="p-4 rounded-2xl bg-slate-800/50 border border-slate-700/50 flex items-center justify-between hover:border-slate-600 transition-colors">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-10 h-10 rounded-xl bg-slate-800/90 border border-slate-700/80 text-[#D4AF37] flex items-center justify-center shrink-0 shadow-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <rect width="14" height="20" x="5" y="2" rx="2" />
                                                <circle cx="12" cy="6" r="1" />
                                                <circle cx="12" cy="10" r="1" />
                                                <line x1="9" x2="15" y1="15" y2="15" />
                                                <line x1="9" x2="15" y1="18" y2="18" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-black text-white uppercase tracking-tight">System Unit</h4>
                                            <p class="text-[9px] text-slate-400">Chassis, power & hardware</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase font-mono tracking-wider"
                                        :class="audit.system_unit_ok ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                                        <span class="size-1.5 rounded-full" :class="audit.system_unit_ok ? 'bg-emerald-400' : 'bg-red-400'"></span>
                                        <span x-text="audit.system_unit_ok ? 'OPERATIONAL' : 'ISSUE'"></span>
                                    </span>
                                </div>

                                {{-- 2. Display Monitor --}}
                                <div class="p-4 rounded-2xl bg-slate-800/50 border border-slate-700/50 flex items-center justify-between hover:border-slate-600 transition-colors">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-10 h-10 rounded-xl bg-slate-800/90 border border-slate-700/80 text-[#D4AF37] flex items-center justify-center shrink-0 shadow-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <rect width="20" height="14" x="2" y="3" rx="2" />
                                                <line x1="8" x2="16" y1="21" y2="21" />
                                                <line x1="12" x2="12" y1="17" y2="21" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-black text-white uppercase tracking-tight">Display Monitor</h4>
                                            <p class="text-[9px] text-slate-400">Screen panel & video signal</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase font-mono tracking-wider"
                                        :class="audit.monitor_ok ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                                        <span class="size-1.5 rounded-full" :class="audit.monitor_ok ? 'bg-emerald-400' : 'bg-red-400'"></span>
                                        <span x-text="audit.monitor_ok ? 'OPERATIONAL' : 'ISSUE'"></span>
                                    </span>
                                </div>

                                {{-- 3. Power Unit (AVR) --}}
                                <div class="p-4 rounded-2xl bg-slate-800/50 border border-slate-700/50 flex items-center justify-between hover:border-slate-600 transition-colors">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-10 h-10 rounded-xl bg-slate-800/90 border border-slate-700/80 text-[#D4AF37] flex items-center justify-center shrink-0 shadow-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-black text-white uppercase tracking-tight">Power Unit (AVR)</h4>
                                            <p class="text-[9px] text-slate-400">Regulator active & grounded</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase font-mono tracking-wider"
                                        :class="audit.avr_ok ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                                        <span class="size-1.5 rounded-full" :class="audit.avr_ok ? 'bg-emerald-400' : 'bg-red-400'"></span>
                                        <span x-text="audit.avr_ok ? 'OPERATIONAL' : 'ISSUE'"></span>
                                    </span>
                                </div>

                                {{-- 4. Optical Mouse --}}
                                <div class="p-4 rounded-2xl bg-slate-800/50 border border-slate-700/50 flex items-center justify-between hover:border-slate-600 transition-colors">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-10 h-10 rounded-xl bg-slate-800/90 border border-slate-700/80 text-[#D4AF37] flex items-center justify-center shrink-0 shadow-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <rect width="14" height="20" x="5" y="2" rx="7" />
                                                <path d="M12 6v4" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-black text-white uppercase tracking-tight">Optical Mouse</h4>
                                            <p class="text-[9px] text-slate-400">Tracking sensor & clicks</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase font-mono tracking-wider"
                                        :class="audit.mouse_ok ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                                        <span class="size-1.5 rounded-full" :class="audit.mouse_ok ? 'bg-emerald-400' : 'bg-red-400'"></span>
                                        <span x-text="audit.mouse_ok ? 'OPERATIONAL' : 'ISSUE'"></span>
                                    </span>
                                </div>

                                {{-- 5. Keyboard Unit --}}
                                <div class="p-4 rounded-2xl bg-slate-800/50 border border-slate-700/50 flex items-center justify-between hover:border-slate-600 transition-colors">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-10 h-10 rounded-xl bg-slate-800/90 border border-slate-700/80 text-[#D4AF37] flex items-center justify-center shrink-0 shadow-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <rect width="20" height="16" x="2" y="4" rx="2" />
                                                <path d="M6 8h.01M10 8h.01M14 8h.01M18 8h.01M8 12h.01M12 12h.01M16 12h.01M7 16h10" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-black text-white uppercase tracking-tight">Keyboard Unit</h4>
                                            <p class="text-[9px] text-slate-400">Keycaps & typing response</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase font-mono tracking-wider"
                                        :class="audit.keyboard_ok ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                                        <span class="size-1.5 rounded-full" :class="audit.keyboard_ok ? 'bg-emerald-400' : 'bg-red-400'"></span>
                                        <span x-text="audit.keyboard_ok ? 'OPERATIONAL' : 'ISSUE'"></span>
                                    </span>
                                </div>

                                {{-- 6. Power & I/O Cables --}}
                                <div class="p-4 rounded-2xl bg-slate-800/50 border border-slate-700/50 flex items-center justify-between hover:border-slate-600 transition-colors">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-10 h-10 rounded-xl bg-slate-800/90 border border-slate-700/80 text-[#D4AF37] flex items-center justify-center shrink-0 shadow-sm">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v4m6-4v4m-8 4h10a2 2 0 012 2v1a5 5 0 01-5 5H10a5 5 0 01-5-5v-1a2 2 0 012-2zm5 11v4" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-black text-white uppercase tracking-tight">Power & I/O Cables</h4>
                                            <p class="text-[9px] text-slate-400">Power, display & USB cords</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase font-mono tracking-wider"
                                        :class="audit.cables_ok ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                                        <span class="size-1.5 rounded-full" :class="audit.cables_ok ? 'bg-emerald-400' : 'bg-red-400'"></span>
                                        <span x-text="audit.cables_ok ? 'OPERATIONAL' : 'ISSUE'"></span>
                                    </span>
                                </div>

                            </div>

                            {{-- Timestamp & Digital Signature Verification --}}
                            <div class="pt-4 border-t border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-[10px] text-slate-400">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-[#D4AF37]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span>Verified At: <strong class="text-white font-mono" x-text="audit.verified_at"></strong></span>
                                </div>
                                <span class="text-emerald-400 font-black uppercase tracking-wider flex items-center gap-1.5">
                                    <span class="size-1.5 rounded-full bg-emerald-400"></span> Digitally Signed by Student
                                </span>
                            </div>
                        </div>

                        {{-- Modal Footer --}}
                        <div class="px-6 py-4 bg-slate-950 border-t border-slate-800/80 flex justify-end">
                            <button type="button" @click="modalOpen = false" class="px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-black text-[10px] uppercase tracking-wider rounded-xl transition-colors active:scale-95 cursor-pointer">
                                Close Report
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </template>

    </div>
</x-app-layout>