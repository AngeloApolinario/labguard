<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-black text-2xl sm:text-4xl text-slate-800 tracking-tighter uppercase">
                    Session <span class="text-[#D4AF37]">History</span>
                </h2>
                <div class="flex items-center space-x-2 mt-1">
                    <div class="size-2 bg-emerald-500 rounded-full animate-pulse"></div>
                    <p class="text-[9px] sm:text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">
                        Student Session Overview
                    </p>
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
    }" @keydown.escape.window="modalOpen = false" class="py-6 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto min-h-screen">

        {{-- Filter Bar --}}
        <div class="mb-6 sm:mb-10">
            <div class="bg-white/80 backdrop-blur-md border border-slate-200/60 p-5 sm:p-8 rounded-3xl sm:rounded-[2.5rem] shadow-xl shadow-slate-900/5">
                <form action="{{ route('dashboard.sessions.index') }}" method="GET" class="flex flex-wrap items-end gap-4 sm:gap-6">

                    <div class="flex-1 min-w-[200px]">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Student Name</label>
                        <input type="text" name="student_name" value="{{ request('student_name') }}" placeholder="Search Student..."
                            class="w-full bg-slate-50/50 border-slate-200/80 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3 px-4 placeholder:text-slate-400">
                    </div>

                    <div class="flex-1 min-w-[140px]">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Terminal ID</label>
                        <input type="text" name="pc_number" value="{{ request('pc_number') }}" placeholder="PC-01"
                            class="w-full bg-slate-50/50 border-slate-200/80 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3 px-4 placeholder:text-slate-400">
                    </div>

                    <div class="flex-1 min-w-[160px]">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Activity Date</label>
                        <input type="date" name="date" value="{{ request('date') }}"
                            class="w-full bg-slate-50/50 border-slate-200/80 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3 px-4">
                    </div>

                    <div class="flex items-center gap-3 w-full lg:w-auto mt-2 lg:mt-0">
                        <button type="submit" class="flex-1 lg:flex-initial bg-slate-900 text-white font-black uppercase text-[10px] px-8 py-3.5 rounded-2xl hover:bg-[#D4AF37] hover:text-slate-950 transition-all active:scale-95 shadow-lg shadow-slate-900/10">
                            Apply Filter
                        </button>
                        <a href="{{ route('dashboard.sessions.index') }}" class="bg-slate-100 text-slate-400 font-black uppercase text-[10px] px-6 py-3.5 rounded-2xl hover:bg-slate-200 hover:text-slate-600 transition-all flex items-center justify-center">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Mobile Cards (< md) & Desktop Grid Table (>= md) Container --}}
        <div class="bg-white/80 backdrop-blur-xl border border-slate-200/60 rounded-3xl sm:rounded-[3rem] overflow-hidden shadow-2xl shadow-slate-900/5 relative">

            {{-- 1. MOBILE CARD FEED (< md) --}}
            <div class="block md:hidden divide-y divide-slate-100 p-4">
                @forelse($sessions as $session)
                <div class="py-4 first:pt-2 last:pb-2">
                    <div class="bg-slate-50/70 border border-slate-200/60 rounded-2xl p-4 shadow-sm space-y-4 hover:border-[#D4AF37]/40 transition-colors">

                        {{-- Top Header: Student Info, Terminal & Checklist Trigger --}}
                        <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-200/50">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#D4AF37]/20 to-[#D4AF37]/5 flex items-center justify-center font-black text-[#D4AF37] text-[10px] border border-[#D4AF37]/30 shrink-0 shadow-sm">
                                    {{ strtoupper(substr($session->student_name ?? '??', 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <span class="font-black text-slate-900 tracking-tight uppercase block leading-none truncate text-xs">
                                        {{ $session->student_name }}
                                    </span>
                                    <span class="text-[8px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1 block">
                                        {{ $session->student_id_number }}
                                    </span>
                                </div>
                            </div>

                            {{-- Actions: Terminal Badge & Audit Trigger --}}
                            <div class="flex items-center gap-2 shrink-0">
                                <span class="inline-flex items-center px-2.5 py-1 bg-slate-900 text-[#D4AF37] rounded-lg font-black text-[9px] border border-slate-800 uppercase shadow-sm tracking-widest">
                                    {{ $session->computer->pc_number ?? 'PC-??' }}
                                </span>

                                @if($session->checklist)
                                <button type="button"
                                    @click="openAudit(@js([
                                        'student_name' => $session->student_name,
                                        'student_id' => $session->student_id_number,
                                        'pc_number' => $session->computer->pc_number ?? ($session->checklist->pc_number ?? 'PC-??'),
                                        'lab_name' => $session->checklist->lab_name ?? ($session->lab->name ?? 'Default Lab'),
                                        'verified_at' => optional($session->checklist->verified_at)->format('M d, Y • h:i A') ?? optional($session->checklist->created_at)->format('M d, Y • h:i A'),
                                        'monitor_ok' => (bool)$session->checklist->monitor_ok,
                                        'keyboard_ok' => (bool)$session->checklist->keyboard_ok,
                                        'mouse_ok' => (bool)$session->checklist->mouse_ok,
                                        'avr_ok' => (bool)$session->checklist->avr_ok,
                                        'pc_case_ok' => (bool)$session->checklist->pc_case_ok,
                                        'headset_ok' => (bool)$session->checklist->headset_ok,
                                        'all_operational' => (bool)$session->checklist->all_operational,
                                    ]))"
                                    class="p-1.5 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 border border-emerald-500/30 rounded-lg active:scale-95 transition-all flex items-center justify-center">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </div>

                        {{-- Middle Row: Time In & Status/Time Out --}}
                        <div class="grid grid-cols-2 gap-2 text-left">
                            <div>
                                <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block mb-0.5">Time In</span>
                                <p class="text-[10px] font-black uppercase text-slate-900 leading-tight">
                                    {{ optional($session->time_in)->format('M d, Y') ?? 'N/A' }}
                                    <span class="text-slate-400 font-semibold block mt-0.5 text-[9px]">
                                        {{ optional($session->time_in)->format('h:i A') ?? '--:--' }}
                                    </span>
                                </p>
                            </div>

                            <div>
                                <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block mb-0.5">Status / Exit</span>
                                @if($session->time_out)
                                <p class="text-[10px] font-black uppercase text-slate-700 leading-tight">
                                    {{ $session->time_out->format('h:i A') }}
                                    <span class="text-slate-400 text-[8px] tracking-widest block font-bold mt-0.5">EXITED</span>
                                </p>
                                @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-500/10 text-emerald-600 rounded-full font-black text-[8px] uppercase border border-emerald-500/20 backdrop-blur-sm mt-0.5">
                                    <span class="relative flex h-1.5 w-1.5">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                    </span>
                                    Active
                                </span>
                                @endif
                            </div>
                        </div>

                        {{-- Bottom Row: Duration Badge --}}
                        <div class="pt-2 border-t border-slate-200/40 flex items-center justify-between">
                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Total Duration</span>
                            <div class="bg-white px-3 py-1 rounded-xl border border-slate-200/60 shadow-xs">
                                <span class="text-[10px] font-black uppercase tracking-tight block">
                                    @if($session->time_out)
                                    <span class="text-slate-900">
                                        {{ $session->time_in->diffForHumans($session->time_out, true) }}
                                    </span>
                                    @else
                                    <span class="text-emerald-600 animate-pulse">
                                        In Progress
                                    </span>
                                    @endif
                                </span>
                            </div>
                        </div>

                    </div>
                </div>
                @empty
                <div class="py-16 text-center">
                    <div class="flex flex-col items-center justify-center gap-2">
                        <div class="size-10 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-300 font-black text-base">
                            Ø
                        </div>
                        <p class="text-slate-400 font-black uppercase tracking-[0.2em] text-[10px]">
                            No Session Records Found
                        </p>
                    </div>
                </div>
                @endforelse
            </div>

            {{-- 2. DESKTOP GRID TABLE (>= md) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[9px] font-black text-slate-400 uppercase tracking-[0.3em] bg-slate-50/80 border-b border-slate-100 backdrop-blur-md">
                            <th class="py-6 px-6 sm:px-10">Student Node</th>
                            <th class="py-6 px-4 text-center">Terminal</th>
                            <th class="py-6 px-4">Time In</th>
                            <th class="py-6 px-4">Time Out</th>
                            <th class="py-6 px-4 text-center">Hardware Audit</th>
                            <th class="py-6 px-6 sm:px-10 text-right">Total Duration</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/80">
                        @forelse($sessions as $session)
                        <tr class="group hover:bg-slate-50/60 transition-all duration-200">
                            {{-- Student Info --}}
                            <td class="py-5 sm:py-6 px-6 sm:px-10">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-[#D4AF37]/20 to-[#D4AF37]/5 flex items-center justify-center font-black text-[#D4AF37] text-[10px] border border-[#D4AF37]/30 shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                                        {{ strtoupper(substr($session->student_name ?? '??', 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-black text-slate-900 tracking-tight uppercase block leading-none truncate text-xs sm:text-sm group-hover:text-[#D4AF37] transition-colors">
                                            {{ $session->student_name }}
                                        </span>
                                        <span class="text-[8px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1.5 block">
                                            {{ $session->student_id_number }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            {{-- Terminal ID --}}
                            <td class="py-5 sm:py-6 px-4 text-center">
                                <span class="inline-flex items-center px-3 py-1.5 bg-slate-900 text-[#D4AF37] rounded-xl font-black text-[10px] border border-slate-800 uppercase shadow-sm tracking-widest group-hover:border-[#D4AF37]/50 transition-colors whitespace-nowrap">
                                    {{ $session->computer->pc_number ?? 'PC-??' }}
                                </span>
                            </td>

                            {{-- Time In --}}
                            <td class="py-5 sm:py-6 px-4">
                                <div class="text-[10px] font-black uppercase tracking-tight text-slate-900 leading-tight">
                                    {{ optional($session->time_in)->format('M d, Y') ?? 'N/A' }}
                                    <span class="text-slate-400 font-semibold block mt-0.5 text-[9px]">
                                        {{ optional($session->time_in)->format('h:i A') ?? '--:--' }}
                                    </span>
                                </div>
                            </td>

                            {{-- Time Out / Active Status --}}
                            <td class="py-5 sm:py-6 px-4">
                                @if($session->time_out)
                                <div class="text-[10px] font-black uppercase tracking-tight text-slate-700 leading-tight">
                                    <span class="text-slate-400 text-[8px] tracking-widest block font-bold mb-0.5">EXITED AT</span>
                                    {{ $session->time_out->format('h:i A') }}
                                </div>
                                @else
                                <span class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-500/10 text-emerald-600 rounded-full font-black text-[9px] uppercase border border-emerald-500/20 backdrop-blur-sm">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    Active
                                </span>
                                @endif
                            </td>

                            {{-- VECTOR HARDWARE AUDIT TRIGGER BUTTON --}}
                            <td class="py-5 sm:py-6 px-4 text-center">
                                @if($session->checklist)
                                <button type="button"
                                    @click="openAudit(@js([
                                        'student_name' => $session->student_name,
                                        'student_id' => $session->student_id_number,
                                        'pc_number' => $session->computer->pc_number ?? ($session->checklist->pc_number ?? 'PC-??'),
                                        'lab_name' => $session->checklist->lab_name ?? ($session->lab->name ?? 'Default Lab'),
                                        'verified_at' => optional($session->checklist->verified_at)->format('M d, Y • h:i A') ?? optional($session->checklist->created_at)->format('M d, Y • h:i A'),
                                        'monitor_ok' => (bool)$session->checklist->monitor_ok,
                                        'keyboard_ok' => (bool)$session->checklist->keyboard_ok,
                                        'mouse_ok' => (bool)$session->checklist->mouse_ok,
                                        'avr_ok' => (bool)$session->checklist->avr_ok,
                                        'pc_case_ok' => (bool)$session->checklist->pc_case_ok,
                                        'headset_ok' => (bool)$session->checklist->headset_ok,
                                        'all_operational' => (bool)$session->checklist->all_operational,
                                    ]))"
                                    class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-gradient-to-r from-emerald-500/10 via-emerald-500/5 to-transparent hover:from-emerald-500/20 hover:to-emerald-500/10 border border-emerald-500/30 hover:border-emerald-500/60 text-emerald-700 hover:text-emerald-800 rounded-xl font-black text-[9px] uppercase tracking-wider transition-all duration-200 shadow-sm hover:shadow-md hover:shadow-emerald-500/10 active:scale-95 group/btn">
                                    <svg class="w-3.5 h-3.5 text-emerald-500 group-hover/btn:scale-110 transition-transform" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                    </svg>
                                    <span>Verified</span>
                                    <svg class="w-3 h-3 text-emerald-600/70 group-hover/btn:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                    </svg>
                                </button>
                                @else
                                <span class="inline-flex items-center px-2.5 py-1 bg-slate-100 text-slate-400 rounded-xl font-black text-[8px] uppercase tracking-widest border border-slate-200/60">
                                    Ø Unlogged
                                </span>
                                @endif
                            </td>

                            {{-- Session Duration --}}
                            <td class="py-5 sm:py-6 px-6 sm:px-10 text-right">
                                <div class="inline-block bg-slate-50/80 px-4 py-2 rounded-2xl border border-slate-200/60 shadow-sm group-hover:bg-white transition-colors">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-0.5 text-center">Session Length</p>
                                    <span class="text-[11px] font-black uppercase tracking-tight block">
                                        @if($session->time_out)
                                        <span class="text-slate-900">
                                            {{ $session->time_in->diffForHumans($session->time_out, true) }}
                                        </span>
                                        @else
                                        <span class="text-emerald-600 font-black animate-pulse">
                                            In Progress
                                        </span>
                                        @endif
                                    </span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-24 text-center">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <div class="size-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-300 font-black text-lg">
                                        Ø
                                    </div>
                                    <p class="text-slate-400 font-black uppercase tracking-[0.3em] text-xs">
                                        No Session Records Found
                                    </p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Bar --}}
            <div class="p-6 bg-slate-50/50 border-t border-slate-100/80 backdrop-blur-md">
                {{ $sessions->withQueryString()->links() }}
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- GLASSMORPHIC HARDWARE CHECKLIST MODAL (VECTOR ICONS) --}}
        {{-- ========================================================================= --}}
        <div x-cloak x-show="modalOpen" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            {{-- Backdrop --}}
            <div x-show="modalOpen"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="modalOpen = false"
                class="fixed inset-0 bg-slate-950/75 backdrop-blur-md transition-opacity">
            </div>

            {{-- Dialog Container --}}
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                <div x-show="modalOpen"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative transform overflow-hidden rounded-[2.5rem] bg-slate-900 border border-slate-700/70 shadow-2xl transition-all w-full max-w-2xl text-left">

                    {{-- Modal Header --}}
                    <div class="p-6 sm:p-8 bg-gradient-to-b from-slate-800/80 via-slate-850 to-slate-900 border-b border-slate-800 relative">
                        <button type="button" @click="modalOpen = false" class="absolute top-6 right-6 text-slate-400 hover:text-white p-2.5 rounded-xl bg-slate-800/60 hover:bg-slate-800 border border-slate-700/50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-[#D4AF37]/10 border border-[#D4AF37]/30 text-[#D4AF37] text-[9px] font-black uppercase tracking-widest mb-3">
                            <svg class="w-3.5 h-3.5 text-[#D4AF37]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                            <span>Station Hardware Audit Log</span>
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

                    {{-- 6-Item Inspection Grid with Vector Icons --}}
                    <div class="p-6 sm:p-8 space-y-4 bg-slate-900/90">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">

                            {{-- 1. Display Monitor --}}
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
                                        <p class="text-[9px] text-slate-400">Screen panel & video feed</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase font-mono tracking-wider"
                                    :class="audit.monitor_ok ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                                    <span class="size-1.5 rounded-full" :class="audit.monitor_ok ? 'bg-emerald-400' : 'bg-red-400'"></span>
                                    <span x-text="audit.monitor_ok ? 'OPERATIONAL' : 'ISSUE'"></span>
                                </span>
                            </div>

                            {{-- 2. Keyboard Unit --}}
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
                                        <p class="text-[9px] text-slate-400">Keycaps & USB connection</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase font-mono tracking-wider"
                                    :class="audit.keyboard_ok ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                                    <span class="size-1.5 rounded-full" :class="audit.keyboard_ok ? 'bg-emerald-400' : 'bg-red-400'"></span>
                                    <span x-text="audit.keyboard_ok ? 'OPERATIONAL' : 'ISSUE'"></span>
                                </span>
                            </div>

                            {{-- 3. Optical Mouse --}}
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
                                        <p class="text-[9px] text-slate-400">Sensor tracking & clicks</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase font-mono tracking-wider"
                                    :class="audit.mouse_ok ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                                    <span class="size-1.5 rounded-full" :class="audit.mouse_ok ? 'bg-emerald-400' : 'bg-red-400'"></span>
                                    <span x-text="audit.mouse_ok ? 'OPERATIONAL' : 'ISSUE'"></span>
                                </span>
                            </div>

                            {{-- 4. Power Unit (AVR) --}}
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

                            {{-- 5. PC Chassis / Tower --}}
                            <div class="p-4 rounded-2xl bg-slate-800/50 border border-slate-700/50 flex items-center justify-between hover:border-slate-600 transition-colors">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-10 h-10 rounded-xl bg-slate-800/90 border border-slate-700/80 text-[#D4AF37] flex items-center justify-center shrink-0 shadow-sm">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <rect width="16" height="20" x="4" y="2" rx="2" />
                                            <circle cx="12" cy="6" r="1" />
                                            <circle cx="12" cy="10" r="1" />
                                            <line x1="8" x2="16" y1="15" y2="15" />
                                            <line x1="8" x2="16" y1="18" y2="18" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-black text-white uppercase tracking-tight">PC Chassis</h4>
                                        <p class="text-[9px] text-slate-400">Enclosure intact & locked</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase font-mono tracking-wider"
                                    :class="audit.pc_case_ok ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                                    <span class="size-1.5 rounded-full" :class="audit.pc_case_ok ? 'bg-emerald-400' : 'bg-red-400'"></span>
                                    <span x-text="audit.pc_case_ok ? 'OPERATIONAL' : 'ISSUE'"></span>
                                </span>
                            </div>

                            {{-- 6. Headset / Audio --}}
                            <div class="p-4 rounded-2xl bg-slate-800/50 border border-slate-700/50 flex items-center justify-between hover:border-slate-600 transition-colors">
                                <div class="flex items-center gap-3.5">
                                    <div class="w-10 h-10 rounded-xl bg-slate-800/90 border border-slate-700/80 text-[#D4AF37] flex items-center justify-center shrink-0 shadow-sm">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-black text-white uppercase tracking-tight">Audio Headset</h4>
                                        <p class="text-[9px] text-slate-400">Wiring & audio cushions</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[9px] font-black uppercase font-mono tracking-wider"
                                    :class="audit.headset_ok ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                                    <span class="size-1.5 rounded-full" :class="audit.headset_ok ? 'bg-emerald-400' : 'bg-red-400'"></span>
                                    <span x-text="audit.headset_ok ? 'OPERATIONAL' : 'ISSUE'"></span>
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
                        <button type="button" @click="modalOpen = false" class="px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-black text-[10px] uppercase tracking-wider rounded-xl transition-colors active:scale-95">
                            Close Report
                        </button>
                    </div>

                </div>
            </div>
        </div>

    </div>
</x-app-layout>