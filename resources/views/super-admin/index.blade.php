<x-app-layout>
    <div x-data="{ 
            reportModal: false, 
            backupModal: false, 
            lockoutModal: false, 
            restoreModal: false 
        }"
        class="py-6 sm:py-10 px-4 sm:px-8 max-w-7xl mx-auto min-h-screen relative">

        {{-- Cinematic Header --}}
        <x-slot name="header">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="font-black text-2xl sm:text-4xl text-slate-800 tracking-tighter uppercase">
                        Command <span class="text-[#D4AF37]">Center</span>
                    </h2>
                    <div class="flex items-center space-x-2 mt-1">
                        <div class="size-2 bg-emerald-500 rounded-full animate-pulse"></div>
                        <p class="text-[9px] sm:text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">
                            Super Admin Central Telemetry & Controls
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-slate-900 text-[#D4AF37] border border-slate-800 rounded-xl text-[10px] font-mono font-black uppercase shadow-sm">
                        <svg class="w-3.5 h-3.5 text-[#D4AF37]" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                        Root Clearance Active
                    </span>
                </div>
            </div>
        </x-slot>

        {{-- Top Summary Stats (KPI Pillars) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">

            {{-- Total Users --}}
            <div class="bg-white/90 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/70 shadow-xl shadow-slate-900/5 group hover:border-[#D4AF37] transition-all">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Authorized Users</span>
                    <div class="p-2.5 rounded-2xl bg-blue-500/10 text-blue-600 border border-blue-500/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-3xl font-black text-slate-900 tracking-tight">{{ number_format($totalUsers ?? 0) }}</h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1.5">Registered system profiles</p>
            </div>

            {{-- Active Sessions --}}
            <div class="bg-white/90 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/70 shadow-xl shadow-slate-900/5 group hover:border-[#D4AF37] transition-all">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Active Sessions</span>
                    <div class="p-2.5 rounded-2xl bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-3xl font-black text-slate-900 tracking-tight">{{ $activeSessions ?? 0 }}</h3>
                    <span class="text-[10px] font-black text-emerald-600 uppercase flex items-center gap-1">
                        <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Live Terminals
                    </span>
                </div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1.5">Concurrent workstation logins</p>
            </div>

            {{-- Unresolved Alerts --}}
            <div class="bg-white/90 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/70 shadow-xl shadow-slate-900/5 group hover:border-[#D4AF37] transition-all">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Pending Alerts</span>
                    <div class="p-2.5 rounded-2xl {{ ($alerts ?? 0) > 0 ? 'bg-rose-500/10 text-rose-600 border border-rose-500/20' : 'bg-slate-100 text-slate-400' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-3xl font-black {{ ($alerts ?? 0) > 0 ? 'text-rose-600' : 'text-slate-900' }} tracking-tight">{{ $alerts ?? 0 }}</h3>
                    <span class="text-[10px] font-black text-slate-400 uppercase">Unresolved</span>
                </div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1.5">Requires technician attention</p>
            </div>

            {{-- Laboratories Monitored --}}
            <div class="bg-white/90 backdrop-blur-xl p-6 rounded-3xl border border-slate-200/70 shadow-xl shadow-slate-900/5 group hover:border-[#D4AF37] transition-all">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em]">Campus Zones</span>
                    <div class="p-2.5 rounded-2xl bg-[#D4AF37]/10 text-[#D4AF37] border border-[#D4AF37]/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                        </svg>
                    </div>
                </div>
                <h3 class="text-3xl font-black text-slate-900 tracking-tight">{{ $totalLabs ?? 0 }}</h3>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mt-1.5">Connected laboratory zones</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8 mb-8">

            {{-- 1. Laboratory Space Utilization (2 cols) --}}
            <div class="lg:col-span-2 bg-white/90 backdrop-blur-xl p-6 sm:p-8 rounded-[2.5rem] border border-slate-200/70 shadow-2xl shadow-slate-900/5 flex flex-col justify-between">
                <div>
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-6">
                        <div>
                            <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight">Laboratory Space Utilization</h3>
                            <p class="text-xs text-slate-400 font-bold mt-0.5">Real-time workstation occupancy across monitored campus rooms</p>
                        </div>
                        <span class="self-start sm:self-auto px-3 py-1 bg-slate-900 text-[#D4AF37] rounded-xl text-[9px] font-mono font-black uppercase tracking-widest border border-slate-800">
                            Telemetry Active
                        </span>
                    </div>

                    <div class="space-y-6">
                        @forelse($labUtilization as $key => $lab)
                        @php
                        // Fixed: Correctly reading 'name' column from labs table
                        $labName = is_array($lab)
                        ? ($lab['name'] ?? $lab['room_name'] ?? "Laboratory #{$key}")
                        : ($lab->name ?? $lab->room_name ?? "Laboratory #{$key}");

                        $percent = is_array($lab) ? ($lab['percent'] ?? 0) : ($lab->percent ?? 0);
                        $labStatus = is_array($lab) ? ($lab['status'] ?? 'active') : ($lab->status ?? 'active');

                        $pcs = is_array($lab)
                        ? ($lab['pcs'] ?? $lab['computers'] ?? $lab['stations'] ?? [])
                        : ($lab->pcs ?? $lab->computers ?? $lab->stations ?? collect());

                        $pcsCollection = collect($pcs);
                        $totalPcs = is_array($lab) ? ($lab['total_pcs'] ?? null) : ($lab->total_pcs ?? null);
                        $maintPcs = is_array($lab) ? ($lab['maintenance_pcs'] ?? null) : ($lab->maintenance_pcs ?? null);

                        $isMaintenance = in_array(strtolower($labStatus), ['maintenance', 'lockdown']);

                        if (!$isMaintenance) {
                        if ($totalPcs !== null && $maintPcs !== null) {
                        $isMaintenance = ($totalPcs > 0) && ($totalPcs == $maintPcs);
                        } else {
                        $isMaintenance = $pcsCollection->isNotEmpty() && $pcsCollection->every(function ($pc) {
                        $pcStatus = is_array($pc) ? ($pc['status'] ?? '') : ($pc->status ?? '');
                        return in_array(strtolower($pcStatus), ['maintenance', 'lockdown', 'disabled', 'offline']);
                        });
                        }
                        }

                        // Dynamic state color
                        $barColor = 'bg-gradient-to-r from-blue-600 to-indigo-600';
                        if ($isMaintenance) {
                        $barColor = 'bg-gradient-to-r from-amber-500 to-rose-500';
                        } elseif ($percent >= 85) {
                        $barColor = 'bg-gradient-to-r from-[#D4AF37] to-rose-600';
                        } elseif ($percent >= 50) {
                        $barColor = 'bg-gradient-to-r from-emerald-500 to-teal-600';
                        }
                        @endphp

                        <div class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/60 hover:bg-slate-50 transition-colors">
                            <div class="flex justify-between items-center mb-2">
                                <div class="flex items-center gap-2.5">
                                    <span class="w-2 h-2 rounded-full {{ $isMaintenance ? 'bg-amber-500 animate-ping' : ($percent > 0 ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300') }}"></span>
                                    <span class="text-xs font-black text-slate-800 uppercase tracking-wider">{{ $labName }}</span>
                                    @if($isMaintenance)
                                    <span class="px-2 py-0.5 rounded-md text-[8px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200">
                                        Maintenance Lock
                                    </span>
                                    @endif
                                </div>
                                <span class="text-xs font-mono font-black text-slate-900 bg-white px-2.5 py-0.5 rounded-lg border border-slate-200 shadow-xs">
                                    {{ $percent }}%
                                </span>
                            </div>

                            {{-- Progress Bar --}}
                            <div class="w-full bg-slate-200/70 h-2.5 rounded-full overflow-hidden p-0.5 border border-slate-200/50">
                                <div class="{{ $barColor }} h-full rounded-full transition-all duration-700 ease-out" style="width: {{ $isMaintenance ? 100 : $percent }}%"></div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-12 text-sm text-slate-400 font-bold">
                            No laboratory rooms have been registered in the database yet.
                        </div>
                        @endforelse
                    </div>
                </div>

                <div class="mt-8 pt-4 border-t border-slate-100 flex items-center justify-between text-[9px] font-black text-slate-400 uppercase tracking-widest">
                    <span>Workstation Telemetry</span>
                    <span class="text-slate-600">Updated in real-time</span>
                </div>
            </div>

            {{-- 2. Mission Control & Emergency Override Console (1 col) --}}
            <div class="bg-white/90 backdrop-blur-xl p-6 sm:p-8 rounded-[2.5rem] border border-slate-200/70 shadow-2xl shadow-slate-900/5 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight">Mission Control</h3>
                        <span class="text-[9px] font-black uppercase tracking-widest px-2.5 py-1 bg-slate-100 text-slate-500 rounded-lg">Root Deck</span>
                    </div>

                    {{-- Action Stack --}}
                    <div class="flex flex-col space-y-3">
                        <button @click="reportModal = true" type="button" class="w-full py-3.5 px-4 bg-slate-900 text-white rounded-2xl font-black uppercase text-xs hover:bg-[#D4AF37] hover:text-slate-950 transition-all duration-200 shadow-md flex items-center justify-between group">
                            <span class="flex items-center tracking-wider">
                                <svg class="w-4 h-4 mr-3 text-[#D4AF37] group-hover:text-slate-950 transition-colors" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                                Generate Report
                            </span>
                            <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>

                        <a href="{{ route('super-admin.logs') }}" class="w-full py-3.5 px-4 bg-amber-50 text-amber-900 border border-amber-200/70 rounded-2xl font-black uppercase text-xs hover:bg-amber-100 transition-all flex items-center justify-between group">
                            <span class="flex items-center tracking-wider">
                                <svg class="w-4 h-4 mr-3 text-amber-600" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                                System Logs
                            </span>
                            <svg class="w-4 h-4 text-amber-500 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </a>

                        <button @click="backupModal = true" type="button" class="w-full py-3.5 px-4 bg-slate-50 border border-slate-200/80 text-slate-700 rounded-2xl font-black uppercase text-xs hover:bg-slate-100 transition-all flex items-center justify-between group">
                            <span class="flex items-center tracking-wider">
                                <svg class="w-4 h-4 mr-3 text-slate-500" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                                </svg>
                                Database Snapshot
                            </span>
                            <svg class="w-4 h-4 text-slate-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Emergency Override Deck --}}
                <div class="mt-8 pt-6 border-t border-slate-100">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[9px] font-black uppercase tracking-[0.2em] text-slate-400">Emergency Override</span>
                        <span class="size-2 rounded-full bg-slate-300"></span>
                    </div>

                    @php
                    $hasActiveMaintenance = collect($labUtilization)->contains(function($lab) {
                    $labStatus = is_array($lab) ? ($lab['status'] ?? 'active') : ($lab->status ?? 'active');
                    if (in_array(strtolower($labStatus), ['maintenance', 'lockdown'])) {
                    return true;
                    }

                    $pcs = is_array($lab)
                    ? ($lab['pcs'] ?? $lab['computers'] ?? $lab['stations'] ?? [])
                    : ($lab->pcs ?? $lab->computers ?? $lab->stations ?? collect());

                    $pcsCollection = collect($pcs);
                    $totalPcs = is_array($lab) ? ($lab['total_pcs'] ?? null) : ($lab->total_pcs ?? null);
                    $maintPcs = is_array($lab) ? ($lab['maintenance_pcs'] ?? null) : ($lab->maintenance_pcs ?? null);

                    if ($totalPcs !== null && $maintPcs !== null) {
                    return ($totalPcs > 0) && ($totalPcs == $maintPcs);
                    }

                    return $pcsCollection->isNotEmpty() && $pcsCollection->every(function ($pc) {
                    $status = is_array($pc) ? ($pc['status'] ?? '') : ($pc->status ?? '');
                    return in_array(strtolower($status), ['maintenance', 'lockdown', 'disabled', 'offline']);
                    });
                    });
                    @endphp

                    @if($hasActiveMaintenance)
                    <div class="mb-3 p-3 bg-amber-50 border border-amber-200 rounded-2xl flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="size-2 bg-amber-500 rounded-full animate-ping"></span>
                            <span class="text-xs font-black text-amber-900 uppercase">Lockdown Active</span>
                        </div>
                        <button @click="restoreModal = true" type="button" class="text-[9px] font-black uppercase bg-amber-600 hover:bg-amber-700 text-white px-3 py-1 rounded-lg transition">
                            Clear
                        </button>
                    </div>
                    @endif

                    <div class="grid grid-cols-2 gap-2.5">
                        <button @click="lockoutModal = true" type="button" class="p-3.5 bg-rose-50 hover:bg-rose-100/80 border border-rose-200/80 text-rose-700 rounded-2xl transition-all text-left flex flex-col justify-between group active:scale-95">
                            <div class="p-2 bg-rose-600 text-white rounded-xl w-max mb-3 shadow-sm group-hover:scale-110 transition-transform">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9" />
                                </svg>
                            </div>
                            <div>
                                <span class="block text-xs font-black uppercase tracking-wide text-rose-800">Lockdown</span>
                                <span class="text-[9px] text-rose-600 font-bold block mt-0.5">Halt terminals</span>
                            </div>
                        </button>

                        <button @click="restoreModal = true" type="button" class="p-3.5 bg-emerald-50 hover:bg-emerald-100/80 border border-emerald-200/80 text-emerald-800 rounded-2xl transition-all text-left flex flex-col justify-between group active:scale-95">
                            <div class="p-2 bg-emerald-600 text-white rounded-xl w-max mb-3 shadow-sm group-hover:scale-110 transition-transform">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                                </svg>
                            </div>
                            <div>
                                <span class="block text-xs font-black uppercase tracking-wide text-emerald-900">Restore</span>
                                <span class="text-[9px] text-emerald-600 font-bold block mt-0.5">Resume fleet</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            {{-- 3. Recent Security Incidents Feed (Full 3 cols) --}}
            <div class="lg:col-span-3 bg-white/90 backdrop-blur-xl p-6 sm:p-8 rounded-[2.5rem] border border-slate-200/70 shadow-2xl shadow-slate-900/5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-6">
                    <div>
                        <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight">Recent Security & Hardware Incidents</h3>
                        <p class="text-xs text-slate-400 font-bold mt-0.5">Real-time alerts reported across connected computer stations</p>
                    </div>
                    <a href="{{ route('dashboard.alerts.index') }}" class="text-[10px] font-black text-[#D4AF37] hover:underline uppercase tracking-widest flex items-center gap-1 self-start sm:self-auto">
                        <span>Incident Center</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </div>

                <div class="space-y-4">
                    @forelse($recentAlerts as $alert)
                    @php
                    // Fixed: Resolves lab name from $alert->lab->name OR $alert->computer->lab->name
                    $resolvedLabName = $alert->lab->name
                    ?? $alert->computer->lab->name
                    ?? 'Main Laboratory';

                    $resolvedPcNumber = $alert->computer->pc_number ?? 'Terminal';
                    @endphp
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 sm:p-5 rounded-2xl bg-slate-50/80 border border-slate-200/60 hover:bg-slate-50 transition gap-4">
                        <div class="flex items-start gap-3.5">
                            <div class="p-2.5 rounded-xl shrink-0 mt-0.5 {{ $alert->status == 'pending' ? 'bg-rose-500/10 text-rose-600 border border-rose-500/20' : ($alert->status == 'discarded' ? 'bg-slate-200 text-slate-500' : 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20') }}">
                                @if($alert->status == 'pending')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                                @elseif($alert->status == 'discarded')
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                @endif
                            </div>

                            <div>
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <span class="px-2.5 py-0.5 rounded-lg text-[9px] font-mono font-black uppercase tracking-wider {{ $alert->status == 'pending' ? 'bg-rose-100 text-rose-700' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $alert->status }}
                                    </span>
                                    <span class="text-xs font-black text-slate-800 uppercase tracking-tight">
                                        {{ $alert->title ?? $alert->issue_type }}
                                    </span>
                                </div>

                                {{-- Fixed: Displays Terminal ID and Resolved Lab Name --}}
                                <p class="text-xs font-bold text-slate-500 flex items-center gap-1.5">
                                    <span class="text-slate-900 font-mono font-black">{{ $resolvedPcNumber }}</span>
                                    <span class="text-slate-300">•</span>
                                    <span>{{ $resolvedLabName }}</span>
                                    @if(!empty($alert->reporter->name ?? $alert->reportedBy->name))
                                    <span class="text-slate-300">•</span>
                                    <span class="text-slate-400 font-medium">Reported by {{ $alert->reporter->name ?? $alert->reportedBy->name }}</span>
                                    @endif
                                </p>

                                @if($alert->remarks ?? $alert->description ?? $alert->desc)
                                <p class="text-xs text-slate-600 italic mt-1.5 border-l-2 border-slate-200 pl-2.5">
                                    "{{ $alert->remarks ?? $alert->description ?? $alert->desc }}"
                                </p>
                                @endif
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <span class="text-[10px] font-mono font-bold text-slate-400 uppercase tracking-wider block">
                                {{ $alert->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-12 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                        <svg class="w-8 h-8 text-emerald-500 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-xs font-black text-slate-800 uppercase tracking-wider">All Workstations Clear</p>
                        <p class="text-[9px] text-slate-400 font-bold uppercase mt-0.5">No recent hardware or security alerts registered in the network</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- MODALS (SUPER ADMIN SYSTEM OPERATIONS) --}}
        {{-- ========================================================================= --}}

        {{-- MODAL 1: REPORT --}}
        <div x-show="reportModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-md">
            <div @click.away="reportModal = false" class="bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-md w-full p-6 sm:p-8">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight">Generate Audit Report</h3>
                    <button @click="reportModal = false" type="button" class="text-slate-400 hover:text-slate-600 p-1">✕</button>
                </div>
                <form action="{{ route('super-admin.reports.generate') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[9px] font-black uppercase text-slate-500 tracking-widest mb-2">Report Scope</label>
                        <select name="type" class="w-full border border-slate-200 rounded-2xl p-3 bg-slate-50 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-[#D4AF37]">
                            <option value="utilization">Laboratory Space Utilization & Attendance</option>
                            <option value="security">Security Alerts & Escalation Logs</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[9px] font-black uppercase text-slate-500 tracking-widest mb-2">Time Frame</label>
                        <select name="range" class="w-full border border-slate-200 rounded-2xl p-3 bg-slate-50 text-xs font-bold text-slate-800 focus:ring-2 focus:ring-[#D4AF37]">
                            <option value="today">Today</option>
                            <option value="week">Past 7 Days</option>
                            <option value="month">Current Month</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full py-3.5 bg-slate-900 text-white font-black text-xs uppercase tracking-wider rounded-2xl mt-2 hover:bg-[#D4AF37] hover:text-slate-950 transition-all shadow-lg active:scale-95">
                        Compile & Export CSV
                    </button>
                </form>
            </div>
        </div>

        {{-- MODAL 2: BACKUP --}}
        <div x-show="backupModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-md">
            <div @click.away="backupModal = false" class="bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-md w-full p-6 sm:p-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-black text-slate-900 uppercase tracking-tight">Database Snapshot</h3>
                    <button @click="backupModal = false" type="button" class="text-slate-400 hover:text-slate-600 p-1">✕</button>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed mb-6 font-medium">
                    This operation executes a secure database export and generates an encrypted snapshot archive in your server's root storage directory.
                </p>
                <form action="{{ route('super-admin.system.backup') }}" method="POST" class="space-y-3">
                    @csrf
                    <button type="submit" class="w-full py-3.5 bg-slate-900 text-white font-black text-xs uppercase tracking-wider rounded-2xl hover:bg-[#D4AF37] hover:text-slate-950 transition-all shadow-lg active:scale-95">
                        Execute System Snapshot
                    </button>
                    <button type="button" @click="backupModal = false" class="w-full py-3 bg-slate-100 text-slate-600 font-black text-xs uppercase tracking-wider rounded-2xl hover:bg-slate-200 transition">
                        Cancel
                    </button>
                </form>
            </div>
        </div>

        {{-- MODAL 3: EMERGENCY LOCKDOWN --}}
        <div x-show="lockoutModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-md">
            <div @click.away="lockoutModal = false" class="bg-white rounded-[2.5rem] shadow-2xl max-w-md w-full overflow-hidden border border-rose-200">
                <div class="p-6 sm:p-8 bg-rose-600 text-white relative">
                    <button @click="lockoutModal = false" type="button" class="absolute top-6 right-6 text-rose-200 hover:text-white transition">✕</button>
                    <div class="size-12 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center mb-4 border border-white/20">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-black uppercase tracking-tight">Initiate Station Lockdown</h3>
                    <p class="text-xs text-rose-100 font-medium mt-1">Force terminal quarantine and session revocation</p>
                </div>

                <div class="p-6 sm:p-8 space-y-4">
                    <form action="{{ route('super-admin.system.lockout') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-2">Target Laboratory Zone</label>
                            <select name="lab_id" class="w-full border border-slate-200 rounded-2xl p-3.5 bg-slate-50 text-xs font-black text-slate-800 focus:ring-2 focus:ring-rose-500">
                                <option value="all">⚠️ ENTIRE CAMPUS (ALL LABORATORIES)</option>
                                @foreach($labUtilization as $key => $lab)
                                @php
                                $id = is_array($lab) ? ($lab['id'] ?? $key) : ($lab->id ?? $key);
                                $name = is_array($lab) ? ($lab['name'] ?? "Lab #{$id}") : ($lab->name ?? "Lab #{$id}");
                                @endphp
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="p-4 bg-rose-50 rounded-2xl border border-rose-100 text-rose-900 text-xs space-y-1.5 font-medium">
                            <span class="font-black uppercase tracking-wider block text-rose-950">Warning:</span>
                            <p>• Immediately halts active workstation sessions in selected room.</p>
                            <p>• Sets workstations to <code class="bg-white/80 px-1 py-0.5 rounded font-mono font-bold">maintenance</code>.</p>
                            <p>• Rejects all new login attempts at terminal lock screens.</p>
                        </div>

                        <div class="space-y-2 pt-2">
                            <button type="submit" class="w-full py-3.5 bg-rose-600 hover:bg-rose-700 text-white font-black text-xs uppercase tracking-wider rounded-2xl transition shadow-lg shadow-rose-600/20 active:scale-95">
                                Confirm Lockdown Order
                            </button>
                            <button type="button" @click="lockoutModal = false" class="w-full py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-black text-xs uppercase tracking-wider rounded-2xl transition">
                                Abort
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- MODAL 4: RESTORE ACCESS --}}
        <div x-show="restoreModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-md">
            <div @click.away="restoreModal = false" class="bg-white rounded-[2.5rem] shadow-2xl max-w-md w-full overflow-hidden border border-emerald-200">
                <div class="p-6 sm:p-8 bg-emerald-600 text-white relative">
                    <button @click="restoreModal = false" type="button" class="absolute top-6 right-6 text-emerald-200 hover:text-white transition">✕</button>
                    <div class="size-12 rounded-2xl bg-white/10 backdrop-blur-md flex items-center justify-center mb-4 border border-white/20">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-black uppercase tracking-tight">Restore Workstation Access</h3>
                    <p class="text-xs text-emerald-100 font-medium mt-1">Clear maintenance quarantine network-wide</p>
                </div>

                <div class="p-6 sm:p-8 space-y-4">
                    <form action="{{ route('super-admin.system.release-lockout') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-[9px] font-black uppercase tracking-widest text-slate-500 mb-2">Target Laboratory Zone</label>
                            <select name="lab_id" class="w-full border border-slate-200 rounded-2xl p-3.5 bg-slate-50 text-xs font-black text-slate-800 focus:ring-2 focus:ring-emerald-500">
                                <option value="all">🌐 ENTIRE CAMPUS (ALL LABORATORIES)</option>
                                @foreach($labUtilization as $key => $lab)
                                @php
                                $id = is_array($lab) ? ($lab['id'] ?? $key) : ($lab->id ?? $key);
                                $name = is_array($lab) ? ($lab['name'] ?? "Lab #{$id}") : ($lab->name ?? "Lab #{$id}");
                                @endphp
                                <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-100 text-emerald-900 text-xs leading-relaxed font-medium">
                            Restores laboratory status back to <code class="bg-white/80 px-1 py-0.5 rounded font-mono font-bold text-emerald-800">active</code> and releases PC terminals from <code class="bg-white/80 px-1 py-0.5 rounded font-mono font-bold text-emerald-800">maintenance</code> back to <code class="bg-white/80 px-1 py-0.5 rounded font-mono font-bold text-emerald-800">available</code>.
                        </div>

                        <div class="space-y-2 pt-2">
                            <button type="submit" class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-wider rounded-2xl transition shadow-lg shadow-emerald-600/20 active:scale-95">
                                Release Maintenance Lock
                            </button>
                            <button type="button" @click="restoreModal = false" class="w-full py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-black text-xs uppercase tracking-wider rounded-2xl transition">
                                Keep Maintenance Active
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>