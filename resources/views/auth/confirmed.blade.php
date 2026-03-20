<x-guest-layout>
    <div class="min-h-[400px] flex flex-col items-center justify-center p-8 bg-white shadow-xl rounded-2xl border border-slate-200">

        <div class="mb-6">
            <div class="bg-emerald-100 p-4 rounded-full">
                <svg class="w-12 h-12 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
        </div>

        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-slate-900 mb-2">
                Email Verified Successfully
            </h1>
            <p class="text-slate-500 text-sm max-w-xs mx-auto">
                Thanks, <span class="font-semibold text-slate-800">{{ Auth::user()->name }}</span>! Your account is now active and you have full access to the LabGuard system.
            </p>
        </div>

        <div class="w-full bg-slate-50 rounded-xl p-5 mb-8 border border-slate-100">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Account Details</span>
                <span class="flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-2 w-2 rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
            </div>

            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-slate-500">Access Level</span>
                    <span class="text-sm font-medium text-slate-700 capitalize">{{ Auth::user()->role }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-slate-500">System Status</span>
                    <span class="text-sm font-medium text-emerald-600">Active</span>
                </div>
            </div>
        </div>

        <div class="w-full space-y-3">
            <a href="{{ url('/') }}" class="w-full flex items-center justify-center py-3 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl transition-all shadow-lg">
                Go to Dashboard
            </a>

            <p class="text-center text-[11px] text-slate-400">
                Logged in as {{ Auth::user()->email }}
            </p>
        </div>
    </div>
</x-guest-layout>