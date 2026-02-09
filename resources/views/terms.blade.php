<x-guest-layout>
    <div class="min-h-screen bg-[#F8FAFC] py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">

        <div class="absolute top-0 left-0 w-full h-full opacity-40 pointer-events-none">
            <div class="absolute top-[-10%] right-[-5%] w-[40%] h-[40%] bg-[#D4AF37]/10 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[10%] left-[-5%] w-[30%] h-[30%] bg-indigo-100 rounded-full blur-[100px]"></div>
        </div>

        <div class="max-w-3xl mx-auto relative z-10">
            <div class="flex flex-col items-center mb-10">
                <div class="p-4 bg-white rounded-[2rem] shadow-sm border border-slate-100 mb-6">
                    <x-authentication-card-logo />
                </div>

                <h2 class="text-3xl font-black text-slate-800 tracking-tighter uppercase text-center">
                    Legal <span class="text-[#D4AF37]">Protocols</span>
                </h2>
                <div class="mt-2 flex items-center space-x-2">
                    <span class="h-px w-8 bg-[#D4AF37]"></span>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.4em]">Araullo University • LabGuard</p>
                    <span class="h-px w-8 bg-[#D4AF37]"></span>
                </div>
            </div>

            <div class="bg-white rounded-[3rem] p-3 shadow-[0_40px_80px_-15px_rgba(0,0,0,0.05)] border border-white">
                <div class="bg-slate-50/50 rounded-[2.8rem] border border-slate-100/50 px-8 py-12 md:px-16">

                    <article class="prose prose-slate prose-headings:uppercase prose-headings:tracking-widest prose-headings:font-black prose-headings:text-slate-800 prose-p:text-slate-600 prose-p:leading-relaxed prose-strong:text-[#D4AF37] prose-hr:border-slate-200">
                        {!! $terms !!}
                    </article>

                    <div class="mt-12 pt-8 border-t border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="flex items-center space-x-2">
                            <svg class="size-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Officially Verified Directive</span>
                        </div>

                        <a href="{{ route('login') }}" class="text-[10px] font-black text-[#D4AF37] uppercase tracking-widest hover:text-slate-800 transition-colors">
                            Return to Terminal
                        </a>
                    </div>
                </div>
            </div>

            <p class="mt-8 text-center text-slate-400 text-[10px] font-medium uppercase tracking-[0.2em]">
                Questions regarding compliance? <a href="#" class="text-[#D4AF37] underline decoration-2 underline-offset-4">Contact System Admin</a>
            </p>
        </div>
    </div>
</x-guest-layout>