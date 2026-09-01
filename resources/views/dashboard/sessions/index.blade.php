<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-black text-2xl sm:text-4xl text-slate-800 tracking-tighter uppercase">
                    Session <span class="text-[#D4AF37]">History</span>
                </h2>
                <div class="flex items-center space-x-2 mt-1">
                    <div class="size-2 bg-emerald-500 rounded-full animate-pulse"></div>
                    <p class="text-[9px] sm:text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">
                        Student Session Overview
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto min-h-screen">

        {{-- Filter Bar --}}
        <div class="mb-6 sm:mb-10">
            <div class="bg-white/80 backdrop-blur-md border border-slate-200/60 p-5 sm:p-8 rounded-3xl sm:rounded-[2.5rem] shadow-xl shadow-slate-900/5">
                <form action="{{ route('dashboard.sessions.index') }}" method="GET" class="flex flex-wrap items-end gap-4 sm:gap-6">

                    <div class="flex-1 min-w-[200px]">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Student Name</label>
                        <input type="text" name="student_name" value="{{ request('student_name') }}" placeholder="Search Student..."
                            class="w-full bg-slate-50/50 border-slate-200/80 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3 px-4 placeholder:text-slate-400">
                    </div>

                    <div class="flex-1 min-w-[140px]">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Terminal ID</label>
                        <input type="text" name="pc_number" value="{{ request('pc_number') }}" placeholder="PC-01"
                            class="w-full bg-slate-50/50 border-slate-200/80 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3 px-4 placeholder:text-slate-400">
                    </div>

                    <div class="flex-1 min-w-[160px]">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Activity Date</label>
                        <input type="date" name="date" value="{{ request('date') }}"
                            class="w-full bg-slate-50/50 border-slate-200/80 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3 px-4">
                    </div>

                    <div class="flex items-center gap-3 w-full lg:w-auto mt-2 lg:mt-0">
                        <button type="submit" class="flex-1 lg:flex-initial bg-slate-900 text-white font-black uppercase text-[10px] px-8 py-3.5 rounded-2xl hover:bg-[#D4AF37] hover:text-slate-950 transition-all active:scale-95 shadow-lg shadow-slate-900/10">
                            Apply Filter
                        </button>
                        <a href="{{ route('dashboard.sessions.index') }}" class="bg-slate-100 text-slate-400 font-black uppercase text-[10px] px-6 py-3.5 rounded-2xl hover:bg-slate-200 hover:text-slate-600 transition-all flex items-center justify-center">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Mobile Cards (< md) & Desktop Grid Table (>= md) Container --}}
        <div class="bg-white/80 backdrop-blur-xl border border-slate-200/60 rounded-3xl sm:rounded-[3rem] overflow-hidden shadow-2xl shadow-slate-900/5 relative">

            {{-- 1. MOBILE CARD FEED (< md) --}}
            <div class="block md:hidden divide-y divide-slate-100 p-4">
                @forelse($sessions as $session)
                <div class="py-4 first:pt-2 last:pb-2">
                    <div class="bg-slate-50/70 border border-slate-200/60 rounded-2xl p-4 shadow-sm space-y-4 hover:border-[#D4AF37]/40 transition-colors">

                        {{-- Top Header: Student Node & Terminal ID --}}
                        <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-200/50">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#D4AF37]/20 to-[#D4AF37]/5 flex items-center justify-center font-black text-[#D4AF37] text-[10px] border border-[#D4AF37]/30 shrink-0 shadow-sm">
                                    {{ strtoupper(substr($session->student_name ?? '??', 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <span class="font-black text-slate-900 tracking-tight uppercase block leading-none truncate text-xs">
                                        {{ $session->student_name }}
                                    </span>
                                    <span class="text-[8px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1 block">
                                        {{ $session->student_id_number }}
                                    </span>
                                </div>
                            </div>

                            {{-- Terminal ID Badge --}}
                            <span class="inline-flex items-center px-2.5 py-1 bg-slate-900 text-[#D4AF37] rounded-lg font-black text-[9px] border border-slate-800 uppercase shadow-sm tracking-widest shrink-0">
                                {{ $session->computer->pc_number ?? 'PC-??' }}
                            </span>
                        </div>

                        {{-- Middle Row: Time In & Status/Time Out --}}
                        <div class="grid grid-cols-2 gap-2 text-left">
                            {{-- Time In --}}
                            <div>
                                <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block mb-0.5">Time In</span>
                                <p class="text-[10px] font-black uppercase text-slate-900 leading-tight">
                                    {{ optional($session->time_in)->format('M d, Y') ?? 'N/A' }}
                                    <span class="text-slate-400 font-semibold block mt-0.5 text-[9px]">
                                        {{ optional($session->time_in)->format('h:i A') ?? '--:--' }}
                                    </span>
                                </p>
                            </div>

                            {{-- Time Out / Status --}}
                            <div>
                                <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block mb-0.5">Status / Exit</span>
                                @if($session->time_out)
                                <p class="text-[10px] font-black uppercase text-slate-700 leading-tight">
                                    {{ $session->time_out->format('h:i A') }}
                                    <span class="text-slate-400 text-[8px] tracking-widest block font-bold mt-0.5">EXITED</span>
                                </p>
                                @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-500/10 text-emerald-600 rounded-full font-black text-[8px] uppercase border border-emerald-500/20 backdrop-blur-sm mt-0.5">
                                    <span class="relative flex h-1.5 w-1.5">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                    </span>
                                    Active
                                </span>
                                @endif
                            </div>
                        </div>

                        {{-- Bottom Row: Duration Badge --}}
                        <div class="pt-2 border-t border-slate-200/40 flex items-center justify-between">
                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Total Duration</span>
                            <div class="bg-white px-3 py-1 rounded-xl border border-slate-200/60 shadow-xs">
                                <span class="text-[10px] font-black uppercase tracking-tight block">
                                    @if($session->time_out)
                                    <span class="text-slate-900">
                                        {{ $session->time_in->diffForHumans($session->time_out, true) }}
                                    </span>
                                    @else
                                    <span class="text-emerald-600 animate-pulse">
                                        In Progress
                                    </span>
                                    @endif
                                </span>
                            </div>
                        </div>

                    </div>
                </div>
                @empty
                <div class="py-16 text-center">
                    <div class="flex flex-col items-center justify-center gap-2">
                        <div class="size-10 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-300 font-black text-base">
                            Ø
                        </div>
                        <p class="text-slate-400 font-black uppercase tracking-[0.2em] text-[10px]">
                            No Session Records Found
                        </p>
                    </div>
                </div>
                @endforelse
            </div>

            {{-- 2. DESKTOP GRID TABLE (>= md) --}}
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[9px] font-black text-slate-400 uppercase tracking-[0.3em] bg-slate-50/80 border-b border-slate-100 backdrop-blur-md">
                            <th class="py-6 px-6 sm:px-10">Student Node</th>
                            <th class="py-6 px-4 text-center">Terminal</th>
                            <th class="py-6 px-4">Time In</th>
                            <th class="py-6 px-4">Time Out</th>
                            <th class="py-6 px-6 sm:px-10 text-right">Total Duration</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100/80">
                        @forelse($sessions as $session)
                        <tr class="group hover:bg-slate-50/60 transition-all duration-200">
                            {{-- Student Info --}}
                            <td class="py-5 sm:py-6 px-6 sm:px-10">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-[#D4AF37]/20 to-[#D4AF37]/5 flex items-center justify-center font-black text-[#D4AF37] text-[10px] border border-[#D4AF37]/30 shrink-0 shadow-sm group-hover:scale-105 transition-transform">
                                        {{ strtoupper(substr($session->student_name ?? '??', 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-black text-slate-900 tracking-tight uppercase block leading-none truncate text-xs sm:text-sm group-hover:text-[#D4AF37] transition-colors">
                                            {{ $session->student_name }}
                                        </span>
                                        <span class="text-[8px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1.5 block">
                                            {{ $session->student_id_number }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            {{-- Terminal ID --}}
                            <td class="py-5 sm:py-6 px-4 text-center">
                                <span class="inline-flex items-center px-3 py-1.5 bg-slate-900 text-[#D4AF37] rounded-xl font-black text-[10px] border border-slate-800 uppercase shadow-sm tracking-widest group-hover:border-[#D4AF37]/50 transition-colors whitespace-nowrap">
                                    {{ $session->computer->pc_number ?? 'PC-??' }}
                                </span>
                            </td>

                            {{-- Time In --}}
                            <td class="py-5 sm:py-6 px-4">
                                <div class="text-[10px] font-black uppercase tracking-tight text-slate-900 leading-tight">
                                    {{ optional($session->time_in)->format('M d, Y') ?? 'N/A' }}
                                    <span class="text-slate-400 font-semibold block mt-0.5 text-[9px]">
                                        {{ optional($session->time_in)->format('h:i A') ?? '--:--' }}
                                    </span>
                                </div>
                            </td>

                            {{-- Time Out / Active Status --}}
                            <td class="py-5 sm:py-6 px-4">
                                @if($session->time_out)
                                <div class="text-[10px] font-black uppercase tracking-tight text-slate-700 leading-tight">
                                    <span class="text-slate-400 text-[8px] tracking-widest block font-bold mb-0.5">EXITED AT</span>
                                    {{ $session->time_out->format('h:i A') }}
                                </div>
                                @else
                                <span class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-500/10 text-emerald-600 rounded-full font-black text-[9px] uppercase border border-emerald-500/20 backdrop-blur-sm">
                                    <span class="relative flex h-2 w-2">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    Active
                                </span>
                                @endif
                            </td>

                            {{-- Session Duration --}}
                            <td class="py-5 sm:py-6 px-6 sm:px-10 text-right">
                                <div class="inline-block bg-slate-50/80 px-4 py-2 rounded-2xl border border-slate-200/60 shadow-sm group-hover:bg-white transition-colors">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-0.5 text-center">Session Length</p>
                                    <span class="text-[11px] font-black uppercase tracking-tight block">
                                        @if($session->time_out)
                                        <span class="text-slate-900">
                                            {{ $session->time_in->diffForHumans($session->time_out, true) }}
                                        </span>
                                        @else
                                        <span class="text-emerald-600 font-black animate-pulse">
                                            In Progress
                                        </span>
                                        @endif
                                    </span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-24 text-center">
                                <div class="flex flex-col items-center justify-center gap-3">
                                    <div class="size-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-300 font-black text-lg">
                                        Ø
                                    </div>
                                    <p class="text-slate-400 font-black uppercase tracking-[0.3em] text-xs">
                                        No Session Records Found
                                    </p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Bar --}}
            <div class="p-6 bg-slate-50/50 border-t border-slate-100/80 backdrop-blur-md">
                {{ $sessions->withQueryString()->links() }}
            </div>
        </div>
    </div>
</x-app-layout>