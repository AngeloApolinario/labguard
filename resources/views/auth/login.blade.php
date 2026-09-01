<x-guest-layout>
    {{-- Convert Laravel validation errors into a Danger Toast --}}
    @if ($errors->any())
    @php
    session()->now('toast', [
    'type' => 'danger',
    'title' => 'Authentication Error',
    'message' => $errors->first()
    ]);
    @endphp
    @endif

    {{-- Convert session status (e.g., password reset confirmation) into a Success Toast --}}
    @if (session('status'))
    @php
    session()->now('toast', [
    'type' => 'success',
    'title' => 'System Notice',
    'message' => session('status')
    ]);
    @endphp
    @endif

    {{-- Render Toast Component --}}
    <x-toast />

    <!-- Include Cloudflare Turnstile Script -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

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
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.4em] mt-2">Secure Laboratory Access</p>
            </div>

            <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 shadow-[0_25px_50px_-12px_rgba(0,0,0,0.5)] p-8 overflow-hidden relative">

                <div class="absolute top-0 left-0 w-full h-[2px] bg-gradient-to-r from-transparent via-[#D4AF37] to-transparent"></div>

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    <div class="space-y-2">
                        <label for="email" class="text-[10px] font-black text-[#D4AF37] uppercase tracking-widest ml-1">Email</label>
                        <input id="email"
                            class="block w-full bg-black/40 border-white/10 rounded-xl text-white text-sm py-4 px-5 focus:border-[#D4AF37] focus:ring-0 transition-all placeholder:text-slate-600 shadow-inner"
                            type="email" name="email" :value="old('email')"
                            placeholder="Email" required autofocus />
                    </div>

                    <div class="space-y-2" x-data="{ show: false }">
                        <label for="password" class="text-[10px] font-black text-[#D4AF37] uppercase tracking-widest ml-1">Password</label>

                        <div class="relative group">
                            <input id="password"
                                class="block w-full bg-black/40 border-white/10 rounded-xl text-white text-sm py-4 px-5 pr-12 focus:border-[#D4AF37] focus:ring-0 transition-all placeholder:text-slate-600 shadow-inner"
                                :type="show ? 'text' : 'password'"
                                name="password"
                                placeholder="••••••••" required />

                            <button type="button"
                                @click="show = !show"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 hover:text-[#D4AF37] transition-colors focus:outline-none">

                                <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>

                                <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
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

                    <!-- Cloudflare Turnstile CAPTCHA Widget -->
                    <div class="flex justify-center my-4">
                        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-theme="dark"></div>
                    </div>

                    <button class="relative w-full group overflow-hidden rounded-xl bg-[#D4AF37] p-4 transition-all hover:bg-[#e6c152] active:scale-95 shadow-[0_10px_20px_-5px_rgba(212,175,55,0.4)]">
                        <span class="relative z-10 text-xs font-black text-[#0f172a] uppercase tracking-[0.3em]">LOGIN</span>
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-white/10 text-center">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-4">New User?</p>
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center w-full p-3 rounded-xl border border-[#D4AF37]/30 text-[#D4AF37] text-[10px] font-black uppercase tracking-[0.2em] hover:bg-[#D4AF37]/10 transition-all group">
                        Create New Account
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

    {{-- Auto-reset Turnstile on validation error --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if($errors - > any())
            if (typeof turnstile !== 'undefined') {
                turnstile.reset();
            }
            @endif
        });
    </script>
</x-guest-layout>