<x-app-layout>
    <div class="py-12 px-6 max-w-7xl mx-auto min-h-screen bg-[#FDFCF9]">

        {{-- Header --}}
        <div class="mb-12 flex justify-between items-end">
            <div class="relative">
                <div class="absolute -left-4 top-0 bottom-0 w-1 bg-[#D4AF37] shadow-[0_0_15px_rgba(212,175,55,0.4)]"></div>
                <h2 class="font-black text-5xl text-slate-900 uppercase tracking-tighter leading-none">
                    SESSION <span class="text-[#D4AF37]">HISTORY</span>
                </h2>
                <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.5em] mt-2 flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-slate-300"></span>
                    ARCHIVED LABORATORY LOGS
                </p>
            </div>
        </div>

        {{-- Cinematic Filter Bar --}}
        <div class="mb-10">
            <div class="bg-white border border-slate-100 p-8 rounded-[2.5rem] shadow-xl shadow-slate-500/5">
                {{-- url()->current() ensures it stays on the right page --}}
                <form action="{{ url()->current() }}" method="GET" class="flex flex-wrap items-end gap-6">

                    <div class="flex-1 min-w-[200px]">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-3 block">Student Name</label>
                        <input type="text" name="student_name" value="{{ request('student_name') }}" placeholder="Search Student..."
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3">
                    </div>

                    <div class="flex-1 min-w-[150px]">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-3 block">Terminal ID</label>
                        <input type="text" name="pc_number" value="{{ request('pc_number') }}" placeholder="PC-01"
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3">
                    </div>

                    <div class="flex-1 min-w-[150px]">
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-3 block">Activity Date</label>
                        <input type="date" name="date" value="{{ request('date') }}"
                            class="w-full bg-slate-50 border-slate-200 text-slate-900 rounded-2xl text-xs focus:ring-[#D4AF37] focus:border-[#D4AF37] py-3">
                    </div>

                    <div class="flex gap-3">
                        <button type="submit" class="bg-slate-900 text-white font-black uppercase text-[10px] px-8 py-3.5 rounded-2xl hover:bg-[#D4AF37] transition-all transform hover:scale-105 active:scale-95 shadow-lg">
                            Apply Filter
                        </button>
                        <a href="{{ url()->current() }}" class="bg-slate-100 text-slate-400 font-black uppercase text-[10px] px-6 py-3.5 rounded-2xl hover:bg-slate-200 transition-all flex items-center">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Data HUD --}}
        <div class="bg-white border border-slate-100/60 rounded-[3rem] overflow-hidden shadow-2xl shadow-slate-500/5">
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
                                <div class="w-10 h-10 rounded-full bg-[#D4AF37]/10 flex items-center justify-center font-black text-[#D4AF37] text-[10px] border border-[#D4AF37]/20">
                                    {{ strtoupper(substr($session->student_name ?? '??', 0, 2)) }}
                                </div>
                                <div>
                                    <span class="font-black text-slate-900 tracking-tighter uppercase block leading-none">{{ $session->student_name }}</span>
                                    <span class="text-[8px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1 block">{{ $session->student_id_number }}</span>
                                </div>
                            </div>
                        </td>

                        <td class="py-8 px-4 text-center">
                            <span class="px-3 py-1 bg-slate-900 text-[#D4AF37] rounded-lg font-black text-[10px] border border-slate-800 uppercase shadow-sm">
                                {{ $session->computer->pc_number ?? 'PC-??' }}
                            </span>
                        </td>

                        <td class="py-8 px-4">
                            <div class="text-[10px] font-black uppercase tracking-tighter text-slate-900">
                                {{ $session->time_in->format('M d, Y') }}<br>
                                <span class="text-slate-400">{{ $session->time_in->format('h:i A') }}</span>
                            </div>
                        </td>

                        <td class="py-8 px-4">
                            <div class="text-[10px] font-black uppercase tracking-tighter text-slate-700">
                                <span class="text-slate-400">EXITED AT</span><br>
                                {{ $session->time_out->format('h:i A') }}
                            </div>
                        </td>

                        <td class="py-8 px-10 text-right">
                            <div class="inline-block bg-slate-50 px-4 py-2 rounded-xl border border-slate-100">
                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1 text-center">Session Length</p>
                                <span class="text-[11px] font-black text-slate-900 uppercase tracking-tight block">
                                    {{ $session->time_in->diffForHumans($session->time_out, true) }}
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

            <div class="p-6 bg-slate-50/50 border-t border-slate-100">
                {{ $sessions->links() }}
            </div>
        </div>
    </div>
</x-app-layout>