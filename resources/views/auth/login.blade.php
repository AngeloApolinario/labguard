<x-guest-layout>
    <div class="min-h-screen bg-[#0f172a] flex items-center justify-center p-6 relative overflow-hidden">

        <div class="absolute top-0 left-0 w-full h-full opacity-20 pointer-events-none">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-[#D4AF37] rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[30%] h-[30%] bg-indigo-600 rounded-full blur-[100px]"></div>
        </div>

        <div class="relative w-full max-w-[450px]">
            <div class="text-center mb-10">
                <div class="inline-block p-3 rounded-2xl bg-white/5 border border-white/10 mb-4 shadow-2xl">
                    <x-authentication-card-logo class="w-16 h-16 shadow-[0_0_20px_rgba(212,175,55,0.4)]" />
                </div>
                <h1 class="text-3xl font-black text-white tracking-tighter uppercase">
                    Lab<span class="text-[#D4AF37]">Guard</span>
                </h1>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.4em] mt-2">Secure Terminal Access</p>
            </div>

            <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 shadow-[0_25px_50px_-12px_rgba(0,0,0,0.5)] p-8 overflow-hidden relative">

                <div class="absolute top-0 left-0 w-full h-[2px] bg-gradient-to-r from-transparent via-[#D4AF37] to-transparent"></div>

                <x-validation-errors class="mb-4 text-rose-400 text-xs font-bold uppercase tracking-wide" />

                @session('status')
                <div class="mb-4 font-bold text-xs text-green-400 uppercase tracking-widest text-center">
                    {{ $value }}
                </div>
                @endsession

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <div class="space-y-2">
                        <label for="email" class="text-[10px] font-black text-[#D4AF37] uppercase tracking-widest ml-1">Email</label>
                        <input id="email"
                            class="block w-full bg-black/40 border-white/10 rounded-xl text-white text-sm py-4 px-5 focus:border-[#D4AF37] focus:ring-0 transition-all placeholder:text-slate-600 shadow-inner"
                            type="email" name="email" :value="old('email')"
                            placeholder="Email" required autofocus />
                    </div>

                    <div class="space-y-2">
                        <label for="password" class="text-[10px] font-black text-[#D4AF37] uppercase tracking-widest ml-1">Password</label>
                        <input id="password"
                            class="block w-full bg-black/40 border-white/10 rounded-xl text-white text-sm py-4 px-5 focus:border-[#D4AF37] focus:ring-0 transition-all placeholder:text-slate-600 shadow-inner"
                            type="password" name="password"
                            placeholder="••••••••" required />
                    </div>

                    <div class="flex items-center justify-between px-1">
                        <label for="remember_me" class="flex items-center cursor-pointer">
                            <x-checkbox id="remember_me" name="remember" class="bg-black/40 border-white/20 text-[#D4AF37] rounded focus:ring-0" />
                            <span class="ms-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ __('Keep session active') }}</span>
                        </label>

                        @if (Route::has('password.request'))
                        <a class="text-[10px] font-bold text-slate-500 hover:text-[#D4AF37] uppercase tracking-wider transition-colors" href="{{ route('password.request') }}">
                            {{ __('Reset Password') }}
                        </a>
                        @endif
                    </div>

                    <button class="relative w-full group overflow-hidden rounded-xl bg-[#D4AF37] p-4 transition-all hover:bg-[#e6c152] active:scale-95 shadow-[0_10px_20px_-5px_rgba(212,175,55,0.4)]">
                        <span class="relative z-10 text-xs font-black text-[#0f172a] uppercase tracking-[0.3em]">LOGIN</span>
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-white/10 text-center">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-4">New User?</p>
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center w-full p-3 rounded-xl border border-[#D4AF37]/30 text-[#D4AF37] text-[10px] font-black uppercase tracking-[0.2em] hover:bg-[#D4AF37]/10 transition-all group">
                        Request System Credentials
                        <svg class="ms-2 size-3 transform group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>

            <p class="mt-8 text-center text-[9px] text-slate-600 uppercase tracking-[0.5em] font-medium">
                Araullo University &bull; Computer Laboratory Management System
            </p>
        </div>
    </div>
</x-guest-layout>