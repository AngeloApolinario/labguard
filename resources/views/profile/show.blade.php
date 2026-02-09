<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-black text-2xl text-slate-800 tracking-tighter uppercase">
                    Personnel <span class="text-[#D4AF37]">File</span>
                </h2>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.4em] mt-1">Identity Management System</p>
            </div>
            {{-- Dynamic Clearance Badge --}}
            <div class="hidden md:block px-4 py-2 bg-white border border-slate-100 rounded-xl shadow-sm">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">
                    Clearance:
                    <span class="{{ Auth::user()->role === 'admin' ? 'text-[#D4AF37]' : 'text-cyan-500' }}">
                        {{ Auth::user()->role === 'admin' ? 'Admin' : 'Personnel' }}
                    </span>
                </span>
            </div>
        </div>
    </x-slot>

    <div class="min-h-screen bg-[#F8FAFC] relative pb-20">
        <div class="absolute top-0 left-0 w-full h-full opacity-40 pointer-events-none overflow-hidden">
            <div class="absolute top-[-10%] right-[-5%] w-[40%] h-[40%] bg-[#D4AF37]/10 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[10%] left-[-5%] w-[30%] h-[30%] bg-indigo-100 rounded-full blur-[100px]"></div>
        </div>

        <div class="max-w-7xl mx-auto py-12 sm:px-6 lg:px-8 relative z-10">

            <div class="space-y-16">

                @if (Laravel\Fortify\Features::canUpdateProfileInformation())
                <div class="bg-white rounded-[2.5rem] p-2 shadow-[0_20px_50px_rgba(0,0,0,0.04)] border border-white">
                    <div class="bg-slate-50/50 rounded-[2.2rem] p-8 border border-slate-100/50">
                        @livewire('profile.update-profile-information-form')
                    </div>
                </div>
                @endif

                @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                <div class="bg-white rounded-[2.5rem] p-2 shadow-[0_20px_50px_rgba(0,0,0,0.04)] border border-white">
                    <div class="bg-slate-50/50 rounded-[2.2rem] p-8 border border-slate-100/50">
                        @livewire('profile.update-password-form')
                    </div>
                </div>
                @endif

                @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <div class="bg-white rounded-[2.5rem] p-2 shadow-[0_20px_50px_rgba(0,0,0,0.04)] border border-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-[#D4AF37]/5 rounded-bl-full pointer-events-none"></div>

                    <div class="bg-slate-50/50 rounded-[2.2rem] p-8 border border-slate-100/50 relative z-10">
                        @livewire('profile.two-factor-authentication-form')
                    </div>
                </div>
                @endif

                <div class="bg-white rounded-[2.5rem] p-2 shadow-[0_20px_50px_rgba(0,0,0,0.04)] border border-white">
                    <div class="bg-slate-50/50 rounded-[2.2rem] p-8 border border-slate-100/50">
                        @livewire('profile.logout-other-browser-sessions-form')
                    </div>
                </div>

                @if (Laravel\Jetstream\Jetstream::hasAccountDeletionFeatures())
                <div class="mt-20">
                    <div class="flex items-center space-x-4 mb-6 px-8">
                        <div class="h-px flex-grow bg-slate-200"></div>
                        <span class="text-[10px] font-black text-rose-400 uppercase tracking-[0.4em]">Decommission Account</span>
                        <div class="h-px flex-grow bg-slate-200"></div>
                    </div>

                    <div class="bg-rose-50/30 rounded-[2.5rem] p-2 border border-rose-100">
                        <div class="bg-white rounded-[2.2rem] p-8 shadow-sm">
                            @livewire('profile.delete-user-form')
                        </div>
                    </div>
                </div>
                @endif

            </div>

            <div class="mt-24 text-center">
                <div class="inline-block px-6 py-2 bg-slate-100 rounded-full">
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.5em]">
                        LabGuard Digital Environment &bull; Secure Protocol
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>