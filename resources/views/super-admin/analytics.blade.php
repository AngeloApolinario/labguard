<x-app-layout>
    <div class="py-6 sm:py-10 px-4 sm:px-8 max-w-7xl mx-auto min-h-screen">

        {{-- Cinematic Header --}}
        <x-slot name="header">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="font-black text-2xl sm:text-4xl text-slate-800 tracking-tighter uppercase">
                        Hardware & Lab <span class="text-[#D4AF37]">Analytics</span>
                    </h2>
                    <div class="flex items-center space-x-2 mt-1">
                        <div class="size-2 bg-emerald-500 rounded-full animate-pulse"></div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            Workstation Diagnostics & Operations Hub
                        </p>
                    </div>
                </div>


            </div>
        </x-slot>

        {{-- ========================================================================= --}}
        {{-- INTERACTIVE CONTROL BAR (POLISHED GRID + DATE LOCK + TOP-LAYER DROPDOWN)  --}}
        {{-- ========================================================================= --}}
        @php
        $todayStr = now()->format('Y-m-d');
        $past7Str = now()->subDays(7)->format('Y-m-d');
        $past30Str = now()->subDays(30)->format('Y-m-d');

        $isToday = ($startDateInput === $todayStr && $endDateInput === $todayStr);
        $isPast7 = ($startDateInput === $past7Str && $endDateInput === $todayStr);
        $isPast30 = ($startDateInput === $past30Str && $endDateInput === $todayStr);
        $isAllTime = ($startDateInput === '2024-01-01' || request('preset') === 'all');
        @endphp

        <div x-data="{
            exportOpen: false,
            today: '{{ $todayStr }}',
            formatDate(date) {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            },
            setPreset(days) {
                const end = new Date();
                const start = new Date();
                if (days === 'all') {
                    start.setFullYear(2024, 0, 1);
                } else {
                    start.setDate(end.getDate() - days);
                }
                document.getElementById('start_date').value = this.formatDate(start);
                document.getElementById('end_date').value = this.formatDate(end);
                document.getElementById('telemetryFilterForm').submit();
            },
            validateDates() {
                const start = document.getElementById('start_date');
                const end = document.getElementById('end_date');
                if (end.value > this.today) {
                    end.value = this.today;
                }
                if (start.value > end.value) {
                    start.value = end.value;
                }
            }
        }"
            class="mb-8 relative z-50 bg-white/95 backdrop-blur-xl p-5 sm:p-7 rounded-[2rem] border border-slate-200/80 shadow-xl shadow-slate-900/5 [isolation:isolate]">

            <form id="telemetryFilterForm" action="{{ url()->current() }}" method="GET" class="space-y-4">

                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">

                    {{-- 1. Laboratory Zone Filter --}}
                    <div class="md:col-span-4 xl:col-span-3 w-full">
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5 ml-1">
                            Laboratory Facility
                        </label>
                        <div class="relative">
                            <select name="lab_id" onchange="this.form.submit()" class="w-full h-11 appearance-none pl-3.5 pr-10 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:ring-2 focus:ring-[#D4AF37] focus:border-[#D4AF37] transition-all cursor-pointer truncate">
                                <option value="">All Campus Laboratories</option>
                                @foreach($allLabs as $lab)
                                <option value="{{ $lab->id }}" {{ ($selectedLabId == $lab->id) ? 'selected' : '' }}>
                                    {{ $lab->name }}
                                </option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    {{-- 2. Calendar Pop-up Date Inputs --}}
                    <div class="md:col-span-8 xl:col-span-5 w-full">
                        <div class="grid grid-cols-2 gap-2.5">
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5 ml-1">From Date</label>
                                <input type="date"
                                    id="start_date"
                                    name="start_date"
                                    max="{{ $todayStr }}"
                                    @change="validateDates()"
                                    value="{{ $startDateInput }}"
                                    class="w-full h-11 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold tabular-nums text-slate-800 focus:ring-2 focus:ring-[#D4AF37] focus:border-[#D4AF37] transition-all [color-scheme:light] cursor-pointer">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1.5 ml-1">To Date</label>
                                <input type="date"
                                    id="end_date"
                                    name="end_date"
                                    max="{{ $todayStr }}"
                                    @change="validateDates()"
                                    value="{{ $endDateInput }}"
                                    class="w-full h-11 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold tabular-nums text-slate-800 focus:ring-2 focus:ring-[#D4AF37] focus:border-[#D4AF37] transition-all [color-scheme:light] cursor-pointer">
                            </div>
                        </div>
                    </div>

                    {{-- 3. Action Group --}}
                    <div class="md:col-span-12 xl:col-span-4 w-full">
                        <label class="hidden md:block xl:block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1 select-none">
                            Actions
                        </label>
                        <div class="flex items-center gap-2 w-full">
                            <button type="submit" class="h-11 px-5 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-black uppercase tracking-wider transition-all active:scale-95 shadow-sm inline-flex items-center justify-center shrink-0">
                                Apply
                            </button>

                            <a href="{{ url()->current() }}" class="h-11 px-3.5 inline-flex items-center justify-center bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold uppercase tracking-wider transition-all shrink-0">
                                Reset
                            </a>

                            {{-- Export Dropdown --}}
                            <div class="relative flex-1" @click.away="exportOpen = false" @keydown.escape.window="exportOpen = false">
                                <button type="button"
                                    @click="exportOpen = !exportOpen"
                                    class="w-full h-11 px-3.5 bg-gradient-to-r from-[#D4AF37] to-amber-500 hover:from-amber-400 hover:to-amber-600 text-slate-950 font-black rounded-xl text-xs uppercase tracking-wider shadow-md shadow-amber-500/15 transition-all flex items-center justify-center gap-1.5 active:scale-95 whitespace-nowrap">
                                    <svg class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                    <span>Export Report</span>
                                    <svg class="size-3.5 shrink-0 transition-transform duration-200" :class="{'rotate-180': exportOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>

                                <div x-show="exportOpen"
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                                    class="absolute right-0 mt-2 w-72 sm:w-80 max-w-[calc(100vw-2.5rem)] bg-white border border-slate-200 rounded-2xl shadow-[0_20px_60px_rgba(0,0,0,0.18)] z-[9999] p-2 space-y-1 ring-1 ring-black/10">

                                    <a href="{{ route('super-admin.analytics.export', array_merge(request()->query(), ['type' => 'checklists'])) }}"
                                        class="flex items-start gap-3 p-3 rounded-xl hover:bg-slate-50 transition-colors group">
                                        <div class="p-2 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200 shrink-0 group-hover:bg-emerald-500 group-hover:text-white transition-colors">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-xs font-black uppercase text-slate-900 tracking-tight">Hardware Check-ins</span>
                                            <span class="block text-[11px] text-slate-500 font-medium mt-0.5 leading-snug">Audit inspection logs, 6 peripheral states, student logins</span>
                                        </div>
                                    </a>

                                    <a href="{{ route('super-admin.analytics.export', array_merge(request()->query(), ['type' => 'alerts'])) }}"
                                        class="flex items-start gap-3 p-3 rounded-xl hover:bg-slate-50 transition-colors group border-t border-slate-100">
                                        <div class="p-2 rounded-lg bg-rose-50 text-rose-600 border border-rose-200 shrink-0 group-hover:bg-rose-500 group-hover:text-white transition-colors">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <span class="block text-xs font-black uppercase text-slate-900 tracking-tight">Security & Alert Logs</span>
                                            <span class="block text-[11px] text-slate-500 font-medium mt-0.5 leading-snug">Defect reports, false alarms, and resolution history</span>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Range Presets --}}
                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mr-1 flex items-center gap-1">
                            <svg class="size-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Range Presets:
                        </span>
                        <button type="button" @click="setPreset(0)" class="px-3 py-1 rounded-lg text-[10px] uppercase tracking-wider transition-all {{ $isToday ? 'bg-slate-900 text-[#D4AF37] font-black shadow-sm ring-1 ring-slate-800' : 'bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold border border-slate-200/80' }}">Today</button>
                        <button type="button" @click="setPreset(7)" class="px-3 py-1 rounded-lg text-[10px] uppercase tracking-wider transition-all {{ $isPast7 ? 'bg-slate-900 text-[#D4AF37] font-black shadow-sm ring-1 ring-slate-800' : 'bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold border border-slate-200/80' }}">Past 7 Days</button>
                        <button type="button" @click="setPreset(30)" class="px-3 py-1 rounded-lg text-[10px] uppercase tracking-wider transition-all {{ $isPast30 ? 'bg-slate-900 text-[#D4AF37] font-black shadow-sm ring-1 ring-slate-800' : 'bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold border border-slate-200/80' }}">Past 30 Days</button>
                        <button type="button" @click="setPreset('all')" class="px-3 py-1 rounded-lg text-[10px] uppercase tracking-wider transition-all {{ $isAllTime ? 'bg-slate-900 text-[#D4AF37] font-black shadow-sm ring-1 ring-slate-800' : 'bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold border border-slate-200/80' }}">All Time</button>
                    </div>

                    <div class="flex items-center gap-1.5 text-[11px] text-slate-500 font-medium shrink-0">
                        <span class="size-1.5 rounded-full bg-[#D4AF37]"></span>
                        <span>Scope:</span>
                        <span class="font-mono font-bold text-slate-900 bg-slate-100 px-2.5 py-0.5 rounded-md border border-slate-200/60">{{ $rangeLabel }}</span>
                    </div>
                </div>
            </form>
        </div>

        {{-- ========================================================================= --}}
        {{-- TOP 4 CRITICAL TELEMETRY STATS --}}
        {{-- ========================================================================= --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">

            {{-- Stat 1: Hardware Integrity Rate --}}
            <div class="bg-white/95 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/80 shadow-xl shadow-slate-900/5 group hover:border-[#D4AF37] transition-all">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Hardware Health Index</span>
                    <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight font-mono">{{ $hardwareIntegrityRate }}%</h3>
                    <span class="text-[10px] font-black text-emerald-600 uppercase">Operational</span>
                </div>
                <div class="w-full bg-slate-100 h-2 rounded-full mt-3 overflow-hidden">
                    <div class="bg-emerald-500 h-full rounded-full transition-all duration-1000" style="width: {{ $hardwareIntegrityRate }}%"></div>
                </div>
            </div>

            {{-- Stat 2: Verified Check-ins --}}
            <div class="bg-white/95 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/80 shadow-xl shadow-slate-900/5 group hover:border-[#D4AF37] transition-all">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Verified Check-ins</span>
                    <div class="p-2 rounded-xl bg-amber-500/10 text-amber-600 border border-amber-500/20">
                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.125 2.25h-4.5c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125v-9M10.125 2.25h.375a9 9 0 019 9v.375M10.125 2.25A3.375 3.375 0 0113.5 5.625v1.5c0 .621.504 1.125 1.125 1.125h1.5a3.375 3.375 0 013.375 3.375M9 15l2.25 2.25L15 12" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight font-mono">{{ number_format($totalChecklists) }}</h3>
                <p class="text-[10px] font-bold text-slate-500 uppercase mt-2">
                    <span class="text-rose-600 font-black">{{ $flaggedChecklists }} flagged</span> discrepancies
                </p>
            </div>

            {{-- Stat 3: Live Workstation Fleet --}}
            <div class="bg-white/95 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/80 shadow-xl shadow-slate-900/5 group hover:border-[#D4AF37] transition-all">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Live Workstation Fleet</span>
                    <div class="p-2 rounded-xl bg-blue-500/10 text-blue-600 border border-blue-500/20">
                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3" />
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight font-mono">{{ $fleetActive }}</h3>
                    <span class="text-xs font-black text-slate-400 font-mono">/ {{ $totalComputers }} Online</span>
                </div>
                <div class="flex items-center gap-2.5 mt-2 text-[10px] font-black uppercase tracking-wider">
                    <span class="text-emerald-600">{{ $fleetAvailable }} Free</span>
                    <span class="text-slate-300">•</span>
                    <span class="text-rose-600">{{ $fleetMaint }} Quarantine</span>
                </div>
            </div>

            {{-- Stat 4: Incident Response Triage --}}
            <div class="bg-white/95 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/80 shadow-xl shadow-slate-900/5 group hover:border-[#D4AF37] transition-all">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Open Incidents</span>
                    <div class="p-2 rounded-xl {{ $pendingAlerts > 0 ? 'bg-rose-500/10 text-rose-600 border border-rose-500/20' : 'bg-slate-100 text-slate-400' }}">
                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-3xl sm:text-4xl font-black {{ $pendingAlerts > 0 ? 'text-rose-600' : 'text-slate-900' }} tracking-tight font-mono">{{ $pendingAlerts }}</h3>
                    <span class="text-[10px] font-black text-slate-400 uppercase">Attention Needed</span>
                </div>
                <p class="text-[10px] font-bold text-slate-500 uppercase mt-2">
                    <span class="text-emerald-600 font-black">{{ $resolvedAlerts }} resolved</span> • {{ $discardedAlerts }} discarded
                </p>
            </div>

        </div>

        {{-- ========================================================================= --}}
        {{-- SECTION 2: HARDWARE AUDIT MATRIX & WEAR-AND-TEAR RADAR (NEW 6 ITEMS)     --}}
        {{-- ========================================================================= --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 mb-8">

            {{-- 1. Peripheral Defect Matrix (Updated to 6 Configured Items) --}}
            <div class="lg:col-span-7 bg-white/95 backdrop-blur-xl p-6 sm:p-8 rounded-[2rem] border border-slate-200/80 shadow-xl shadow-slate-900/5 flex flex-col justify-between">
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-6">
                        <div>
                            <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight">Workstation Peripheral Wear Index</h3>
                            <p class="text-xs text-slate-500 font-medium mt-0.5">Component discrepancies flagged by students during check-in</p>
                        </div>
                        <span class="self-start sm:self-auto px-3 py-1 bg-slate-900 text-[#D4AF37] rounded-xl text-[10px] font-mono font-black uppercase tracking-widest border border-slate-800">
                            6-Point Verification
                        </span>
                    </div>

                    {{-- Component Progress Matrix (Updated: system_unit, monitor, avr, mouse, keyboard, cables) --}}
                    <div class="space-y-3.5">
                        @php
                        $maxFailures = max(max(array_values($peripheralFailures ?? [1])), 1);
                        $peripheralMeta = [
                        'system_unit' => [
                        'name' => 'System Unit',
                        'desc' => 'Power Button, Chassis, Hardware Defects',
                        'icon' => 'M5 4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4zm7 2a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm0 4a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm-3 5h6m-6 3h6'
                        ],
                        'monitor' => [
                        'name' => 'Display Monitor',
                        'desc' => 'Cracks, Dead Pixels, No Video Signal',
                        'icon' => 'M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3'
                        ],
                        'avr' => [
                        'name' => 'Power Regulator (AVR)',
                        'desc' => 'Voltage Surge Active, Grounded Power',
                        'icon' => 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z'
                        ],
                        'mouse' => [
                        'name' => 'Optical Mouse',
                        'desc' => 'Laser Tracking, Unresponsive Buttons',
                        'icon' => 'M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672z'
                        ],
                        'keyboard' => [
                        'name' => 'Keyboard Unit',
                        'desc' => 'Missing Keys, Stuck Switches, Typing Response',
                        'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h12A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6zM6 7.5h.008v.008H6V7.5zm3.75 0h.008v.008H9.75V7.5zm3.75 0h.008v.008H13.5V7.5zm3.75 0h.008v.008H17.25V7.5z'
                        ],
                        'cables' => [
                        'name' => 'Power & I/O Cables',
                        'desc' => 'HDMI/VGA Display, Power, and USB Cords',
                        'icon' => 'M9 3v4m6-4v4m-8 4h10a2 2 0 012 2v1a5 5 0 01-5 5H10a5 5 0 01-5-5v-1a2 2 0 012-2zm5 11v4'
                        ],
                        ];
                        @endphp

                        @foreach($peripheralMeta as $key => $meta)
                        @php
                        // Fallbacks for backwards compatibility with legacy database records
                        $failCount = $peripheralFailures[$key] ?? (
                        $key === 'system_unit' ? ($peripheralFailures['chassis'] ?? 0) :
                        ($key === 'cables' ? ($peripheralFailures['headset'] ?? 0) : 0)
                        );
                        $pct = round(($failCount / $maxFailures) * 100);
                        @endphp
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70 hover:bg-slate-100/70 transition-colors">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-3">
                                    <div class="size-8 rounded-xl bg-white border border-slate-200 text-amber-600 flex items-center justify-center shrink-0 shadow-sm">
                                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $meta['icon'] }}" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-black text-slate-800 uppercase tracking-tight">{{ $meta['name'] }}</h4>
                                        <p class="text-[10px] text-slate-400 font-bold">{{ $meta['desc'] }}</p>
                                    </div>
                                </div>

                                <div class="text-right">
                                    @if($failCount > 0)
                                    <span class="text-xs font-mono font-black text-rose-600">
                                        {{ $failCount }} {{ Str::plural('Defect', $failCount) }}
                                    </span>
                                    @else
                                    <span class="text-[10px] font-black uppercase text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                        Flawless
                                    </span>
                                    @endif
                                </div>
                            </div>

                            <div class="w-full bg-slate-200/80 h-2 rounded-full overflow-hidden">
                                @if($failCount > 0)
                                <div class="bg-gradient-to-r from-amber-500 to-rose-500 h-full rounded-full transition-all duration-700" style="width: {{ $pct }}%"></div>
                                @else
                                <div class="bg-emerald-400/30 h-full rounded-full" style="width: 100%"></div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    <span>Live Telemetry Stream</span>
                    <span class="text-slate-600">Station Terminal Synced</span>
                </div>
            </div>

            {{-- 2. Check-in Integrity Ratio Doughnut --}}
            <div class="lg:col-span-5 bg-white/95 backdrop-blur-xl p-6 sm:p-8 rounded-[2rem] border border-slate-200/80 shadow-xl shadow-slate-900/5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight">Checklist Ratio</h3>
                        <span class="text-[10px] font-black text-emerald-700 bg-emerald-500/15 border border-emerald-500/20 px-2.5 py-1 rounded-full uppercase">
                            Audit Compliance
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 font-medium mb-6">Ratio of clean, flawless check-ins versus student-reported discrepancies</p>

                    {{-- Chart Container --}}
                    <div class="relative flex items-center justify-center p-2">
                        <div class="relative size-56 flex items-center justify-center">
                            <canvas id="checklistDoughnutChart"></canvas>
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center">
                                <span class="text-3xl font-black text-slate-900 font-mono">{{ $hardwareIntegrityRate }}%</span>
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Passing Rate</span>
                            </div>
                        </div>
                    </div>

                    {{-- Breakdown Pills --}}
                    <div class="grid grid-cols-2 gap-3 mt-6">
                        <div class="p-3.5 bg-emerald-500/5 rounded-2xl border border-emerald-500/20 text-center">
                            <span class="text-[10px] font-black text-emerald-700 uppercase tracking-wider block">Flawless Passes</span>
                            <span class="text-xl font-black text-slate-900 font-mono mt-0.5 block">{{ number_format($flawlessChecklists) }}</span>
                        </div>
                        <div class="p-3.5 bg-rose-500/5 rounded-2xl border border-rose-500/20 text-center">
                            <span class="text-[10px] font-black text-rose-700 uppercase tracking-wider block">Issues Flagged</span>
                            <span class="text-xl font-black text-slate-900 font-mono mt-0.5 block">{{ number_format($flaggedChecklists) }}</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 text-center text-[10px] text-slate-400 font-bold uppercase tracking-widest">
                    Inspection protocol enforced prior to desktop unlock
                </div>
            </div>

        </div>

        {{-- ========================================================================= --}}
        {{-- SECTION 3: HOURLY CHECK-IN VOLUME --}}
        {{-- ========================================================================= --}}
        <div class="bg-white/95 backdrop-blur-xl p-6 sm:p-8 rounded-[2rem] border border-slate-200/80 shadow-xl shadow-slate-900/5 mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                <div>
                    <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight">Lab Traffic & Check-in Velocity</h3>
                    <p class="text-xs text-slate-500 font-medium">Terminal check-in peak distribution by hour of day (7:00 AM – 8:00 PM)</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="size-2.5 rounded-full bg-amber-500"></span>
                    <span class="text-[10px] font-black text-slate-600 uppercase tracking-wider">Student Sessions</span>
                </div>
            </div>

            <div class="relative h-64 w-full">
                <canvas id="trafficAreaChart"></canvas>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- SECTION 4: TECHNICIAN ACTION QUEUE (FLAGGED STATIONS) --}}
        {{-- ========================================================================= --}}
        <div class="bg-white/95 backdrop-blur-xl p-6 sm:p-8 rounded-[2rem] border border-slate-200/80 shadow-xl shadow-slate-900/5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
                <div>
                    <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight">Technician Triage Queue</h3>
                    <p class="text-xs text-slate-500 font-medium">Recent workstation check-ins that detected broken or missing components</p>
                </div>
                <a href="{{ route('dashboard.sessions.index') }}" class="text-[10px] font-black text-amber-700 hover:text-amber-800 hover:underline uppercase tracking-wider flex items-center gap-1">
                    <span>View All Station Logs</span>
                    <svg class="size-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($recentIssues as $audit)
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 hover:border-rose-300 transition-all space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="size-8 rounded-xl bg-slate-900 text-[#D4AF37] font-mono font-black text-xs flex items-center justify-center">
                                {{ $audit->pc_number }}
                            </div>
                            <div>
                                <h4 class="text-xs font-black text-slate-900 uppercase">{{ $audit->lab_name ?? 'Default Lab' }}</h4>
                                <span class="text-[10px] font-mono text-slate-400 font-bold block">{{ $audit->student_id_number }}</span>
                            </div>
                        </div>

                        <span class="px-2.5 py-1 rounded-lg text-[9px] font-mono font-black uppercase bg-rose-500/10 text-rose-600 border border-rose-500/20">
                            Discrepancy
                        </span>
                    </div>

                    {{-- Badges of failed components (Updated to the 6 items with backwards compatibility) --}}
                    <div>
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider block mb-1.5">Failed Components</span>
                        <div class="flex flex-wrap gap-1.5">
                            @if(isset($audit->system_unit_ok) ? !$audit->system_unit_ok : (isset($audit->pc_case_ok) && !$audit->pc_case_ok))
                            <span class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-700 text-[9px] font-black uppercase">System Unit</span>
                            @endif

                            @if(!$audit->monitor_ok)
                            <span class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-700 text-[9px] font-black uppercase">Monitor</span>
                            @endif

                            @if(!$audit->avr_ok)
                            <span class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-700 text-[9px] font-black uppercase">AVR</span>
                            @endif

                            @if(!$audit->mouse_ok)
                            <span class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-700 text-[9px] font-black uppercase">Mouse</span>
                            @endif

                            @if(!$audit->keyboard_ok)
                            <span class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-700 text-[9px] font-black uppercase">Keyboard</span>
                            @endif

                            @if(isset($audit->cables_ok) ? !$audit->cables_ok : (isset($audit->headset_ok) && !$audit->headset_ok))
                            <span class="px-2 py-0.5 rounded-md bg-rose-100 text-rose-700 text-[9px] font-black uppercase">Cables</span>
                            @endif
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between text-[10px] text-slate-500 font-bold">
                        <span>
                            {{ $audit->verified_at ? \Carbon\Carbon::parse($audit->verified_at)->diffForHumans() : 'Just now' }}
                        </span>
                        <a href="{{ route('dashboard.sessions.index', ['pc_number' => $audit->pc_number]) }}" class="text-amber-700 font-black hover:underline uppercase">
                            Audit Station →
                        </a>
                    </div>
                </div>
                @empty
                <div class="col-span-full py-12 text-center bg-slate-50 rounded-2xl border border-slate-200/60">
                    <svg class="size-8 text-emerald-500 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-xs font-black text-slate-800 uppercase tracking-wider">All Workstations Verified Operational</p>
                    <p class="text-[10px] text-slate-400 font-bold uppercase mt-0.5">No hardware discrepancies reported during current inspection window</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ========================================================================= --}}
    {{-- CHART.JS INITIALIZATION --}}
    {{-- ========================================================================= --}}
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Doughnut Chart
            const flawless = {
                {
                    (int)($flawlessChecklists ?? 0)
                }
            };
            const flagged = {
                {
                    (int)($flaggedChecklists ?? 0)
                }
            };
            const hasData = (flawless + flagged) > 0;

            const ctxDoughnut = document.getElementById('checklistDoughnutChart').getContext('2d');
            new Chart(ctxDoughnut, {
                type: 'doughnut',
                data: {
                    labels: hasData ? ['Flawless Passes', 'Flagged Issues'] : ['No Data Recorded'],
                    datasets: [{
                        data: hasData ? [flawless, flagged] : [1],
                        backgroundColor: hasData ? ['#10b981', '#f43f5e'] : ['#e2e8f0'],
                        borderWidth: 0,
                        hoverOffset: hasData ? 4 : 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '76%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: hasData,
                            backgroundColor: '#0f172a',
                            titleFont: {
                                family: 'Arial',
                                size: 12,
                                weight: 'bold'
                            },
                            bodyFont: {
                                family: 'Arial',
                                size: 11
                            },
                            padding: 10,
                            cornerRadius: 8,
                        }
                    }
                }
            });

            // 2. Area Chart: Hourly Check-in Velocity
            const ctxTraffic = document.getElementById('trafficAreaChart').getContext('2d');
            const gradient = ctxTraffic.createLinearGradient(0, 0, 0, 240);
            gradient.addColorStop(0, 'rgba(212, 175, 55, 0.35)');
            gradient.addColorStop(1, 'rgba(212, 175, 55, 0.0)');

            const hourlyLabels = @json(array_column($hourlyData ?? [], 'hour'));
            const hourlyCounts = @json(array_column($hourlyData ?? [], 'count'));

            new Chart(ctxTraffic, {
                type: 'line',
                data: {
                    labels: hourlyLabels,
                    datasets: [{
                        label: 'Check-ins',
                        data: hourlyCounts,
                        borderColor: '#D4AF37',
                        borderWidth: 2.5,
                        backgroundColor: gradient,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3.5,
                        pointBackgroundColor: '#0f172a',
                        pointBorderColor: '#D4AF37',
                        pointBorderWidth: 2,
                        pointHoverRadius: 5.5,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleFont: {
                                family: 'Arial',
                                size: 12,
                                weight: 'bold'
                            },
                            bodyFont: {
                                family: 'Arial',
                                size: 11
                            },
                            padding: 10,
                            cornerRadius: 8,
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: 'Arial',
                                    size: 10,
                                    weight: 'bold'
                                },
                                color: '#94a3b8'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(226, 232, 240, 0.7)'
                            },
                            ticks: {
                                font: {
                                    family: 'Arial',
                                    size: 10,
                                    weight: 'bold'
                                },
                                color: '#94a3b8',
                                precision: 0
                            }
                        }
                    }
                }
            });
        });
    </script>
    @endpush
</x-app-layout>