<x-app-layout>
    {{-- Replaced the deep black with a soft, warm off-white --}}
    <div class="py-12 px-6 max-w-7xl mx-auto min-h-screen bg-[#FDFCF9]">

        {{-- Header: Shifted to light, bold text with Gold accent --}}
        <div class="mb-12 flex justify-between items-center">
            <div class="relative">
                {{-- Left Accent Bar: Moved from Rose to Neon Gold Glow --}}
                <div class="absolute -left-4 top-0 bottom-0 w-1 bg-[#D4AF37] shadow-[0_0_15px_rgba(212,175,55,0.6)]"></div>
                <h2 class="font-black text-5xl text-slate-900 uppercase tracking-tighter leading-none">
                    TERMINAL <span class="text-[#D4AF37]">ALERTS</span>
                </h2>
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.5em] mt-2 flex items-center gap-2">
                    {{-- Pulsing Dot: Now Gold --}}
                    <span class="inline-block w-2 h-2 rounded-full bg-[#D4AF37] animate-pulse"></span>
                    System Diagnostics & Active Incident Reports
                </p>
            </div>

            <div class="flex gap-3">
                {{-- Counters: Made white, border subtle, and highlighted with Gold text --}}
                <div class="bg-white px-6 py-4 rounded-3xl border border-slate-100 shadow-sm backdrop-blur-xl">
                    <p class="text-[8px] font-black text-slate-400 uppercase mb-1 tracking-widest">Logged Events</p>
                    <p class="text-2xl font-black text-slate-800">{{ $alerts->count() }}</p>
                </div>
                {{-- Critical Counter: Moved from Rose to subtle Gold background --}}
                <div class="bg-[#D4AF37]/10 px-6 py-4 rounded-3xl border border-[#D4AF37]/20 relative overflow-hidden group backdrop-blur-xl shadow-lg shadow-[#D4AF37]/5">
                    <div class="absolute inset-0 bg-[#D4AF37]/5 group-hover:bg-[#D4AF37]/10 transition-colors"></div>
                    <p class="text-[8px] font-black text-[#B08D2A] uppercase mb-1 tracking-widest relative">Critical Pending</p>
                    <p class="text-2xl font-black text-[#D4AF37] relative">{{ $alerts->where('status', 'pending')->count() }}</p>
                </div>
            </div>
        </div>

        {{-- Cinematic Filter Bar: Now "Alabaster" white card with clean inputs --}}
        <div class="mb-10 group">
            <div class="bg-white border border-slate-100 p-8 rounded-[2.5rem] shadow-xl shadow-slate-500/5 transition-all hover:border-slate-200">
                <form action="{{ route('dashboard.alerts.index') }}" method="GET" class="flex flex-wrap items-end gap-8">

                    <div class="flex-1 min-w-[200px]">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-3 block">PC Identifier</label>
                        {{-- Input: White bg, slate borders, Gold focus ring --}}
                        <input type="text" name="pc_number" value="{{ request('pc_number') }}" placeholder="Search PC..."
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] placeholder:text-slate-400 py-3">
                    </div>

                    <div class="flex-1 min-w-[150px]">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-3 block">Temporal Range</label>
                        {{-- [color-scheme:light] for correct date picker on white --}}
                        <input type="date" name="date" value="{{ request('date') }}"
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] [color-scheme:light] py-3">
                    </div>

                    <div class="flex-1 min-w-[150px]">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-3 block">Incident Status</label>
                        <select name="status" class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3">
                            <option value="">Full Archive</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending Intervention</option>
                            <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved / Clear</option>
                        </select>
                    </div>

                    <div class="flex gap-3">
                        {{-- Apply Button: Now sleek black text on white, but Gold hover --}}
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

        {{-- Main HUD Table: Now a White "Glass" container with cleaner borders --}}
        <div class="bg-white border border-slate-100/60 rounded-[3rem] overflow-hidden shadow-2xl shadow-slate-500/5">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[9px] font-black text-slate-400 uppercase tracking-[0.4em] bg-slate-50/50">
                        <th class="py-6 px-10">System Node</th>
                        <th class="py-6 px-4">Reported By</th>
                        <th class="py-6 px-4">Classification</th>
                        <th class="py-6 px-4">Transmission</th>
                        <th class="py-6 px-4">Log Time</th>
                        <th class="py-6 px-10 text-right">Protocol</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($alerts as $alert)
                    {{-- Row: Light text, simplified "Grayed" state --}}
                    <tr class="group hover:bg-slate-50/50 transition-colors {{ $alert->status == 'resolved' ? 'opacity-40 grayscale-[0.8]' : '' }}">
                        <td class="py-8 px-10">
                            <div class="flex items-center gap-4">
                                {{-- Node Badge: Now Gold border and text --}}
                                <div class="w-10 h-10 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center font-black text-[#D4AF37] text-xs shadow-inner">
                                    {{ substr($alert->computer->pc_number, -2) }}
                                </div>
                                <span class="font-black text-slate-900 tracking-tighter">{{ $alert->computer->pc_number }}</span>
                            </div>
                        </td>

                        {{-- Added Column: Displays accountability information cleanly --}}
                        <td class="py-8 px-4">
                            <div class="flex flex-col">
                                <span class="font-black text-slate-900 text-xs tracking-tight">
                                    {{ $alert->reporter->name ?? 'Unknown System Student' }}
                                </span>
                                <span class="text-[9px] text-slate-400 font-bold uppercase mt-0.5 tracking-tight">
                                    {{ $alert->reporter->student_number ?? 'N/A' }}
                                </span>
                            </div>
                        </td>

                        <td class="py-8 px-4">
                            <div class="flex flex-col">
                                {{-- Issue Type: Rose moved to Gold --}}
                                <span class="text-[10px] font-black uppercase tracking-widest {{ $alert->issue_type == 'Hardware Issue' ? 'text-[#D4AF37]' : 'text-sky-500' }}">
                                    {{ $alert->issue_type }}
                                </span>
                                <span class="text-[8px] text-slate-400 font-bold uppercase mt-1">Severity: High</span>
                            </div>
                        </td>
                        <td class="py-8 px-4 max-w-xs">
                            {{-- Remark: Lightened italic --}}
                            <p class="text-slate-600 text-sm font-medium leading-relaxed italic border-l-2 border-slate-100 pl-4 uppercase text-[11px] tracking-tight">
                                "{{ $alert->remarks }}"
                            </p>
                        </td>
                        <td class="py-8 px-4">
                            <div class="text-[10px] font-black uppercase tracking-tighter">
                                <div class="text-slate-900 mb-1">{{ $alert->created_at->format('M d, Y') }}</div>
                                <div class="text-slate-400">{{ $alert->created_at->format('H:i:s') }} <span class="text-[8px]">UTC</span></div>
                            </div>
                        </td>
                        <td class="py-8 px-10 text-right">
                            @if($alert->status == 'pending')
                            {{-- Change route target from dashboard.alerts.resolve to personnel.alerts.resolve --}}
                            <form action="{{ route('personnel.alerts.resolve', $alert->id) }}" method="POST">
                                @csrf
                                @method('PATCH')

                                <button type="submit" class="group/btn relative overflow-hidden bg-[#D4AF37] hover:bg-[#B08D2A] text-white text-[9px] font-black uppercase px-8 py-3 rounded-2xl transition-all shadow-[0_0_20px_rgba(212,175,55,0.25)]">
                                <span class="relative z-10">Resolve Incident</span>
                                    <div class="absolute inset-0 bg-white/10 translate-y-full group-hover/btn:translate-y-0 transition-transform"></div>
                                </button>
                            </form>
                            @else
                            {{-- Resolved Badge: Gold theme --}}
                            <div class="inline-flex items-center gap-2 px-6 py-2 rounded-2xl bg-emerald-500/10 border border-emerald-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span class="text-emerald-500 text-[9px] font-black uppercase tracking-[0.2em]">Archived</span>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-32 text-center">
                            <div class="space-y-4">
                                {{-- Empty State: Neutral light Gray --}}
                                <div class="text-slate-100 text-6xl font-black">CLEAN</div>
                                <p class="text-slate-400 font-black uppercase tracking-[0.8em] text-[10px]">All systems operational</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>