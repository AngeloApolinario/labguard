<x-app-layout>
    <div class="py-6 sm:py-12 px-4 sm:px-6 max-w-7xl mx-auto min-h-screen">

        {{-- Header --}}
        <x-slot name="header">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="font-black text-3xl sm:text-4xl text-slate-800 tracking-tighter uppercase">
                        Session <span class="text-[#D4AF37]">History</span>
                    </h2>
                    <div class="flex items-center space-x-2 mt-1">
                        <div class="size-2 bg-green-500 rounded-full animate-pulse"></div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">
                            Student Session Overview
                        </p>
                    </div>
                </div>
            </div>
        </x-slot>

        {{-- Cinematic Filter Bar --}}
        <div class="mb-8 sm:mb-10">
            <div class="bg-white/80 backdrop-blur-xl border border-slate-100 p-5 sm:p-8 rounded-3xl sm:rounded-[2.5rem] shadow-xl shadow-slate-500/5">
                <form action="{{ url()->current() }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 items-end">

                    <div class="w-full">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Student Name</label>
                        <input type="text" name="student_name" value="{{ request('student_name') }}" placeholder="Search Student..."
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3 px-4">
                    </div>

                    <div class="w-full">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Terminal ID</label>
                        <input type="text" name="pc_number" value="{{ request('pc_number') }}" placeholder="PC-01"
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3 px-4">
                    </div>

                    <div class="w-full">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2 block">Activity Date</label>
                        <input type="date" name="date" value="{{ request('date') }}"
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3 px-4">
                    </div>

                    <div class="flex gap-3 w-full">
                        <button type="submit" class="flex-1 bg-slate-900 text-white font-black uppercase text-[10px] px-6 py-3.5 rounded-2xl hover:bg-[#D4AF37] transition-all transform hover:scale-[1.02] active:scale-95 shadow-lg shadow-slate-900/10">
                            Apply
                        </button>
                        <a href="{{ url()->current() }}" class="bg-slate-100 text-slate-400 font-black uppercase text-[10px] px-5 py-3.5 rounded-2xl hover:bg-slate-200 transition-all flex items-center justify-center">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Data Container --}}
        <div class="bg-white/80 backdrop-blur-xl border border-slate-100/60 rounded-3xl sm:rounded-[3rem] overflow-hidden shadow-2xl shadow-slate-500/5">

            {{-- Desktop Table View (Hidden on mobile) --}}
            <div class="hidden md:block overflow-x-auto">
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
                            <td class="py-6 px-10">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-[#D4AF37]/10 flex items-center justify-center font-black text-[#D4AF37] text-[10px] border border-[#D4AF37]/20 flex-shrink-0">
                                        {{ strtoupper(substr($session->student_name ?? '??', 0, 2)) }}
                                    </div>
                                    <div>
                                        <span class="font-black text-slate-900 tracking-tighter uppercase block leading-none">{{ $session->student_name }}</span>
                                        <span class="text-[8px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1 block">{{ $session->student_id_number }}</span>
                                    </div>
                                </div>
                            </td>

                            <td class="py-6 px-4 text-center">
                                <span class="px-3 py-1 bg-slate-900 text-[#D4AF37] rounded-lg font-black text-[10px] border border-slate-800 uppercase shadow-sm inline-block">
                                    {{ $session->computer->pc_number ?? 'PC-??' }}
                                </span>
                            </td>

                            <td class="py-6 px-4">
                                <div class="text-[10px] font-black uppercase tracking-tighter text-slate-900">
                                    {{ $session->time_in ? $session->time_in->format('M d, Y') : 'N/A' }}<br>
                                    <span class="text-slate-400">{{ $session->time_in ? $session->time_in->format('h:i A') : '--:--' }}</span>
                                </div>
                            </td>

                            <td class="py-6 px-4">
                                <div class="text-[10px] font-black uppercase tracking-tighter text-slate-700">
                                    <span class="text-slate-400">EXITED AT</span><br>
                                    {{ $session->time_out ? $session->time_out->format('h:i A') : 'ACTIVE' }}
                                </div>
                            </td>

                            <td class="py-6 px-10 text-right">
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
                            <td colspan="5" class="py-24 text-center text-slate-300 font-black uppercase tracking-[0.5em] text-xs">
                                No session records found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Mobile Card View (Visible on small screens) --}}
            <div class="block md:hidden divide-y divide-slate-100">
                @forelse($sessions as $session)
                <div class="p-5 space-y-4 hover:bg-slate-50/50 transition-colors">
                    {{-- Header: Avatar, Name & Terminal --}}
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-[#D4AF37]/10 flex items-center justify-center font-black text-[#D4AF37] text-xs border border-[#D4AF37]/20 flex-shrink-0">
                                {{ strtoupper(substr($session->student_name ?? '??', 0, 2)) }}
                            </div>
                            <div>
                                <span class="font-black text-slate-900 tracking-tighter uppercase block leading-tight text-sm">{{ $session->student_name }}</span>
                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.15em] block">{{ $session->student_id_number }}</span>
                            </div>
                        </div>

                        <span class="px-3 py-1 bg-slate-900 text-[#D4AF37] rounded-lg font-black text-[10px] border border-slate-800 uppercase shadow-sm flex-shrink-0">
                            {{ $session->computer->pc_number ?? 'PC-??' }}
                        </span>
                    </div>

                    {{-- Body: Time Details Grid --}}
                    <div class="grid grid-cols-2 gap-3 bg-slate-50/60 p-3.5 rounded-2xl border border-slate-100">
                        <div>
                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block mb-0.5">Time In</span>
                            <p class="text-[10px] font-black text-slate-900 uppercase tracking-tighter">
                                {{ $session->time_in ? $session->time_in->format('M d, Y') : 'N/A' }}
                            </p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                                {{ $session->time_in ? $session->time_in->format('h:i A') : '--:--' }}
                            </p>
                        </div>

                        <div>
                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest block mb-0.5">Time Out</span>
                            <p class="text-[10px] font-black text-slate-900 uppercase tracking-tighter">
                                {{ $session->time_out ? $session->time_out->format('M d, Y') : 'ACTIVE' }}
                            </p>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                                {{ $session->time_out ? $session->time_out->format('h:i A') : 'Ongoing' }}
                            </p>
                        </div>
                    </div>

                    {{-- Footer: Duration Badge --}}
                    <div class="flex justify-between items-center pt-1">
                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Total Duration</span>
                        <div class="bg-slate-900 px-3 py-1 rounded-xl">
                            <span class="text-[10px] font-black text-[#D4AF37] uppercase tracking-tight">
                                {{ $session->time_out ? $session->time_in->diffForHumans($session->time_out, true) : 'Ongoing' }}
                            </span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="py-16 px-4 text-center text-slate-300 font-black uppercase tracking-[0.3em] text-xs">
                    No session records found
                </div>
                @endforelse
            </div>

            {{-- Pagination Footer --}}
            <div class="p-4 sm:p-6 bg-slate-50/50 border-t border-slate-100">
                {{ $sessions->links() }}
            </div>
        </div>
    </div>
</x-app-layout>