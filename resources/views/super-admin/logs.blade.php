<x-app-layout>
    <div class="p-8 bg-[#f8fafc] min-h-screen">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">System Logs</h1>
                <p class="text-slate-500">Live stream of framework events and diagnostics</p>
            </div>
            <a href="{{ route('super-admin.index') }}" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold transition">
                Back to Dashboard
            </a>
        </div>

        <div class="bg-slate-900 text-slate-200 font-mono text-sm p-6 rounded-3xl shadow-inner border border-slate-800 max-h-[70vh] overflow-y-auto whitespace-pre-wrap">
            @forelse($rawLogs as $line)
            <div class="py-1 border-b border-slate-800/50 hover:bg-slate-800/30 transition {{ str_contains($line, '.ERROR') ? 'text-rose-400 font-semibold' : '' }}">
                {{ $line }}
            </div>
            @empty
            <div class="text-center py-12 text-slate-500">
                The system log file is currently empty or hasn't been generated yet.
            </div>
            @endforelse
        </div>
    </div>
</x-app-layout>