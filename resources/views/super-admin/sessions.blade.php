<x-app-layout>
    {{-- Header --}}
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="font-black text-2xl sm:text-3xl md:text-4xl text-slate-800 tracking-tighter uppercase">
                    Session <span class="text-[#D4AF37]">History</span>
                </h2>
                <div class="flex items-center space-x-2 mt-1">
                    <div class="size-2 bg-green-500 rounded-full animate-pulse"></div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">
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
    }" @keydown.escape.window="modalOpen = false" class="py-6 sm:py-12 px-4 sm:px-6 max-w-7xl mx-auto min-h-screen">

        {{-- Cinematic Filter Bar --}}
        <div class="mb-8 sm:mb-10">
            <div class="bg-white/80 backdrop-blur-xl border border-slate-100 p-5 sm:p-8 rounded-3xl sm:rounded-[2.5rem] shadow-xl shadow-slate-500/5">
                <form action="{{ url()->current() }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 items-end">

                    <div class="w-full">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Student Name</label>
                        <input type="text" name="student_name" value="{{ request('student_name') }}" placeholder="Search Student..."
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3 px-4">
                    </div>

                    <div class="w-full">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Terminal ID</label>
                        <input type="text" name="pc_number" value="{{ request('pc_number') }}" placeholder="PC-01"
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3 px-4">
                    </div>

                    <div class="w-full">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Activity Date</label>
                        <input type="date" name="date" value="{{ request('date') }}"
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3 px-4">
                    </div>

                    <div class="flex gap-3 w-full">
                        <button type="submit" class="flex-1 bg-slate-900 text-white font-black uppercase text-[10px] px-6 py-3.5 rounded-2xl hover:bg-[#D4AF37] transition-all transform hover:scale-[1.02] active:scale-95 shadow-lg shadow-slate-900/10">
                            Apply
                        </button>
                        <a href="{{ url()->current() }}" class="bg-slate-100 text-slate-400 font-black uppercase text-[10px] px-5 py-3.5 rounded-2xl hover:bg-slate-200 transition-all flex items-center justify-center">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Data Container --}}
        <div class="bg-white/80 backdrop-blur-xl border border-slate-100/60 rounded-3xl sm:rounded-[3rem] overflow-hidden shadow-2xl shadow-slate-500/5">

            {{-- Desktop Table View (Hidden on mobile) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[9px] font-black text-slate-400 uppercase tracking-[0.4em] bg-slate-50/50">
                            <th class="py-6 px-10">Student Node</th>
                            <th class="py-6 px-4 text-center">Terminal</th>
                            <th class="py-6 px-4">Time In</th>
                            <th class="py-6 px-4">Time Out</th>
                            <th class="py-6 px-4 text-center">Hardware Audit</th>
                            <th class="py-6 px-10 text-right">Total Duration</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($sessions as $session)
                        <tr class="group hover:bg-slate-50/50 transition-colors">
                            {{-- Student Info --}}
                            <td class="py-6 px-10">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-[#D4AF37]/10 flex items-center justify-center font-black text-[#D4AF37] text-[10px] border border-[#D4AF37]/20 flex-shrink-0">
                                        {{ strtoupper(substr($session->student_name ?? '??', 0, 2)) }}
                                    </div>
                                    <div>
                                        <span class="font-black text-slate-900 tracking-tighter uppercase block leading-none">{{ $session->student_name }}</span>
                                        <span class="text-[8px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1 block">{{ $session->student_id_number }}</span>
                                    </div>
                                </div>
                            </td>

                            {{-- Terminal Badge --}}
                            <td class="py-6 px-4 text-center">
                                <span class="px-3 py-1 bg-slate-900 text-[#D4AF37] rounded-lg font-black text-[10px] border border-slate-800 uppercase shadow-sm inline-block">
                                    {{ $session->computer->pc_number ?? 'PC-??' }}
                                </span>
                            </td>

                            {{-- Time In --}}
                            <td class="py-6 px-4 whitespace-nowrap">
                                <div class="text-[10px] font-black uppercase tracking-tighter text-slate-900">
                                    {{ $session->time_in ? $session->time_in->format('M d, Y') : 'N/A' }}<br>
                                    <span class="text-slate-400">{{ $session->time_in ? $session->time_in->format('h:i A') : '--:--' }}</span>
                                </div>
                            </td>

                            {{-- Time Out --}}
                            <td class="py-6 px-4 whitespace-nowrap">
                                <div class="text-[10px] font-black uppercase tracking-tighter text-slate-700">
                                    <span class="text-slate-400">EXITED AT</span><br>
                                    {{ $session->time_out ? $session->time_out->format('h:i A') : 'ACTIVE' }}
                                </div>
                            </td>

                            {{-- Hardware Audit Trigger (Desktop) --}}
                            <td class="py-6 px-4 text-center whitespace-nowrap">
                                @if($session->checklist)
                                <button type="button"
                                    @click="openAudit(@js([
                                        'student_name'    => $session->student_name,
                                        'student_id'      => $session->student_id_number,
                                        'pc_number'       => $session->computer->pc_number ?? ($session->checklist->pc_number ?? 'PC-??'),
                                        'lab_name'        => $session->checklist->lab_name ?? ($session->lab->name ?? 'Default Lab'),
                                        'verified_at'     => optional($session->checklist->verified_at)->format('M d, Y • h:i A') ?? optional($session->checklist->created_at)->format('M d, Y • h:i A'),
                                        'system_unit_ok'  => (bool)($session->checklist->system_unit_ok ?? $session->checklist->pc_case_ok ?? true),
                                        'monitor_ok'      => (bool)$session->checklist->monitor_ok,
                                        'avr_ok'          => (bool)$session->checklist->avr_ok,
                                        'mouse_ok'        => (bool)$session->checklist->mouse_ok,
                                        'keyboard_ok'     => (bool)$session->checklist->keyboard_ok,
                                        'cables_ok'       => (bool)($session->checklist->cables_ok ?? $session->checklist->headset_ok ?? true),
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

                            {{-- Total Duration --}}
                            <td class="py-6 px-10 text-right">
                                <div class="inline-block bg-slate-50 px-4 py-2 rounded-xl border border-slate-100">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1 text-center">Session Length</p>
                                    <span class="text-[11px] font-black text-slate-900 uppercase tracking-tight block">
                                        {{ $session->time_out ? $session->time_in->diffForHumans($session->time_out, true) : 'Ongoing' }}
                                    </span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-32 text-center text-slate-300 font-black uppercase tracking-[0.5em] text-xs">
                                No session records found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile Card View (Visible on small screens) --}}
            <div class="block md:hidden divide-y divide-slate-100">
                @forelse($sessions as $session)
                <div class="p-5 space-y-4 hover:bg-slate-50/50 transition-colors">
                    {{-- Header: Avatar, Name, Terminal & Audit Trigger --}}
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-full bg-[#D4AF37]/10 flex items-center justify-center font-black text-[#D4AF37] text-xs border border-[#D4AF37]/20 flex-shrink-0">
                                {{ strtoupper(substr($session->student_name ?? '??', 0, 2)) }}
                            </div>
                            <div class="min-w-0">
                                <span class="font-black text-slate-900 tracking-tighter uppercase block leading-tight text-sm truncate">{{ $session->student_name }}</span>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.15em] block">{{ $session->student_id_number }}</span>
                            </div>
                        </div>

                        {{-- Terminal Badge + Mobile Audit Button --}}
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="px-3 py-1 bg-slate-900 text-[#D4AF37] rounded-lg font-black text-[10px] border border-slate-800 uppercase shadow-sm">
                                {{ $session->computer->pc_number ?? 'PC-??' }}
                            </span>

                            @if($session->checklist)
                            <button type="button"
                                @click="openAudit(@js([
                                    'student_name'    => $session->student_name,
                                    'student_id'      => $session->student_id_number,
                                    'pc_number'       => $session->computer->pc_number ?? ($session->checklist->pc_number ?? 'PC-??'),
                                    'lab_name'        => $session->checklist->lab_name ?? ($session->lab->name ?? 'Default Lab'),
                                    'verified_at'     => optional($session->checklist->verified_at)->format('M d, Y • h:i A') ?? optional($session->checklist->created_at)->format('M d, Y • h:i A'),
                                    'system_unit_ok'  => (bool)($session->checklist->system_unit_ok ?? $session->checklist->pc_case_ok ?? true),
                                    'monitor_ok'      => (bool)$session->checklist->monitor_ok,
                                    'avr_ok'          => (bool)$session->checklist->avr_ok,
                                    'mouse_ok'        => (bool)$session->checklist->mouse_ok,
                                    'keyboard_ok'     => (bool)$session->checklist->keyboard_ok,
                                    'cables_ok'       => (bool)($session->checklist->cables_ok ?? $session->checklist->headset_ok ?? true),
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

                    {{-- Body: Time Details Grid --}}
                    <div class="grid grid-cols-2 gap-3 bg-slate-50/60 p-3.5 rounded-2xl border border-slate-100">
                        <div>
                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block mb-0.5">Time In</span>
                            <p class="text-[10px] font-black text-slate-900 uppercase tracking-tighter">
                                {{ $session->time_in ? $session->time_in->format('M d, Y') : 'N/A' }}
                            </p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                                {{ $session->time_in ? $session->time_in->format('h:i A') : '--:--' }}
                            </p>
                        </div>

                        <div>
                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block mb-0.5">Time Out</span>
                            <p class="text-[10px] font-black text-slate-700 uppercase tracking-tighter">
                                {{ $session->time_out ? $session->time_out->format('M d, Y') : 'ACTIVE' }}
                            </p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                                {{ $session->time_out ? $session->time_out->format('h:i A') : 'Ongoing' }}
                            </p>
                        </div>
                    </div>

                    {{-- Footer: Duration Badge --}}
                    <div class="flex justify-between items-center pt-1">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Total Duration</span>
                        <div class="bg-slate-900 px-3 py-1 rounded-xl">
                            <span class="text-[10px] font-black text-[#D4AF37] uppercase tracking-tight">
                                {{ $session->time_out ? $session->time_in->diffForHumans($session->time_out, true) : 'Ongoing' }}
                            </span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="py-16 px-4 text-center text-slate-300 font-black uppercase tracking-[0.3em] text-xs">
                    No session records found
                </div>
                @endforelse
            </div>

            {{-- Pagination Footer --}}
            <div class="p-4 sm:p-6 bg-slate-50/50 border-t border-slate-100">
                {{ $sessions->links() }}
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- GLASSMORPHIC HARDWARE CHECKLIST MODAL (6 UPDATED HARDWARE ITEMS) --}}
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

                    {{-- 6-Item Inspection Grid (Updated to match Python Client & DB Migration) --}}
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
                        <button type="button" @click="modalOpen = false" class="px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-black text-[10px] uppercase tracking-wider rounded-xl transition-colors active:scale-95">
                            Close Report
                        </button>
                    </div>

                </div>
            </div>
        </div>

    </div>
</x-app-layout>