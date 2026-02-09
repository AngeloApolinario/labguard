<x-app-layout>
    <div class="py-12 px-4 sm:px-6 lg:px-8">
        {{-- Header Section --}}
        <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between border-b border-[#D4AF37]/20 pb-6">
            <div>
                <h2 class="text-3xl font-black text-white tracking-tight">
                    STATION <span class="text-[#D4AF37]">OVERVIEW</span>
                </h2>
                <p class="text-slate-400 mt-1 font-medium">Welcome back, {{ Auth::user()->name }}. Monitoring active systems at Araullo University.</p>
            </div>
            <div class="mt-4 md:mt-0 flex items-center space-x-3">
                <span class="flex h-3 w-3 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                </span>
                <span class="text-xs font-bold text-green-500 uppercase tracking-widest">System Live</span>
            </div>
        </div>

        {{-- Quick Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            {{-- Assigned Labs Card --}}
            <div class="bg-white/5 border border-white/10 p-6 rounded-2xl backdrop-blur-sm hover:border-[#D4AF37]/50 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-[#D4AF37]/10 rounded-lg text-[#D4AF37]">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <span class="text-2xl font-black text-white">04</span>
                </div>
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider">Assigned Labs</h3>
            </div>

            {{-- Active Alerts Card --}}
            <div class="bg-white/5 border border-white/10 p-6 rounded-2xl backdrop-blur-sm hover:border-rose-500/50 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-rose-500/10 rounded-lg text-rose-500">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <span class="text-2xl font-black text-white">02</span>
                </div>
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider">Active Alerts</h3>
            </div>

            {{-- Terminal Status Card --}}
            <div class="bg-white/5 border border-white/10 p-6 rounded-2xl backdrop-blur-sm hover:border-cyan-500/50 transition-all">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-2 bg-cyan-500/10 rounded-lg text-cyan-500">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                    </div>
                    <span class="text-2xl font-black text-white">Online</span>
                </div>
                <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider">Terminal Node</h3>
            </div>
        </div>

        {{-- Main Content Area --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Lab Status Table --}}
            <div class="bg-white/5 border border-white/10 rounded-2xl overflow-hidden shadow-xl">
                <div class="p-6 border-b border-white/10 flex justify-between items-center">
                    <h3 class="font-bold text-white uppercase tracking-widest text-sm">Assigned Lab Status</h3>
                    <a href="{{ route('personnel.labs') }}" class="text-[10px] text-[#D4AF37] font-black uppercase hover:underline">View All</a>
                </div>
                <div class="p-0">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-white/5">
                                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Lab Name</th>
                                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Occupancy</th>
                                <th class="p-4 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr>
                                <td class="p-4 text-sm font-medium text-white">Comp Lab 01</td>
                                <td class="p-4 text-sm text-slate-400">12 / 40 PCs Active</td>
                                <td class="p-4 text-right">
                                    <button class="px-3 py-1 bg-[#D4AF37]/10 text-[#D4AF37] text-[10px] font-black rounded hover:bg-[#D4AF37]/20 transition-colors uppercase">Enter</button>
                                </td>
                            </tr>
                            <tr>
                                <td class="p-4 text-sm font-medium text-white">Comp Lab 05</td>
                                <td class="p-4 text-sm text-slate-400">Full Capacity</td>
                                <td class="p-4 text-right">
                                    <button class="px-3 py-1 bg-[#D4AF37]/10 text-[#D4AF37] text-[10px] font-black rounded hover:bg-[#D4AF37]/20 transition-colors uppercase">Enter</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- System Notifications / Logs --}}
            <div class="bg-[#0f172a] border border-[#D4AF37]/20 rounded-2xl shadow-xl p-6 font-mono overflow-hidden relative">
                <div class="absolute top-0 right-0 p-2 opacity-10">
                    <svg class="size-24" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zM11 18H5v-2h6v2zm8-5H5V7h14v6z" />
                    </svg>
                </div>
                <h3 class="font-bold text-[#D4AF37] uppercase tracking-widest text-sm mb-4 flex items-center">
                    <span class="mr-2">></span> System_Logs.log
                </h3>
                <div class="space-y-3 text-xs">
                    <p class="text-slate-400"><span class="text-green-500">[09:22:15]</span> Login detected: User_Personnel_01</p>
                    <p class="text-slate-400"><span class="text-cyan-500">[09:25:01]</span> Comp Lab 01 synchronization successful.</p>
                    <p class="text-rose-500"><span class="text-rose-500">[10:15:33]</span> Alert: High temperature detected in Server Rack A.</p>
                    <p class="text-slate-400"><span class="text-green-500">[10:30:00]</span> Routine scan completed. 0 threats found.</p>
                </div>
                <div class="mt-6 pt-4 border-t border-white/5 animate-pulse">
                    <span class="text-[#D4AF37] text-xs">_</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>