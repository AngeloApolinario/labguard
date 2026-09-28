<div class="max-w-7xl mx-auto px-6 py-8">

    {{-- COMMAND CENTER HEADER --}}
    <div class="relative mb-12 overflow-hidden rounded-[2.5rem] bg-white border border-slate-100 shadow-2xl shadow-slate-200/50">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-rose-500 via-amber-400 to-emerald-500"></div>
        <div class="p-8 md:flex md:items-center md:justify-between">
            <div class="flex items-center gap-6">
                <div class="relative flex items-center justify-center size-16 bg-slate-50 rounded-2xl border border-slate-100 shadow-inner">
                    <svg class="size-8 text-slate-800" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <div class="absolute -top-1 -right-1 size-3 bg-emerald-500 rounded-full animate-pulse border-2 border-white"></div>
                </div>
                <div>
                    <h2 class="text-3xl font-black text-slate-900 uppercase tracking-tighter leading-none">
                        {{ $labName ?? ($lab->name ?? 'Laboratory') }}
                    </h2>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.3em] mt-1">Realtime Surveillance</p>
                </div>
            </div>

            <div class="mt-6 md:mt-0 flex flex-wrap items-center gap-3">
                {{-- Active PCs Counter --}}
                <div class="px-5 py-3 bg-slate-50 rounded-2xl border border-slate-100 flex flex-col items-center min-w-[90px]">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Active</span>
                    <span class="text-xl font-black text-rose-500">{{ $computers->where('status', 'active')->count() }}</span>
                </div>

                {{-- Clock --}}
                <div class="px-5 py-3 bg-slate-50 rounded-2xl border border-slate-100 flex flex-col items-center min-w-[90px]">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Time</span>
                    <span class="text-xl font-black text-slate-800 font-mono">{{ now()->format('h:i A') }}</span>
                </div>

                {{-- TERMINATE ALL ACTIVE WORKSTATIONS --}}
                @php
                $targetLabId = $lab->id ?? ($computers->first()->lab_id ?? 0);
                $activeCount = $computers->where('status', 'active')->count();
                @endphp

                @if($activeCount > 0)
                <button type="button"
                    onclick="confirmTerminateAll({{ $targetLabId }})"
                    class="px-5 py-3.5 bg-rose-500 hover:bg-rose-600 active:scale-95 text-white rounded-2xl border border-rose-600 text-[10px] font-black uppercase tracking-wider transition-all flex items-center gap-2 shadow-lg shadow-rose-500/20 cursor-pointer">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    <span>Terminate All ({{ $activeCount }})</span>
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- SURVEILLANCE GRID --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-6">
        @foreach($computers as $pc)
        @php
        $isActive = ($pc->status === 'active');
        $session = $isActive ? $pc->activeSession : null;
        $hasData = ($isActive && $session);
        $name = $hasData ? $session->student_name : null;
        $initial = $name ? strtoupper(substr($name, 0, 1)) : '?';
        @endphp

        <div class="group relative aspect-[4/5] bg-white rounded-[2rem] border transition-all duration-500 overflow-hidden
                {{ $hasData ? 'border-rose-100 shadow-xl shadow-rose-100/50' : 'border-slate-100 shadow-sm hover:border-emerald-200' }}">

            <div class="absolute inset-0 p-5 flex flex-col items-center justify-between z-10">
                <div class="w-full flex justify-between items-center">
                    <span class="text-[10px] font-black {{ $hasData ? 'text-rose-400' : 'text-slate-300' }} uppercase tracking-wider">
                        {{ $pc->pc_number }}
                    </span>
                    <div class="flex items-center gap-1.5">
                        <div class="size-1.5 rounded-full {{ $hasData ? 'bg-rose-500 animate-pulse' : 'bg-emerald-400' }}"></div>
                        <span class="text-[8px] font-bold {{ $hasData ? 'text-rose-500' : 'text-emerald-500' }} uppercase">
                            {{ $hasData ? 'LIVE' : 'READY' }}
                        </span>
                    </div>
                </div>

                <div class="relative">
                    @if($hasData)
                    <div class="size-20 rounded-[1.5rem] bg-gradient-to-br from-rose-500 to-rose-600 shadow-lg shadow-rose-200 flex items-center justify-center">
                        <span class="text-3xl font-black text-white">{{ $initial }}</span>
                    </div>
                    @else
                    <div class="size-20 rounded-[1.5rem] bg-slate-50 flex items-center justify-center border border-slate-100">
                        <svg class="size-8 text-slate-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    @endif
                </div>

                <div class="w-full text-center pb-2">
                    @if($hasData)
                    <p class="text-[11px] font-black text-slate-800 truncate uppercase">{{ $name }}</p>
                    <p class="text-[9px] font-bold text-slate-400 font-mono mt-0.5">{{ $session->student_id_number ?? $session->student_number }}</p>
                    @else
                    <p class="text-[9px] font-bold text-slate-300 uppercase tracking-widest">Available</p>
                    @endif
                </div>
            </div>

            {{-- HOVER DRAWER FOR ACTIVE SESSIONS --}}
            @if($hasData)
            <div class="absolute inset-x-0 bottom-0 bg-slate-900/95 backdrop-blur-md p-5 transform translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-out z-20">
                <div class="space-y-3 mb-5 text-left">
                    <div>
                        <p class="text-[8px] font-bold text-slate-500 uppercase tracking-widest">In-Time</p>
                        <p class="text-xs font-mono text-[#D4AF37]">{{ \Carbon\Carbon::parse($session->time_in)->format('h:i A') }}</p>
                    </div>
                </div>

                {{-- SINGLE FORCE RELEASE BUTTON --}}
                <button type="button"
                    onclick="confirmForceRelease({{ $pc->id }}, '{{ $pc->pc_number }}', '{{ addslashes($name) }}')"
                    class="w-full py-3 bg-white text-slate-900 text-[10px] font-black uppercase rounded-xl hover:bg-rose-500 hover:text-white transition-all shadow-md active:scale-95 cursor-pointer">
                    Force Release
                </button>
            </div>
            @endif
        </div>
        @endforeach
    </div>

    {{-- SWEETALERT2 & FETCH CONTROLS --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const LabGuardToast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true,
            background: '#1e293b',
            color: '#ffffff',
            iconColor: '#D4AF37',
        });

        let isModalOpen = false;

        // Auto-refresh surveillance grid every 10 seconds (pauses when modal is active)
        setInterval(() => {
            if (!isModalOpen) {
                location.reload();
            }
        }, 10000);

        // 1. Single PC Release Dialog
        function confirmForceRelease(pcId, pcNumber, studentName) {
            isModalOpen = true;
            Swal.fire({
                title: `TERMINATE ${pcNumber}?`,
                html: `This will immediately disconnect <strong class="text-amber-400">${studentName}</strong> and lock workstation <strong class="text-white">${pcNumber}</strong>.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#334155',
                confirmButtonText: 'Yes, Release',
                cancelButtonText: 'Cancel',
                background: '#0f172a',
                color: '#ffffff',
                iconColor: '#ef4444'
            }).then(async (result) => {
                isModalOpen = false;
                if (result.isConfirmed) {
                    await executeRequest(`/terminal/release/${pcId}`);
                }
            });
        }

        // 2. Terminate All PCs Dialog
        function confirmTerminateAll(labId) {
            isModalOpen = true;
            Swal.fire({
                title: 'TERMINATE ALL ACTIVE SESSIONS?',
                text: 'EMERGENCY PROTOCOL: This will immediately disconnect all students in this laboratory and lock every workstation.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#334155',
                confirmButtonText: 'Yes, Terminate All',
                cancelButtonText: 'Abort',
                background: '#0f172a',
                color: '#ffffff',
                iconColor: '#ef4444'
            }).then(async (result) => {
                isModalOpen = false;
                if (result.isConfirmed) {
                    await executeRequest(`/terminal/terminate-all/${labId}`);
                }
            });
        }

        // 3. Centralized Fetch Execution
        async function executeRequest(endpoint) {
            document.body.style.cursor = 'wait';
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok && (data.status === 'success' || data.status === 'warning')) {
                    LabGuardToast.fire({
                        icon: data.status === 'success' ? 'success' : 'warning',
                        title: data.message
                    });

                    // Fast refresh so the PC card immediately turns available
                    setTimeout(() => location.reload(), 1000);
                } else {
                    LabGuardToast.fire({
                        icon: data.status === 'info' ? 'info' : 'error',
                        title: data.message || 'Operation could not be processed.'
                    });
                }
            } catch (error) {
                LabGuardToast.fire({
                    icon: 'error',
                    title: 'System Communication Error'
                });
            } finally {
                document.body.style.cursor = 'default';
            }
        }
    </script>
</div>