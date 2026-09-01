{{-- Load SweetAlert2 script once at top --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Global Toast configuration for inline JS calls
    const LabGuardToast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        background: '#1e293b',
        color: '#ffffff',
        iconColor: '#D4AF37',
    });

    /**
     * Force release a terminal station asynchronously
     */
    function forceReleasePC(computerId) {
        const url = `/terminal/release/${computerId}`;

        fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    LabGuardToast.fire({
                        icon: 'success',
                        title: data.message
                    });
                    setTimeout(() => location.reload(), 1500);
                } else {
                    LabGuardToast.fire({
                        icon: 'warning',
                        title: data.message,
                        iconColor: '#f59e0b'
                    });
                }
            })
            .catch(() => {
                LabGuardToast.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: 'Could not communicate with the terminal.'
                });
            });
    }
</script>

<x-app-layout>
    <x-slot name="header">
        {{-- Responsive Header Header Container --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-6">
            <div>
                <h2 class="font-black text-2xl sm:text-3xl md:text-4xl text-slate-800 tracking-tighter uppercase">
                    Assigned <span class="text-[#D4AF37]">Facilities</span>
                </h2>
                <p class="text-[9px] sm:text-[10px] font-black text-slate-400 uppercase tracking-[0.25em] sm:tracking-[0.3em] mt-0.5 sm:mt-1">
                    Facilities overview
                </p>
            </div>

            {{-- Global Status Indicator --}}
            <div class="flex items-center justify-between sm:justify-end space-x-4 bg-white border border-slate-200/80 p-2 rounded-2xl shadow-sm self-start sm:self-auto w-full sm:w-auto">
                <div class="px-3 sm:px-4 py-1.5 sm:py-2 bg-emerald-500/10 rounded-xl border border-emerald-500/20">
                    <p class="text-[8px] font-black text-emerald-600 uppercase">Status</p>
                    <p class="text-xs font-black text-emerald-700 uppercase">Active</p>
                </div>
                <div class="pr-2 sm:pr-4">
                    <p class="text-[8px] font-black text-slate-400 uppercase text-right">Server Latency</p>
                    <p class="text-xs font-black text-slate-800 text-right">14ms</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8 md:py-12 px-4 sm:px-6 bg-[#F8FAFC] min-h-screen">
        <div class="max-w-7xl mx-auto space-y-6 sm:space-y-8">

            {{-- Quick Stats Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
                <div class="bg-slate-800 p-6 sm:p-8 rounded-3xl sm:rounded-[2.5rem] text-white shadow-xl relative overflow-hidden group">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Total Computers</p>
                    <h4 class="text-3xl sm:text-4xl font-black mt-2 italic">{{ $labs->sum('total') }}</h4>
                    <div class="absolute -right-4 -bottom-4 size-24 bg-white/5 rounded-full blur-2xl group-hover:bg-[#D4AF37]/10 transition-all pointer-events-none"></div>
                </div>

                <div class="bg-white p-6 sm:p-8 rounded-3xl sm:rounded-[2.5rem] border border-slate-200 shadow-sm">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Live Traffic</p>
                    <h4 class="text-3xl sm:text-4xl font-black text-slate-800 mt-2 italic">{{ $labs->sum('occupied') }}</h4>
                </div>

                <div class="bg-[#D4AF37] p-6 sm:p-8 rounded-3xl sm:rounded-[2.5rem] text-white shadow-lg shadow-[#D4AF37]/20 sm:col-span-2 lg:col-span-1">
                    <p class="text-[10px] font-black text-white/80 uppercase tracking-[0.2em]">Available Computers</p>
                    <h4 class="text-3xl sm:text-4xl font-black mt-2 italic">{{ $labs->sum('total') - $labs->sum('occupied') }}</h4>
                </div>
            </div>

            {{-- Detailed Lab Cards --}}
            <div class="space-y-4 sm:space-y-6">
                @foreach($labs as $lab)
                @php $isMaintenance = $lab->isUnderMaintenance(); @endphp
                <div class="bg-white rounded-3xl sm:rounded-[2.5rem] p-5 sm:p-8 border border-slate-200/80 shadow-sm hover:shadow-lg transition-all duration-300 group">
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 lg:gap-8">

                        {{-- Lab Identity --}}
                        <div class="flex items-center space-x-4 sm:space-x-6">
                            <div class="size-12 sm:size-16 shrink-0 bg-slate-50 rounded-2xl sm:rounded-3xl flex items-center justify-center border border-slate-100 group-hover:bg-[#D4AF37] transition-all duration-300">
                                <svg class="size-6 sm:size-8 text-slate-400 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-xl sm:text-2xl font-black text-slate-800 uppercase tracking-tighter truncate">{{ $lab->name }}</h3>
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Facility Terminal</p>
                            </div>
                        </div>

                        {{-- Progress Visualizer --}}
                        <div class="w-full lg:max-w-md">
                            <div class="flex justify-between text-[9px] sm:text-[10px] font-black uppercase mb-1.5">
                                <span class="text-slate-400 tracking-widest">Occupancy Rate</span>
                                <span class="text-slate-800">{{ $lab->occupied }} / {{ $lab->total }}</span>
                            </div>
                            <div class="h-2.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-slate-800 rounded-full transition-all duration-700 group-hover:bg-[#D4AF37]"
                                    style="width: {{ $lab->total > 0 ? ($lab->occupied / $lab->total) * 100 : 0 }}%"></div>
                            </div>
                        </div>

                        {{-- Action Button Container --}}
                        <div class="w-full lg:w-auto pt-2 lg:pt-0">
                            @if($isMaintenance)
                            <div class="w-full lg:w-auto justify-center px-5 py-3.5 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl text-[11px] font-black uppercase tracking-wider flex items-center gap-2.5">
                                <svg class="size-4 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3V6a3 3 0 10-6 0v2c0 1.657 1.343 3 3 3zM5 11v6a2 2 0 002 2h10a2 2 0 002-2v-6" />
                                </svg>
                                <span>Under Maintenance</span>
                            </div>
                            @else
                            <a href="{{ route('personnel.lab.show', $lab) }}"
                                class="w-full lg:w-auto justify-center flex items-center space-x-3 px-6 sm:px-8 py-3.5 sm:py-4 bg-slate-100 text-slate-700 rounded-2xl text-[10px] font-black uppercase tracking-widest group-hover:bg-slate-900 group-hover:text-white transition-all shadow-sm">
                                <span>Enter Facility</span>
                                <svg class="size-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                            @endif
                        </div>

                    </div>
                </div>
                @endforeach
            </div>

        </div>
    </div>

    {{-- Session Flash Notifications --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
            LabGuardToast.fire({
                icon: 'success',
                title: "{{ session('success') }}",
                iconColor: '#D4AF37'
            });
            @endif

            @if(session('error'))
            LabGuardToast.fire({
                icon: 'error',
                title: "{{ session('error') }}",
                iconColor: '#ef4444'
            });
            @endif
        });
    </script>
</x-app-layout>