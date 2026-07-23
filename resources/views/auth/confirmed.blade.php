<x-guest-layout>
    <div class="min-h-screen bg-[#F8FAFC] flex flex-col justify-center items-center p-6 relative overflow-hidden">

        {{-- Background Glow --}}
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-gradient-to-tr from-emerald-100/50 via-transparent to-blue-100/30 rounded-full blur-3xl pointer-events-none"></div>

        {{-- Main Status Card --}}
        <div class="w-full max-w-sm bg-white shadow-2xl shadow-slate-200/80 border border-slate-100 border-t-4 border-t-emerald-500 rounded-3xl p-8 text-center relative z-10">

            <div class="size-16 bg-emerald-50 border border-emerald-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="size-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h2 class="text-xl font-black text-slate-900 uppercase tracking-tight">
                Email <span class="text-emerald-600">Verified</span>
            </h2>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] mt-1">
                LabGuard Authorization Control
            </p>

            <div class="mt-6 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                <p class="text-xs font-semibold text-slate-600 leading-relaxed">
                    Your institutional email is now verified. You may close this window and return to your computer.
                </p>
            </div>

            <p class="mt-6 text-[9px] font-black text-slate-300 uppercase tracking-[0.3em]">
                AU - Computer Laboratory System
            </p>
        </div>
    </div>
</x-guest-layout>