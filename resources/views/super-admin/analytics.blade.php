<x-app-layout>
    <div class="p-8  min-h-screen">

        <x-slot name="header">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="font-black text-4xl text-slate-800 tracking-tighter uppercase">
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

        <!-- Action Bar Layout -->
        <div class="flex justify-between items-end mb-6">
            <div>
                <p class="text-sm text-slate-400">Filtering criteria: <span class="text-blue-600 font-bold underline">{{ $rangeLabel }}</span></p>
            </div>
            <div class="flex space-x-3 items-center relative">

                <!-- Click-to-Toggle Dropdown Selector Structure using Alpine.js -->
                <div class="relative inline-block text-left" x-data="{ open: false }" @click.away="open = false">
                    <button
                        @click="open = !open"
                        type="button"
                        class="flex items-center px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-bold text-slate-600 shadow-sm hover:bg-slate-50 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <x-heroicon-o-calendar class="size-5 mr-2 text-slate-400" />
                        <span>Range: {{ $rangeLabel }}</span>
                        <svg class="w-4 h-4 ml-2 text-slate-400 transition-transform duration-200" :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Dropdown Options Window -->
                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute right-0 bottom-full mb-2 w-48 bg-white border border-slate-100 rounded-xl shadow-xl z-50 p-1"
                        style="display: none;">
                        <a href="{{ route('super-admin.analytics', ['range' => 'all']) }}" class="block px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 rounded-lg {{ $range === 'all' ? 'bg-blue-50 text-blue-600' : '' }}">All Time Records</a>
                        <a href="{{ route('super-admin.analytics', ['range' => 'today']) }}" class="block px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 rounded-lg {{ $range === 'today' ? 'bg-blue-50 text-blue-600' : '' }}">Today</a>
                        <a href="{{ route('super-admin.analytics', ['range' => 'week']) }}" class="block px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 rounded-lg {{ $range === 'week' ? 'bg-blue-50 text-blue-600' : '' }}">Past 7 Days</a>
                        <a href="{{ route('super-admin.analytics', ['range' => 'month']) }}" class="block px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50 rounded-lg {{ $range === 'month' ? 'bg-blue-50 text-blue-600' : '' }}">Current Month</a>
                    </div>
                </div>

                <!-- Dynamic Exporter Link -->
                <a href="{{ route('super-admin.analytics.export', ['range' => $range]) }}" class="flex items-center px-4 py-2 bg-[#D4AF37] hover:bg-[#b5932a] text-white rounded-lg text-sm font-bold shadow-sm transition">
                    <x-heroicon-o-arrow-down-tray class="size-5 mr-2" /> Export Report
                </a>
            </div>
        </div>

        <!-- Live Statistics Grid Layout (Dynamically displays total activity_log count) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            @foreach($stats as $stat)
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                <p class="text-xs font-bold text-slate-400 mb-2">{{ $stat['label'] }}</p>
                <h3 class="text-2xl font-black text-slate-900">{{ $stat['value'] }}</h3>
                <p class="text-[10px] font-bold mt-1 text-slate-400">{{ $stat['change'] }}</p>
            </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Lab Distribution Data Column -->
            <div class="lg:col-span-1 bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
                <h3 class="text-lg font-bold text-[#1e2945] mb-6">Lab Usage Distribution</h3>
                <div class="space-y-5">
                    @forelse($labUsage as $lab)
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-xs font-bold text-slate-500">{{ $lab['name'] }}</span>
                            <span class="text-xs font-black text-slate-900">{{ $lab['percent'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="{{ $lab['color'] }} h-full transition-all duration-500" style="width: {{ $lab['percent'] }}%"></div>
                        </div>
                    </div>
                    @empty
                    <p class="text-xs text-slate-400 py-4 text-center">No lab session activity tracked yet.</p>
                    @endforelse
                </div>
            </div>

            <!-- Real-Time Top Issues Track List -->
            <div class="lg:col-span-2 bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
                <h3 class="text-lg font-bold text-[#1e2945] mb-6">Top Reported Issues ({{ $rangeLabel }})</h3>
                <div class="space-y-3">
                    @forelse($topIssues as $issue)
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100/50 hover:bg-slate-100/40 transition">
                        <span class="text-sm font-bold text-slate-700 ml-2">{{ $issue->issue_type }}</span>
                        <div class="flex items-center space-x-4">
                            <span class="px-3 py-1 bg-slate-200 text-slate-600 rounded-lg text-[10px] font-black uppercase">
                                {{ $issue->count }} {{ Str::plural('report', $issue->count) }}
                            </span>
                            <x-heroicon-o-arrow-trending-up class="size-5 text-rose-500" />
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-12 text-slate-400 text-sm">
                        No alerts reported during this tracking period.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</x-app-layout>