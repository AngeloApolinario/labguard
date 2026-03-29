<x-app-layout>
    <div class="p-6 bg-[#f4f7f9] min-h-screen font-sans">

        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">Terminal <span class="text-[#D4AF37]">Guard</span></h1>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Entry-Point Monitoring Service</p>
            </div>
            <div class="px-4 py-2 bg-white border border-slate-200 rounded-lg shadow-sm">
                <div class="flex items-center text-[10px] font-black text-slate-500 uppercase tracking-tighter">
                    <span class="size-2 bg-green-500 rounded-full animate-pulse mr-2"></span>
                    Server Link: Stable
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Active Now</p>
                    <h2 class="text-4xl font-black text-slate-800 tracking-tighter">18</h2>
                </div>
                <x-heroicon-o-user-group class="size-10 text-slate-100" />
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between border-t-4 border-t-rose-500">
                <div>
                    <p class="text-[10px] font-black text-rose-400 uppercase tracking-widest">Offline PCs</p>
                    <h2 class="text-4xl font-black text-slate-800 tracking-tighter">03</h2>
                </div>
                <x-heroicon-o-exclamation-triangle class="size-10 text-rose-50" />
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Denied Entries</p>
                    <h2 class="text-4xl font-black text-slate-800 tracking-tighter">12</h2>
                </div>
                <x-heroicon-o-shield-exclamation class="size-10 text-slate-100" />
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">

            <div class="lg:col-span-3">
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                        <h3 class="text-xs font-black text-slate-800 uppercase tracking-widest italic">Live Integrity Feed</h3>
                        <span class="text-[9px] font-bold text-slate-400 uppercase">Updating in real-time</span>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <div class="p-6 flex items-center justify-between hover:bg-slate-50 transition-colors">
                            <div class="flex items-center space-x-4">
                                <div class="size-10 rounded-full bg-rose-100 flex items-center justify-center text-rose-600 font-black text-xs">!</div>
                                <div>
                                    <p class="text-sm font-black text-slate-800 uppercase">PC-04: Connection Lost</p>
                                    <p class="text-xs text-slate-500">No heartbeat detected for 5 minutes. PC may be shut down.</p>
                                </div>
                            </div>
                            <button class="text-[10px] font-black text-rose-600 hover:underline uppercase italic">Force Logout</button>
                        </div>

                        <div class="p-6 flex items-center justify-between hover:bg-slate-50 transition-colors">
                            <div class="flex items-center space-x-4">
                                <div class="size-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 font-black text-xs">?</div>
                                <div>
                                    <p class="text-sm font-black text-slate-800 uppercase">Invalid ID: 2024-9999</p>
                                    <p class="text-xs text-slate-500">User attempted to log into PC-12 with an unregistered ID.</p>
                                </div>
                            </div>
                            <button class="text-[10px] font-black text-slate-400 uppercase italic">Dismiss</button>
                        </div>

                        <div class="p-6 flex items-center justify-between hover:bg-slate-50 transition-colors">
                            <div class="flex items-center space-x-4">
                                <div class="size-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 font-black text-xs">i</div>
                                <div>
                                    <p class="text-sm font-black text-slate-800 uppercase">PC-21: Session Timeout</p>
                                    <p class="text-xs text-slate-500">Scheduled time ended, but student is still logged in.</p>
                                </div>
                            </div>
                            <button class="text-[10px] font-black text-[#D4AF37] hover:underline uppercase italic">Lock PC</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest px-2">Master Controls</h3>

                <button class="w-full p-4 bg-rose-600 hover:bg-rose-700 text-white rounded-2xl flex items-center justify-center transition-shadow shadow-lg">
                    <x-heroicon-o-no-symbol class="size-4 mr-2" />
                    <span class="text-[10px] font-black uppercase tracking-widest">Emergency Lock</span>
                </button>

                <button class="w-full p-4 bg-slate-800 hover:bg-slate-900 text-white rounded-2xl flex items-center justify-center transition-all">
                    <x-heroicon-o-arrow-path class="size-4 mr-2 text-[#D4AF37]" />
                    <span class="text-[10px] font-black uppercase tracking-widest">Sync Heartbeats</span>
                </button>

                <div class="p-6 bg-white rounded-3xl border border-slate-200 mt-6">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4">System Guide</p>
                    <ul class="space-y-3">
                        <li class="flex items-center text-[10px] font-bold text-slate-600 uppercase">
                            <span class="size-2 bg-green-500 rounded-full mr-3"></span> Normal Entry
                        </li>
                        <li class="flex items-center text-[10px] font-bold text-slate-600 uppercase">
                            <span class="size-2 bg-amber-500 rounded-full mr-3"></span> Mismatch Error
                        </li>
                        <li class="flex items-center text-[10px] font-bold text-slate-600 uppercase">
                            <span class="size-2 bg-rose-500 rounded-full mr-3"></span> Force Shutdown
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>