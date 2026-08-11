<x-guest-layout>
    <div class="min-h-screen bg-[#0f172a] flex items-center justify-center p-6 relative overflow-hidden"
        x-data="{ 
             sent: {{ session('status') == 'verification-link-sent' ? 'true' : 'false' }},
             cooldown: 0,
             timer: null,
             startCooldown(seconds) {
                 this.sent = true;
                 this.cooldown = seconds;
                 clearInterval(this.timer);
                 this.timer = setInterval(() => {
                     if (this.cooldown > 0) {
                         this.cooldown--;
                     } else {
                         clearInterval(this.timer);
                     }
                 }, 1000);
             }
         }"
        x-init="if (sent) startCooldown(60)">

        {{-- Ambient Background Glow --}}
        <div class="absolute top-0 left-0 w-full h-full opacity-20 pointer-events-none">
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-[#D4AF37] rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[30%] h-[30%] bg-indigo-600 rounded-full blur-[100px]"></div>
        </div>

        <div class="relative w-full max-w-[450px]">
            {{-- Branding Header --}}
            <div class="text-center mb-10">
                <div class="inline-block p-3 rounded-2xl bg-white/5 border border-white/10 mb-4 shadow-2xl">
                    <x-authentication-card-logo class="w-16 h-16 shadow-[0_0_20px_rgba(212,175,55,0.4)]" />
                </div>
                <h1 class="text-3xl font-black text-white tracking-tighter uppercase">
                    Lab<span class="text-[#D4AF37]">Guard</span>
                </h1>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.4em] mt-2">Safe Computer Access</p>
            </div>

            {{-- Main Verification Card --}}
            <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 shadow-[0_25px_50px_-12px_rgba(0,0,0,0.5)] p-8 overflow-hidden relative">

                <div class="absolute top-0 left-0 w-full h-[2px] bg-gradient-to-r from-transparent via-[#D4AF37] to-transparent"></div>

                <div class="text-center mb-8">
                    <h2 class="text-[10px] font-black text-[#D4AF37] uppercase tracking-widest mb-4">Email Verification Required</h2>
                    <p class="text-sm text-slate-300 font-medium leading-relaxed">
                        {{ __('To access the lab workstations, please request and click the secure verification link sent to your institutional email.') }}
                    </p>
                </div>

                {{-- Success Banner --}}
                @if (session('status') == 'verification-link-sent')
                <div class="mb-6 font-bold text-xs text-emerald-400 uppercase tracking-widest text-center bg-emerald-400/10 border border-emerald-400/20 py-3 rounded-xl flex items-center justify-center gap-2">
                    <svg class="size-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ __('Verification email dispatched!') }}</span>
                </div>
                @endif

                <div class="space-y-6">
                    <form method="POST" action="{{ route('verification.send') }}" @submit="startCooldown(60)">
                        @csrf
                        <button type="submit"
                            :disabled="cooldown > 0"
                            :class="cooldown > 0 ? 'bg-slate-700/60 text-slate-400 cursor-not-allowed shadow-none' : 'bg-[#D4AF37] hover:bg-[#e6c152] text-[#0f172a] active:scale-95 shadow-[0_10px_20px_-5px_rgba(212,175,55,0.4)]'"
                            class="relative w-full group overflow-hidden rounded-xl p-4 transition-all">

                            {{-- Initial State --}}
                            <span class="relative z-10 text-xs font-black uppercase tracking-[0.25em]" x-show="!sent">
                                Click Here to Send Email
                            </span>

                            {{-- Cooldown State --}}
                            <span class="relative z-10 text-xs font-black uppercase tracking-[0.2em] flex items-center justify-center gap-2" x-show="sent && cooldown > 0" x-cloak>
                                <svg class="size-3.5 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Try again in <span x-text="cooldown"></span>s</span>
                            </span>

                            {{-- Ready to Resend State --}}
                            <span class="relative z-10 text-xs font-black uppercase tracking-[0.2em]" x-show="sent && cooldown === 0" x-cloak>
                                Didn't receive an email? Try again
                            </span>
                        </button>
                    </form>

                    <div class="flex items-center justify-between pt-6 border-t border-white/10">
                        <a href="{{ route('profile.show') }}" class="text-[10px] font-bold text-slate-400 hover:text-[#D4AF37] uppercase tracking-wider transition-colors">
                            {{ __('Update User Information') }}
                        </a>

                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-[10px] font-bold text-slate-400 hover:text-rose-400 uppercase tracking-wider transition-colors">
                                {{ __('Sign Out') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <p class="mt-8 text-center text-[9px] text-slate-500 uppercase tracking-[0.5em] font-medium leading-relaxed">
                Araullo University &bull; Computer Lab Management
            </p>
        </div>
    </div>
</x-guest-layout>

{{-- Real-time cross-device verification polling --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const checkStatus = () => {
            fetch('/api/check-verification-status')
                .then(res => res.json())
                .then(data => {
                    if (data.verified) {
                        sessionStorage.setItem('just_verified', 'true');
                        window.location.href = "{{ route('profile.show') }}";
                    }
                })
                .catch(err => console.error(err));
        };

        // Poll every 2 seconds
        setInterval(checkStatus, 2000);
    });
</script>