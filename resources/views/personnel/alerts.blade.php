<x-app-layout>
    <x-slot name="header">
        {{-- Header Container: Stacks on mobile, aligns horizontally on tablet/desktop --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-black text-2xl sm:text-3xl md:text-4xl text-slate-800 tracking-tighter uppercase">
                    Alerts <span class="text-[#D4AF37]">History</span>
                </h2>
                <div class="flex items-center space-x-2 mt-1">
                    <div class="size-2 bg-green-500 rounded-full animate-pulse"></div>
                    <p class="text-[9px] sm:text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] sm:tracking-[0.3em]">
                        Computer Alerts Overview
                    </p>
                </div>
            </div>

            {{-- Stat Cards Header: Grid on tiny screens, flex on larger views --}}
            <div class="grid grid-cols-2 sm:flex gap-3 w-full md:w-auto">
                <div class="bg-white px-4 sm:px-6 py-3 sm:py-4 rounded-2xl sm:rounded-3xl border border-slate-100 shadow-sm backdrop-blur-xl flex-1 sm:flex-initial">
                    <p class="text-[8px] font-black text-slate-400 uppercase mb-1 tracking-widest">Total Reports</p>
                    <p class="text-xl sm:text-2xl font-black text-slate-800">{{ $alerts->count() }}</p>
                </div>
                <div class="bg-[#D4AF37]/10 px-4 sm:px-6 py-3 sm:py-4 rounded-2xl sm:rounded-3xl border border-[#D4AF37]/20 relative overflow-hidden group backdrop-blur-xl shadow-lg shadow-[#D4AF37]/5 flex-1 sm:flex-initial">
                    <div class="absolute inset-0 bg-[#D4AF37]/5 group-hover:bg-[#D4AF37]/10 transition-colors"></div>
                    <p class="text-[8px] font-black text-[#B08D2A] uppercase mb-1 tracking-widest relative">Unresolved</p>
                    <p class="text-xl sm:text-2xl font-black text-[#D4AF37] relative">{{ $alerts->where('status', 'pending')->count() }}</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 md:py-12 px-4 sm:px-6 max-w-7xl mx-auto min-h-screen">

        {{-- Filter Bar Container --}}
        <div class="mb-6 sm:mb-10 group">
            <div class="bg-white border border-slate-100 p-4 sm:p-6 md:p-8 rounded-3xl sm:rounded-[2.5rem] shadow-xl shadow-slate-500/5 transition-all hover:border-slate-200">
                <form action="{{ url()->current() }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 items-end">

                    <div class="w-full">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">PC Name or Number</label>
                        <input type="text" name="pc_number" value="{{ request('pc_number') }}" placeholder="Search PC..."
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] placeholder:text-slate-400 py-3">
                    </div>

                    <div class="w-full">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Date Reported</label>
                        <input type="date" name="date" value="{{ request('date') }}"
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] [color-scheme:light] py-3">
                    </div>

                    <div class="w-full">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Status</label>
                        <select name="status" class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3">
                            <option value="">All Reports</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Needs Attention</option>
                            <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                        </select>
                    </div>

                    <div class="flex gap-2 sm:gap-3 w-full">
                        <button type="submit" class="flex-1 bg-slate-900 text-white font-black uppercase text-[10px] px-4 sm:px-8 py-3.5 rounded-2xl hover:bg-[#D4AF37] transition-all transform hover:scale-105 active:scale-95 shadow-xl shadow-black/10">
                            Filter
                        </button>
                        <a href="{{ url()->current() }}" class="flex-1 text-center justify-center bg-slate-100 text-slate-500 font-black uppercase text-[10px] px-4 sm:px-6 py-3.5 rounded-2xl hover:bg-slate-200 transition-all flex items-center">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Desktop Table View (Hidden on mobile/tablet) --}}
        <div class="hidden md:block bg-white border border-slate-100/60 rounded-[3rem] overflow-hidden shadow-2xl shadow-slate-500/5">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] bg-slate-50/50">
                        <th class="py-6 px-8">PC Name</th>
                        <th class="py-6 px-4">Reported By</th>
                        <th class="py-6 px-4">Issue Type</th>
                        <th class="py-6 px-4">Details / Remarks</th>
                        <th class="py-6 px-4">Date & Time</th>
                        <th class="py-6 px-8 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($alerts as $alert)
                    <tr class="group hover:bg-slate-50/50 transition-colors {{ $alert->status == 'resolved' ? 'opacity-70 bg-slate-50/30' : '' }}">
                        <td class="py-8 px-8">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center font-black text-[#D4AF37] text-xs shadow-inner shrink-0">
                                    {{ substr($alert->computer->pc_number ?? 'PC', -2) }}
                                </div>
                                <span class="font-black text-slate-900 tracking-tight">{{ $alert->computer->pc_number ?? 'Unknown PC' }}</span>
                            </div>
                        </td>

                        <td class="py-8 px-4">
                            <div class="flex flex-col">
                                <span class="font-black text-slate-900 text-xs tracking-tight">
                                    {{ $alert->reporter->name ?? 'Unknown Student' }}
                                </span>
                                <span class="text-[9px] text-slate-400 font-bold uppercase mt-0.5 tracking-tight">
                                    {{ $alert->reporter->student_number ?? 'N/A' }}
                                </span>
                            </div>
                        </td>

                        <td class="py-8 px-4">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-black uppercase tracking-wider {{ $alert->issue_type == 'Hardware Issue' ? 'text-[#D4AF37]' : 'text-sky-500' }}">
                                    {{ $alert->issue_type }}
                                </span>
                            </div>
                        </td>

                        <td class="py-8 px-4 max-w-xs">
                            <p class="text-slate-600 text-xs font-medium leading-relaxed italic border-l-2 border-slate-100 pl-3 line-clamp-3">
                                "{{ $alert->remarks }}"
                            </p>
                        </td>

                        <td class="py-8 px-4 whitespace-nowrap">
                            <div class="text-[10px] font-black uppercase">
                                <div class="text-slate-900 mb-1">{{ $alert->created_at->format('M d, Y') }}</div>
                                <div class="text-slate-400">{{ $alert->created_at->format('h:i A') }}</div>
                            </div>
                        </td>

                        <td class="py-8 px-8 text-right">
                            @if($alert->status == 'pending')
                            <form action="{{ route('personnel.alerts.resolve', $alert->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="group/btn relative overflow-hidden bg-[#D4AF37] hover:bg-[#B08D2A] text-white text-[9px] font-black uppercase px-6 py-3 rounded-2xl transition-all shadow-[0_0_20px_rgba(212,175,55,0.25)]">
                                    <span class="relative z-10">Mark as Resolved</span>
                                    <div class="absolute inset-0 bg-white/10 translate-y-full group-hover/btn:translate-y-0 transition-transform"></div>
                                </button>
                            </form>
                            @else
                            <div class="flex items-center justify-end gap-3">
                                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl bg-emerald-500/10 border border-emerald-500/20">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span class="text-emerald-600 text-[9px] font-black uppercase tracking-wider">Resolved</span>
                                </div>

                                <form action="{{ route('personnel.alerts.undo', $alert->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to undo this resolution and mark it as pending again?');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" title="Undo resolution" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 border border-slate-200/80 text-slate-500 hover:text-slate-800 text-[9px] font-black uppercase tracking-wider rounded-xl transition-all active:scale-95 flex items-center gap-1">
                                        <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                        </svg>
                                        <span>Undo</span>
                                    </button>
                                </form>
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

        {{-- Mobile & Tablet Card View (Visible below MD breakpoint) --}}
        <div class="block md:hidden space-y-4">
            @forelse($alerts as $alert)
            <div class="bg-white border border-slate-100/80 rounded-3xl p-5 shadow-xl shadow-slate-500/5 {{ $alert->status == 'resolved' ? 'opacity-70 bg-slate-50/50' : '' }}">

                {{-- Card Header: PC & Timestamp --}}
                <div class="flex items-start justify-between gap-3 pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center font-black text-[#D4AF37] text-xs shadow-inner shrink-0">
                            {{ substr($alert->computer->pc_number ?? 'PC', -2) }}
                        </div>
                        <div>
                            <span class="font-black text-slate-900 text-sm tracking-tight block">{{ $alert->computer->pc_number ?? 'Unknown PC' }}</span>
                            <span class="text-[10px] font-black uppercase tracking-wider {{ $alert->issue_type == 'Hardware Issue' ? 'text-[#D4AF37]' : 'text-sky-500' }}">
                                {{ $alert->issue_type }}
                            </span>
                        </div>
                    </div>
                    <div class="text-[9px] font-black uppercase text-right shrink-0">
                        <div class="text-slate-900">{{ $alert->created_at->format('M d, Y') }}</div>
                        <div class="text-slate-400">{{ $alert->created_at->format('h:i A') }}</div>
                    </div>
                </div>

                {{-- Card Body: Reporter & Remarks --}}
                <div class="py-4 space-y-3">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Reported By</span>
                        <div class="text-right">
                            <span class="font-black text-slate-900 text-xs block">{{ $alert->reporter->name ?? 'Unknown Student' }}</span>
                            <span class="text-[9px] text-slate-400 font-bold uppercase block">{{ $alert->reporter->student_number ?? 'N/A' }}</span>
                        </div>
                    </div>

                    <div class="pt-1">
                        <p class="text-slate-600 text-xs font-medium leading-relaxed italic border-l-2 border-slate-200 pl-3 bg-slate-50/50 py-2 rounded-r-xl">
                            "{{ $alert->remarks }}"
                        </p>
                    </div>
                </div>

                {{-- Card Footer: Action Buttons --}}
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                    @if($alert->status == 'pending')
                    <form action="{{ route('personnel.alerts.resolve', $alert->id) }}" method="POST" class="w-full">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="w-full bg-[#D4AF37] hover:bg-[#B08D2A] text-white text-[10px] font-black uppercase py-3 rounded-2xl transition-all shadow-[0_0_20px_rgba(212,175,55,0.25)] text-center">
                            Mark as Resolved
                        </button>
                    </form>
                    @else
                    <div class="flex items-center justify-between w-full gap-2">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-emerald-600 text-[9px] font-black uppercase tracking-wider">Resolved</span>
                        </div>

                        <form action="{{ route('personnel.alerts.undo', $alert->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to undo this resolution and mark it as pending again?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 border border-slate-200/80 text-slate-500 hover:text-slate-800 text-[9px] font-black uppercase tracking-wider rounded-xl transition-all active:scale-95 flex items-center gap-1">
                                <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                                <span>Undo</span>
                            </button>
                        </form>
                    </div>
                    @endif
                </div>

            </div>
            @empty
            <div class="bg-white border border-slate-100/60 rounded-3xl p-12 text-center shadow-xl shadow-slate-500/5">
                <div class="space-y-2">
                    <div class="text-slate-200 text-3xl font-black">ALL CLEAR</div>
                    <p class="text-slate-400 font-black uppercase tracking-widest text-[10px]">No alerts found</p>
                </div>
            </div>
            @endforelse
        </div>

    </div>
</x-app-layout>