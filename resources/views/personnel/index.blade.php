<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-black text-4xl text-slate-800 tracking-tighter uppercase">
                    Facility <span class="text-[#D4AF37]">Selection</span>
                </h2>
                <div class="flex items-center space-x-2 mt-1">
                    <div class="size-2 bg-green-500 rounded-full animate-pulse"></div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">
                        Laboratory Overview
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                {{-- Master Schedule Link --}}
                <a href="{{ route('personnel.full-schedule') }}" class="px-5 py-3 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-[#D4AF37] transition-all flex items-center gap-3 shadow-lg shadow-slate-200">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2z" />
                    </svg>
                    Weekly Grid
                </a>

                {{-- Quick Summary Stats --}}
                <div class="flex items-center space-x-6 bg-white px-6 py-3 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="text-center">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Total Labs</p>
                        <p class="text-lg font-black text-slate-800">{{ $labs->count() }}</p>
                    </div>
                    <div class="w-px h-8 bg-slate-100"></div>
                    <div class="text-center">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Global Occupancy</p>
                        <p class="text-lg font-black text-[#D4AF37]">
                            {{ $labs->sum('occupied') }} <span class="text-slate-300 text-xs">/ {{ $labs->sum('total') }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12 px-6 min-h-screen relative overflow-hidden">

        {{-- High-End Ambient Background --}}
        <div class="absolute top-0 left-0 w-full h-full opacity-30 pointer-events-none">
            <div class="absolute top-[-5%] right-[-2%] w-[500px] h-[500px] bg-[#D4AF37]/10 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[5%] left-[-2%] w-[400px] h-[400px] bg-blue-100/50 rounded-full blur-[100px]"></div>
        </div>

        <div class="max-w-7xl mx-auto relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 items-stretch">
                @foreach($labs as $lab)
                @php
                $currentTime = now()->format('H:i:s');
                $currentDate = now()->toDateString();
                $currentDay = now()->format('l');
                $currentUser = auth()->user();
                $isAdmin = $currentUser && in_array($currentUser->role, ['admin', 'superadmin']);

                // 1. Check for one-time Event taking place TODAY during this time window
                $activeSched = $lab->schedules
                ->filter(function ($s) use ($currentDate, $currentTime) {
                return !empty($s->is_event)
                && !empty($s->event_date)
                && \Carbon\Carbon::parse($s->event_date)->toDateString() === $currentDate
                && $s->start_time <= $currentTime
                    && $s->end_time >= $currentTime;
                    })
                    ->first();

                    // 2. Fallback to regular recurring class
                    if (! $activeSched) {
                    $activeSched = $lab->schedules
                    ->filter(function ($s) use ($currentDay, $currentTime) {
                    return empty($s->is_event)
                    && $s->day === $currentDay
                    && $s->start_time <= $currentTime
                        && $s->end_time >= $currentTime;
                        })
                        ->first();
                        }

                        // 3. Strict Lock Check: Locked if active schedule belongs to another teacher/facilitator
                        $isLocked = false;
                        if ($activeSched) {
                        $isAssignedHost = $currentUser && $activeSched->user_id && ((int) $currentUser->id === (int) $activeSched->user_id);
                        $isLocked = !$isAssignedHost && !$isAdmin;
                        }

                        $isMaintenance = $lab->isUnderMaintenance();
                        $isUnavailable = $isLocked || $isMaintenance;
                        @endphp

                        {{-- Card Container with Fixed Dimensions & Disabled State Handling --}}
                        <div class="h-full {{ $isUnavailable ? 'cursor-not-allowed select-none' : '' }}">
                            <a href="{{ $isUnavailable ? 'javascript:void(0);' : route('personnel.lab.show', $lab->id) }}"
                                @if($isUnavailable) onclick="event.preventDefault(); return false;" tabindex="-1" aria-disabled="true" @endif
                                class="h-full flex flex-col {{ $isUnavailable ? 'pointer-events-none' : 'group cursor-pointer' }}">

                                <div class="h-full flex flex-col justify-between bg-white border {{ $isLocked ? 'border-slate-200/90' : 'border-slate-200/70 group-hover:border-[#D4AF37]/40 group-hover:shadow-[0_30px_70px_rgba(212,175,55,0.12)] group-hover:-translate-y-2' }} rounded-[2.8rem] p-7 transition-all duration-300 shadow-[0_10px_30px_rgba(0,0,0,0.02)]">

                                    <div>
                                        {{-- Card Header: Icon & Location --}}
                                        <div class="flex justify-between items-start mb-6">
                                            <div class="relative">
                                                <div class="size-14 rounded-2xl flex items-center justify-center border transition-all duration-300 {{ $isLocked ? 'bg-slate-50 text-slate-400 border-slate-100' : 'bg-slate-50 text-slate-600 border-slate-100 group-hover:bg-[#D4AF37] group-hover:text-white group-hover:border-[#D4AF37]' }}">
                                                    @if($isLocked)
                                                    <svg class="size-6 text-rose-500" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z" />
                                                    </svg>
                                                    @else
                                                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                    </svg>
                                                    @endif
                                                </div>
                                                @if($lab->occupied > 0 && !$isLocked)
                                                <div class="absolute -top-1 -right-1 size-3.5 bg-[#D4AF37] border-2 border-white rounded-full"></div>
                                                @endif
                                            </div>

                                            <div class="text-right">
                                                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest block">Location</span>
                                                <p class="text-xs font-black text-slate-700 truncate max-w-[130px]">{{ $lab->location ?? 'Main Campus' }}</p>
                                            </div>
                                        </div>

                                        {{-- Lab Name --}}
                                        <h3 class="text-2xl font-black text-slate-900 tracking-tight uppercase">
                                            {{ $lab->name }}
                                        </h3>

                                        {{-- UNIFORM HEIGHT SCHEDULE SECTION --}}
                                        <div class="min-h-[100px] mt-3 flex flex-col justify-start">
                                            @if($activeSched)
                                            @if($activeSched->is_event)
                                            {{-- Event Active --}}
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[8px] font-black uppercase tracking-wider bg-amber-50 text-amber-800 border border-amber-200/80">
                                                        <svg class="size-2.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 100-6 3 3 0 000 6z" />
                                                        </svg>
                                                        <span>Event &bull; {{ $activeSched->subject_code }}</span>
                                                    </span>
                                                </div>
                                                <p class="text-[11px] font-bold text-slate-700 truncate">
                                                    <span class="text-slate-400 font-semibold">Speaker:</span> {{ $activeSched->speaker_name ?? 'Guest Speaker' }}
                                                </p>
                                                <p class="text-[10px] font-bold {{ $isLocked ? 'text-rose-600' : 'text-[#856404]' }} truncate">
                                                    <span class="text-slate-400 font-semibold">Facilitator:</span> {{ $activeSched->user->name ?? 'Unassigned' }}
                                                </p>
                                            </div>
                                            @else
                                            {{-- Regular Class Active --}}
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[8px] font-black uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                                                        <svg class="size-2.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                                        </svg>
                                                        <span>Class &bull; {{ $activeSched->subject_code }}</span>
                                                    </span>
                                                </div>
                                                <p class="text-[11px] font-bold text-slate-700 truncate">
                                                    <span class="text-slate-400 font-semibold">Instructor:</span> {{ $activeSched->user->name ?? 'Assigned Faculty' }}
                                                </p>
                                                <p class="text-[10px] font-bold {{ $isLocked ? 'text-rose-600' : 'text-emerald-700' }} truncate">
                                                    <span class="text-slate-400 font-semibold">Status:</span> {{ $isLocked ? 'Reserved' : 'Your Scheduled Session' }}
                                                </p>
                                            </div>
                                            @endif
                                            @else
                                            {{-- No Active Schedule --}}
                                            <div class="space-y-1">
                                                <div class="flex items-center gap-1.5">
                                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[8px] font-black uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200/70">
                                                        <span class="size-1.5 rounded-full bg-emerald-500"></span>
                                                        <span>Available &bull; Open Lab</span>
                                                    </span>
                                                </div>
                                                <p class="text-[11px] font-bold text-slate-600">No active reservation</p>
                                                <p class="text-[10px] text-slate-400 font-semibold">Ready for open access</p>
                                            </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Card Footer: Occupancy & Action --}}
                                    <div class="mt-4 pt-4 border-t border-slate-100 space-y-4">
                                        {{-- Occupancy Visualizer --}}
                                        <div>
                                            <div class="flex justify-between text-[10px] font-black mb-2">
                                                <span class="text-slate-400 uppercase tracking-wider">Occupancy</span>
                                                <span class="text-slate-800 font-mono">{{ floor(($lab->occupied / max($lab->total, 1)) * 100) }}%</span>
                                            </div>
                                            <div class="h-2 w-full bg-slate-100 rounded-full overflow-hidden">
                                                <div class="h-full bg-[#D4AF37] rounded-full transition-all duration-700"
                                                    style="width: {{ ($lab->occupied / max($lab->total, 1)) * 100 }}%">
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Action Row --}}
                                        <div class="flex items-center justify-between pt-1">
                                            @if($isMaintenance)
                                            <span class="text-[10px] font-black text-rose-500 uppercase tracking-wider">Under Maintenance</span>
                                            <div class="size-9 bg-rose-50 text-rose-400 rounded-xl flex items-center justify-center border border-rose-100">
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                </svg>
                                            </div>
                                            @elseif($isLocked)
                                            <span class="text-[10px] font-black text-rose-500 uppercase tracking-widest">Locked</span>
                                            <div class="size-9 bg-rose-50 text-rose-500 rounded-xl flex items-center justify-center border border-rose-100 shadow-2xs">
                                                <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z" />
                                                </svg>
                                            </div>
                                            @else
                                            <span class="text-[10px] font-black text-[#D4AF37] uppercase tracking-wider group-hover:text-slate-900 transition-colors">
                                                {{ $activeSched && $activeSched->is_event ? 'Manage Event' : 'Enter Lab' }}
                                            </span>
                                            <div class="size-9 bg-slate-900 text-white group-hover:bg-[#D4AF37] group-hover:text-slate-950 rounded-xl flex items-center justify-center transition-all duration-200 shadow-xs">
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                </svg>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        @endforeach
            </div>
        </div>
    </div>
</x-app-layout>