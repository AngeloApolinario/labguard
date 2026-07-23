<x-guest-layout>
    <div class="min-h-screen bg-[#F8FAFC] flex flex-col justify-center items-center p-4 sm:p-6 relative overflow-hidden">

        {{-- Subtle Radial Glow (Background Depth) --}}
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-gradient-to-tr from-blue-100/40 via-transparent to-amber-100/30 rounded-full blur-3xl pointer-events-none"></div>

        {{-- Main Security Card --}}
        <div class="w-full sm:max-w-md bg-white shadow-2xl shadow-slate-200/80 border border-slate-100 border-t-2 border-t-[#D4AF37] rounded-3xl sm:rounded-[2.5rem] p-8 sm:p-10 relative z-10">

            {{-- Header & Status Pill --}}
            <div class="mb-8">

                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 uppercase tracking-tight">
                    Password <span class="text-[#D4AF37]">Recovery</span>
                </h2>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] mt-1">
                    LabGuard Authorization Control
                </p>
            </div>

            {{-- Instructions --}}
            <div class="mb-6 p-4 bg-slate-50/80 rounded-2xl border border-slate-100/80">
                <p class="text-[11px] font-semibold text-slate-600 leading-relaxed uppercase tracking-wider">
                    {{ __('Forgot your password? Enter your institutional email below to dispatch a secure reset link to your inbox.') }}
                </p>
            </div>

            {{-- Status Alert --}}
            @session('status')
            <div class="mb-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-2xl font-black text-[10px] text-emerald-800 uppercase tracking-widest flex items-center gap-2">
                <svg class="size-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ $value }}</span>
            </div>
            @endsession

            {{-- Validation Errors --}}
            <x-validation-errors class="mb-6 p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-2xl text-[10px] font-bold text-rose-700" />

            {{-- Recovery Form --}}
            <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
                @csrf

                <div class="space-y-1.5">
                    <x-label for="email" value="{{ __('Registered Institutional Email') }}" class="text-[9px] font-black text-slate-500 uppercase ml-1 tracking-widest" />
                    <div class="relative">
                        <x-input id="email"
                            class="block w-full rounded-2xl border-slate-200 bg-slate-50/50 text-xs sm:text-sm py-3.5 px-4 font-semibold text-slate-800 placeholder:text-slate-400 placeholder:font-normal focus:bg-white focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition-all"
                            type="email"
                            name="email"
                            :value="old('email')"
                            placeholder="username@phinmaed.com"
                            required autofocus />

                        <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-300">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="pt-2 space-y-3">
                    <button type="submit" class="w-full py-4 bg-slate-900 hover:bg-indigo-950 text-white rounded-2xl text-[10px] sm:text-xs font-black uppercase tracking-[0.2em] shadow-lg shadow-slate-900/20 hover:shadow-xl hover:shadow-indigo-900/20 border border-slate-800 hover:border-[#D4AF37]/50 active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                        <span>{{ __('Dispatch Reset Link') }}</span>
                        <svg class="size-3.5 text-[#D4AF37]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>

                    <a href="{{ route('login') }}" class="block text-center text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-blue-600 active:text-[#D4AF37] transition-colors py-2">
                        Return to Secure Login
                    </a>
                </div>
            </form>
        </div>

        {{-- Institutional Footer --}}
        <div class="mt-8 text-center">
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.4em]">
                Araullo University <span class="text-[#D4AF37] mx-1">•</span> Computer Laboratory System
            </p>
        </div>
    </div>
</x-guest-layout>