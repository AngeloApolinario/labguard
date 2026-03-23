<x-guest-layout>
    <div class="min-h-screen bg-slate-50 flex flex-col sm:justify-center items-center p-6">

        <div class="w-full sm:max-w-md px-10 py-12 bg-white shadow-[0_20px_50px_rgba(0,0,0,0.05)] border border-slate-100 sm:rounded-[3rem]">

            <div class="mb-10 text-center">
                <h2 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Update Credentials</h2>
                <div class="flex items-center justify-center gap-2 mt-2">
                    <div class="h-[1px] w-8 bg-slate-200"></div>
                    <p class="text-[10px] font-black text-[#D4AF37] uppercase tracking-[0.3em]">Identity Verification</p>
                    <div class="h-[1px] w-8 bg-slate-200"></div>
                </div>
            </div>

            <x-validation-errors class="mb-6 p-4 bg-red-50 rounded-2xl border-l-4 border-red-500" />

            <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="space-y-1">
                    <x-label for="email" value="{{ __('Registered Institutional Email') }}" class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest" />
                    <x-input id="email"
                        class="block mt-1 w-full rounded-2xl border-slate-200 bg-slate-100 text-slate-500 text-sm py-4 px-5 focus:ring-[#D4AF37] transition-all cursor-not-allowed"
                        type="email"
                        name="email"
                        :value="old('email', $request->email)"
                        required readonly />
                </div>

                <div class="space-y-1">
                    <x-label for="password" value="{{ __('New Secure Password') }}" class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest" />
                    <x-input id="password"
                        class="block mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 text-sm py-4 px-5 focus:ring-[#D4AF37] focus:border-[#D4AF37] transition-all"
                        type="password"
                        name="password"
                        placeholder="••••••••"
                        required autocomplete="new-password" />
                </div>

                <div class="space-y-1">
                    <x-label for="password_confirmation" value="{{ __('Verify New Password') }}" class="text-[10px] font-black text-slate-400 uppercase ml-1 tracking-widest" />
                    <x-input id="password_confirmation"
                        class="block mt-1 w-full rounded-2xl border-slate-200 bg-slate-50 text-sm py-4 px-5 focus:ring-[#D4AF37] focus:border-[#D4AF37] transition-all"
                        type="password"
                        name="password_confirmation"
                        placeholder="••••••••"
                        required autocomplete="new-password" />
                </div>

                <div class="pt-6">
                    <button type="submit" class="w-full py-4 bg-slate-900 text-white rounded-2xl text-xs font-black uppercase tracking-[0.2em] shadow-xl hover:bg-slate-800 hover:scale-[1.02] active:scale-95 transition-all">
                        {{ __('Update Password') }}
                    </button>
                </div>
            </form>
        </div>

        <p class="mt-8 text-[10px] font-black text-slate-300 uppercase tracking-[0.5em]">
            LabGuard | Secure Session Management
        </p>
    </div>
</x-guest-layout>