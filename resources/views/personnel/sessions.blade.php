<x-app-layout>
    {{-- Header --}}
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-black text-2xl sm:text-3xl md:text-4xl text-slate-800 tracking-tighter uppercase">
                    Session <span class="text-[#D4AF37]">History</span>
                </h2>
                <div class="flex items-center space-x-2 mt-1">
                    <div class="size-2 bg-green-500 rounded-full animate-pulse"></div>
                    <p class="text-[9px] sm:text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] sm:tracking-[0.3em]">
                        Student Session Overview
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 md:py-12 px-4 sm:px-6 max-w-7xl mx-auto min-h-screen">

        {{-- Cinematic Filter Bar --}}
        <div class="mb-6 sm:mb-10">
            <div class="bg-white border border-slate-100 p-4 sm:p-6 md:p-8 rounded-3xl sm:rounded-[2.5rem] shadow-xl shadow-slate-500/5">
                <form action="{{ route('dashboard.sessions.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 items-end">

                    <div class="w-full">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Student Name</label>
                        <input type="text" name="student_name" value="{{ request('student_name') }}" placeholder="Search Student..."
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3">
                    </div>

                    <div class="w-full">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Terminal ID</label>
                        <input type="text" name="pc_number" value="{{ request('pc_number') }}" placeholder="PC-01"
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3">
                    </div>

                    <div class="w-full">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Activity Date</label>
                        <input type="date" name="date" value="{{ request('date') }}"
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3">
                    </div>

                    <div class="flex gap-2 sm:gap-3 w-full">
                        <button type="submit" class="flex-1 bg-slate-900 text-white font-black uppercase text-[10px] px-4 sm:px-8 py-3.5 rounded-2xl hover:bg-[#D4AF37] transition-all transform hover:scale-105 active:scale-95 shadow-lg text-center">
                            Apply Filter
                        </button>
                        <a href="{{ route('dashboard.sessions.index') }}" class="flex-1 justify-center bg-slate-100 text-slate-400 font-black uppercase text-[10px] px-4 sm:px-6 py-3.5 rounded-2xl hover:bg-slate-200 transition-all flex items-center text-center">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Desktop Table View (Visible on MD and larger viewports) --}}
        <div class="hidden md:block bg-white border border-slate-100/60 rounded-[3rem] overflow-hidden shadow-2xl shadow-slate-500/5">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[9px] font-black text-slate-400 uppercase tracking-[0.4em] bg-slate-50/50">
                        <th class="py-6 px-10">Student Node</th>
                        <th class="py-6 px-4 text-center">Terminal</th>
                        <th class="py-6 px-4">Time In</th>
                        <th class="py-6 px-4">Time Out</th>
                        <th class="py-6 px-10 text-right">Total Duration</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($sessions as $session)
                    <tr class="group hover:bg-slate-50/50 transition-colors">
                        <td class="py-8 px-10">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-[#D4AF37]/10 flex items-center justify-center font-black text-[#D4AF37] text-[10px] border border-[#D4AF37]/20 shrink-0">
                                    {{ strtoupper(substr($session->student_name ?? '??', 0, 2)) }}
                                </div>
                                <div>
                                    <span class="font-black text-slate-900 tracking-tighter uppercase block leading-none">{{ $session->student_name }}</span>
                                    <span class="text-[8px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1 block">{{ $session->student_id_number }}</span>
                                </div>
                            </div>
                        </td>

                        <td class="py-8 px-4 text-center">
                            <span class="px-3 py-1 bg-slate-900 text-[#D4AF37] rounded-lg font-black text-[10px] border border-slate-800 uppercase shadow-sm whitespace-nowrap">
                                {{ $session->computer->pc_number ?? 'PC-??' }}
                            </span>
                        </td>

                        <td class="py-8 px-4 whitespace-nowrap">
                            <div class="text-[10px] font-black uppercase tracking-tighter text-slate-900">
                                {{ $session->time_in->format('M d, Y') }}<br>
                                <span class="text-slate-400">{{ $session->time_in->format('h:i A') }}</span>
                            </div>
                        </td>

                        <td class="py-8 px-4 whitespace-nowrap">
                            <div class="text-[10px] font-black uppercase tracking-tighter text-slate-700">
                                <span class="text-slate-400">EXITED AT</span><br>
                                {{ $session->time_out ? $session->time_out->format('h:i A') : 'ACTIVE' }}
                            </div>
                        </td>

                        <td class="py-8 px-10 text-right">
                            <div class="inline-block bg-slate-50 px-4 py-2 rounded-xl border border-slate-100">
                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1 text-center">Session Length</p>
                                <span class="text-[11px] font-black text-slate-900 uppercase tracking-tight block">
                                    {{ $session->time_out ? $session->time_in->diffForHumans($session->time_out, true) : 'Ongoing' }}
                                </span>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-32 text-center text-slate-300 font-black uppercase tracking-[0.5em] text-xs">
                            No session records found
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Gilded Pagination --}}
            <div class="p-6 bg-slate-50/50 border-t border-slate-100">
                {{ $sessions->links() }}
            </div>
        </div>

        {{-- Mobile & Tablet Card View (Visible below MD breakpoint) --}}
        <div class="block md:hidden space-y-4">
            @forelse($sessions as $session)
            <div class="bg-white border border-slate-100/80 rounded-3xl p-5 shadow-xl shadow-slate-500/5 space-y-4">

                {{-- Student Node Header --}}
                <div class="flex items-center justify-between gap-3 pb-3 border-b border-slate-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-[#D4AF37]/10 flex items-center justify-center font-black text-[#D4AF37] text-[10px] border border-[#D4AF37]/20 shrink-0">
                            {{ strtoupper(substr($session->student_name ?? '??', 0, 2)) }}
                        </div>
                        <div>
                            <span class="font-black text-slate-900 text-sm tracking-tight block uppercase leading-tight">{{ $session->student_name }}</span>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.15em] block">{{ $session->student_id_number }}</span>
                        </div>
                    </div>
                    <span class="px-3 py-1 bg-slate-900 text-[#D4AF37] rounded-lg font-black text-[10px] border border-slate-800 uppercase shadow-sm shrink-0">
                        {{ $session->computer->pc_number ?? 'PC-??' }}
                    </span>
                </div>

                {{-- Time Timeline Grid --}}
                <div class="grid grid-cols-2 gap-3 bg-slate-50/50 p-3 rounded-2xl border border-slate-100">
                    <div>
                        <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block mb-0.5">Time In</span>
                        <div class="text-[10px] font-black uppercase text-slate-900">
                            {{ $session->time_in->format('M d, Y') }}
                            <span class="text-slate-400 block">{{ $session->time_in->format('h:i A') }}</span>
                        </div>
                    </div>
                    <div>
                        <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block mb-0.5">Time Out</span>
                        <div class="text-[10px] font-black uppercase text-slate-700">
                            @if($session->time_out)
                            {{ $session->time_out->format('M d, Y') }}
                            <span class="text-slate-400 block">{{ $session->time_out->format('h:i A') }}</span>
                            @else
                            <span class="text-emerald-600 block">ACTIVE</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Session Duration Footer --}}
                <div class="flex items-center justify-between pt-1">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Total Duration</span>
                    <div class="bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-100">
                        <span class="text-[10px] font-black text-slate-900 uppercase tracking-tight">
                            {{ $session->time_out ? $session->time_in->diffForHumans($session->time_out, true) : 'Ongoing' }}
                        </span>
                    </div>
                </div>

            </div>
            @empty
            <div class="bg-white border border-slate-100/60 rounded-3xl p-12 text-center shadow-xl shadow-slate-500/5">
                <p class="text-slate-300 font-black uppercase tracking-[0.3em] text-xs">
                    No session records found
                </p>
            </div>
            @endforelse

            {{-- Gilded Pagination for Mobile --}}
            <div class="pt-2">
                {{ $sessions->links() }}
            </div>
        </div>

    </div>
</x-app-layout>