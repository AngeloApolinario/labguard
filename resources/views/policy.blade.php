<x-guest-layout>
    <div class="min-h-screen bg-[#F8FAFC] py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">

        <div class="absolute top-0 left-0 w-full h-full opacity-30 pointer-events-none">
            <div class="absolute top-[-5%] left-[-10%] w-[40%] h-[40%] bg-indigo-200 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[35%] h-[35%] bg-[#D4AF37]/10 rounded-full blur-[100px]"></div>
        </div>

        <div class="max-w-3xl mx-auto relative z-10">
            <div class="flex flex-col items-center mb-10 text-center">
                <div class="p-4 bg-white rounded-[2rem] shadow-[0_10px_30px_rgba(0,0,0,0.03)] border border-slate-100 mb-6 group hover:scale-105 transition-transform">
                    <x-authentication-card-logo />
                </div>

                <h2 class="text-3xl font-black text-slate-800 tracking-tighter uppercase">
                    Privacy <span class="text-[#D4AF37]">Policy</span>
                </h2>
                <p class="mt-2 text-[10px] font-bold text-slate-400 uppercase tracking-[0.4em]">Data Protection & Privacy Protocol • LabGuard</p>
            </div>

            <div class="bg-white rounded-[3rem] p-3 shadow-[0_50px_100px_-20px_rgba(0,0,0,0.04)] border border-white">
                <div class="bg-slate-50/50 rounded-[2.8rem] border border-slate-100/50 px-8 py-12 md:px-16">

                    <div class="mb-10 flex items-center justify-between border-b border-slate-200 pb-6">
                        <div class="flex flex-col">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Document ID</span>
                            <span class="text-xs font-mono text-slate-600">AU-LG-2026-PP</span>
                        </div>
                        <div class="text-right flex flex-col">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Effective Date</span>
                            <span class="text-xs font-mono text-slate-600">09 FEB 2026</span>
                        </div>
                    </div>

                    <article class="prose prose-slate prose-headings:uppercase prose-headings:tracking-widest prose-headings:font-black prose-headings:text-slate-800 prose-p:text-slate-600 prose-p:leading-relaxed prose-strong:text-[#D4AF37] prose-li:text-slate-600 prose-hr:border-slate-200">
                        {!! $policy !!}
                    </article>

                    <div class="mt-16 pt-8 border-t border-slate-200">
                        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                            <div class="flex items-center space-x-3 bg-white px-4 py-2 rounded-2xl border border-slate-100 shadow-sm">
                                <div class="size-2 rounded-full bg-green-500 animate-pulse"></div>
                                <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Encrypted Data Management Active</span>
                            </div>

                            <a href="{{ route('register') }}" class="group flex items-center space-x-2 text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-[#D4AF37] transition-colors">
                                <span>Return to Registration</span>
                                <svg class="size-3 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 text-center space-y-2">
                <p class="text-slate-400 text-[10px] font-medium uppercase tracking-[0.2em]">
                    Subject to Araullo University Digital Governance Standards
                </p>
                <div class="flex justify-center space-x-4">
                    <span class="size-1 rounded-full bg-slate-300"></span>
                    <span class="size-1 rounded-full bg-slate-300"></span>
                    <span class="size-1 rounded-full bg-slate-300"></span>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>