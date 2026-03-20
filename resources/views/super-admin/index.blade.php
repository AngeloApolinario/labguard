<x-app-layout>
    <div class="p-8 bg-[#f8fafc] min-h-screen">

        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">System Overview</h1>
                <p class="text-slate-500">Real-time system monitoring and statistics</p>
            </div>
            <div class="flex items-center space-x-4">
                <span class="flex items-center px-4 py-2 bg-green-100 text-green-700 rounded-full text-xs font-bold border border-green-200">
                    <span class="size-2 bg-green-500 rounded-full mr-2 animate-pulse"></span> System Active
                </span>
                <div class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg text-xs font-bold uppercase tracking-widest flex items-center">
                    <x-heroicon-o-shield-check class="size-4 mr-2" /> Super Admin
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
            <x-stats-card label="Total Users" :value="number_format($totalUsers)" change="+12% this month" icon="heroicon-o-user-group" color="blue" />
            <x-stats-card label="Active Sessions" :value="$activeSessions" change="+5% this month" icon="heroicon-o-chart-bar" color="green" />
            <x-stats-card label="Security Alerts" :value="$alerts" change="-8% this month" icon="heroicon-o-exclamation-triangle" color="red" />
            <x-stats-card label="System Uptime" :value="$uptime" change="+0.2% this month" icon="heroicon-o-check-badge" color="emerald" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                <h3 class="text-lg font-bold text-slate-800 mb-6">System Health Status</h3>

                <div class="space-y-8">
                    <x-health-progress label="CPU Usage" :percent="$health['cpu']" color="bg-amber-500" />
                    <x-health-progress label="Memory Usage" :percent="$health['memory']" color="bg-orange-500" />
                    <x-health-progress label="Database Health" :percent="$health['database']" color="bg-green-500" />
                    <x-health-progress label="Network Latency" :percent="$health['latency']" color="bg-emerald-500" suffix="ms" />
                </div>
            </div>

            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                <h3 class="text-lg font-bold text-slate-800 mb-6">Quick Actions</h3>
                <div class="flex flex-col space-y-4">
                    <button class="w-full py-4 bg-[#1e2945] text-white rounded-xl font-bold hover:bg-[#161d31] transition shadow-lg">Generate Report</button>
                    <button class="w-full py-4 bg-[#D4AF37] text-white rounded-xl font-bold hover:bg-[#b8962d] transition shadow-lg">View Logs</button>
                    <button class="w-full py-4 bg-rose-100 text-rose-600 rounded-xl font-bold hover:bg-rose-200 transition">Emergency Shutdown</button>
                    <button class="w-full py-4 bg-slate-100 text-slate-600 rounded-xl font-bold hover:bg-slate-200 transition">System Backup</button>
                </div>
            </div>

            <div class="lg:col-span-3 bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                <h3 class="text-lg font-bold text-slate-800 mb-6">Recent Activities</h3>
                <div class="space-y-6">
                    @foreach($activities as $activity)
                    <div class="flex items-start p-4 rounded-2xl bg-slate-50 border border-slate-100 hover:bg-white hover:shadow-md transition">
                        <div class="p-2 rounded-lg {{ $activity['type'] == 'warning' ? 'bg-amber-100 text-amber-600' : 'bg-blue-100 text-blue-600' }} mr-4">
                            @if($activity['type'] == 'warning') <x-heroicon-o-exclamation-circle class="size-6" /> @else <x-heroicon-o-information-circle class="size-6" /> @endif
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between">
                                <h4 class="font-bold text-slate-800">{{ $activity['title'] }}</h4>
                                <span class="text-xs text-slate-400">{{ $activity['time'] }}</span>
                            </div>
                            <p class="text-sm text-slate-500">{{ $activity['desc'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>