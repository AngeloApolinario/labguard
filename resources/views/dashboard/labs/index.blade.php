<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col">
            <h2 class="font-black text-4xl text-slate-800 tracking-tighter">
                Computer <span class="text-[#D4AF37]">Labs</span>
            </h2>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.3em] mt-1">Inventory Management & Node Status</p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @php
                $labs = [
                ['name' => 'Lab 1', 'desc' => 'Computer Lab A - Programming'],
                ['name' => 'Lab 2', 'desc' => 'Computer Lab B - Network & DB'],
                ['name' => 'Lab 3', 'desc' => 'Computer Lab C - Web & Security'],
                ];
                @endphp

                @foreach($labs as $lab)
                <div class="bg-white p-10 rounded-2xl border-2 border-slate-100 shadow-sm hover:border-[#D4AF37] hover:shadow-xl transition-all duration-300 group cursor-pointer">
                    <h3 class="text-3xl font-black text-slate-900 mb-2">{{ $lab['name'] }}</h3>
                    <p class="text-sm text-slate-500 font-medium mb-8">{{ $lab['desc'] }}</p>

                    <div class="flex items-center text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] group-hover:text-indigo-600 transition-colors">
                        <span>Click to view & items</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>