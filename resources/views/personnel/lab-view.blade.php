<x-app-layout>
    <div class="py-12 px-6 min-h-screen bg-[#F8FAFC]" x-data="{ openModal: false, selectedPc: '', pcId: '' }">
        <div class="max-w-7xl mx-auto">

            {{-- Header Card --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-12 bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm">
                <div>
                    <div class="flex items-center space-x-2 text-[#D4AF37] mb-1">
                        <div class="size-2 bg-[#D4AF37] rounded-full animate-pulse"></div>
                        <span class="text-[9px] font-black uppercase tracking-[0.2em]">Live Monitoring</span>
                    </div>
                    <h2 class="text-3xl font-black text-slate-800 uppercase tracking-tighter">
                        {{ $name }} <span class="text-[#D4AF37]">Grid</span>
                    </h2>
                </div>

                <div class="mt-6 md:mt-0 flex items-center space-x-8">
                    <div class="text-center">
                        <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Active Sessions</p>
                        <p class="text-xl font-black text-slate-800">{{ $computers->where('status', 'active')->count() }}</p>
                    </div>
                    <div class="h-10 w-px bg-slate-100"></div>
                    <a href="{{ route('personnel.index') }}" class="px-6 py-3 bg-slate-800 text-white text-[10px] font-black uppercase rounded-xl hover:bg-slate-700 transition-all">
                        Back to Selection
                    </a>
                </div>
            </div>

            {{-- Tactical Grid --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
                @foreach($computers as $pc)
                <div class="relative bg-white rounded-[2rem] p-6 border {{ $pc->status === 'active' ? 'border-rose-100 bg-rose-50/20' : 'border-slate-100' }} transition-all duration-300 shadow-sm">

                    <div class="flex justify-between items-center mb-6">
                        <span class="text-[10px] font-black {{ $pc->status === 'active' ? 'text-rose-500' : 'text-slate-400' }} uppercase">{{ $pc->pc_number }}</span>
                        <div class="size-2 rounded-full {{ $pc->status === 'active' ? 'bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.5)]' : 'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.3)]' }}"></div>
                    </div>

                    @if($pc->status === 'available')
                    <div class="flex flex-col items-center py-4">
                        <svg class="size-8 text-slate-100 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <button @click="openModal = true; selectedPc = '{{ $pc->pc_number }}'; pcId = '{{ $pc->id }}'"
                            class="w-full py-2 bg-slate-50 text-slate-600 text-[9px] font-black uppercase rounded-xl hover:bg-slate-800 hover:text-white transition-all">
                            Assign PC
                        </button>
                    </div>
                    @else
                    <div class="space-y-4">
                        <div class="bg-white/60 p-3 rounded-xl border border-rose-100">
                            <p class="text-[10px] font-black text-slate-800 truncate uppercase">{{ $pc->activeSession->student_name }}</p>
                            <p class="text-[8px] font-bold text-rose-400 mt-0.5 tracking-wider">{{ $pc->activeSession->student_id_number }}</p>
                        </div>
                        <form method="POST" action="{{ route('personnel.release', $pc->id) }}">
                            @csrf
                            <button type="submit" class="w-full py-2 bg-rose-500 text-white text-[9px] font-black uppercase rounded-xl hover:bg-rose-600 transition-all shadow-lg shadow-rose-100">
                                Release
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Assignment Modal --}}
        <div x-show="openModal" class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-slate-900/40 backdrop-blur-sm" x-cloak x-transition>
            <div @click.away="openModal = false" class="bg-white w-full max-w-md p-10 rounded-[3rem] shadow-2xl border border-slate-100">
                <div class="text-center mb-8">
                    <div class="size-14 bg-[#D4AF37]/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="size-7 text-[#D4AF37]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">Register <span class="text-[#D4AF37]" x-text="selectedPc"></span></h2>
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-1">Establishing Hardware Liability</p>
                </div>

                {{-- NOTICE: Using Manual URL to avoid Parameter Error --}}
                <form :action="'/terminal/assign/' + pcId" method="POST">
                    @csrf
                    <div class="space-y-4">
                        <div class="space-y-1">
                            <label class="text-[9px] font-black text-slate-400 uppercase ml-2 tracking-widest">Student Name</label>
                            <input type="text" name="student_name" placeholder="Full Legal Name" required class="w-full bg-slate-50 border-slate-200 rounded-2xl py-4 px-6 text-sm font-bold text-slate-800 focus:border-[#D4AF37] focus:ring-0">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[9px] font-black text-slate-400 uppercase ml-2 tracking-widest">ID Number</label>
                            <input type="text" name="student_id" placeholder="Ex: 2024-0001" required class="w-full bg-slate-50 border-slate-200 rounded-2xl py-4 px-6 text-sm font-bold text-slate-800 focus:border-[#D4AF37] focus:ring-0">
                        </div>
                        <div class="pt-4 space-y-3">
                            <button type="submit" class="w-full py-4 bg-slate-800 text-white font-black uppercase rounded-2xl hover:bg-slate-700 transition-all shadow-xl shadow-slate-200">
                                Start Session
                            </button>
                            <button type="button" @click="openModal = false" class="w-full text-[10px] font-black text-slate-400 uppercase tracking-widest">
                                Cancel Operation
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>