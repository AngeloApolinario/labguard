<x-app-layout>
    <div class="p-8 bg-[#f8fafc] min-h-screen">

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
            <x-stats-card label="Total Users" :value="number_format($totalUsers)" change="Registered system profiles" icon="heroicon-o-user-group" color="blue" />
            <x-stats-card label="Active Sessions" :value="$activeSessions" change="Current concurrent terminals" icon="heroicon-o-chart-bar" color="green" />
            <x-stats-card label="Unresolved Alerts" :value="$alerts" change="Needs security attention" icon="heroicon-o-exclamation-triangle" color="red" />
            <x-stats-card label="Active Laboratories" :value="$totalLabs" change="Configured campus zones" icon="heroicon-o-building-office" color="emerald" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- Laboratory Space Utilization --}}
            <div class="lg:col-span-2 bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                <h3 class="text-lg font-bold text-slate-800 mb-2">Laboratory Space Utilization</h3>
                <p class="text-xs text-slate-400 mb-6">Percentage of operational computer stations currently occupied by active students</p>

                <div class="space-y-8">
                    @forelse($labUtilization as $labId => $data)
                    @php
                    $color = 'bg-blue-600';
                    if($data['percent'] >= 85) { $color = 'bg-rose-500'; }
                    elseif($data['percent'] >= 50) { $color = 'bg-indigo-500'; }
                    @endphp
                    <x-health-progress :label="$data['name']" :percent="$data['percent']" :color="$color" />
                    @empty
                    <div class="text-center py-6 text-sm text-slate-400">
                        No laboratory rooms have been registered in the database yet.
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Action Controls Side Deck --}}
            <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                <h3 class="text-lg font-bold text-slate-800 mb-6">Quick Actions</h3>
                <div class="flex flex-col space-y-4">
                    <button onclick="document.getElementById('report_modal').showModal()" class="w-full py-4 bg-[#1e2945] text-white rounded-xl font-bold hover:bg-[#161d31] transition shadow-lg flex items-center justify-center">
                        <x-heroicon-o-document-chart-bar class="size-5 mr-2" /> Generate Report
                    </button>

                    <a href="{{ route('super-admin.logs') }}" class="w-full py-4 bg-[#D4AF37] text-white rounded-xl font-bold hover:bg-[#b8962d] transition shadow-lg flex items-center justify-center text-center">
                        <x-heroicon-o-clipboard-document-list class="size-5 mr-2" /> View Logs
                    </a>

                    <button onclick="document.getElementById('backup_modal').showModal()" class="w-full py-4 bg-slate-100 text-slate-600 rounded-xl font-bold hover:bg-slate-200 transition flex items-center justify-center">
                        <x-heroicon-o-cloud-arrow-up class="size-5 mr-2" /> System Backup
                    </button>

                    <button onclick="document.getElementById('lockout_modal').showModal()" class="w-full py-4 bg-rose-100 text-rose-600 rounded-xl font-bold hover:bg-rose-200 transition flex items-center justify-center">
                        <x-heroicon-o-power class="size-5 mr-2" /> Emergency Lockout
                    </button>
                </div>
            </div>

            {{-- Recent Security Incidents & Alerts Feed --}}
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
                                {{-- MATCHED SCHEMA: $alert->lab instead of $alert->laboratory --}}
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
    </div>

    {{-- MODAL 1: REPORT DIALOG ELEMENT --}}
    <dialog id="report_modal" class="modal p-6 rounded-2xl shadow-2xl border border-slate-100 bg-white max-w-md w-full backdrop:bg-black/40">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-extrabold text-slate-900">Generate Audit Report</h3>
            <button onclick="document.getElementById('report_modal').close()" class="text-slate-400 hover:text-slate-600">✕</button>
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
    </dialog>

    {{-- MODAL 2: BACKUP CONFIRMATION DIALOG --}}
    <dialog id="backup_modal" class="modal p-6 rounded-2xl shadow-2xl border border-slate-100 bg-white max-w-md w-full backdrop:bg-black/40">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-extrabold text-slate-900">Database Snapshot</h3>
            <button onclick="document.getElementById('backup_modal').close()" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <div class="text-sm text-slate-500 mb-6">
            This will trigger an architectural `mysqldump` file write to your secure server storage path (`storage/app/backups/`).
        </div>
        <form action="{{ route('super-admin.system.backup') }}" method="POST" class="space-y-2">
            @csrf
            <button type="submit" class="w-full py-3 bg-slate-800 text-white font-bold rounded-xl hover:bg-slate-900 transition">
                Execute System Backup
            </button>
            <button type="button" onclick="document.getElementById('backup_modal').close()" class="w-full py-3 bg-slate-100 text-slate-500 font-medium rounded-xl hover:bg-slate-200 transition text-center text-sm">
                Cancel
            </button>
        </form>
    </dialog>

    {{-- MODAL 3: CRITICAL LOCKOUT PROTOCOL DIALOG --}}
    <dialog id="lockout_modal" class="modal p-6 rounded-2xl shadow-2xl border border-rose-100 bg-white max-w-md w-full backdrop:bg-black/40">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-extrabold text-rose-600 flex items-center">
                <x-heroicon-o-exclamation-triangle class="size-6 mr-2 text-rose-500" /> Emergency Lockdown
            </h3>
            <button onclick="document.getElementById('lockout_modal').close()" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>
        <div class="text-sm text-slate-600 mb-6 bg-rose-50 p-4 rounded-xl border border-rose-100">
            <strong>WARNING:</strong> This action terminates all running `LabSession` records network-wide and switches all student workstations to <code>maintenance</code> mode.
        </div>
        <form action="{{ route('super-admin.system.lockout') }}" method="POST" class="space-y-2">
            @csrf
            <button type="submit" class="w-full py-3 bg-rose-600 text-white font-bold rounded-xl hover:bg-rose-700 transition shadow-lg shadow-rose-200">
                Confirm Global System Lockout
            </button>
            <button type="button" onclick="document.getElementById('lockout_modal').close()" class="w-full py-3 bg-slate-100 text-slate-500 font-medium rounded-xl hover:bg-slate-200 transition text-center text-sm">
                Abort Protocol
            </button>
        </form>
    </dialog>
</x-app-layout>