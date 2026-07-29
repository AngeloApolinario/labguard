<x-app-layout>
    <div class="py-12 px-6 max-w-7xl mx-auto min-h-screen bg-[#FDFCF9]">

        {{-- Header --}}
        <div class="mb-12 flex justify-between items-center">
            <div class="relative">
                <div class="absolute -left-4 top-0 bottom-0 w-1 bg-[#D4AF37] shadow-[0_0_15px_rgba(212,175,55,0.6)]"></div>
                <h2 class="font-black text-5xl text-slate-900 uppercase tracking-tighter leading-none">
                    LAB <span class="text-[#D4AF37]">ALERTS</span>
                </h2>
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mt-2 flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-[#D4AF37] animate-pulse"></span>
                    Reported Issues & Incident Logs
                </p>
            </div>

            <div class="flex gap-3">
                <div class="bg-white px-6 py-4 rounded-3xl border border-slate-100 shadow-sm backdrop-blur-xl">
                    <p class="text-[8px] font-black text-slate-400 uppercase mb-1 tracking-widest">Total Reports</p>
                    <p class="text-2xl font-black text-slate-800">{{ $alerts->count() }}</p>
                </div>
                <div class="bg-[#D4AF37]/10 px-6 py-4 rounded-3xl border border-[#D4AF37]/20 relative overflow-hidden group backdrop-blur-xl shadow-lg shadow-[#D4AF37]/5">
                    <div class="absolute inset-0 bg-[#D4AF37]/5 group-hover:bg-[#D4AF37]/10 transition-colors"></div>
                    <p class="text-[8px] font-black text-[#B08D2A] uppercase mb-1 tracking-widest relative">Unresolved</p>
                    <p class="text-2xl font-black text-[#D4AF37] relative">{{ $alerts->where('status', 'pending')->count() }}</p>
                </div>
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="mb-10 group">
            <div class="bg-white border border-slate-100 p-8 rounded-[2.5rem] shadow-xl shadow-slate-500/5 transition-all hover:border-slate-200">
                <form action="{{ route('dashboard.alerts.index') }}" method="GET" class="flex flex-wrap items-end gap-8">

                    <div class="flex-1 min-w-[200px]">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-3 block">PC Name or Number</label>
                        <input type="text" name="pc_number" value="{{ request('pc_number') }}" placeholder="Search PC..."
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] placeholder:text-slate-400 py-3">
                    </div>

                    <div class="flex-1 min-w-[150px]">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-3 block">Date Reported</label>
                        <input type="date" name="date" value="{{ request('date') }}"
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] [color-scheme:light] py-3">
                    </div>

                    <div class="flex-1 min-w-[150px]">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-3 block">Status</label>
                        <select name="status" class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3">
                            <option value="">All Reports</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Needs Attention</option>
                            <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                        </select>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="bg-slate-900 text-white font-black uppercase text-[10px] px-8 py-3.5 rounded-2xl hover:bg-[#D4AF37] transition-all transform hover:scale-105 active:scale-95 shadow-xl shadow-black/10">
                            Filter
                        </button>
                        <a href="{{ route('dashboard.alerts.index') }}" class="bg-slate-100 text-slate-500 font-black uppercase text-[10px] px-6 py-3.5 rounded-2xl hover:bg-slate-200 transition-all flex items-center">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Alerts Table --}}
        <div class="bg-white border border-slate-100/60 rounded-[3rem] overflow-hidden shadow-2xl shadow-slate-500/5">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] bg-slate-50/50">
                        <th class="py-6 px-8">PC Name</th>
                        <th class="py-6 px-4">Laboratory</th>
                        <th class="py-6 px-4">Issue Type</th>
                        <th class="py-6 px-4">Details / Remarks</th>
                        <th class="py-6 px-4">Date & Time</th>
                        <th class="py-6 px-8 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($alerts as $alert)
                    <tr class="group hover:bg-slate-50/50 transition-colors {{ $alert->status == 'resolved' ? 'opacity-40 grayscale-[0.8]' : '' }}">
                        {{-- PC Name --}}
                        <td class="py-8 px-8">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center font-black text-[#D4AF37] text-xs shadow-inner">
                                    {{ substr($alert->computer->pc_number ?? 'PC', -2) }}
                                </div>
                                <span class="font-black text-slate-900 tracking-tight">{{ $alert->computer->pc_number ?? 'Unknown PC' }}</span>
                            </div>
                        </td>

                        {{-- Laboratory Location --}}
                        <td class="py-8 px-4">
                            <span class="text-xs font-bold text-slate-700">
                                {{ $alert->computer->lab->name ?? $alert->computer->lab_name ?? 'Unassigned Lab' }}
                            </span>
                        </td>

                        {{-- Issue Type --}}
                        <td class="py-8 px-4">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-black uppercase tracking-wider {{ $alert->issue_type == 'Hardware Issue' ? 'text-[#D4AF37]' : 'text-sky-500' }}">
                                    {{ $alert->issue_type }}
                                </span>
                            </div>
                        </td>

                        {{-- Details / Remarks --}}
                        <td class="py-8 px-4 max-w-xs">
                            <p class="text-slate-600 text-xs font-medium leading-relaxed italic border-l-2 border-slate-100 pl-3">
                                "{{ $alert->remarks }}"
                            </p>
                        </td>

                        {{-- Date & Time --}}
                        <td class="py-8 px-4">
                            <div class="text-[10px] font-black uppercase">
                                <div class="text-slate-900 mb-1">{{ $alert->created_at->format('M d, Y') }}</div>
                                <div class="text-slate-400">{{ $alert->created_at->format('h:i A') }}</div>
                            </div>
                        </td>

                        {{-- Action Button --}}
                        <td class="py-8 px-8 text-right">
                            @if($alert->status == 'pending')
                            <form action="{{ route('dashboard.alerts.resolve', $alert->id) }}" method="POST">
                                @csrf @method('PATCH')
                                <button class="group/btn relative overflow-hidden bg-[#D4AF37] hover:bg-[#B08D2A] text-white text-[9px] font-black uppercase px-6 py-3 rounded-2xl transition-all shadow-[0_0_20px_rgba(212,175,55,0.25)]">
                                    <span class="relative z-10">Mark as Resolved</span>
                                    <div class="absolute inset-0 bg-white/10 translate-y-full group-hover/btn:translate-y-0 transition-transform"></div>
                                </button>
                            </form>
                            @else
                            <div class="inline-flex items-center gap-2 px-5 py-2 rounded-2xl bg-emerald-500/10 border border-emerald-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span class="text-emerald-600 text-[9px] font-black uppercase tracking-wider">Resolved</span>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-32 text-center">
                            <div class="space-y-3">
                                <div class="text-slate-200 text-5xl font-black">ALL CLEAR</div>
                                <p class="text-slate-400 font-black uppercase tracking-widest text-[10px]">No alerts found</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>