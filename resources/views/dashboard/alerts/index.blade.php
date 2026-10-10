<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="size-2 bg-emerald-500 rounded-full animate-pulse"></span>
                    <span class="text-[9px] font-black uppercase tracking-[0.3em] text-[#D4AF37]">Admin Incident & Alerts Command</span>
                </div>
                <h2 class="font-black text-2xl sm:text-4xl text-slate-800 tracking-tight uppercase">
                    Computer <span class="text-[#D4AF37]">Alerts</span>
                </h2>
            </div>

            {{-- Stat Cards --}}
            <div class="flex gap-2 sm:gap-3 w-full sm:w-auto">
                <div class="flex-1 sm:flex-none bg-white px-5 sm:px-6 py-3.5 sm:py-4 rounded-2xl sm:rounded-3xl border border-slate-100 shadow-sm backdrop-blur-xl">
                    <p class="text-[8px] font-black text-slate-400 uppercase mb-1 tracking-widest">Total Reports</p>
                    <p class="text-xl sm:text-2xl font-black text-slate-800 font-mono">{{ $totalReports ?? ($alerts->total() ?? $alerts->count()) }}</p>
                </div>
                <div class="flex-1 sm:flex-none bg-white px-5 sm:px-6 py-3.5 sm:py-4 rounded-2xl sm:rounded-3xl border border-amber-500/20 shadow-lg shadow-amber-500/5 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-[#D4AF37] to-amber-500"></div>
                    <p class="text-[8px] font-black text-[#B08D2A] uppercase mb-1 tracking-widest">Needs Attention</p>
                    <p class="text-xl sm:text-2xl font-black text-[#D4AF37] font-mono">{{ $unresolvedCount ?? $alerts->where('status', 'pending')->count() }}</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 md:py-10 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto min-h-screen">

        {{-- ========================================================================= --}}
        {{-- FILTER BAR (LIVE DYNAMIC SUBMISSION)                                      --}}
        {{-- ========================================================================= --}}
        <div class="mb-6 sm:mb-8" x-data="{
            pcNumber: '{{ request('pc_number') }}',
            labId: '{{ request('lab_id') }}',
            date: '{{ request('date') }}',
            status: '{{ request('status') }}',
            submitForm() {
                const url = new URL(window.location.href);
                
                if (this.pcNumber) url.searchParams.set('pc_number', this.pcNumber);
                else url.searchParams.delete('pc_number');

                if (this.labId) url.searchParams.set('lab_id', this.labId);
                else url.searchParams.delete('lab_id');
                
                if (this.date) url.searchParams.set('date', this.date);
                else url.searchParams.delete('date');

                if (this.status) url.searchParams.set('status', this.status);
                else url.searchParams.delete('status');

                window.location.href = url.toString();
            }
        }">
            <div class="bg-white border border-slate-200/80 p-5 sm:p-7 rounded-3xl sm:rounded-[2.2rem] shadow-xl shadow-slate-900/5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">

                    {{-- 1. PC Number (Debounced live search) --}}
                    <div>
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5 block ml-1">Terminal ID</label>
                        <input type="text" x-model="pcNumber" @input.debounce.500ms="submitForm()" placeholder="Search PC (e.g. PC-01)..."
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs font-semibold focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] placeholder:text-slate-400 py-3 px-4 transition-all">
                    </div>

                    {{-- 2. Laboratory Filter (Live on change) --}}
                    <div>
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5 block ml-1">Laboratory</label>
                        <select x-model="labId" @change="submitForm()" class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs font-semibold focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] py-3 px-3.5 transition-all cursor-pointer">
                            <option value="">All Laboratories</option>
                            @foreach($allLabs ?? $labs ?? \App\Models\Lab::orderBy('name')->get() as $l)
                            <option value="{{ $l->id }}">{{ $l->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- 3. Date Reported (Live on change) --}}
                    <div>
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5 block ml-1">Date Logged</label>
                        <input type="date" x-model="date" @change="submitForm()"
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs font-semibold focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] [color-scheme:light] py-3 px-4 transition-all cursor-pointer">
                    </div>

                    {{-- 4. Status (Live on change) --}}
                    <div>
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-1.5 block ml-1">Triage Status</label>
                        <select x-model="status" @change="submitForm()" class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs font-semibold focus:ring-2 focus:ring-[#D4AF37]/30 focus:border-[#D4AF37] py-3 px-3.5 transition-all cursor-pointer">
                            <option value="">All Reports</option>
                            <option value="pending">Needs Attention</option>
                            <option value="resolved">Resolved</option>
                            <option value="discarded">Discarded (False Alarm)</option>
                        </select>
                    </div>

                    {{-- 5. Reset Action --}}
                    <div class="flex items-center gap-2 w-full">
                        <a href="{{ route('dashboard.alerts.index') }}" class="w-full py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold uppercase text-[10px] tracking-wider rounded-2xl transition-all text-center flex items-center justify-center gap-1.5">
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Reset Filters</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- DESKTOP TABLE LAYOUT (>= md screens)                                      --}}
        {{-- ========================================================================= --}}
        <div class="hidden md:block bg-white border border-slate-200/80 rounded-[2.5rem] overflow-hidden shadow-2xl shadow-slate-900/5">
            <div class="overflow-x-auto">
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
                                            {{ $alert->reporter?->name ?? $alert->reportedBy?->name ?? 'Unknown Student' }}
                                        </span>
                                        <span class="text-[9px] text-slate-400 font-mono font-bold uppercase mt-0.5 tracking-tight">
                                            {{ $alert->reporter?->student_number ?? $alert->reportedBy?->student_number ?? ($alert->student_id ?? 'N/A') }}
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
                                        {{-- Discard Action --}}
                                        <form action="{{ route('dashboard.alerts.discard', $alert->id) }}" method="POST" onsubmit="return confirm('Discard this alert as a false alarm / student trolling?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" title="Discard as false alarm" class="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 text-[9px] font-black uppercase px-3.5 py-2.5 rounded-xl transition-all cursor-pointer">
                                                Discard
                                            </button>
                                        </form>

                                        {{-- Resolve Action --}}
                                        <form action="{{ route('dashboard.alerts.resolve', $alert->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="bg-[#D4AF37] hover:bg-[#B08D2A] text-slate-950 font-black text-[9px] uppercase px-4 py-2.5 rounded-xl transition-all shadow-md active:scale-95 cursor-pointer">
                                                Resolve
                                            </button>
                                        </form>
                                    </div>
                                    @elseif($alert->status == 'discarded')
                                    <div class="flex items-center justify-end gap-2.5">
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-500/10 border border-rose-500/20">
                                            <span class="size-1.5 rounded-full bg-rose-500"></span>
                                            <span class="text-rose-600 text-[9px] font-black uppercase tracking-wider">Discarded</span>
                                        </div>

                                        <form action="{{ route('dashboard.alerts.undo', $alert->id) }}" method="POST" onsubmit="return confirm('Restore this discarded alert back to pending?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" title="Undo discard" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 border border-slate-200/80 text-slate-500 hover:text-slate-800 text-[9px] font-black uppercase tracking-wider rounded-lg transition-all cursor-pointer flex items-center gap-1">
                                                <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                                <span>Undo</span>
                                            </button>
                                        </form>
                                    </div>
                                    @else
                                    <div class="flex items-center justify-end gap-2.5">
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                                            <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span class="text-emerald-600 text-[9px] font-black uppercase tracking-wider">Resolved</span>
                                        </div>

                                        <form action="{{ route('dashboard.alerts.undo', $alert->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to undo this resolution?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" title="Undo resolution" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 border border-slate-200/80 text-slate-500 hover:text-slate-800 text-[9px] font-black uppercase tracking-wider rounded-lg transition-all cursor-pointer flex items-center gap-1">
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
                                <td colspan="6" class="py-24 text-center">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <div class="size-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-500 mb-2">
                                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="text-slate-800 text-sm font-black uppercase tracking-wider">All Workstations Clear</div>
                                        <p class="text-slate-400 font-medium text-xs">No alerts found matching your specified criteria.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Desktop Pagination --}}
            @if($alerts->hasPages())
            <div class="p-6 bg-slate-50/50 border-t border-slate-100/80">
                {{ $alerts->links() }}
            </div>
            @endif
        </div>

        {{-- ========================================================================= --}}
        {{-- MOBILE CARDS LAYOUT (< md screens)                                        --}}
        {{-- ========================================================================= --}}
        <div class="block md:hidden space-y-4">
            @forelse($alerts as $alert)
            @php
            $resolvedLabName = $alert->computer->lab->name ?? $alert->lab->name ?? $alert->lab_name ?? null;
            $resolvedSubject = $alert->subject_code ?? $alert->subject ?? $alert->session->subject_code ?? null;
            $resolvedTeacher = $alert->teacher->name ?? $alert->instructor->name ?? $alert->teacher_name ?? null;
            @endphp

            <div class="bg-white border border-slate-200/80 p-5 rounded-3xl shadow-xl shadow-slate-900/5 space-y-4 {{ in_array($alert->status, ['resolved', 'discarded']) ? 'opacity-65 bg-slate-50/50' : '' }}">

                {{-- Card Top --}}
                <div class="flex items-start justify-between border-b border-slate-100 pb-3.5">
                    <div class="flex items-center gap-3">
                        <div class="size-10 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center font-mono font-black text-[#D4AF37] text-xs shadow-inner shrink-0">
                            {{ substr($alert->computer->pc_number ?? 'PC', -2) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-black text-slate-900 text-sm tracking-tight block">{{ $alert->computer->pc_number ?? 'Unknown PC' }}</span>
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
                    <div class="text-right text-[9px] font-mono font-black uppercase shrink-0">
                        <div class="text-slate-800">{{ $alert->created_at->format('M d, Y') }}</div>
                        <div class="text-slate-400">{{ $alert->created_at->format('h:i A') }}</div>
                    </div>
                </div>

                {{-- Session Context Badge (Mobile) --}}
                @if($resolvedSubject || $resolvedTeacher)
                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-50 border border-slate-200 text-[9px] font-medium text-slate-600 w-full">
                    <span class="size-1.5 rounded-full bg-[#D4AF37] shrink-0"></span>
                    <span class="font-black font-mono text-slate-900 uppercase">{{ $resolvedSubject ?? 'Class' }}</span>
                    @if($resolvedTeacher)
                    <span class="text-slate-300">•</span>
                    <span class="truncate text-slate-500">{{ $resolvedTeacher }}</span>
                    @endif
                </div>
                @endif

                {{-- Reporter Info --}}
                <div class="flex justify-between items-start text-xs pt-1">
                    <div>
                        <span class="text-[9px] text-slate-400 font-bold uppercase block tracking-wider">Reported By</span>
                        <span class="font-black text-slate-900 tracking-tight block">{{ $alert->reporter?->name ?? $alert->reportedBy?->name ?? 'Unknown Student' }}</span>
                        <span class="text-[9px] text-slate-400 font-mono font-bold uppercase tracking-tight block">{{ $alert->reporter?->student_number ?? $alert->reportedBy?->student_number ?? ($alert->student_id ?? 'N/A') }}</span>
                    </div>
                </div>

                {{-- Remarks --}}
                @if($alert->remarks)
                <p class="text-slate-600 text-xs font-medium leading-relaxed italic border-l-2 border-[#D4AF37] pl-3 py-1 bg-slate-50/60 rounded-r-xl">
                    "{{ $alert->remarks }}"
                </p>
                @endif

                {{-- Action Bar --}}
                <div class="pt-2 border-t border-slate-100">
                    @if($alert->status == 'pending')
                    <div class="grid grid-cols-2 gap-2">
                        {{-- Discard Action --}}
                        <form action="{{ route('dashboard.alerts.discard', $alert->id) }}" method="POST" onsubmit="return confirm('Discard this alert as a false alarm / student trolling?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="w-full bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 text-[10px] font-black uppercase py-2.5 rounded-xl transition-all cursor-pointer">
                                Discard
                            </button>
                        </form>

                        {{-- Resolve Action --}}
                        <form action="{{ route('dashboard.alerts.resolve', $alert->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="w-full bg-[#D4AF37] hover:bg-[#B08D2A] text-slate-950 font-black text-[10px] uppercase py-2.5 rounded-xl transition-all shadow-md active:scale-95 cursor-pointer">
                                Resolve
                            </button>
                        </form>
                    </div>
                    @elseif($alert->status == 'discarded')
                    <div class="flex items-center justify-between">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-500/10 border border-rose-500/20">
                            <span class="size-1.5 rounded-full bg-rose-500"></span>
                            <span class="text-rose-600 text-[9px] font-black uppercase tracking-wider">Discarded</span>
                        </div>

                        {{-- Undo Discard --}}
                        <form action="{{ route('dashboard.alerts.undo', $alert->id) }}" method="POST" onsubmit="return confirm('Restore this discarded alert back to pending?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 border border-slate-200/80 text-slate-500 text-[9px] font-black uppercase tracking-wider rounded-xl transition-all flex items-center gap-1 cursor-pointer">
                                <svg class="size-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                </svg>
                                <span>Undo</span>
                            </button>
                        </form>
                    </div>
                    @else
                    <div class="flex items-center justify-between">
                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20">
                            <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-emerald-600 text-[9px] font-black uppercase tracking-wider">Resolved</span>
                        </div>

                        {{-- Undo Resolution --}}
                        <form action="{{ route('dashboard.alerts.undo', $alert->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to undo this resolution?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 border border-slate-200/80 text-slate-500 text-[9px] font-black uppercase tracking-wider rounded-xl transition-all flex items-center gap-1 cursor-pointer">
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
            <div class="bg-white border border-slate-200/80 rounded-3xl p-12 text-center shadow-xl shadow-slate-900/5">
                <div class="space-y-1">
                    <div class="text-slate-800 text-sm font-black uppercase tracking-wider">All Workstations Clear</div>
                    <p class="text-slate-400 font-medium text-xs">No alerts found</p>
                </div>
            </div>
            @endforelse

            {{-- Mobile Pagination --}}
            @if($alerts->hasPages())
            <div class="pt-2">
                {{ $alerts->links() }}
            </div>
            @endif
        </div>

    </div>
</x-app-layout>