<x-guest-layout>
    <div class="min-h-screen bg-[#0f172a] flex items-center justify-center p-6 relative overflow-hidden">

        <div class="absolute top-0 left-0 w-full h-full opacity-20 pointer-events-none">
            <div class="absolute top-[-10%] right-[-10%] w-[40%] h-[40%] bg-[#D4AF37] rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[-10%] left-[-10%] w-[30%] h-[30%] bg-indigo-600 rounded-full blur-[100px]"></div>
        </div>

        <div class="relative w-full max-w-[500px]">
            <div class="text-center mb-8">
                <div class="inline-block p-3 rounded-2xl bg-white/5 border border-white/10 mb-4 shadow-2xl">
                    <x-authentication-card-logo class="w-14 h-14 shadow-[0_0_20px_rgba(212,175,55,0.3)]" />
                </div>
                <h1 class="text-3xl font-black text-white tracking-tighter uppercase">
                    Lab<span class="text-[#D4AF37]">Guard</span>
                </h1>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.4em] mt-2">Personnel Registration Portal</p>
            </div>

            <div class="bg-white/5 backdrop-blur-xl rounded-3xl border border-white/10 shadow-[0_25px_50px_-12px_rgba(0,0,0,0.5)] p-8 overflow-hidden relative">

                <div class="absolute top-0 left-0 w-full h-[2px] bg-gradient-to-r from-transparent via-[#D4AF37] to-transparent"></div>

                <x-validation-errors class="mb-4 text-rose-400 text-xs font-bold uppercase tracking-wide" />

                <form method="POST" action="{{ route('register') }}" class="space-y-5">
                    @csrf

                    <div class="space-y-1">
                        <label for="name" class="text-[10px] font-black text-[#D4AF37] uppercase tracking-widest ml-1">Legal Identity</label>
                        <input id="name" class="block w-full bg-black/40 border-white/10 rounded-xl text-white text-sm py-3.5 px-5 focus:border-[#D4AF37] focus:ring-0 transition-all placeholder:text-slate-600" type="text" name="name" :value="old('name')" placeholder="Full Name" required autofocus autocomplete="name" />
                    </div>

                    <div class="space-y-1">
                        <label for="email" class="text-[10px] font-black text-[#D4AF37] uppercase tracking-widest ml-1">System Email</label>
                        <input id="email" class="block w-full bg-black/40 border-white/10 rounded-xl text-white text-sm py-3.5 px-5 focus:border-[#D4AF37] focus:ring-0 transition-all placeholder:text-slate-600" type="email" name="email" :value="old('email')" placeholder="Email Address" required autocomplete="username" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label for="student_number" class="text-[10px] font-black text-[#D4AF37] uppercase tracking-widest ml-1">Student ID</label>
                            <input id="student_number" class="block w-full bg-black/40 border-white/10 rounded-xl text-white text-sm py-3.5 px-5 focus:border-[#D4AF37] focus:ring-0 transition-all placeholder:text-slate-600" type="text" name="student_number" :value="old('student_number')" placeholder="01-2324-048389" required />
                        </div>

                        <div class="space-y-1">
                            <label for="phone" class="text-[10px] font-black text-[#D4AF37] uppercase tracking-widest ml-1">Contact No.</label>
                            <input id="phone" class="block w-full bg-black/40 border-white/10 rounded-xl text-white text-sm py-3.5 px-5 focus:border-[#D4AF37] focus:ring-0 transition-all placeholder:text-slate-600" type="text" name="phone" :value="old('phone')" placeholder="09XXXXXXXXX" required />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label for="password" class="text-[10px] font-black text-[#D4AF37] uppercase tracking-widest ml-1">New Password</label>
                            <input id="password" class="block w-full bg-black/40 border-white/10 rounded-xl text-white text-sm py-3.5 px-5 focus:border-[#D4AF37] focus:ring-0 transition-all placeholder:text-slate-600" type="password" name="password" placeholder="••••••••" required autocomplete="new-password" />
                        </div>

                        <div class="space-y-1">
                            <label for="password_confirmation" class="text-[10px] font-black text-[#D4AF37] uppercase tracking-widest ml-1">Confirm</label>
                            <input id="password_confirmation" class="block w-full bg-black/40 border-white/10 rounded-xl text-white text-sm py-3.5 px-5 focus:border-[#D4AF37] focus:ring-0 transition-all placeholder:text-slate-600" type="password" name="password_confirmation" placeholder="••••••••" required autocomplete="new-password" />
                        </div>
                    </div>

                    @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
                    <div class="mt-4">
                        <label for="terms" class="flex items-center cursor-pointer">
                            <x-checkbox name="terms" id="terms" required class="bg-black/40 border-white/20 text-[#D4AF37] rounded focus:ring-0" />
                            <div class="ms-3 text-[10px] font-bold text-slate-400 uppercase tracking-wider leading-relaxed">
                                {!! __('I accept the :terms_of_service and :privacy_policy', [
                                'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'" class="text-[#D4AF37] hover:underline transition-all">'.__('Terms').'</a>',
                                'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="text-[#D4AF37] hover:underline transition-all">'.__('Privacy Policy').'</a>',
                                ]) !!}
                            </div>
                        </label>
                    </div>
                    @endif

                    <div class="flex flex-col space-y-4 pt-2">
                        <button class="relative w-full group overflow-hidden rounded-xl bg-[#D4AF37] p-4 transition-all hover:bg-[#e6c152] active:scale-95 shadow-[0_10px_20px_-5px_rgba(212,175,55,0.4)]">
                            <span class="relative z-10 text-xs font-black text-[#0f172a] uppercase tracking-[0.3em]">Authorize Enrollment</span>
                        </button>

                        <a class="text-center text-[10px] font-bold text-slate-500 hover:text-[#D4AF37] uppercase tracking-[0.2em] transition-colors" href="{{ route('login') }}">
                            {{ __('Already registered? Login') }}
                        </a>
                    </div>
                </form>
            </div>

            <p class="mt-8 text-center text-[9px] text-slate-600 uppercase tracking-[0.5em] font-medium">
                ARAULLO UNIVERSITY &bull; COMPUTER LABORATORY MANAGEMENT SYSTEM
            </p>
        </div>
    </div>
</x-guest-layout>