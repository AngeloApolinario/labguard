<x-app-layout>
    <div class="p-8 bg-[#f8fafc] min-h-screen">

        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">System Security</h1>
                <p class="text-slate-500">Monitor security threats and system integrity</p>
            </div>
            <div class="flex items-center space-x-4">
                <span class="flex items-center px-4 py-2 bg-green-100 text-green-700 rounded-full text-xs font-bold border border-green-200">
                    <span class="size-2 bg-green-500 rounded-full mr-2"></span> System Active
                </span>
                <div class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg text-xs font-bold uppercase tracking-widest flex items-center">
                    <x-heroicon-o-shield-check class="size-4 mr-2" /> Super Admin
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <x-security-stat-card label="Security Score" :value="$securityStats['score']" sub="Excellent security posture" icon="heroicon-o-shield-check" iconColor="text-green-500" />
            <x-security-stat-card label="Active Threats" :value="$securityStats['threats']" sub="Require immediate attention" icon="heroicon-o-exclamation-triangle" iconColor="text-rose-500" />
            <x-security-stat-card label="Vulnerabilities" :value="$securityStats['vulnerabilities']" sub="1 Critical, 4 Medium" icon="heroicon-o-briefcase" iconColor="text-blue-500" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 space-y-4">
                <h3 class="text-lg font-bold text-slate-800 mb-6 px-2">Security Alerts</h3>

                @foreach($alerts as $alert)
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm flex items-center justify-between">
                    <div class="flex items-start space-x-4">
                        <div class="p-3 {{ $alert['bgColor'] }} rounded-xl">
                            <x-dynamic-component :component="$alert['icon']" class="size-6 {{ $alert['iconColor'] }}" />
                        </div>
                        <div>
                            <div class="flex items-center space-x-3 mb-1">
                                <h4 class="font-bold text-slate-900">{{ $alert['title'] }}</h4>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-tighter 
                                    {{ $alert['type'] == 'critical' ? 'bg-rose-500 text-white' : '' }}
                                    {{ $alert['type'] == 'warning' ? 'bg-slate-200 text-slate-600' : '' }}
                                    {{ $alert['type'] == 'info' ? 'bg-slate-800 text-white' : '' }}">
                                    {{ $alert['badge'] }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 mb-2">{{ $alert['desc'] }}</p>
                            <span class="text-[10px] text-slate-400 font-medium">{{ $alert['time'] }}</span>
                        </div>
                    </div>
                    <button class="px-4 py-2 bg-[#D4AF37] hover:bg-amber-500 text-white text-xs font-black rounded-lg transition shadow-sm">
                        {{ $alert['action'] }}
                    </button>
                </div>
                @endforeach
            </div>

            <div class="space-y-6">
                <h3 class="text-lg font-bold text-slate-800 mb-6 px-2">Quick Actions</h3>
                <div class="flex flex-col space-y-3">
                    <x-security-action-btn label="View Audit Log" icon="heroicon-o-eye" />
                    <x-security-action-btn label="Reset Passwords" icon="heroicon-o-key" />
                    <x-security-action-btn label="Run Security Scan" icon="heroicon-o-magnifying-glass" />
                </div>
            </div>

            <div class="lg:col-span-3 bg-white p-10 rounded-3xl shadow-sm border border-slate-100 mt-4">
                <h3 class="text-lg font-bold text-slate-800 mb-8">Security Settings</h3>
                <div class="flex items-center justify-between p-6 bg-slate-50 rounded-2xl border border-slate-100">
                    <div>
                        <h4 class="font-bold text-slate-900">Two-Factor Authentication</h4>
                        <p class="text-sm text-slate-500 italic">Require 2FA for all user accounts</p>
                    </div>
                    <div class="w-12 h-6 bg-[#D4AF37] rounded-full relative shadow-inner">
                        <div class="absolute right-1 top-1 w-4 h-4 bg-white rounded-full"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>