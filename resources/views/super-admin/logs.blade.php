<x-app-layout>
    <!-- Page Header -->
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-[#D4AF37] animate-pulse"></span>
                    <span class="text-xs font-mono tracking-widest text-slate-500 uppercase font-bold">LabGuard Core</span>
                </div>
                <h2 class="font-black text-3xl md:text-4xl text-slate-900 tracking-tight uppercase mt-1">
                    System <span class="text-[#D4AF37]">Audit Logs</span>
                </h2>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mt-1">
                    Track system activity, user actions, and security events
                </p>
            </div>

            <div class="flex items-center gap-3">
                <button onclick="window.print()" class="bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 px-4 py-2.5 rounded-2xl font-bold text-xs uppercase tracking-wider flex items-center gap-2 shadow-sm transition-all active:scale-95">
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Print Log
                </button>
            </div>
        </div>
    </x-slot>

    <div class="min-h-screen bg-slate-50 text-slate-800 p-4 md:p-8 font-sans relative"
        x-data="{ 
            logModal: false, 
            currentLog: {},
            search: '',
            selectedSeverity: '',
            selectedEvent: ''
        }">

        <div class="max-w-7xl mx-auto space-y-6">

            {{-- Controls Bar: Search & Filters --}}
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 p-5 rounded-2xl bg-white border border-slate-200 shadow-sm">
                <div class="flex flex-1 flex-wrap items-center gap-3 w-full sm:w-auto">
                    {{-- Search Input --}}
                    <div class="relative w-full sm:w-80">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </span>
                        <input type="text"
                            x-model="search"
                            placeholder="Search user, action, or IP address..."
                            class="block w-full pl-10 pr-4 py-2 bg-slate-50 text-xs font-semibold text-slate-800 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-400 focus:border-slate-400 focus:outline-none transition-all">
                    </div>

                    {{-- Status / Severity Filter --}}
                    <select x-model="selectedSeverity" class="bg-slate-50 text-xs font-semibold text-slate-700 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-slate-400 focus:border-slate-400 focus:outline-none cursor-pointer transition-all">
                        <option value="">All Status Levels</option>
                        <option value="info">Info</option>
                        <option value="warning">Warning</option>
                        <option value="danger">Danger</option>
                    </select>

                    {{-- Event Category Filter --}}
                    <select x-model="selectedEvent" class="bg-slate-50 text-xs font-semibold text-slate-700 border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-slate-400 focus:border-slate-400 focus:outline-none cursor-pointer transition-all">
                        <option value="">All Categories</option>
                        <option value="auth">Login & Security</option>
                        <option value="user_management">User Changes</option>
                        <option value="hardware">Lab Equipment</option>
                        <option value="system">System Setup</option>
                    </select>
                </div>
            </div>

            {{-- Main Table Container --}}
            <div class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden p-6">
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-2">
                        <h3 class="text-lg font-bold text-slate-900">Recorded Activity</h3>
                        <span class="bg-slate-100 text-slate-700 border border-slate-200 px-2.5 py-0.5 rounded-full text-xs font-bold">
                            {{ $logs->count() }} Total
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-separate border-spacing-y-1">
                        <thead>
                            <tr class="text-[11px] font-bold text-slate-500 uppercase tracking-wider bg-slate-50 rounded-xl">
                                <th class="py-3 px-4 rounded-l-lg">Date & Time</th>
                                <th class="py-3 px-4">User</th>
                                <th class="py-3 px-4">Action Done</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">IP Address</th>
                                <th class="py-3 px-4 text-right pr-6 rounded-r-lg">Details</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($logs as $log)
                            <tr class="group hover:bg-slate-50/80 transition-colors"
                                x-show="(search === '' || $el.innerText.toLowerCase().includes(search.toLowerCase())) && (selectedSeverity === '' || '{{ $log->properties['severity'] ?? 'info' }}' === selectedSeverity) && (selectedEvent === '' || '{{ $log->log_name ?? 'system' }}' === selectedEvent)"
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100">

                                {{-- Date & Time --}}
                                <td class="py-3.5 px-4 rounded-l-xl">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-700 font-mono">{{ $log->created_at->format('M d, Y') }}</span>
                                        <span class="text-[10px] text-slate-400 font-mono">{{ $log->created_at->format('h:i:s A') }}</span>
                                    </div>
                                </td>

                                {{-- User (Spatie uses $log->causer) --}}
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-slate-800 text-white flex items-center justify-center font-bold text-xs">
                                            {{ strtoupper(substr($log->causer->name ?? 'S', 0, 1)) }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-slate-800">{{ $log->causer->name ?? 'System Process' }}</span>
                                            <span class="text-[10px] text-slate-400 font-mono">{{ $log->causer->email ?? 'system@labguard' }}</span>
                                        </div>
                                    </div>
                                </td>

                                {{-- Action --}}
                                <td class="py-3.5 px-4">
                                    <span class="text-xs font-medium text-slate-700 block max-w-xs truncate" title="{{ $log->description }}">
                                        {{ $log->description }}
                                    </span>
                                </td>

                                {{-- Status Badge --}}
                                <td class="py-3.5 px-4">
                                    @php
                                    $severity = $log->properties['severity'] ?? 'info';
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide border
                                        {{ $severity == 'danger' ? 'bg-red-50 text-red-700 border-red-200' : '' }}
                                        {{ $severity == 'warning' ? 'bg-amber-50 text-amber-700 border-amber-200' : '' }}
                                        {{ $severity == 'info' ? 'bg-blue-50 text-blue-700 border-blue-200' : '' }}">
                                        {{ $severity }}
                                    </span>
                                </td>

                                {{-- IP Address (retrieved from Spatie properties) --}}
                                <td class="py-3.5 px-4 text-xs font-mono text-slate-500">
                                    {{ $log->properties['ip_address'] ?? '127.0.0.1' }}
                                </td>

                                {{-- View Details Button --}}
                                <td class="py-3.5 px-4 rounded-r-xl text-right pr-6">
                                    <button @click="logModal = true; currentLog = {{ json_encode([
                                        'id' => $log->id,
                                        'user_name' => $log->causer->name ?? 'System Process',
                                        'ip_address' => $log->properties['ip_address'] ?? '127.0.0.1',
                                        'description' => $log->description,
                                        'properties' => $log->properties
                                    ]) }}"
                                        class="text-xs font-bold text-slate-700 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-lg border border-slate-200 transition-all active:scale-95">
                                        View
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination Links --}}
                @if(method_exists($logs, 'links'))
                <div class="mt-5 pt-4 border-t border-slate-100">
                    {{ $logs->links() }}
                </div>
                @endif
            </div>
        </div>

        {{-- Log View Modal --}}
        <div x-show="logModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm" x-cloak>
            <div class="bg-white border border-slate-200 rounded-2xl p-6 max-w-xl w-full shadow-xl relative" @click.away="logModal = false">

                <div class="mb-4 pb-3 border-b border-slate-100 flex justify-between items-start">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Log Details</h3>
                        <p class="text-xs font-mono text-slate-400 mt-0.5">
                            ID: <span x-text="currentLog.id"></span>
                        </p>
                    </div>
                    <button @click="logModal = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">✕</button>
                </div>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">User</span>
                            <span class="text-xs font-bold text-slate-800" x-text="currentLog.user_name"></span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">IP Address</span>
                            <span class="text-xs font-mono text-slate-600" x-text="currentLog.ip_address"></span>
                        </div>
                    </div>

                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">What Happened</span>
                        <p class="text-xs text-slate-800 bg-white p-3 rounded-xl border border-slate-200" x-text="currentLog.description"></p>
                    </div>

                    <div>
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Extra Data (Changes)</span>
                        <pre class="text-[11px] font-mono bg-slate-900 text-emerald-400 p-4 rounded-xl overflow-x-auto max-h-40 border border-slate-800" x-text="JSON.stringify(currentLog.properties || {}, null, 2)"></pre>
                    </div>
                </div>

                <div class="pt-5 flex justify-end">
                    <button type="button" @click="logModal = false" class="px-5 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold uppercase tracking-wider hover:bg-slate-800 transition-all">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>