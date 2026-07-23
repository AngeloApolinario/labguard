<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-black text-2xl sm:text-4xl text-slate-800 tracking-tighter uppercase">
                    {{ $lab->lab_name }} <span class="text-[#D4AF37]">Occupancy</span>
                </h2>
                <div class="flex items-center space-x-2 mt-1">
                    <div class="size-2 bg-emerald-500 rounded-full animate-pulse"></div>
                    <p class="text-[9px] sm:text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] sm:tracking-[0.3em]">
                        Scheduling & Laboratory Allocation
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden sm:block bg-white px-5 py-2 rounded-2xl border border-slate-100 shadow-sm text-right">
                    <p class="text-[9px] font-black text-[#D4AF37] uppercase tracking-widest">System Date</p>
                    <p class="text-xs sm:text-sm font-black text-slate-700 uppercase">{{ now()->format('D, M d, Y') }}</p>
                </div>
                <a href="{{ route('dashboard.labs') }}" class="px-5 py-2.5 bg-slate-800 text-white text-[10px] font-black uppercase rounded-xl hover:bg-slate-700 active:scale-95 transition-all shadow-sm">
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    {{-- Floating Toast Notifications --}}
    <div class="fixed top-4 right-4 left-4 sm:left-auto sm:top-10 sm:right-10 z-[100] flex flex-col gap-3 sm:w-80 pointer-events-none">
        @if(session('success'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)" x-show="show"
            x-transition:enter="transform transition ease-out duration-300"
            x-transition:enter-start="translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto bg-white/95 backdrop-blur-md border-l-4 border-emerald-500 shadow-2xl p-4 rounded-2xl flex items-center space-x-3 border border-slate-100">
            <div class="bg-emerald-100/80 p-2 rounded-xl shrink-0">
                <svg class="size-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black text-slate-800 uppercase tracking-widest">Entry Verified</p>
                <p class="text-[9px] font-bold text-slate-500 uppercase truncate leading-tight mt-0.5">{{ session('success') }}</p>
            </div>
        </div>
        @endif

        @if(session('error') || $errors->any())
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 8000)" x-show="show"
            x-transition:enter="transform transition ease-out duration-300"
            x-transition:enter-start="translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            class="pointer-events-auto bg-white/95 backdrop-blur-md border-l-4 border-rose-500 shadow-2xl p-4 rounded-2xl flex items-start space-x-3 border border-slate-100">
            <div class="bg-rose-100/80 p-2 rounded-xl shrink-0">
                <svg class="size-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black text-slate-800 uppercase tracking-widest">System Conflict</p>
                <p class="text-[9px] font-bold text-rose-600 uppercase leading-tight mt-1 break-words">
                    {{ session('error') ?? $errors->first() }}
                </p>
            </div>
        </div>
        @endif
    </div>

    <div class="py-6 sm:py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto min-h-screen bg-[#F8FAFC]">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 sm:gap-10 items-start">

            {{-- Left Column: Entry Form --}}
            <div class="bg-white p-6 sm:p-8 rounded-3xl sm:rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/50 sticky top-6">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Assign Instructor</p>

                <form action="{{ route('dashboard.labs.schedule.store', $lab->id) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="text-[8px] font-black text-slate-400 uppercase ml-2 mb-1 block">Authorized Teacher</label>
                        <select name="user_id" class="w-full rounded-2xl border-slate-200/80 bg-slate-50 text-xs sm:text-sm py-3 px-4 focus:ring-[#D4AF37] focus:border-[#D4AF37] transition-all">
                            <option value="" disabled selected>Select Instructor...</option>
                            @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}" {{ old('user_id') == $teacher->id ? 'selected' : '' }}>{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[8px] font-black text-slate-400 uppercase ml-2 mb-1 block">Day</label>
                            <select name="day" class="w-full rounded-2xl border-slate-200/80 bg-slate-50 text-xs sm:text-sm py-3 px-4 focus:ring-[#D4AF37] focus:border-[#D4AF37] transition-all">
                                @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day)
                                <option value="{{ $day }}" {{ old('day') == $day ? 'selected' : '' }}>{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[8px] font-black text-slate-400 uppercase ml-2 mb-1 block">Subject Code</label>
                            <input type="text" name="subject_code" value="{{ old('subject_code') }}" placeholder="E.g. IT-402" class="w-full rounded-2xl border-slate-200/80 bg-slate-50 text-xs sm:text-sm py-3 px-4 uppercase font-bold focus:ring-[#D4AF37] focus:border-[#D4AF37] transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[8px] font-black text-slate-400 uppercase ml-2 mb-1 block">Start Time</label>
                            <input type="time" name="start_time" value="{{ old('start_time') }}" class="w-full rounded-2xl border-slate-200/80 bg-slate-50 text-xs sm:text-sm py-3 px-3 sm:px-4 focus:ring-[#D4AF37] focus:border-[#D4AF37] transition-all">
                        </div>
                        <div>
                            <label class="text-[8px] font-black text-slate-400 uppercase ml-2 mb-1 block">End Time</label>
                            <input type="time" name="end_time" value="{{ old('end_time') }}" class="w-full rounded-2xl border-slate-200/80 bg-slate-50 text-xs sm:text-sm py-3 px-3 sm:px-4 focus:ring-[#D4AF37] focus:border-[#D4AF37] transition-all">
                        </div>
                    </div>

                    <button type="submit" class="w-full py-4 mt-4 bg-[#D4AF37] text-white text-[10px] font-black uppercase rounded-2xl shadow-lg shadow-[#D4AF37]/20 hover:bg-[#c29f2e] transition-all active:scale-[0.98]">
                        Establish Slot
                    </button>
                </form>
            </div>

            {{-- Right Column: Schedule Container with Fixed Height Scrollbar --}}
            <div class="lg:col-span-2 bg-slate-900 rounded-3xl sm:rounded-[2.5rem] p-6 sm:p-8 shadow-2xl flex flex-col" x-data="{ activeDay: 'All' }">

                {{-- Header & Filters --}}
                <div class="mb-6 flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                    <div>
                        <h4 class="text-white font-black uppercase tracking-tighter text-lg sm:text-xl">Active <span class="text-[#D4AF37]">Roster</span></h4>
                        <p class="text-[8px] font-bold text-slate-500 uppercase tracking-widest mt-0.5">Filtering by: <span class="text-[#D4AF37]" x-text="activeDay"></span></p>
                    </div>

                    {{-- Day Filter Chips --}}
                    <div class="flex items-center gap-2 overflow-x-auto pb-2 xl:pb-0 scrollbar-thin scrollbar-thumb-slate-700">
                        <template x-for="day in ['All', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']">
                            <button
                                @click="activeDay = day"
                                :class="activeDay === day ? 'bg-[#D4AF37] text-white' : 'bg-slate-800 text-slate-400 hover:bg-slate-700'"
                                class="px-3.5 py-2 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all border border-white/5 whitespace-nowrap shrink-0 shadow-sm"
                                x-text="day">
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Desktop Table View with Fixed Scroll Area --}}
                <div class="hidden sm:block overflow-y-auto max-h-[580px] pr-2 scrollbar-thin scrollbar-thumb-slate-700 scrollbar-track-slate-800/50">
                    <table class="w-full text-left border-collapse">
                        <thead class="sticky top-0 bg-slate-900 z-10 shadow-sm">
                            <tr class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] border-b border-slate-800">
                                <th class="pb-4 bg-slate-900">Instructor</th>
                                <th class="pb-4 bg-slate-900">Day</th>
                                <th class="pb-4 bg-slate-900">Time Window</th>
                                <th class="pb-4 text-right bg-slate-900">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/80">
                            @forelse($schedules as $entry)
                            <tr class="group hover:bg-white/5 transition-colors"
                                x-show="activeDay === 'All' || activeDay === '{{ $entry->day }}'"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform -translate-y-2">

                                <td class="py-4 font-black text-white text-sm uppercase">
                                    <div class="flex flex-col">
                                        {{ $entry->user->name }}
                                        <span class="text-[9px] text-[#D4AF37] tracking-widest italic font-bold">{{ $entry->subject_code }}</span>
                                    </div>
                                </td>
                                <td class="py-4 font-bold text-slate-400 text-xs uppercase tracking-widest">
                                    <span :class="activeDay !== 'All' ? 'text-[#D4AF37]' : ''">{{ $entry->day }}</span>
                                </td>
                                <td class="py-4 font-bold text-white text-xs">
                                    <span class="px-3 py-1.5 bg-slate-800 rounded-lg border border-slate-700/60 inline-block">
                                        {{ date('h:i A', strtotime($entry->start_time)) }} — {{ date('h:i A', strtotime($entry->end_time)) }}
                                    </span>
                                </td>
                                <td class="py-4 text-right">
                                    <form action="{{ route('dashboard.labs.schedule.destroy', $entry->id) }}" method="POST" onsubmit="return confirm('Revoke this laboratory slot?')">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-500 hover:text-rose-400 text-[10px] font-black uppercase tracking-widest border border-rose-500/20 px-3.5 py-1.5 rounded-xl hover:bg-rose-500/10 active:scale-95 transition-all">
                                            Revoke
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-16 text-center text-slate-600 font-black uppercase tracking-widest text-[10px]">No scheduled slots found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Mobile Card Layout View with Fixed Scroll Area --}}
                <div class="block sm:hidden overflow-y-auto max-h-[500px] space-y-3 pr-1 scrollbar-thin scrollbar-thumb-slate-700">
                    @forelse($schedules as $entry)
                    <div class="p-4 bg-slate-800/60 rounded-2xl border border-slate-700/50 space-y-3"
                        x-show="activeDay === 'All' || activeDay === '{{ $entry->day }}'"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2">
                        <div class="flex items-start justify-between">
                            <div>
                                <h5 class="text-white font-black text-sm uppercase">{{ $entry->user->name }}</h5>
                                <p class="text-[9px] text-[#D4AF37] font-bold tracking-widest italic">{{ $entry->subject_code }}</p>
                            </div>
                            <span class="px-2.5 py-1 bg-slate-700/80 rounded-lg text-[9px] font-black uppercase text-slate-300 tracking-wider">
                                {{ $entry->day }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between pt-1 border-t border-slate-700/40">
                            <span class="text-slate-300 font-mono text-xs font-bold">
                                {{ date('h:i A', strtotime($entry->start_time)) }} - {{ date('h:i A', strtotime($entry->end_time)) }}
                            </span>
                            <form action="{{ route('dashboard.labs.schedule.destroy', $entry->id) }}" method="POST" onsubmit="return confirm('Revoke this laboratory slot?')">
                                @csrf @method('DELETE')
                                <button class="text-rose-400 text-[9px] font-black uppercase tracking-widest border border-rose-500/30 px-3 py-1.5 rounded-lg active:bg-rose-500/20 transition-all">
                                    Revoke
                                </button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="py-12 text-center text-slate-600 font-black uppercase tracking-widest text-[10px]">
                        No scheduled slots found
                    </div>
                    @endforelse
                </div>

                {{-- Empty Filter State --}}
                <div x-show="document.querySelectorAll('.lg\\:col-span-2 [x-show*=\'activeDay\']:not([style*=\'display: none\'])').length === 0"
                    x-cloak
                    class="py-16 text-center text-slate-600 font-black uppercase tracking-widest text-[10px]">
                    No sessions scheduled for <span x-text="activeDay" class="text-[#D4AF37]"></span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


<style>
    /* Modern Custom Scrollbar for Dark Container */
    .lg\:col-span-2 ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .lg\:col-span-2 ::-webkit-scrollbar-track {
        background: rgba(15, 23, 42, 0.6);
        /* Slate 900 tint */
        border-radius: 9999px;
    }

    .lg\:col-span-2 ::-webkit-scrollbar-thumb {
        background: #334155;
        /* Slate 700 */
        border-radius: 9999px;
        transition: background 0.2s ease;
    }

    .lg\:col-span-2 ::-webkit-scrollbar-thumb:hover {
        background: #D4AF37;
        /* Signature Gold Accent */
    }

    /* Firefox Support */
    .lg\:col-span-2 * {
        scrollbar-width: thin;
        scrollbar-color: #334155 rgba(15, 23, 42, 0.6);
    }
</style>