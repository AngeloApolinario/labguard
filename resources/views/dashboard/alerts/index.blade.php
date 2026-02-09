<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col">
            <h2 class="font-black text-4xl text-slate-800 tracking-tighter">
                Alerts <span class="text-[#D4AF37]">History</span>
            </h2>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.3em] mt-1">LabGuard</p>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.3em] mt-1"></p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto space-y-8">

            <div>
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center">
                    Today
                </h3>
                <div class="space-y-3">
                    <x-alert-row pc="PC 12" issue="Monitor - Missing Equipment" time="12:00 PM" level="Critical" color="red" />
                    <x-alert-row pc="Lab A" issue="Access Point - Unauthorized Access Attempt" time="10:30 AM" level="High" color="orange" />
                    <x-alert-row pc="Server Room" issue="Temperature Alert" time="9:15 AM" level="Medium" color="amber" />
                </div>
            </div>

            <div>
                <h3 class="text-lg font-bold text-slate-400 mb-4">Yesterday</h3>
                <div class="space-y-3 opacity-80">
                    <x-alert-row pc="PC 5" issue="Unexpected Restart" time="6:45 PM" level="Low" color="blue" />
                </div>
            </div>

        </div>
    </div>
</x-app-layout>