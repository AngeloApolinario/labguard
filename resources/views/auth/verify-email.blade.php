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
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.4em] mt-2">Safe Computer Access</p>
            </div>

            <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 shadow-[0_25px_50px_-12px_rgba(0,0,0,0.5)] p-8 overflow-hidden relative">

                <div class="absolute top-0 left-0 w-full h-[2px] bg-gradient-to-r from-transparent via-[#D4AF37] to-transparent"></div>

                <div class="text-center mb-8">
                    <h2 class="text-[10px] font-black text-[#D4AF37] uppercase tracking-widest mb-4">Almost Ready</h2>
                    <p class="text-sm text-slate-300 font-medium leading-relaxed">
                        {{ __('To use the computers, please click the link in the email we just sent you.') }}
                    </p>
                </div>

                @if (session('status') == 'verification-link-sent')
                <div class="mb-6 font-bold text-xs text-green-400 uppercase tracking-widest text-center bg-green-400/10 py-2 rounded-lg">
                    {{ __('New email sent!') }}
                </div>
                @endif

                <div class="space-y-6">
                    <form method="POST" action="{{ route('verification.send') }}">
                        @csrf
                        <button type="submit" class="relative w-full group overflow-hidden rounded-xl bg-[#D4AF37] p-4 transition-all hover:bg-[#e6c152] active:scale-95 shadow-[0_10px_20px_-5px_rgba(212,175,55,0.4)]">
                            <span class="relative z-10 text-xs font-black text-[#0f172a] uppercase tracking-[0.3em]">
                                Send Email Again
                            </span>
                        </button>
                    </form>

                    <div class="flex items-center justify-between pt-6 border-t border-white/10">
                        <a href="{{ route('profile.show') }}" class="text-[10px] font-bold text-slate-500 hover:text-[#D4AF37] uppercase tracking-wider transition-colors">
                            {{ __('Change Details') }}
                        </a>

                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-[10px] font-bold text-slate-500 hover:text-rose-400 uppercase tracking-wider transition-colors">
                                {{ __('Sign Out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <p class="mt-8 text-center text-[9px] text-slate-600 uppercase tracking-[0.5em] font-medium leading-relaxed">
                Araullo University &bull; Computer Lab Management
            </p>
        </div>
    </div>
</x-guest-layout>