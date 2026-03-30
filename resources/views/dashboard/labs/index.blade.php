<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-black text-4xl text-slate-800 tracking-tighter uppercase">
                    Facility <span class="text-[#D4AF37]">Inventory</span>
                </h2>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.3em] mt-1">Database-Synced Lab </p>
            </div>

            {{-- TRIGGER: Native JavaScript --}}
            <button onclick="openLabModal()" type="button" class="flex items-center space-x-2 bg-slate-800 text-white px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-[#D4AF37] transition-all shadow-xl shadow-slate-200 active:scale-95">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                </svg>
                <span>New Lab</span>
            </button>
        </div>
    </x-slot>

    <div class="py-12 px-6 bg-[#F8FAFC] min-h-screen">
        <div class="max-w-7xl mx-auto">

            {{-- Flash Success Message --}}
            @if(session('success'))
            <div id="flash-message" class="mb-8 p-4 bg-emerald-50 border border-emerald-100 rounded-2xl flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="size-2 bg-emerald-500 rounded-full animate-pulse"></div>
                    <p class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">{{ session('success') }}</p>
                </div>
                <button onclick="document.getElementById('flash-message').remove()" class="text-emerald-400">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M6 18L18 6M6 6l12 12" stroke-width="2" />
                    </svg>
                </button>
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                @forelse($labs as $lab)
                <div class="group relative bg-white border border-slate-200 rounded-[3rem] p-10 transition-all duration-500 hover:shadow-[0_30px_60px_rgba(0,0,0,0.05)] hover:-translate-y-2">

                    <div class="flex justify-between items-start mb-12">
                        <div class="p-4 bg-slate-50 rounded-2xl group-hover:bg-[#D4AF37]/10 transition-colors">
                            <svg class="size-8 text-slate-400 group-hover:text-[#D4AF37] transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div class="text-right">
                            <span class="text-[9px] font-black text-slate-300 uppercase tracking-widest">FACILITY ID</span>
                            <p class="text-xs font-black text-slate-700 uppercase">#{{ str_pad($lab->id, 3, '0', STR_PAD_LEFT) }}</p>
                        </div>
                    </div>

                    <h3 class="text-4xl font-black text-slate-800 mb-2 tracking-tighter uppercase">{{ $lab->name }}</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-6">{{ $lab->location }}</p>

                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-tighter">Total Units</p>
                                <p class="text-xl font-black text-slate-800">{{ $lab->computers->count() }}</p>
                            </div>
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-tighter">Active Now</p>
                                <p class="text-xl font-black text-[#D4AF37]">{{ $lab->computers->where('status', 'active')->count() }}</p>
                            </div>
                        </div>

                        @php
                        $total = $lab->computers->count();
                        $active = $lab->computers->where('status', 'active')->count();
                        $percent = $total > 0 ? round(($active / $total) * 100) : 0;
                        @endphp

                        <div class="space-y-2">
                            <div class="flex justify-between text-[9px] font-black uppercase tracking-widest text-slate-500">
                                <span>Utilization</span>
                                <span>{{ $percent }}%</span>
                            </div>
                            <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-slate-800 transition-all duration-1000 group-hover:bg-[#D4AF37]" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-slate-50 grid grid-cols-2 gap-4">
                            <a href="#" class="w-full py-4 bg-slate-50 text-slate-600 text-[10px] flex items-center justify-center font-black uppercase tracking-[0.2em] rounded-2xl group-hover:bg-slate-800 group-hover:text-white transition-all">
                                Inspect
                            </a>
                            <a href="{{ route('dashboard.labs.schedule', $lab->id) }}" class="w-full py-4 bg-[#D4AF37]/10 text-[#D4AF37] text-[10px] flex items-center justify-center font-black uppercase tracking-[0.2em] rounded-2xl hover:bg-[#D4AF37] hover:text-white transition-all">
                                Schedule
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full py-20 text-center border-2 border-dashed border-slate-200 rounded-[3rem] bg-white">
                    <p class="text-slate-400 font-black uppercase tracking-widest">No Facility Data detected.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- THE MODAL --}}
    <div id="labModal" class="hidden fixed inset-0 z-[10000] flex items-center justify-center p-6 bg-slate-900/90 backdrop-blur-md" style="display: none;">
        <div class="bg-white w-full max-w-lg rounded-[3rem] p-10 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-[#D4AF37] to-amber-200"></div>

            <h3 class="text-4xl font-black text-slate-900 uppercase tracking-tighter mb-2">Initialize <span class="text-[#D4AF37]">Laboratory</span></h3>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-10">Deploying new facility to central server</p>

            <form action="{{ route('dashboard.labs.store') }}" method="POST" onsubmit="setLoadingState(this)">
                @csrf
                <div class="space-y-8">
                    <div>
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] ml-2">Laboratory Name</label>
                        <input type="text" name="name" required placeholder="e.g., Computer Lab 1" class="w-full mt-2 bg-slate-50 border-slate-100 border rounded-2xl p-4 text-sm font-black focus:ring-2 focus:ring-[#D4AF37] transition-all outline-none uppercase">
                    </div>

                    <div>
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] ml-2">Facility Location</label>
                        <input type="text" name="location" required placeholder="e.g., 3rd Floor" class="w-full mt-2 bg-slate-50 border-slate-100 border rounded-2xl p-4 text-sm font-black focus:ring-2 focus:ring-[#D4AF37] transition-all outline-none">
                    </div>

                    <div>
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] ml-2">PC Capacity</label>
                        <input type="number" name="pc_count" min="1" max="60" required placeholder="Units" class="w-full mt-2 bg-slate-50 border-slate-100 border rounded-2xl p-4 text-sm font-black focus:ring-2 focus:ring-[#D4AF37] outline-none">
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button type="button" onclick="closeLabModal()" class="flex-1 py-5 text-[10px] font-black uppercase text-slate-400">Cancel</button>
                        <button type="submit" id="submitBtn" class="flex-1 py-5 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-[#D4AF37] shadow-xl transition-all">
                            Create Facility
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openLabModal() {
            const modal = document.getElementById('labModal');
            modal.style.display = 'flex';
        }

        function closeLabModal() {
            const modal = document.getElementById('labModal');
            modal.style.display = 'none';
        }

        function setLoadingState(form) {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = 'Initializing...';
            btn.classList.add('opacity-50', 'animate-pulse');
        }

        // Close on Esc key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeLabModal();
        });
    </script>

</x-app-layout>