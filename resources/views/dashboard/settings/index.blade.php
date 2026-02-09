<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col">
            <h2 class="font-black text-4xl text-slate-800 tracking-tighter">
                Control <span class="text-[#D4AF37]">Center</span>
            </h2>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.3em] mt-1">System Configuration & Security</p>
        </div>
    </x-slot>

    <div class="py-10 max-w-5xl mx-auto space-y-8 px-4">

        <div class="relative group">
            <div class="absolute -inset-1 bg-gradient-to-r from-[#D4AF37] to-amber-600 rounded-3xl blur opacity-10 group-hover:opacity-20 transition duration-1000"></div>

            <div class="relative bg-[#0f172a] rounded-2xl border border-white/5 overflow-hidden shadow-2xl">
                <div class="p-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <h3 class="text-xl font-black text-white tracking-tight flex items-center">
                            <span class="size-2 bg-[#D4AF37] rounded-full mr-3 shadow-[0_0_10px_#D4AF37]"></span>
                            Lab Security Infrastructure
                        </h3>
                        <p class="text-slate-400 text-sm mt-1">Maintains the encrypted lockdown and PC station monitoring.</p>
                    </div>

                    <div x-data="{ enabled: true }" class="flex items-center space-x-4 bg-white/5 p-3 rounded-2xl border border-white/10">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400" x-text="enabled ? 'Armed' : 'Disarmed'"></span>
                        <button
                            type="button"
                            @click="enabled = !enabled"
                            :class="enabled ? 'bg-[#D4AF37]' : 'bg-slate-700'"
                            class="relative inline-flex h-7 w-14 shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none">
                            <span
                                :class="enabled ? 'translate-x-7' : 'translate-x-1'"
                                class="mt-1 inline-block size-5 transform rounded-full bg-white shadow-lg transition duration-200 ease-in-out">
                            </span>
                        </button>
                    </div>
                </div>

                <div class="px-8 pb-8">
                    <div class="bg-gradient-to-b from-[#1a233a] to-[#0f172a] rounded-xl p-8 border border-[#D4AF37]/30 shadow-inner">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[10px] font-bold text-indigo-400 uppercase tracking-[0.2em] mb-2">Network Broadcast Status</p>
                                <h2 class="text-5xl font-black text-white tracking-tighter">
                                    SYSTEM <span class="text-[#D4AF37]">ACTIVE</span>
                                </h2>
                            </div>
                            <div class="hidden md:block">
                                <svg class="size-16 text-[#D4AF37] opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="md:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
                <h4 class="text-lg font-black text-slate-800 mb-6">Inventory Registration</h4>
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Serial Identification</label>
                        <input type="text" placeholder="AU-LAB-XXXX-XXXX" class="w-full mt-1 px-4 py-4 bg-slate-50 border-2 border-slate-100 rounded-xl focus:border-[#D4AF37] focus:ring-0 transition-all font-mono text-sm">
                    </div>
                    <button class="w-full bg-[#0f172a] text-[#D4AF37] py-4 rounded-xl font-black text-sm uppercase tracking-[0.2em] border border-[#D4AF37]/30 hover:bg-black transition-all shadow-xl">
                        + Register New Device
                    </button>
                </div>
            </div>

            <div class="bg-gradient-to-br from-[#1a233a] to-[#0f172a] rounded-2xl p-8 text-white border border-white/5 flex flex-col justify-center items-center">
                <p class="text-[10px] font-bold text-[#D4AF37] uppercase tracking-[0.2em] mb-1">Current Inventory</p>
                <h3 class="text-6xl font-black tracking-tighter">482</h3>
                <p class="text-[10px] text-slate-500 text-center mt-4 uppercase tracking-widest leading-relaxed">Devices Synced Across 5 Active Labs</p>
            </div>
        </div>

        <div class="pt-4">
            <form method="POST" action="{{ route('logout') }}" x-data>
                @csrf
                <button type="submit" @click.prevent="$root.submit();" class="group relative w-full overflow-hidden rounded-2xl bg-white border border-rose-200 p-4 transition-all hover:bg-rose-50">
                    <span class="text-sm font-black text-rose-600 uppercase tracking-[0.3em] group-hover:scale-110 transition-transform block">Terminate Session</span>
                </button>
            </form>
        </div>
    </div>
</x-app-layout>