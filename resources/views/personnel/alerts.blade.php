<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="size-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-[9px] font-black uppercase tracking-[0.3em] text-[#D4AF37]">Surveillance Incident Ledger</span>
                </div>
                <h2 class="font-black text-2xl sm:text-3xl md:text-4xl text-slate-800 tracking-tight uppercase">
                    Alerts <span class="text-[#D4AF37]">History</span>
                </h2>
            </div>

            {{-- Metric Badges --}}
            <div class="grid grid-cols-2 sm:flex gap-3 w-full md:w-auto">
                <div class="bg-white px-5 sm:px-6 py-3.5 rounded-2xl sm:rounded-3xl border border-slate-100 shadow-sm flex-1 sm:flex-initial">
                    <p class="text-[8px] font-black text-slate-400 uppercase mb-1 tracking-widest">Total Reports</p>
                    <p class="text-xl sm:text-2xl font-black text-slate-800 font-mono">{{ $totalReports ?? ($alerts->total() ?? $alerts->count()) }}</p>
                </div>
                <div class="bg-white px-5 sm:px-6 py-3.5 rounded-2xl sm:rounded-3xl border border-amber-500/20 shadow-lg shadow-amber-500/5 flex-1 sm:flex-initial relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-[#D4AF37] to-amber-500"></div>
                    <p class="text-[8px] font-black text-[#B08D2A] uppercase mb-1 tracking-widest">Needs Attention</p>
                    <p class="text-xl sm:text-2xl font-black text-[#D4AF37] font-mono">{{ $unresolvedCount ?? $alerts->where('status', 'pending')->count() }}</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 md:py-10 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto min-h-screen">

        {{-- ========================================================================= --}}
        {{-- FILTER BAR (Includes New Laboratory Dropdown)                             --}}
        {{-- ========================================================================= --}}
        <div class="mb-6 sm:mb-8">
            <div class="bg-white border border-slate-200/80 p-5 sm:p-7 rounded-3xl sm:rounded-[2.2rem] shadow-xl shadow-slate-900/5">
                <form action="{{ route('personnel.alerts') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">

                    {{-- 1. PC Number --}}
                    <div>
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5 block ml-1">Terminal ID</label>
                        <input type="text" name="pc_number" value="{{ request('pc_number') }}" placeholder="E.g. PC-01..."
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs font-semibold focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] placeholder:text-slate-400 py-3 px-4 transition-all">
                    </div>

                    {{-- 2. NEW: Laboratory Filter --}}
                    <div>
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5 block ml-1">Laboratory</label>
                        <select name="lab_id" class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs font-semibold focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] py-3 px-3.5 transition-all cursor-pointer">
                            <option value="">All Laboratories</option>
                            @foreach($allLabs ?? $labs ?? \App\Models\Lab::orderBy('name')->get() as $l)
                            <option value="{{ $l->id }}" {{ request('lab_id') == $l->id ? 'selected' : '' }}>
                                {{ $l->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 3. Date Reported --}}
                    <div>
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5 block ml-1">Date Logged</label>
                        <input type="date" name="date" value="{{ request('date') }}"
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs font-semibold focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] [color-scheme:light] py-3 px-4 transition-all cursor-pointer">
                    </div>

                    {{-- 4. Status --}}
                    <div>
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5 block ml-1">Triage Status</label>
                        <select name="status" class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs font-semibold focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] py-3 px-3.5 transition-all cursor-pointer">
                            <option value="">All Reports</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Needs Attention</option>
                            <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                            <option value="discarded" {{ request('status') == 'discarded' ? 'selected' : '' }}>False Alarm / Discarded</option>
                        </select>
                    </div>

                    {{-- 5. Buttons --}}
                    <div class="flex items-center gap-2 w-full">
                        <button type="submit" class="flex-1 bg-slate-900 hover:bg-slate-800 text-white font-black uppercase text-[10px] tracking-wider py-3.5 rounded-2xl shadow-md transition-all active:scale-95 cursor-pointer flex items-center justify-center gap-1.5">
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <span>Filter</span>
                        </button>
                        <a href="{{ route('personnel.alerts') }}" class="px-4 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold uppercase text-[10px] tracking-wider rounded-2xl transition-all text-center shrink-0">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- DESKTOP TABLE VIEW                                                        --}}
        {{-- ========================================================================= --}}
        <div class="hidden md:block bg-white border border-slate-200/80 rounded-[2.5rem] overflow-hidden shadow-2xl shadow-slate-900/5">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[9px] font-black text-slate-400 uppercase tracking-[0.25em] bg-slate-50/70 border-b border-slate-100">
                        <th class="py-5 px-7">Station & Lab</th>
                        <th class="py-5 px-4">Student Reporter</th>
                        <th class="py-5 px-4">Issue & Class Context</th>
                        <th class="py-5 px-4">Student Remarks</th>
                        <th class="py-5 px-4">Timestamp</th>
                        <th class="py-5 px-7 text-right">Verification Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($alerts as $alert)
                    @php
                    // Context resolution: Resolves Laboratory, Subject, and Teacher gracefully
                    $resolvedLabName = $alert->computer->lab->name
                    ?? $alert->lab->name
                    ?? $alert->lab_name
                    ?? null;

                    $resolvedSubject = $alert->subject_code
                    ?? $alert->subject
                    ?? $alert->session->subject_code
                    ?? $alert->labSession->subject_code
                    ?? null;

                    $resolvedTeacher = $alert->teacher->name
                    ?? $alert->instructor->name
                    ?? $alert->teacher_name
                    ?? $alert->session->teacher->name
                    ?? $alert->labSession->teacher->name
                    ?? null;

                    // Fallback check against Lab Schedule if not stored directly
                    if (!$resolvedSubject && isset($alert->computer->lab_id)) {
                    $activeSched = \App\Models\Schedule::where('lab_id', $alert->computer->lab_id)
                    ->where('day', $alert->created_at->format('l'))
                    ->whereTime('start_time', '<=', $alert->created_at->format('H:i:s'))
                        ->whereTime('end_time', '>=', $alert->created_at->format('H:i:s'))
                        ->first();

                        if ($activeSched) {
                        $resolvedSubject = $activeSched->subject_code;
                        $resolvedTeacher = $activeSched->is_event
                        ? ($activeSched->speaker_name ?? 'Guest Speaker')
                        : ($activeSched->user->name ?? null);
                        }
                        }
                        @endphp

                        <tr class="group hover:bg-slate-50/50 transition-colors {{ in_array($alert->status, ['resolved', 'discarded']) ? 'opacity-65 bg-slate-50/30' : '' }}">

                            {{-- 1. PC & Lab --}}
                            <td class="py-6 px-7">
                                <div class="flex items-center gap-3">
                                    <div class="size-11 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center font-mono font-black text-[#D4AF37] text-xs shadow-inner shrink-0">
                                        {{ substr($alert->computer->pc_number ?? 'PC', -2) }}
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-black text-slate-900 text-sm tracking-tight">{{ $alert->computer->pc_number ?? 'Unknown PC' }}</span>
                                        @if($resolvedLabName)
                                        <span class="text-[9px] font-mono font-bold uppercase tracking-wider text-slate-400 mt-0.5">
                                            {{ $resolvedLabName }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- 2. Student Reporter --}}
                            <td class="py-6 px-4">
                                <div class="flex flex-col">
                                    <span class="font-black text-slate-900 text-xs tracking-tight">
                                        {{ $alert->reporter->name ?? 'Unknown Student' }}
                                    </span>
                                    <span class="text-[9px] text-slate-400 font-mono font-bold uppercase mt-0.5 tracking-tight">
                                        {{ $alert->reporter->student_number ?? ($alert->student_id ?? 'N/A') }}
                                    </span>
                                </div>
                            </td>

                            {{-- 3. Issue & Class Context (Uncrowded Micro-Pill) --}}
                            <td class="py-6 px-4">
                                <div class="flex flex-col items-start gap-1.5">
                                    <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-lg {{ $alert->issue_type == 'Hardware Issue' ? 'bg-amber-50 text-amber-700 border border-amber-200/60' : 'bg-sky-50 text-sky-700 border border-sky-200/60' }}">
                                        {{ $alert->issue_type }}
                                    </span>

                                    {{-- "During Subject • During Teacher" Badge --}}
                                    @if($resolvedSubject || $resolvedTeacher)
                                    <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200/80 text-[8px] font-medium text-slate-600 max-w-[200px]" title="During {{ $resolvedSubject ?? 'Class' }} &bull; {{ $resolvedTeacher ?? 'Instructor' }}">
                                        <span class="size-1 rounded-full bg-[#D4AF37] shrink-0"></span>
                                        <span class="font-black font-mono text-slate-800 truncate uppercase">{{ $resolvedSubject ?? 'Active Class' }}</span>
                                        @if($resolvedTeacher)
                                        <span class="text-slate-300 shrink-0">•</span>
                                        <span class="truncate text-slate-500">{{ $resolvedTeacher }}</span>
                                        @endif
                                    </div>
                                    @else
                                    <span class="text-[8px] text-slate-400 font-medium italic">
                                        Open Terminal Session
                                    </span>
                                    @endif
                                </div>
                            </td>

                            {{-- 4. Remarks --}}
                            <td class="py-6 px-4 max-w-xs">
                                <p class="text-slate-600 text-xs font-medium leading-relaxed italic border-l-2 border-[#D4AF37]/50 pl-3 line-clamp-2">
                                    "{{ $alert->remarks }}"
                                </p>
                            </td>

                            {{-- 5. Timestamp --}}
                            <td class="py-6 px-4 whitespace-nowrap">
                                <div class="text-[10px] font-black uppercase">
                                    <div class="text-slate-800 font-mono mb-0.5">{{ $alert->created_at->format('M d, Y') }}</div>
                                    <div class="text-slate-400 font-mono">{{ $alert->created_at->format('h:i A') }}</div>
                                </div>
                            </td>

                            {{-- 6. Actions --}}
                            <td class="py-6 px-7 text-right">
                                @if($alert->status == 'pending')
                                <div class="flex items-center justify-end gap-2">
                                    {{-- Discard / False Alarm --}}
                                    <form action="{{ route('personnel.alerts.discard', $alert->id) }}" method="POST" onsubmit="return confirm('Discard this alert as a false alarm / student trolling?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" title="Discard as false alarm" class="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 text-[9px] font-black uppercase px-3.5 py-2.5 rounded-xl transition-all cursor-pointer">
                                            Discard
                                        </button>
                                    </form>

                                    {{-- Resolve --}}
                                    <form action="{{ route('personnel.alerts.resolve', $alert->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="bg-[#D4AF37] hover:bg-[#B08D2A] text-slate-950 font-black text-[9px] uppercase px-4 py-2.5 rounded-xl transition-all shadow-md active:scale-95 cursor-pointer">
                                            Resolve
                                        </button>
                                    </form>
                                </div>
                                @else
                                <div class="flex items-center justify-end gap-2.5">
                                    @if($alert->status == 'discarded')
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-500/10 border border-rose-500/20">
                                        <span class="size-1.5 rounded-full bg-rose-500"></span>
                                        <span class="text-rose-600 text-[9px] font-black uppercase tracking-wider">False Alarm</span>
                                    </div>
                                    @else
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                                        <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span class="text-emerald-600 text-[9px] font-black uppercase tracking-wider">Resolved</span>
                                    </div>
                                    @endif

                                    <form action="{{ route('personnel.alerts.undo', $alert->id) }}" method="POST" onsubmit="return confirm('Undo this status and return to pending?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" title="Undo status" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 border border-slate-200/80 text-slate-500 hover:text-slate-800 text-[9px] font-black uppercase tracking-wider rounded-lg transition-all cursor-pointer">
                                            Undo
                                        </button>
                                    </form>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-24 text-center">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <div class="size-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-500 mb-2">
                                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="text-slate-800 text-sm font-black uppercase tracking-wider">All Workstations Clear</div>
                                    <p class="text-slate-400 font-medium text-xs">No terminal incidents found matching the specified parameters.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                </tbody>
            </table>

            {{-- Desktop Pagination --}}
            @if(method_exists($alerts, 'links') && $alerts->hasPages())
            <div class="p-6 bg-slate-50/50 border-t border-slate-100">
                {{ $alerts->links() }}
            </div>
            @endif
        </div>

        {{-- ========================================================================= --}}
        {{-- MOBILE CARD VIEW                                                          --}}
        {{-- ========================================================================= --}}
        <div class="block md:hidden space-y-4">
            @forelse($alerts as $alert)
            @php
            $resolvedLabName = $alert->computer->lab->name ?? $alert->lab->name ?? $alert->lab_name ?? null;
            $resolvedSubject = $alert->subject_code ?? $alert->subject ?? $alert->session->subject_code ?? null;
            $resolvedTeacher = $alert->teacher->name ?? $alert->instructor->name ?? $alert->teacher_name ?? null;
            @endphp

            <div class="bg-white border border-slate-200/80 rounded-3xl p-5 shadow-xl shadow-slate-900/5 {{ in_array($alert->status, ['resolved', 'discarded']) ? 'opacity-65 bg-slate-50/50' : '' }}">

                {{-- Card Top --}}
                <div class="flex items-start justify-between gap-3 pb-3.5 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="size-10 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center font-mono font-black text-[#D4AF37] text-xs shadow-inner shrink-0">
                            {{ substr($alert->computer->pc_number ?? 'PC', -2) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-black text-slate-900 text-sm tracking-tight">{{ $alert->computer->pc_number ?? 'Unknown PC' }}</span>
                                @if($resolvedLabName)
                                <span class="text-[8px] font-mono font-bold uppercase px-1.5 py-0.2 rounded bg-slate-100 text-slate-500 border border-slate-200">
                                    {{ $resolvedLabName }}
                                </span>
                                @endif
                            </div>
                            <span class="text-[9px] font-black uppercase tracking-wider {{ $alert->issue_type == 'Hardware Issue' ? 'text-[#D4AF37]' : 'text-sky-500' }}">
                                {{ $alert->issue_type }}
                            </span>
                        </div>
                    </div>
                    <div class="text-[9px] font-mono font-black uppercase text-right shrink-0">
                        <div class="text-slate-800">{{ $alert->created_at->format('M d, Y') }}</div>
                        <div class="text-slate-400">{{ $alert->created_at->format('h:i A') }}</div>
                    </div>
                </div>

                {{-- Session Context Badge (Mobile) --}}
                @if($resolvedSubject || $resolvedTeacher)
                <div class="mt-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-200 text-[9px] font-medium text-slate-600 w-full">
                    <span class="size-1.5 rounded-full bg-[#D4AF37] shrink-0"></span>
                    <span class="font-black font-mono text-slate-900 uppercase">{{ $resolvedSubject ?? 'Class' }}</span>
                    @if($resolvedTeacher)
                    <span class="text-slate-300">•</span>
                    <span class="truncate text-slate-500">{{ $resolvedTeacher }}</span>
                    @endif
                </div>
                @endif

                {{-- Reporter & Remarks --}}
                <div class="py-3.5 space-y-2.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Reported By</span>
                        <div class="text-right">
                            <span class="font-black text-slate-900 text-xs block">{{ $alert->reporter->name ?? 'Unknown Student' }}</span>
                            <span class="text-[9px] text-slate-400 font-mono font-bold uppercase block">{{ $alert->reporter->student_number ?? 'N/A' }}</span>
                        </div>
                    </div>

                    <div class="pt-1">
                        <p class="text-slate-600 text-xs font-medium leading-relaxed italic border-l-2 border-[#D4AF37] pl-3 bg-slate-50/60 py-2 rounded-r-xl">
                            "{{ $alert->remarks }}"
                        </p>
                    </div>
                </div>

                {{-- Action Row --}}
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                    @if($alert->status == 'pending')
                    <div class="grid grid-cols-2 gap-2 w-full">
                        <form action="{{ route('personnel.alerts.discard', $alert->id) }}" method="POST" onsubmit="return confirm('Discard this alert as a false alarm?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="w-full bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 text-[9px] font-black uppercase py-2.5 rounded-xl transition-all text-center">
                                Discard
                            </button>
                        </form>
                        <form action="{{ route('personnel.alerts.resolve', $alert->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="w-full bg-[#D4AF37] hover:bg-[#B08D2A] text-slate-950 font-black text-[9px] uppercase py-2.5 rounded-xl transition-all shadow-md text-center">
                                Resolve
                            </button>
                        </form>
                    </div>
                    @else
                    <div class="flex items-center justify-between w-full gap-2">
                        @if($alert->status == 'discarded')
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-rose-500/10 border border-rose-500/20">
                            <span class="size-1.5 rounded-full bg-rose-500"></span>
                            <span class="text-rose-600 text-[9px] font-black uppercase tracking-wider">False Alarm</span>
                        </div>
                        @else
                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                            <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-emerald-600 text-[9px] font-black uppercase tracking-wider">Resolved</span>
                        </div>
                        @endif

                        <form action="{{ route('personnel.alerts.undo', $alert->id) }}" method="POST" onsubmit="return confirm('Undo this status?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 border border-slate-200/80 text-slate-500 hover:text-slate-800 text-[9px] font-black uppercase tracking-wider rounded-xl transition-all">
                                Undo
                            </button>
                        </form>
                    </div>
                    @endif
                </div>

            </div>
            @empty
            <div class="bg-white border border-slate-200/80 rounded-3xl p-12 text-center shadow-xl shadow-slate-900/5">
                <div class="space-y-1">
                    <div class="text-slate-800 text-sm font-black uppercase tracking-wider">All Workstations Clear</div>
                    <p class="text-slate-400 font-medium text-xs">No alerts found matching your criteria</p>
                </div>
            </div>
            @endforelse

            {{-- Mobile Pagination --}}
            @if(method_exists($alerts, 'links') && $alerts->hasPages())
            <div class="pt-2">
                {{ $alerts->links() }}
            </div>
            @endif
        </div>

    </div>
</x-app-layout>