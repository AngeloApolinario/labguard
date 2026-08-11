<x-guest-layout>
    <div class="min-h-screen bg-[#0f172a] flex items-center justify-center p-6 relative overflow-hidden font-sans">

        {{-- Background Gold & Indigo Glow Accents --}}
        <div class="absolute top-0 left-0 w-full h-full opacity-20 pointer-events-none">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-[#D4AF37] rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[30%] h-[30%] bg-indigo-600 rounded-full blur-[100px]"></div>
        </div>

        <div class="relative w-full max-w-[450px]">
            {{-- Header & Branding --}}
            <div class="text-center mb-10">
                <div class="inline-block p-3 rounded-2xl bg-white/5 border border-white/10 mb-4 shadow-2xl">
                    <x-authentication-card-logo class="w-16 h-16 shadow-[0_0_20px_rgba(212,175,55,0.4)]" />
                </div>
                <h1 class="text-3xl font-black text-white tracking-tighter uppercase">
                    Lab<span class="text-[#D4AF37]">Guard</span>
                </h1>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.4em] mt-2">Security Verification</p>
            </div>

            {{-- Main Glassmorphic Form Card --}}
            <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 shadow-[0_25px_50px_-12px_rgba(0,0,0,0.5)] p-8 overflow-hidden relative" x-data="{ recovery: false }">

                {{-- Top Gold Gradient Border Line --}}
                <div class="absolute top-0 left-0 w-full h-[2px] bg-gradient-to-r from-transparent via-[#D4AF37] to-transparent"></div>

                {{-- Helper Text Callout Box --}}
                <div class="p-4 rounded-2xl bg-black/40 border border-white/10 mb-6 shadow-inner">
                    <div class="flex items-start space-x-3">
                        <svg class="size-5 text-[#D4AF37] shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <div>
                            <p class="text-xs font-medium text-slate-300 leading-relaxed" x-show="! recovery">
                                {{ __('Please confirm access to your account by entering the authentication code provided by your authenticator application.') }}
                            </p>
                            <p class="text-xs font-medium text-slate-300 leading-relaxed" x-cloak x-show="recovery">
                                {{ __('Please confirm access to your account by entering one of your emergency recovery codes.') }}
                            </p>
                        </div>
                    </div>
                </div>

                <x-validation-errors class="mb-4 text-rose-400 text-xs font-bold uppercase tracking-wide" />

                <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-6">
                    @csrf

                    {{-- Authenticator Code Input --}}
                    <div class="space-y-2" x-show="! recovery">
                        <label for="code" class="text-[10px] font-black text-[#D4AF37] uppercase tracking-widest ml-1">{{ __('Authentication Code') }}</label>
                        <input id="code"
                            class="block w-full bg-black/40 border-white/10 rounded-xl text-white font-mono text-center tracking-[0.5em] text-xl py-4 px-5 focus:border-[#D4AF37] focus:ring-0 transition-all placeholder:text-slate-600 shadow-inner"
                            type="text"
                            inputmode="numeric"
                            name="code"
                            autofocus
                            x-ref="code"
                            autocomplete="one-time-code"
                            placeholder="••••••" />
                    </div>

                    {{-- Emergency Recovery Code Input --}}
                    <div class="space-y-2" x-cloak x-show="recovery">
                        <label for="recovery_code" class="text-[10px] font-black text-[#D4AF37] uppercase tracking-widest ml-1">{{ __('Recovery Code') }}</label>
                        <input id="recovery_code"
                            class="block w-full bg-black/40 border-white/10 rounded-xl text-white font-mono text-center tracking-widest text-sm py-4 px-5 focus:border-[#D4AF37] focus:ring-0 transition-all placeholder:text-slate-600 shadow-inner"
                            type="text"
                            name="recovery_code"
                            x-ref="recovery_code"
                            autocomplete="one-time-code"
                            placeholder="xxxx-xxxx-xxxx" />
                    </div>

                    {{-- Primary Authorize Button --}}
                    <button class="relative w-full group overflow-hidden rounded-xl bg-[#D4AF37] p-4 transition-all hover:bg-[#e6c152] active:scale-95 shadow-[0_10px_20px_-5px_rgba(212,175,55,0.4)]">
                        <span class="relative z-10 text-xs font-black text-[#0f172a] uppercase tracking-[0.3em]">{{ __('Authorize & Log In') }}</span>
                    </button>
                </form>

                {{-- Alternative Option Toggle Button --}}
                <div class="mt-8 pt-6 border-t border-white/10 text-center">
                    <button type="button"
                        class="inline-flex items-center justify-center w-full p-3 rounded-xl border border-[#D4AF37]/30 text-[#D4AF37] text-[10px] font-black uppercase tracking-[0.2em] hover:bg-[#D4AF37]/10 transition-all group"
                        x-show="! recovery"
                        x-on:click="
                            recovery = true;
                            $nextTick(() => { $refs.recovery_code.focus() })
                        ">
                        <span>{{ __('Use Emergency Recovery Code') }}</span>
                        <svg class="ms-2 size-3 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>

                    <button type="button"
                        class="inline-flex items-center justify-center w-full p-3 rounded-xl border border-[#D4AF37]/30 text-[#D4AF37] text-[10px] font-black uppercase tracking-[0.2em] hover:bg-[#D4AF37]/10 transition-all group"
                        x-cloak
                        x-show="recovery"
                        x-on:click="
                            recovery = false;
                            $nextTick(() => { $refs.code.focus() })
                        ">
                        <span>{{ __('Use Authenticator Code') }}</span>
                        <svg class="ms-2 size-3 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Footer Branding --}}
            <p class="mt-8 text-center text-[9px] text-slate-600 uppercase tracking-[0.5em] font-medium">
                Araullo University &bull; Computer Laboratory Management System
            </p>
        </div>
    </div>
</x-guest-layout>