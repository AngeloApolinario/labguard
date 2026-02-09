<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col">
            <h2 class="font-black text-4xl text-slate-800 tracking-tighter">
                LabGuard <span class="text-[#D4AF37]">Dashboard</span>
            </h2>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.3em] mt-1">System Overview & Live Monitoring</p>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-blue-600 rounded-2xl p-6 shadow-lg text-white relative overflow-hidden group">
                <div class="relative z-10">
                    <p class="text-sm font-medium opacity-80 mb-1">Total Computers</p>
                    <h3 class="text-4xl font-black">250</h3>
                </div>
                <x-heroicon-s-computer-desktop class="absolute -right-4 -bottom-4 size-24 opacity-10 group-hover:scale-110 transition-transform" />
            </div>

            <div class="bg-amber-400 rounded-2xl p-6 shadow-lg text-[#1a233a] relative overflow-hidden group">
                <div class="relative z-10">
                    <p class="text-sm font-bold opacity-80 mb-1">Active Stations</p>
                    <h3 class="text-4xl font-black">18</h3>
                </div>
                <x-heroicon-s-bolt class="absolute -right-4 -bottom-4 size-24 opacity-20 group-hover:rotate-12 transition-transform" />
            </div>

            <div class="bg-[#1a233a] rounded-2xl p-6 shadow-lg text-white border border-[#D4AF37]/30 relative overflow-hidden group">
                <div class="relative z-10">
                    <p class="text-sm font-medium text-[#D4AF37] mb-1">Alerts Today</p>
                    <h3 class="text-4xl font-black">3</h3>
                </div>
                <x-heroicon-s-exclamation-triangle class="absolute -right-4 -bottom-4 size-24 text-[#D4AF37] opacity-10 group-hover:animate-pulse" />
            </div>

            <div class="bg-[#111827] rounded-2xl p-6 shadow-lg text-white border-2 border-green-500 relative overflow-hidden">
                <div class="text-center">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Lab Status</p>
                    <div class="inline-block px-4 py-1 rounded-full bg-green-500/20 border border-green-500 text-green-500 font-black text-xl tracking-tighter">
                        ACTIVE
                    </div>
                </div>
                <x-heroicon-s-lock-closed class="absolute right-2 top-2 size-4 text-[#D4AF37]" />
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                <h4 class="font-bold text-slate-700">Computer Station Map</h4>
            </div>
            <div class="p-8 grid grid-cols-5 md:grid-cols-10 gap-3">
                @foreach(range(1, 30) as $pc)
                @php
                // Simulating the "Critical" PC states from your image
                $isCritical = in_array($pc, [4, 9, 15, 24, 25]);
                @endphp

                <div class="aspect-square flex flex-col items-center justify-center rounded-lg border-2 transition-all cursor-pointer
                        {{ $isCritical 
                            ? 'bg-red-600 border-red-700 text-white shadow-lg shadow-red-200 scale-105' 
                            : 'bg-[#1a233a] border-transparent text-white hover:border-[#D4AF37]' }}">
                    <span class="text-[10px] font-bold">PC{{ str_pad($pc, 2, '0', STR_PAD_LEFT) }}</span>
                    @if($isCritical)
                    <span class="text-lg font-black leading-none">!</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>