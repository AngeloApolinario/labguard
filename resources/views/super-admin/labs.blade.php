<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-black text-4xl text-slate-800 tracking-tighter uppercase">
                    Facility <span class="text-[#D4AF37]">Inventory</span>
                </h2>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.3em] mt-1">Database-Synced Lab Nodes</p>
            </div>

            {{-- FIXED: This button no longer uses $lab->id because it's for creating a NEW lab --}}
            <button class="flex items-center space-x-2 bg-slate-800 text-white px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-[#D4AF37] transition-all shadow-xl shadow-slate-200">
                <x-heroicon-o-plus class="size-4" stroke-width="3" />
                <span>Provision New Lab</span>
            </button>
        </div>
    </x-slot>

    <div class="py-12 px-6">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">

                @forelse($labs as $lab)
                <div class="group relative bg-white border border-slate-200 rounded-[3rem] p-10 transition-all duration-500 hover:shadow-[0_30px_60px_rgba(0,0,0,0.05)] hover:-translate-y-2">

                    {{-- Status Badge --}}
                    <div class="flex justify-between items-start mb-12">
                        <div class="p-4 bg-slate-50 rounded-2xl group-hover:bg-[#D4AF37]/10 transition-colors">
                            <x-heroicon-o-building-office-2 class="size-8 text-slate-400 group-hover:text-[#D4AF37]" />
                        </div>
                        <div class="text-right">
                            <span class="text-[9px] font-black text-slate-300 uppercase tracking-widest">Network Node</span>
                            {{-- SAFE: Inside loop, $lab exists --}}
                            <p class="text-xs font-black text-slate-700 uppercase italic">AU-{{ str_pad($lab->id, 3, '0', STR_PAD_LEFT) }}</p>
                        </div>
                    </div>

                    {{-- SAFE: Inside loop --}}
                    <h3 class="text-4xl font-black text-slate-800 mb-2 tracking-tighter uppercase">{{ $lab->name }}</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-8">System-Verified Facility</p>

                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-slate-50 p-4 rounded-2xl">
                                <p class="text-[8px] font-black text-slate-400 uppercase">Total Units</p>
                                <p class="text-xl font-black text-slate-800">{{ $lab->total_pcs }}</p>
                            </div>
                            <div class="bg-slate-50 p-4 rounded-2xl">
                                <p class="text-[8px] font-black text-slate-400 uppercase">Active Now</p>
                                <p class="text-xl font-black text-[#D4AF37]">{{ $lab->active_pcs }}</p>
                            </div>
                        </div>

                        {{-- Occupancy Bar --}}
                        @php
                        $percent = $lab->total_pcs > 0 ? ($lab->active_pcs / $lab->total_pcs) * 100 : 0;
                        @endphp
                        <div class="space-y-2">
                            <div class="flex justify-between text-[9px] font-black uppercase tracking-widest text-slate-500">
                                <span>Utilization</span>
                                <span>{{ round($percent) }}%</span>
                            </div>
                            <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-slate-800 transition-all duration-1000"
                                    style="width: {{ $percent }}%"></div>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-slate-50 grid grid-cols-2 gap-4">
                            {{-- SAFE: Inside loop --}}
                            <a href="{{ route('super-admin.labs.show', $lab->id) }}" class="w-full py-4 bg-slate-50 text-slate-600 text-center text-[10px] font-black uppercase tracking-[0.2em] rounded-2xl group-hover:bg-slate-800 group-hover:text-white transition-all">
                                Inspect
                            </a>

                            <a href="{{ route('super-admin.labs.schedule', $lab->id) }}"
                                class="w-full py-4 bg-[#D4AF37]/10 text-[#D4AF37] text-[10px] flex items-center justify-center font-black uppercase tracking-[0.2em] rounded-2xl hover:bg-[#D4AF37] hover:text-white transition-all">
                                Schedule
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full py-20 text-center">
                    <p class="text-slate-400 font-bold uppercase tracking-widest">No Lab Data detected in the central server.</p>
                </div>
                @endforelse

            </div>
        </div>
    </div>
</x-app-layout>