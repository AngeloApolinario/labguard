<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-6">
            <div>
                <h2 class="font-black text-2xl sm:text-3xl md:text-4xl text-slate-800 tracking-tighter uppercase">
                    Facility <span class="text-[#D4AF37]">Operations</span>
                </h2>
                <div class="flex items-center space-x-2 mt-1">
                    <div class="size-2 bg-emerald-500 rounded-full animate-pulse"></div>
                    <p class="text-[9px] sm:text-[10px] font-black text-slate-400 uppercase tracking-[0.25em]">
                        Live Campus Lab Monitoring & Diagnostics
                    </p>
                </div>
            </div>

            {{-- System Telemetry Pulse --}}
            <div class="flex items-center gap-3 bg-white border border-slate-200/80 p-2 sm:p-2.5 rounded-2xl shadow-xs">
                <div class="px-3 py-1.5 bg-emerald-500/10 rounded-xl border border-emerald-500/20 flex items-center gap-2">
                    <span class="size-1.5 rounded-full bg-emerald-500 animate-ping"></span>
                    <span class="text-[9px] font-black text-emerald-700 uppercase tracking-wider">Sync Active</span>
                </div>
                <div class="px-2 pr-3 border-l border-slate-100">
                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">System Date</p>
                    <p class="text-[11px] font-mono font-black text-slate-800">{{ now()->format('D, M d • h:i A') }}</p>
                </div>
            </div>
        </div>
    </x-slot>

    {{-- Main Container with Reactive Alpine Store --}}
    <div x-data="{ 
            search: '', 
            filter: 'all',
            labs: @js($labs->map(function($l) {
                $isMaint = $l->isUnderMaintenance();
                $occ = (int)($l->occupied ?? 0);
                $tot = (int)($l->total ?? 0);
                $pendingAlerts = $l->pending_alerts_count ?? ($l->alerts ? $l->alerts->where('status', 'pending')->count() : 0);
                return [
                    'id' => $l->id,
                    'name' => (string)$l->name,
                    'location' => (string)($l->location ?? ''),
                    'status' => $isMaint ? 'maintenance' : ($occ > 0 ? 'active' : 'free'),
                    'occupied' => $occ,
                    'total' => $tot,
                    'hasAlerts' => $pendingAlerts > 0,
                ];
            })),
            isVisible(lab) {
                if (!lab) return false;
                const query = this.search.toLowerCase().trim();
                const text = (lab.name + ' ' + lab.location).toLowerCase();
                if (query && !text.includes(query)) return false;

                if (this.filter === 'active') return lab.occupied > 0 && lab.status !== 'maintenance';
                if (this.filter === 'free') return lab.occupied === 0 && lab.status !== 'maintenance';
                if (this.filter === 'maintenance') return lab.status === 'maintenance';
                if (this.filter === 'alerts') return lab.hasAlerts;
                return true;
            },
            get visibleCount() {
                return this.labs.filter(l => this.isVisible(l)).length;
            },
            get emptyTitle() {
                if (this.filter === 'free') return 'No Laboratory is Currently Vacant';
                if (this.filter === 'active') return 'No Laboratory In-Session';
                if (this.filter === 'maintenance') return 'All Laboratories Operational';
                if (this.filter === 'alerts') return 'Zero Discrepancies Reported';
                if (this.search) return 'No Matching Facilities Found';
                return 'No Facilities Match Filters';
            },
            get emptySubtitle() {
                if (this.filter === 'free') return 'All computer laboratory workstations across the campus are currently in use or occupied by active sessions.';
                if (this.filter === 'active') return 'There are currently no classes or active student sessions running at this time.';
                if (this.filter === 'maintenance') return 'All laboratory equipment and rooms are healthy. No facilities are currently under quarantine.';
                if (this.filter === 'alerts') return 'All workstations have passed inspection with no pending technical support alerts.';
                if (this.search) return 'We couldn\'t find any laboratory matching “' + this.search + '”. Please check your spelling or search by room number.';
                return 'Try resetting your active filters to display all laboratory facilities.';
            }
        }"
        class="py-6 sm:py-8 md:py-12 px-4 sm:px-6 bg-[#F8FAFC] min-h-screen">

        <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">

            {{-- ========================================================================= --}}
            {{-- 1. OPERATIONAL TELEMETRY METRICS --}}
            {{-- ========================================================================= --}}
            @php
            $totalPcs = $labs->sum('total');
            $occupiedPcs = $labs->sum('occupied');
            $availablePcs = max(0, $totalPcs - $occupiedPcs);
            $occupancyRate = $totalPcs > 0 ? round(($occupiedPcs / $totalPcs) * 100) : 0;
            @endphp

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                {{-- Overall Occupancy --}}
                <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-[#D4AF37] transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Campus Occupancy</span>
                        <span class="px-2 py-0.5 rounded-lg text-[9px] font-black font-mono {{ $occupancyRate > 80 ? 'bg-rose-50 text-rose-600' : 'bg-emerald-50 text-emerald-600' }}">
                            {{ $occupancyRate }}%
                        </span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h4 class="text-3xl font-black text-slate-900 tracking-tight font-mono">{{ $occupiedPcs }}</h4>
                        <span class="text-xs font-bold text-slate-400">/ {{ $totalPcs }} Workstations</span>
                    </div>
                    <div class="w-full bg-slate-100 h-1.5 rounded-full mt-4 overflow-hidden">
                        <div class="bg-slate-900 h-full rounded-full transition-all duration-700" style="width: {{ $occupancyRate }}%"></div>
                    </div>
                </div>

                {{-- Available Free Workstations --}}
                <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-[#D4AF37] transition-all">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Available Terminals</span>
                        <span class="size-2 rounded-full bg-[#D4AF37]"></span>
                    </div>
                    <h4 class="text-3xl font-black text-[#D4AF37] tracking-tight font-mono">{{ $availablePcs }}</h4>
                    <p class="text-[10px] font-bold text-slate-400 uppercase mt-2">Ready for student walk-ins</p>
                </div>

                {{-- Active Rooms Ratio --}}
                <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-[#D4AF37] transition-all">
                    @php
                    $inUseLabsCount = $labs->filter(fn($l) => ($l->occupied ?? 0) > 0 && !$l->isUnderMaintenance())->count();
                    @endphp
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Rooms In-Session</span>
                        <div class="size-2 rounded-full bg-emerald-500 animate-pulse"></div>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h4 class="text-3xl font-black text-slate-900 tracking-tight font-mono">{{ $inUseLabsCount }}</h4>
                        <span class="text-xs font-bold text-slate-400">/ {{ $labs->count() }} Facilities</span>
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase mt-2">Classes or open labs active</p>
                </div>

                {{-- Maintenance & Quarantine --}}
                <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-rose-300 transition-all">
                    @php
                    $maintenanceLabsCount = $labs->filter(fn($l) => $l->isUnderMaintenance())->count();
                    @endphp
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Under Maintenance</span>
                        <span class="size-2 rounded-full {{ $maintenanceLabsCount > 0 ? 'bg-rose-500' : 'bg-slate-300' }}"></span>
                    </div>
                    <div class="flex items-baseline gap-2">
                        <h4 class="text-3xl font-black {{ $maintenanceLabsCount > 0 ? 'text-rose-600' : 'text-slate-900' }} tracking-tight font-mono">{{ $maintenanceLabsCount }}</h4>
                        <span class="text-xs font-bold text-slate-400">Rooms Locked</span>
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase mt-2">Quarantined for servicing</p>
                </div>
            </div>

            {{-- ========================================================================= --}}
            {{-- 2. COMMAND CONTROL & INSTANT FILTER BAR --}}
            {{-- ========================================================================= --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white/90 backdrop-blur-xl p-4 sm:p-5 rounded-3xl border border-slate-200/80 shadow-xs">
                {{-- Live Search --}}
                <div class="relative flex-1 max-w-md">
                    <svg class="size-4 text-slate-400 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <input type="text"
                        x-model="search"
                        placeholder="Filter facility by name or location (e.g. LAB 1, Building B)..."
                        class="w-full pl-11 pr-4 py-2.5 bg-slate-50 border border-slate-200/80 rounded-2xl text-xs font-bold text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-[#D4AF37]/50 focus:border-[#D4AF37] transition-all">
                </div>

                {{-- Status Filter Chips --}}
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                    <button type="button" @click="filter = 'all'"
                        :class="filter === 'all' ? 'bg-slate-900 text-[#D4AF37] shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-3.5 py-2 rounded-xl text-[9px] font-black uppercase tracking-wider transition-all shrink-0">
                        All Facilities
                    </button>
                    <button type="button" @click="filter = 'active'"
                        :class="filter === 'active' ? 'bg-slate-900 text-[#D4AF37] shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-3.5 py-2 rounded-xl text-[9px] font-black uppercase tracking-wider transition-all shrink-0 flex items-center gap-1.5">
                        <span class="size-1.5 rounded-full bg-emerald-500"></span> In-Session
                    </button>
                    <button type="button" @click="filter = 'free'"
                        :class="filter === 'free' ? 'bg-slate-900 text-[#D4AF37] shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-3.5 py-2 rounded-xl text-[9px] font-black uppercase tracking-wider transition-all shrink-0">
                        Vacant / Free
                    </button>
                    <button type="button" @click="filter = 'maintenance'"
                        :class="filter === 'maintenance' ? 'bg-slate-900 text-[#D4AF37] shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                        class="px-3.5 py-2 rounded-xl text-[9px] font-black uppercase tracking-wider transition-all shrink-0">
                        Maintenance
                    </button>
                </div>
            </div>

            {{-- ========================================================================= --}}
            {{-- 3. DETAILED FACILITY COMMAND TILES --}}
            {{-- ========================================================================= --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8">
                @foreach($labs as $lab)
                @php
                $isMaintenance = $lab->isUnderMaintenance();
                $total = $lab->total ?? 0;
                $occupied = $lab->occupied ?? 0;
                $available = max(0, $total - $occupied);
                $rate = $total > 0 ? round(($occupied / $total) * 100) : 0;

                $pendingAlertsCount = $lab->pending_alerts_count ?? ($lab->alerts ? $lab->alerts->where('status', 'pending')->count() : 0);

                $nowTime = now()->format('H:i:s');
                $nowDay = now()->format('l');
                $currentSchedule = $lab->schedules?->first(function($s) use ($nowDay, $nowTime) {
                return $s->day === $nowDay && $s->start_time <= $nowTime && $s->end_time >= $nowTime;
                    });

                    $isOpenLab = $currentSchedule ? (str_contains(strtoupper($currentSchedule->subject_code), 'OPEN') || str_contains(strtoupper($currentSchedule->subject_code), 'FREE')) : false;
                    @endphp

                    <div x-show="isVisible(labs[{{ $loop->index }}])"
                        x-transition
                        class="bg-white rounded-3xl sm:rounded-[2.5rem] p-6 sm:p-8 border border-slate-200/80 shadow-xs hover:shadow-xl hover:border-slate-300 transition-all duration-300 flex flex-col justify-between group relative overflow-hidden">

                        {{-- Card Top: Header & Operational Badges --}}
                        <div>
                            <div class="flex items-start justify-between gap-4 pb-5 border-b border-slate-100">
                                <div class="flex items-center gap-4">
                                    <div class="size-14 rounded-2xl bg-slate-900 text-[#D4AF37] border border-slate-800 flex items-center justify-center font-mono font-black text-sm shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                                        {{ substr($lab->name, 0, 4) }}
                                    </div>
                                    <div>
                                        <h3 class="text-xl sm:text-2xl font-black text-slate-900 uppercase tracking-tight group-hover:text-[#D4AF37] transition-colors">
                                            {{ $lab->name }}
                                        </h3>
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider flex items-center gap-1.5 mt-0.5">
                                            <span>{{ $lab->location ?? 'Campus IT Facility Wing' }}</span>
                                            <span class="text-slate-300">•</span>
                                            <span>Cap: {{ $lab->capacity ?? $total }} PCs</span>
                                        </p>
                                    </div>
                                </div>

                                {{-- Operational State Pill --}}
                                <div class="shrink-0">
                                    @if($isMaintenance)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-[9px] font-black uppercase tracking-wider">
                                        <span class="size-1.5 rounded-full bg-rose-500 animate-ping"></span>
                                        Locked
                                    </span>
                                    @elseif($occupied > 0)
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-[9px] font-black uppercase tracking-wider">
                                        <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        In-Session
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-600 text-[9px] font-black uppercase tracking-wider">
                                        Vacant
                                    </span>
                                    @endif
                                </div>
                            </div>

                            {{-- In-Session Class / Instructor Context --}}
                            <div class="mt-4 p-4 rounded-2xl bg-slate-50/80 border border-slate-100 space-y-2">
                                <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block">Current Class Slot</span>

                                @if($isMaintenance)
                                <div class="flex items-center gap-2 text-rose-600">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <span class="text-xs font-black uppercase tracking-tight">Workstation Telemetry Locked Down</span>
                                </div>
                                @elseif($currentSchedule)
                                <div class="flex items-center justify-between">
                                    <div class="min-w-0">
                                        <p class="text-xs font-black text-slate-900 uppercase truncate">
                                            {{ $currentSchedule->user->name ?? 'Instructor Assigned' }}
                                        </p>
                                        @if($isOpenLab)
                                        <span class="inline-flex items-center gap-1 text-[9px] font-black text-emerald-600 uppercase">
                                            <span class="size-1 rounded-full bg-emerald-500"></span> Open Lab (All Students)
                                        </span>
                                        @else
                                        <span class="text-[9px] font-mono font-bold text-[#D4AF37] uppercase">
                                            {{ $currentSchedule->subject_code }}
                                        </span>
                                        @endif
                                    </div>
                                    <div class="text-right font-mono text-[10px] font-bold text-slate-500 bg-white px-2.5 py-1 rounded-lg border border-slate-200/60 shadow-2xs">
                                        {{ date('h:i A', strtotime($currentSchedule->start_time)) }} — {{ date('h:i A', strtotime($currentSchedule->end_time)) }}
                                    </div>
                                </div>
                                @else
                                <div class="flex items-center justify-between text-slate-400">
                                    <span class="text-xs font-bold uppercase">No Scheduled Class In Progress</span>
                                    <span class="text-[9px] font-mono uppercase bg-slate-200/60 px-2 py-0.5 rounded font-bold">Unreserved</span>
                                </div>
                                @endif
                            </div>

                            {{-- Station Telemetry Breakdown Pills --}}
                            <div class="grid grid-cols-3 gap-2 sm:gap-3 mt-4">
                                <div class="p-3 rounded-2xl bg-white border border-slate-200/70 shadow-2xs text-center">
                                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block">Active Users</span>
                                    <span class="text-lg font-mono font-black text-slate-900 block mt-0.5">{{ $occupied }}</span>
                                </div>
                                <div class="p-3 rounded-2xl bg-white border border-slate-200/70 shadow-2xs text-center">
                                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block">Free Stations</span>
                                    <span class="text-lg font-mono font-black text-[#D4AF37] block mt-0.5">{{ $available }}</span>
                                </div>
                                <div class="p-3 rounded-2xl bg-white border border-slate-200/70 shadow-2xs text-center">
                                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block">Alerts Logged</span>
                                    <span class="text-lg font-mono font-black {{ $pendingAlertsCount > 0 ? 'text-rose-600' : 'text-slate-400' }} block mt-0.5">
                                        {{ $pendingAlertsCount }}
                                    </span>
                                </div>
                            </div>

                            {{-- Progress Visualizer --}}
                            <div class="mt-5">
                                <div class="flex justify-between text-[9px] font-black uppercase mb-1.5">
                                    <span class="text-slate-400 tracking-wider">Facility Capacity Load</span>
                                    <span class="text-slate-900 font-mono">{{ $occupied }} / {{ $total }} Units ({{ $rate }}%)</span>
                                </div>
                                <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden p-0.5 border border-slate-200/60">
                                    <div class="h-full rounded-full transition-all duration-700 {{ $rate > 85 ? 'bg-rose-500' : ($rate > 50 ? 'bg-[#D4AF37]' : 'bg-slate-900') }}"
                                        style="width: {{ $isMaintenance ? 100 : $rate }}%"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Card Footer Actions --}}
                        <div class="mt-6 pt-5 border-t border-slate-100 flex items-center justify-between gap-3">
                            @if($pendingAlertsCount > 0)
                            <span class="inline-flex items-center gap-1.5 text-[9px] font-black text-rose-600 uppercase tracking-wider">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                Hardware Defect Reported
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1.5 text-[9px] font-bold text-slate-400 uppercase tracking-wider">
                                <svg class="size-3 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                                Peripherals Verified
                            </span>
                            @endif

                            <a href="{{ route('personnel.lab.show', $lab) }}"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-[#D4AF37] hover:text-slate-950 text-white rounded-xl text-[10px] font-black uppercase tracking-wider transition-all duration-200 shadow-sm active:scale-95 group/btn">
                                <span>Open Facility Console</span>
                                <svg class="size-3.5 group-hover/btn:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        </div>

                    </div>
                    @endforeach
            </div>

            {{-- ========================================================================= --}}
            {{-- 4. DYNAMIC CLIENT-SIDE EMPTY STATE (SMART FILTER FALLBACK) --}}
            {{-- ========================================================================= --}}
            <div x-show="visibleCount === 0"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                style="display: none;"
                class="py-16 sm:py-20 text-center bg-white rounded-3xl sm:rounded-[2.5rem] border border-slate-200/80 p-8 sm:p-12 shadow-xs max-w-2xl mx-auto space-y-4">

                {{-- Clean Status Icon --}}
                <div class="size-16 rounded-3xl bg-slate-50 border border-slate-200/80 text-[#D4AF37] flex items-center justify-center mx-auto shadow-2xs">
                    <template x-if="filter === 'free'">
                        <svg class="size-8 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                    </template>
                    <template x-if="filter === 'active'">
                        <svg class="size-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                    <template x-if="filter === 'maintenance'">
                        <svg class="size-8 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                    <template x-if="filter === 'all' || filter === 'alerts'">
                        <svg class="size-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                    </template>
                </div>

                {{-- Dynamic Messages --}}
                <div class="space-y-1 max-w-md mx-auto">
                    <h4 class="text-base sm:text-lg font-black text-slate-900 uppercase tracking-tight" x-text="emptyTitle"></h4>
                    <p class="text-xs text-slate-500 font-medium leading-relaxed" x-text="emptySubtitle"></p>
                </div>

                {{-- Quick Action to Clear Filter --}}
                <div class="pt-2">
                    <button @click="search = ''; filter = 'all'"
                        type="button"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-slate-900 hover:bg-[#D4AF37] hover:text-slate-950 text-white rounded-xl text-[10px] font-black uppercase tracking-wider transition-all duration-200 shadow-sm active:scale-95">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <span>Reset All Filters</span>
                    </button>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>