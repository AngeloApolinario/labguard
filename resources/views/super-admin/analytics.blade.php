<x-app-layout>
    <div class="py-6 sm:py-10 px-4 sm:px-8 max-w-7xl mx-auto min-h-screen">

        {{-- Cinematic Header --}}
        <x-slot name="header">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="font-black text-3xl sm:text-4xl text-slate-800 tracking-tighter uppercase">
                        Analytics & <span class="text-[#D4AF37]">Reports</span>
                    </h2>
                    <div class="flex items-center space-x-2 mt-1">
                        <div class="size-2 bg-green-500 rounded-full animate-pulse"></div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">
                            Labguard System Analytics Overview
                        </p>
                    </div>
                </div>
            </div>
        </x-slot>

        {{-- Action & Filter Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 bg-white/80 backdrop-blur-xl p-4 sm:p-5 rounded-2xl sm:rounded-3xl border border-slate-100 shadow-xl shadow-slate-500/5">
            <div class="flex items-center gap-2">
                <span class="size-2 rounded-full bg-blue-500"></span>
                <p class="text-xs font-bold text-slate-400">
                    Filtering criteria: <span class="text-blue-600 font-black underline underline-offset-4">{{ $rangeLabel }}</span>
                </p>
            </div>

            <div class="flex flex-wrap sm:flex-nowrap items-center gap-3 w-full sm:w-auto">

                {{-- Range Selector Dropdown --}}
                <div class="relative w-full sm:w-auto text-left" x-data="{ open: false }" @click.away="open = false">
                    <button
                        @click="open = !open"
                        type="button"
                        class="w-full sm:w-auto flex items-center justify-between sm:justify-start px-4 py-2.5 bg-slate-50 border border-slate-200/80 rounded-xl text-xs font-black uppercase text-slate-700 shadow-sm hover:bg-slate-100/80 transition-all focus:outline-none focus:ring-2 focus:ring-[#D4AF37]/50">
                        <div class="flex items-center">
                            <x-heroicon-o-calendar class="size-4 mr-2 text-slate-400" />
                            <span>Range: {{ $rangeLabel }}</span>
                        </div>
                        <svg class="w-4 h-4 ml-2 text-slate-400 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="transform opacity-0 scale-95 -translate-y-2"
                        x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
                        x-transition:leave-end="transform opacity-0 scale-95 -translate-y-2"
                        class="absolute left-0 sm:right-0 sm:left-auto top-full sm:top-auto sm:bottom-full mt-2 sm:mt-0 sm:mb-2 w-full sm:w-52 bg-white/95 backdrop-blur-xl border border-slate-100 rounded-2xl shadow-2xl shadow-slate-900/10 z-50 p-1.5"
                        style="display: none;">
                        <a href="{{ route('super-admin.analytics', ['range' => 'all']) }}" class="flex items-center justify-between px-3.5 py-2.5 text-xs font-black uppercase tracking-wider rounded-xl transition-all {{ $range === 'all' ? 'bg-slate-900 text-[#D4AF37]' : 'text-slate-600 hover:bg-slate-50' }}">All Time Records</a>
                        <a href="{{ route('super-admin.analytics', ['range' => 'today']) }}" class="flex items-center justify-between px-3.5 py-2.5 text-xs font-black uppercase tracking-wider rounded-xl transition-all {{ $range === 'today' ? 'bg-slate-900 text-[#D4AF37]' : 'text-slate-600 hover:bg-slate-50' }}">Today</a>
                        <a href="{{ route('super-admin.analytics', ['range' => 'week']) }}" class="flex items-center justify-between px-3.5 py-2.5 text-xs font-black uppercase tracking-wider rounded-xl transition-all {{ $range === 'week' ? 'bg-slate-900 text-[#D4AF37]' : 'text-slate-600 hover:bg-slate-50' }}">Past 7 Days</a>
                        <a href="{{ route('super-admin.analytics', ['range' => 'month']) }}" class="flex items-center justify-between px-3.5 py-2.5 text-xs font-black uppercase tracking-wider rounded-xl transition-all {{ $range === 'month' ? 'bg-slate-900 text-[#D4AF37]' : 'text-slate-600 hover:bg-slate-50' }}">Current Month</a>
                    </div>
                </div>

                {{-- Exporter Button --}}
                <a href="{{ route('super-admin.analytics.export', ['range' => $range]) }}" class="w-full sm:w-auto flex items-center justify-center px-5 py-2.5 bg-[#D4AF37] hover:bg-[#b5932a] text-white rounded-xl text-xs font-black uppercase tracking-wider shadow-lg shadow-[#D4AF37]/20 transition-all transform hover:scale-[1.02] active:scale-95">
                    <x-heroicon-o-arrow-down-tray class="size-4 mr-2" />
                    <span>Export Report</span>
                </a>
            </div>
        </div>

        {{-- Live Statistics Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6 mb-8">
            @foreach($stats as $stat)
            <div class="relative overflow-hidden bg-white/80 backdrop-blur-xl p-6 rounded-3xl border border-slate-100/80 shadow-xl shadow-slate-500/5 hover:border-[#D4AF37]/40 transition-all group">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.25em]">{{ $stat['label'] }}</p>
                    <div class="w-2 h-2 rounded-full bg-[#D4AF37] opacity-0 group-hover:opacity-100 transition-opacity"></div>
                </div>
                <h3 class="text-3xl font-black text-slate-900 tracking-tight">{{ $stat['value'] }}</h3>
                <div class="flex items-center gap-1.5 mt-2">
                    <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wide">{{ $stat['change'] }}</span>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Analytics Breakdown Section --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8 mb-8">

            {{-- Lab Distribution Card --}}
            <div class="lg:col-span-1 bg-white/80 backdrop-blur-xl p-6 sm:p-8 rounded-3xl sm:rounded-[2.5rem] border border-slate-100/80 shadow-2xl shadow-slate-500/5 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-base sm:text-lg font-black text-slate-900 uppercase tracking-tight">Lab Usage</h3>
                        <span class="text-[9px] font-black text-[#D4AF37] uppercase tracking-widest bg-[#D4AF37]/10 px-2.5 py-1 rounded-full border border-[#D4AF37]/20">Distribution</span>
                    </div>

                    <div class="space-y-6">
                        @forelse($labUsage as $lab)
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-black text-slate-700 uppercase tracking-wider">{{ $lab['name'] }}</span>
                                <span class="text-xs font-black text-slate-900 bg-slate-100 px-2 py-0.5 rounded-lg">{{ $lab['percent'] }}%</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden p-0.5 border border-slate-200/50">
                                <div class="{{ $lab['color'] }} h-full rounded-full transition-all duration-700 ease-out" style="width: {{ $lab['percent'] }}%"></div>
                            </div>
                        </div>
                        @empty
                        <div class="py-12 text-center text-slate-300 font-black uppercase tracking-widest text-xs">
                            No lab session activity logged
                        </div>
                        @endforelse
                    </div>
                </div>

                <div class="mt-8 pt-4 border-t border-slate-100 text-center">
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.2em]">Real-time telemetry updated live</p>
                </div>
            </div>

            {{-- Top Reported Issues Card --}}
            <div class="lg:col-span-2 bg-white/80 backdrop-blur-xl p-6 sm:p-8 rounded-3xl sm:rounded-[2.5rem] border border-slate-100/80 shadow-2xl shadow-slate-500/5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-6">
                    <h3 class="text-base sm:text-lg font-black text-slate-900 uppercase tracking-tight">Top Reported Issues</h3>
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] sm:text-right">Scope: {{ $rangeLabel }}</span>
                </div>

                <div class="space-y-3">
                    @forelse($topIssues as $issue)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 sm:p-5 bg-slate-50/70 rounded-2xl border border-slate-100 hover:bg-slate-100/60 transition-all gap-3 group">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 rounded-full bg-rose-500 group-hover:scale-125 transition-transform"></div>
                            <span class="text-xs sm:text-sm font-black text-slate-800 uppercase tracking-tight">{{ $issue->issue_type }}</span>
                        </div>

                        <div class="flex items-center justify-between sm:justify-end space-x-4">
                            <span class="px-3 py-1.5 bg-slate-900 text-[#D4AF37] rounded-xl text-[10px] font-black uppercase tracking-widest border border-slate-800 shadow-sm">
                                {{ $issue->count }} {{ Str::plural('report', $issue->count) }}
                            </span>
                            <x-heroicon-o-arrow-trending-up class="size-5 text-rose-500 flex-shrink-0" />
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-16 text-slate-300 font-black uppercase tracking-[0.3em] text-xs">
                        No alerts or issues reported during this period
                    </div>
                    @endforelse
                </div>
            </div>

        </div>

    </div>
</x-app-layout>