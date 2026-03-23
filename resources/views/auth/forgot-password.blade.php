<x-guest-layout>
    <div class="min-h-screen bg-slate-50 flex flex-col sm:justify-center items-center pt-6 sm:pt-0">



        <div class="w-full sm:max-w-md mt-6 px-10 py-12 bg-white shadow-[0_20px_50px_rgba(0,0,0,0.05)] border border-slate-100 overflow-hidden sm:rounded-[3rem]">

            <div class="mb-8">
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Password Recovery</h2>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mt-1">LabGuard Security Protocol</p>
            </div>

            <div class="mb-6 text-xs font-bold text-slate-500 leading-relaxed uppercase tracking-wider">
                {{ __('Forgot your password? Enter your institutional email below. We will dispatch a secure reset link to your inbox.') }}
            </div>

            @session('status')
            <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 font-black text-[10px] text-green-700 uppercase tracking-widest animate-pulse">
                {{ $value }}
            </div>
            @endsession

            <x-validation-errors class="mb-6 p-4 bg-red-50 rounded-2xl" />

            <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
                @csrf

                <div class="space-y-1">
                    <x-label for="email" value="{{ __('Registered Email') }}" class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest" />
                    <x-input id="email"
                        class="block mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 text-sm py-4 px-5 focus:ring-[#D4AF37] focus:border-[#D4AF37] transition-all"
                        type="email"
                        name="email"
                        :value="old('email')"
                        placeholder="username@phinmaed.com"
                        required autofocus />
                </div>

                <div class="flex flex-col gap-4 pt-4">
                    <button class="w-full py-4 bg-slate-900 text-white rounded-2xl text-xs font-black uppercase tracking-[0.2em] shadow-xl hover:bg-slate-800 hover:scale-[1.02] active:scale-95 transition-all">
                        {{ __('Email Password Reset Link') }}
                    </button>

                    <a href="{{ route('login') }}" class="text-center text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-[#D4AF37] transition-colors">
                        Return to Secure Login
                    </a>
                </div>
            </form>
        </div>

        <p class="mt-8 text-[10px] font-black text-slate-300 uppercase tracking-[0.5em]">
            AU - Computer Laboratory Management
        </p>
    </div>
</x-guest-layout>