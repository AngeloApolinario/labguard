{{-- SweetAlert2 for Cinematic Popups --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Create a reusable Gold/Slate Toast configuration
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
     * Call this function from any button to release a PC without leaving the page
     * Example: <button onclick="forceReleasePC(5)"> 
     */
    function forceReleasePC(computerId) {
        // Construct the URL dynamically
        const url = `/terminal/release/${computerId}`;

        fetch(url, {
                method: 'GET', // Or POST if you update your routes
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

                    // Refresh specific UI elements or reload after 1.5s
                    setTimeout(() => location.reload(), 1500);
                } else {
                    LabGuardToast.fire({
                        icon: 'warning',
                        title: data.message,
                        iconColor: '#f59e0b'
                    });
                }
            })
            .catch(error => {
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
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <h2 class="font-black text-4xl text-slate-800 tracking-tighter uppercase">
                    Assigned <span class="text-[#D4AF37]">Facilities</span>
                </h2>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mt-1">Facilities overview</p>
            </div>

            {{-- Global Status --}}
            <div class="flex items-center space-x-4 bg-white border border-slate-100 p-2 rounded-2xl shadow-sm">
                <div class="px-4 py-2 bg-green-500/10 rounded-xl border border-green-500/20">
                    <p class="text-[8px] font-black text-green-600 uppercase">Status</p>
                    <p class="text-xs font-black text-green-700 uppercase">Active</p>
                </div>
                <div class="pr-4">
                    <p class="text-[8px] font-black text-slate-400 uppercase text-right">Server Latency</p>
                    <p class="text-xs font-black text-slate-800 text-right">14ms</p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12 px-6 bg-[#F8FAFC] min-h-screen">
        <div class="max-w-7xl mx-auto">

            {{-- Quick Stats Row --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                <div class="bg-slate-800 p-8 rounded-[2.5rem] text-white shadow-2xl relative overflow-hidden group">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Total Computers</p>
                    <h4 class="text-4xl font-black mt-2 italic">{{ $labs->sum('total') }}</h4>
                    <div class="absolute -right-4 -bottom-4 size-24 bg-white/5 rounded-full blur-2xl group-hover:bg-[#D4AF37]/10 transition-all"></div>
                </div>

                <div class="bg-white p-8 rounded-[2.5rem] border border-slate-200 shadow-sm">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Live Traffic</p>
                    <h4 class="text-4xl font-black text-slate-800 mt-2 italic">{{ $labs->sum('occupied') }}</h4>
                </div>

                <div class="bg-[#D4AF37] p-8 rounded-[2.5rem] text-white shadow-xl shadow-[#D4AF37]/20">
                    <p class="text-[10px] font-black text-white/70 uppercase tracking-[0.2em]">Available Computers</p>
                    <h4 class="text-4xl font-black mt-2 italic">{{ $labs->sum('total') - $labs->sum('occupied') }}</h4>
                </div>
            </div>

            {{-- Detailed Lab Cards --}}
            <div class="space-y-6">
                @foreach($labs as $lab)
                @php $isMaintenance = $lab->isUnderMaintenance(); @endphp
                <div class="bg-white rounded-[2.5rem] p-8 border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-500 group">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-8">

                        {{-- Lab Identity --}}
                        <div class="flex items-center space-x-6 min-w-[250px]">
                            <div class="size-16 bg-slate-50 rounded-3xl flex items-center justify-center border border-slate-100 group-hover:bg-[#D4AF37] transition-all duration-500">
                                <svg class="size-8 text-slate-300 group-hover:text-white transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">{{ $lab->name }}</h3>
                                {{-- simplified subtitle --}}
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Facility</p>
                            </div>
                        </div>

                        {{-- Progress Visualizer --}}
                        <div class="flex-1 max-w-md">
                            <div class="flex justify-between text-[9px] font-black uppercase mb-2">
                                <span class="text-slate-400 tracking-widest">Occupancy</span>
                                <span class="text-slate-800">{{ $lab->occupied }} / {{ $lab->total }}</span>
                            </div>
                            <div class="h-2 w-full bg-slate-50 rounded-full overflow-hidden border border-slate-100">
                                <div class="h-full bg-slate-800 rounded-full transition-all duration-1000 group-hover:bg-[#D4AF37]"
                                    style="width: {{ $lab->total > 0 ? ($lab->occupied / $lab->total) * 100 : 0 }}%"></div>
                            </div>
                        </div>

                        {{-- Action Button --}}
                        <div>
                            @if($isMaintenance)
                            <div class="px-6 py-4 bg-rose-50 text-rose-700 rounded-2xl text-[12px] font-black uppercase tracking-widest flex items-center gap-3">
                                <svg class="w-5 h-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3V6a3 3 0 10-6 0v2c0 1.657 1.343 3 3 3zM5 11v6a2 2 0 002 2h10a2 2 0 002-2v-6" />
                                </svg>
                                This lab is currently under maintenance
                            </div>
                            @else
                            <a href="{{ route('personnel.lab.show', $lab) }}" class="flex items-center space-x-3 px-8 py-4 bg-slate-50 text-slate-600 rounded-2xl text-[10px] font-black uppercase tracking-widest group-hover:bg-slate-800 group-hover:text-white transition-all shadow-sm">
                                <span>Enter</span>
                                <svg class="size-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" />
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

    {{-- SCRIPTS FOR AJAX POPUPS --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configuration for the Gold/Slate Toast
            const LabGuardToast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                background: '#1e293b', // Slate-800
                color: '#ffffff',
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            // If you have a force-release button in THIS view, 
            // add a class like 'force-release-btn' to it.
            // This listener intercepts the click and handles the JSON response.
            document.querySelectorAll('.force-release-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = this.getAttribute('href') || this.dataset.url;

                    fetch(url, {
                            method: 'POST', // or GET depending on your route
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                LabGuardToast.fire({
                                    icon: 'success',
                                    title: data.message,
                                    iconColor: '#D4AF37' // Your signature Gold
                                });
                                // Optional: Refresh the page or update the UI live
                                setTimeout(() => location.reload(), 1500);
                            }
                        })
                        .catch(error => {
                            LabGuardToast.fire({
                                icon: 'error',
                                title: 'Communication Error',
                                text: 'Could not reach the terminal.'
                            });
                        });
                });
            });

            // Handle Flash Messages from Controller redirects
            @if(session('success'))
            LabGuardToast.fire({
                icon: 'success',
                title: "{{ session('success') }}",
                iconColor: '#D4AF37'
            });
            @endif
        });
    </script>
</x-app-layout>