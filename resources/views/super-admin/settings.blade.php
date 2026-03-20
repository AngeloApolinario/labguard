<x-app-layout>
    <div class="p-8 bg-[#f8fafc] min-h-screen">

        <div class="flex justify-between items-center mb-10">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">System Settings</h1>
                <p class="text-slate-500">Configure system-wide settings</p>
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

        <div class="bg-white p-10 rounded-3xl shadow-sm border border-slate-100 mb-8">
            <div class="flex items-center space-x-3 mb-8">
                <x-heroicon-o-bolt class="size-6 text-amber-500" />
                <h2 class="text-xl font-bold text-[#1e2945]">System Configuration</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <x-settings-input label="System Name" value="{{ $settings['system_name'] }}" />
                <x-settings-input label="Institution" value="{{ $settings['institution'] }}" />
                <x-settings-input label="Database Backup Time" value="{{ $settings['backup_time'] }}" type="time" icon="heroicon-o-clock" />
                <x-settings-input label="Session Timeout (minutes)" value="{{ $settings['session_timeout'] }}" type="number" />
                <div class="md:col-span-2">
                    <x-settings-input label="System Email" value="{{ $settings['system_email'] }}" type="email" />
                </div>
            </div>
        </div>

        <div class="bg-white p-10 rounded-3xl shadow-sm border border-slate-100">
            <div class="flex items-center space-x-3 mb-8">
                <x-heroicon-o-bell class="size-6 text-blue-600" />
                <h2 class="text-xl font-bold text-[#1e2945]">Notification Settings</h2>
            </div>

            <div class="space-y-4">
                <x-settings-toggle title="Security Alerts" desc="Receive alerts for security-related events" active="true" />
                <x-settings-toggle title="Equipment Issues" desc="Notify when equipment malfunction is detected" active="true" />
                <x-settings-toggle title="User Activities" desc="Log unusual user activities" active="true" />
                <x-settings-toggle title="System Maintenance" desc="Receive notifications before maintenance" active="true" />
                <x-settings-toggle title="Daily Reports" desc="Receive daily system activity reports" active="true" />
            </div>

            <div class="mt-10 flex justify-end">
                <button class="px-10 py-3 bg-[#D4AF37] text-white font-bold rounded-xl shadow-lg hover:bg-[#b8962d] transition">
                    Save Settings
                </button>
            </div>
        </div>

    </div>
</x-app-layout>