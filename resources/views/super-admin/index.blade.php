<x-app-layout>
    <div x-data="{ 
            reportModal: false, 
            backupModal: false, 
            lockoutModal: false, 
            restoreModal: false 
        }"
        class="p-8 bg-[#f8fafc] min-h-screen relative">

        {{-- Header Status Row --}}
        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">System Overview</h1>
                <p class="text-slate-500">Real-time laboratory monitoring and super admin analytics</p>
            </div>
            <div class="flex items-center space-x-4">
                <span class="flex items-center px-4 py-2 bg-green-100 text-green-700 rounded-full text-xs font-bold border border-green-200">
                    <span class="size-2 bg-green-500 rounded-full mr-2 animate-pulse"></span> LabGuard Active
                </span>
                <div class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg text-xs font-bold uppercase tracking-widest flex items-center">
                    <x-heroicon-o-shield-check class="size-4 mr-2" /> Super Admin
                </div>
            </div>
        </div>

        {{-- Top Summary Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
            <x-stats-card label="Total Users" :value="number_format($totalUsers ?? 0)" change="Registered system profiles" icon="heroicon-o-user-group" color="blue" />
            <x-stats-card label="Active Sessions" :value="$activeSessions ?? 0" change="Current concurrent terminals" icon="heroicon-o-chart-bar" color="green" />
            <x-stats-card label="Unresolved Alerts" :value="$alerts ?? 0" change="Needs security attention" icon="heroicon-o-exclamation-triangle" color="red" />
            <x-stats-card label="Active Laboratories" :value="$totalLabs ?? 0" change="Configured campus zones" icon="heroicon-o-building-office" color="emerald" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- Laboratory Space Utilization --}}
            <div class="lg:col-span-2 bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                <h3 class="text-lg font-bold text-slate-800 mb-2">Laboratory Space Utilization</h3>
                <p class="text-xs text-slate-400 mb-6">Percentage of operational computer stations currently occupied by active students</p>

                <div class="space-y-8">
                    @forelse($labUtilization as $key => $lab)
                    @php
                    $labName = is_array($lab) ? ($lab['name'] ?? $lab['room_name'] ?? "Laboratory #{$key}") : ($lab->name ?? $lab->room_name ?? "Laboratory #{$key}");
                    $percent = is_array($lab) ? ($lab['percent'] ?? 0) : ($lab->percent ?? 0);

                    $color = 'bg-blue-600';
                    if($percent >= 85) { $color = 'bg-rose-500'; }
                    elseif($percent >= 50) { $color = 'bg-indigo-500'; }
                    @endphp
                    <x-health-progress :label="$labName" :percent="$percent" :color="$color" />
                    @empty
                    <div class="text-center py-6 text-sm text-slate-400">
                        No laboratory rooms have been registered in the database yet.
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Action Controls Side Deck --}}
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-slate-800">Quick Actions</h3>
                        <span class="text-[10px] font-black uppercase tracking-widest px-2.5 py-1 bg-slate-100 text-slate-500 rounded-md">Control Center</span>
                    </div>

                    <div class="flex flex-col space-y-3">
                        <button @click="reportModal = true" type="button" class="w-full py-3.5 px-4 bg-[#1e2945] text-white rounded-2xl font-semibold hover:bg-[#161d31] transition duration-200 shadow-sm flex items-center justify-between group">
                            <span class="flex items-center text-sm">
                                <x-heroicon-o-document-chart-bar class="size-5 mr-3 text-slate-300 group-hover:text-white transition" />
                                Generate Report
                            </span>
                            <x-heroicon-o-chevron-right class="size-4 text-slate-400 group-hover:translate-x-0.5 transition" />
                        </button>

                        <a href="{{ route('super-admin.logs') }}" class="w-full py-3.5 px-4 bg-amber-50 text-amber-900 border border-amber-200/60 rounded-2xl font-semibold hover:bg-amber-100/80 transition duration-200 flex items-center justify-between group">
                            <span class="flex items-center text-sm">
                                <x-heroicon-o-clipboard-document-list class="size-5 mr-3 text-amber-600" />
                                View System Logs
                            </span>
                            <x-heroicon-o-chevron-right class="size-4 text-amber-500 group-hover:translate-x-0.5 transition" />
                        </a>

                        <button @click="backupModal = true" type="button" class="w-full py-3.5 px-4 bg-slate-50 border border-slate-200/80 text-slate-700 rounded-2xl font-semibold hover:bg-slate-100 transition duration-200 flex items-center justify-between group">
                            <span class="flex items-center text-sm">
                                <x-heroicon-o-cloud-arrow-up class="size-5 mr-3 text-slate-500" />
                                System Backup
                            </span>
                            <x-heroicon-o-chevron-right class="size-4 text-slate-400 group-hover:translate-x-0.5 transition" />
                        </button>
                    </div>
                </div>

                {{-- System Override Console Box --}}
                <div class="mt-8 pt-6 border-t border-slate-100">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Emergency Override</span>
                        <span class="size-2 rounded-full bg-slate-300"></span>
                    </div>
                    <div class="grid grid-cols-2 gap-2.5">
                        <button @click="lockoutModal = true" type="button" class="group relative overflow-hidden p-3.5 bg-rose-50 hover:bg-rose-100/80 border border-rose-200/70 text-rose-700 rounded-2xl transition-all duration-200 text-left flex flex-col justify-between">
                            <div class="p-2 bg-rose-500 text-white rounded-xl w-max mb-3 shadow-sm group-hover:scale-105 transition-transform">
                                <x-heroicon-o-power class="size-4" />
                            </div>
                            <div>
                                <span class="block text-xs font-extrabold uppercase tracking-wide text-rose-800">Lockdown</span>
                                <span class="text-[10px] text-rose-600/80 font-medium">Halt terminals</span>
                            </div>
                        </button>

                        <button @click="restoreModal = true" type="button" class="group relative overflow-hidden p-3.5 bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200/70 text-emerald-800 rounded-2xl transition-all duration-200 text-left flex flex-col justify-between">
                            <div class="p-2 bg-emerald-600 text-white rounded-xl w-max mb-3 shadow-sm group-hover:scale-105 transition-transform">
                                <x-heroicon-o-key class="size-4" />
                            </div>
                            <div>
                                <span class="block text-xs font-extrabold uppercase tracking-wide text-emerald-900">Restore</span>
                                <span class="text-[10px] text-emerald-600/80 font-medium">Clear maintenance</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Recent Security Incidents --}}
            <div class="lg:col-span-3 bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                <h3 class="text-lg font-bold text-slate-800 mb-6">Recent Lab Guard Incidents</h3>
                <div class="space-y-6">
                    @forelse($recentAlerts as $alert)
                    <div class="flex items-start p-4 rounded-2xl bg-slate-50 border border-slate-100 hover:bg-white hover:shadow-md transition">
                        <div class="p-2 rounded-lg {{ $alert->status == 'pending' ? 'bg-rose-100 text-rose-600' : 'bg-green-100 text-green-600' }} mr-4">
                            @if($alert->status == 'pending')
                            <x-heroicon-o-exclamation-triangle class="size-6" />
                            @else
                            <x-heroicon-o-check-circle class="size-6" />
                            @endif
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between">
                                <span class="text-xs uppercase font-extrabold tracking-wider {{ $alert->status == 'pending' ? 'text-rose-600' : 'text-slate-400' }}">
                                    {{ $alert->status }}
                                </span>
                                <span class="text-xs text-slate-400">{{ $alert->created_at->diffForHumans() }}</span>
                            </div>
                            <h4 class="font-bold text-slate-800 mt-1">
                                {{ $alert->title ?? $alert->issue_type }}
                                <span class="text-xs font-normal text-slate-400">at {{ $alert->lab->room_name ?? 'Unknown Lab' }}</span>
                            </h4>
                            <p class="text-sm text-slate-500 text-left mt-1">
                                {{ $alert->description ?? $alert->desc ?? $alert->issue_type }}
                            </p>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8 text-sm text-slate-400 border border-dashed border-slate-200 rounded-2xl">
                        No recent incidents or lab alerts registered in the system.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- MODAL 1: REPORT --}}
        <div x-show="reportModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div @click.away="reportModal = false" class="bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-extrabold text-slate-900">Generate Audit Report</h3>
                    <button @click="reportModal = false" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <form action="{{ route('super-admin.reports.generate') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Report Scope</label>
                        <select name="type" class="w-full border border-slate-200 rounded-xl p-3 bg-slate-50 text-sm">
                            <option value="utilization">Laboratory Space Utilization & Attendance</option>
                            <option value="security">Security Alerts & Escalation Logs</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-2">Time Frame</label>
                        <select name="range" class="w-full border border-slate-200 rounded-xl p-3 bg-slate-50 text-sm">
                            <option value="today">Today</option>
                            <option value="week">Past 7 Days</option>
                            <option value="month">Current Month</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full py-3 bg-[#1e2945] text-white font-bold rounded-xl mt-2 hover:bg-[#161d31] transition">
                        Compile & Export CSV
                    </button>
                </form>
            </div>
        </div>

        {{-- MODAL 2: BACKUP --}}
        <div x-show="backupModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div @click.away="backupModal = false" class="bg-white rounded-2xl shadow-2xl border border-slate-100 max-w-md w-full p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-extrabold text-slate-900">Database Snapshot</h3>
                    <button @click="backupModal = false" type="button" class="text-slate-400 hover:text-slate-600">✕</button>
                </div>
                <div class="text-sm text-slate-500 mb-6">
                    This will trigger an architectural <code>mysqldump</code> file write to your secure server storage path.
                </div>
                <form action="{{ route('super-admin.system.backup') }}" method="POST" class="space-y-2">
                    @csrf
                    <button type="submit" class="w-full py-3 bg-slate-800 text-white font-bold rounded-xl hover:bg-slate-900 transition">
                        Execute System Backup
                    </button>
                    <button type="button" @click="backupModal = false" class="w-full py-3 bg-slate-100 text-slate-500 font-medium rounded-xl hover:bg-slate-200 transition text-center text-sm">
                        Cancel
                    </button>
                </form>
            </div>
        </div>

        {{-- MODAL 3: EMERGENCY LOCKDOWN --}}
        <div x-show="lockoutModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div @click.away="lockoutModal = false" class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden">
                <div class="p-6 bg-rose-600 text-white relative">
                    <button @click="lockoutModal = false" type="button" class="absolute top-5 right-5 text-rose-200 hover:text-white transition rounded-lg p-1">
                        ✕
                    </button>
                    <div class="size-12 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center mb-4 border border-white/20">
                        <x-heroicon-o-exclamation-triangle class="size-6 text-white" />
                    </div>
                    <h3 class="text-xl font-black tracking-tight">Initiate Station Lockdown</h3>
                    <p class="text-xs text-rose-100 mt-1 font-medium">Emergency maintenance execution</p>
                </div>

                <div class="p-6 space-y-4">
                    <form action="{{ route('super-admin.system.lockout') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Target Laboratory Zone</label>
                            <select name="lab_id" class="w-full border border-slate-200 rounded-xl p-3 bg-slate-50 text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-rose-500 focus:outline-none">
                                <option value="all">⚠️ Entire Campus (All Laboratories)</option>
                                @foreach($labUtilization as $key => $lab)
                                @php
                                $id = is_array($lab) ? ($lab['id'] ?? $key) : ($lab->id ?? $key);
                                $name = is_array($lab) ? ($lab['name'] ?? $lab['room_name'] ?? "Lab #{$id}") : ($lab->name ?? $lab->room_name ?? "Lab #{$id}");
                                @endphp
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 text-slate-600 text-xs space-y-2">
                            <div class="flex items-center font-bold text-slate-800">
                                <span class="size-1.5 rounded-full bg-rose-500 mr-2"></span> System Consequences:
                            </div>
                            <ul class="list-disc list-inside space-y-1 text-slate-500 pl-1">
                                <li>Terminates active student sessions in target lab.</li>
                                <li>Forces stations into <code class="bg-slate-200/70 px-1 py-0.5 rounded text-slate-700">maintenance</code> state.</li>
                                <li>Blocks new login attempts until restored.</li>
                            </ul>
                        </div>

                        <div class="space-y-2 pt-2">
                            <button type="submit" class="w-full py-3.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm rounded-xl transition shadow-lg shadow-rose-600/20 active:scale-[0.98]">
                                Confirm Lockdown Order
                            </button>
                            <button type="button" @click="lockoutModal = false" class="w-full py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold text-xs rounded-xl transition">
                                Cancel / Abort
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL 4: RESTORE LOCKOUT --}}
        <div x-show="restoreModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div @click.away="restoreModal = false" class="bg-white rounded-3xl shadow-2xl max-w-md w-full overflow-hidden">
                <div class="p-6 bg-emerald-600 text-white relative">
                    <button @click="restoreModal = false" type="button" class="absolute top-5 right-5 text-emerald-200 hover:text-white transition rounded-lg p-1">
                        ✕
                    </button>
                    <div class="size-12 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center mb-4 border border-white/20">
                        <x-heroicon-o-key class="size-6 text-white" />
                    </div>
                    <h3 class="text-xl font-black tracking-tight">Restore Workstation Access</h3>
                    <p class="text-xs text-emerald-100 mt-1 font-medium">Re-enable terminal authorization network-wide</p>
                </div>

                <div class="p-6 space-y-4">
                    <form action="{{ route('super-admin.system.release-lockout') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">Target Laboratory Zone</label>
                            <select name="lab_id" class="w-full border border-slate-200 rounded-xl p-3 bg-slate-50 text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                                <option value="all">🌐 Entire Campus (All Laboratories)</option>
                                @foreach($labUtilization as $key => $lab)
                                @php
                                $id = is_array($lab) ? ($lab['id'] ?? $key) : ($lab->id ?? $key);
                                $name = is_array($lab) ? ($lab['name'] ?? $lab['room_name'] ?? "Lab #{$id}") : ($lab->name ?? $lab->room_name ?? "Lab #{$id}");
                                @endphp
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="p-4 bg-emerald-50/60 rounded-2xl border border-emerald-100/80 text-emerald-900 text-xs space-y-2">
                            <div class="flex items-center font-bold text-emerald-900">
                                <span class="size-1.5 rounded-full bg-emerald-500 mr-2"></span> System Restoration:
                            </div>
                            <p class="text-emerald-700 leading-relaxed">
                                Releases stations in the selected lab currently marked as <code class="bg-emerald-100 px-1 py-0.5 rounded text-emerald-800 font-mono">maintenance</code> back to <code class="bg-emerald-100 px-1 py-0.5 rounded text-emerald-800 font-mono">available</code> status.
                            </p>
                        </div>

                        <div class="space-y-2 pt-2">
                            <button type="submit" class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-xl transition shadow-lg shadow-emerald-600/20 active:scale-[0.98]">
                                Release Maintenance Lock
                            </button>
                            <button type="button" @click="restoreModal = false" class="w-full py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-semibold text-xs rounded-xl transition">
                                Keep Maintenance Active
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>