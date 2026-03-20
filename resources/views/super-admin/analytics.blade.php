<x-app-layout>
    <div class="p-8 bg-[#f8fafc] min-h-screen">

        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Analytics & Reports</h1>
                <p class="text-slate-500">Detailed system analytics and reports</p>
            </div>
            <div class="flex items-center space-x-4">
                <span class="flex items-center px-4 py-2 bg-green-100 text-green-700 rounded-full text-xs font-bold border border-green-200">
                    <span class="size-2 bg-green-500 rounded-full mr-2"></span> System Active
                </span>
                <div class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg text-xs font-bold uppercase tracking-widest flex items-center">
                    <x-heroicon-o-shield-check class="size-4 mr-2" /> Super Admin
                </div>
            </div>
        </div>

        <div class="flex justify-between items-end mb-6">
            <div>
                <h2 class="text-xl font-bold text-[#1e2945]">System Analytics & Reports</h2>
                <p class="text-sm text-slate-400">Monitor system performance and user engagement</p>
            </div>
            <div class="flex space-x-3">
                <button class="flex items-center px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-bold text-slate-600 shadow-sm">
                    <x-heroicon-o-calendar class="size-5 mr-2" /> Date Range
                </button>
                <button class="flex items-center px-4 py-2 bg-[#D4AF37] text-white rounded-lg text-sm font-bold shadow-sm">
                    <x-heroicon-o-arrow-down-tray class="size-5 mr-2" /> Export Report
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            @foreach($stats as $stat)
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                <p class="text-xs font-bold text-slate-400 mb-2">{{ $stat['label'] }}</p>
                <h3 class="text-2xl font-black text-slate-900">{{ $stat['value'] }}</h3>
                <p class="text-[10px] font-bold mt-1 text-green-500">{{ $stat['change'] }}</p>
            </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <div class="lg:col-span-2 bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
                <h3 class="text-lg font-bold text-[#1e2945] mb-12">Login Trends</h3>
                <div class="h-48 flex items-end justify-between px-4 border-b border-slate-100">
                    @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                    <div class="flex flex-col items-center w-full group">
                        <div class="w-8 bg-slate-50 group-hover:bg-blue-100 rounded-t-lg transition-all duration-500" style="height: {{ rand(30, 90) }}%"></div>
                        <span class="text-xs text-slate-400 mt-4">{{ $day }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
                <h3 class="text-lg font-bold text-[#1e2945] mb-6">Lab Usage Distribution</h3>
                <div class="space-y-5">
                    @foreach($labUsage as $lab)
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-xs font-bold text-slate-500">{{ $lab['name'] }}</span>
                            <span class="text-xs font-black text-slate-900">{{ $lab['percent'] }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="{{ $lab['color'] }} h-full" style="width: {{ $lab['percent'] }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="bg-white p-8 rounded-3xl border border-slate-100 shadow-sm">
            <h3 class="text-lg font-bold text-[#1e2945] mb-6">Top Issues This Month</h3>
            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                <span class="text-sm font-bold text-slate-700 ml-4">Keyboard Malfunction</span>
                <div class="flex items-center space-x-4">
                    <span class="px-3 py-1 bg-slate-200 text-slate-600 rounded-lg text-[10px] font-black uppercase">12 reports</span>
                    <x-heroicon-o-arrow-trending-up class="size-5 text-rose-500" />
                </div>
            </div>
        </div>

    </div>
</x-app-layout>