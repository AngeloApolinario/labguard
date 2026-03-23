<x-app-layout>
    <div class="py-12 px-6 max-w-7xl mx-auto min-h-screen bg-[#F8FAFC]">
        {{-- Header Section --}}
        <div class="flex items-center justify-between mb-10">
            <div>
                <h2 class="font-black text-4xl text-slate-800 uppercase tracking-tighter">
                    {{ $lab->lab_name }} <span class="text-[#D4AF37]">Occupancy</span>
                </h2>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mt-1">
                    Scheduling & Laboratory Allocation
                </p>
            </div>
            <a href="{{ route('dashboard.labs') }}" class="px-6 py-2 bg-slate-800 text-white text-[10px] font-black uppercase rounded-xl hover:bg-slate-700 transition-all">
                Back to Command
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            {{-- Left Column: Entry Form --}}
            <div class="bg-white p-8 rounded-[3rem] border border-slate-100 shadow-xl self-start">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Assign Instructor</p>

                <form action="{{ route('dashboard.labs.schedule.store', $lab->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="text-[8px] font-black text-slate-400 uppercase ml-2 mb-1 block">Authorized Teacher</label>
                        <select name="user_id" class="w-full rounded-2xl border-slate-100 bg-slate-50 text-sm py-3 focus:ring-[#D4AF37] focus:border-[#D4AF37]">
                            <option value="" disabled selected>Select Instructor...</option>
                            @foreach($teachers as $teacher)
                            {{-- SKIP SYSTEM ADMINS: Assuming role is 'admin' or ID is 1 --}}
                            @if($teacher->role !== 'admin' && $teacher->id !== 1)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[8px] font-black text-slate-400 uppercase ml-2 mb-1 block">Day</label>
                            <select name="day" class="w-full rounded-2xl border-slate-100 bg-slate-50 text-sm py-3">
                                @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day)
                                <option value="{{ $day }}">{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[8px] font-black text-slate-400 uppercase ml-2 mb-1 block">Subject Code</label>
                            <input type="text" name="subject_code" placeholder="E.g. IT-402" class="w-full rounded-2xl border-slate-100 bg-slate-50 text-sm py-3 uppercase font-bold">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[8px] font-black text-slate-400 uppercase ml-2 mb-1 block">Start Time</label>
                            <input type="time" name="start_time" class="w-full rounded-2xl border-slate-100 bg-slate-50 text-sm py-3">
                        </div>
                        <div>
                            <label class="text-[8px] font-black text-slate-400 uppercase ml-2 mb-1 block">End Time</label>
                            <input type="time" name="end_time" class="w-full rounded-2xl border-slate-100 bg-slate-50 text-sm py-3">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-4 mt-4 bg-[#D4AF37] text-white text-[10px] font-black uppercase rounded-2xl shadow-lg shadow-[#D4AF37]/20 hover:scale-[1.02] transition-transform active:scale-95">
                        Establish Slot
                    </button>
                </form>
            </div>

            {{-- Right Column: Schedule Table --}}
            <div class="lg:col-span-2 bg-slate-900 rounded-[3rem] p-10 shadow-2xl overflow-hidden">
                <div class="mb-8 flex items-center justify-between">
                    <h4 class="text-white font-black uppercase tracking-tighter text-xl">Active <span class="text-[#D4AF37]">Roster</span></h4>
                    <span class="px-4 py-1 bg-slate-800 text-slate-400 text-[8px] font-black uppercase rounded-full tracking-widest border border-slate-700">
                        {{ count($schedules) }} Slots Assigned
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] border-b border-slate-800">
                                <th class="pb-6">Instructor</th>
                                <th class="pb-6">Day</th>
                                <th class="pb-6">Time Window</th>
                                <th class="pb-6 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @forelse($schedules as $entry)
                            <tr class="group hover:bg-white/5 transition-colors">
                                <td class="py-6 font-black text-white text-sm uppercase">
                                    <div class="flex flex-col">
                                        {{ $entry->user->name }}
                                        <span class="text-[9px] text-[#D4AF37] tracking-widest italic">{{ $entry->subject_code }}</span>
                                    </div>
                                </td>
                                <td class="py-6 font-bold text-slate-400 text-xs uppercase tracking-widest">{{ $entry->day }}</td>
                                <td class="py-6 font-bold text-white text-xs">
                                    <span class="px-3 py-1 bg-slate-800 rounded-lg border border-slate-700">
                                        {{ date('h:i A', strtotime($entry->start_time)) }} — {{ date('h:i A', strtotime($entry->end_time)) }}
                                    </span>
                                </td>
                                <td class="py-6 text-right">
                                    <form action="{{ route('dashboard.labs.schedule.destroy', $entry->id) }}" method="POST" onsubmit="return confirm('Revoke this laboratory slot?')">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-500 hover:text-rose-400 text-[10px] font-black uppercase tracking-widest border border-rose-500/20 px-4 py-2 rounded-xl hover:bg-rose-500/10 transition-all">
                                            Revoke
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-20 text-center">
                                    <p class="text-slate-600 font-black uppercase tracking-widest text-[10px]">No scheduled slots found for this node</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>