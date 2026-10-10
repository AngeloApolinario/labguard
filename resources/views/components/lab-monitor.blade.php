@php
$myBannedApps = $myBannedApps
?? (class_exists(\App\Models\BannedApp::class)
? \App\Models\BannedApp::where('user_id', auth()->id())->latest()->get()
: collect());

$targetLabId = $lab->id ?? ($computers->first()->lab_id ?? 0);
$targetLabName = $labName ?? ($lab->name ?? 'Laboratory');
$activeCount = $computers->where('status', 'active')->count();
$totalCount = $computers->count();
$availableCount = $totalCount - $activeCount;
@endphp

<div wire:poll.4s>
    {{-- Toast Component --}}
    <x-toast />

    <style>
        [x-cloak] {
            display: none !important;
        }

        .lg-panel {
            background: #fff;
            border: 1px solid #e8e4d7;
            box-shadow: 0 12px 35px rgb(30 41 59 / .045);
        }

        .lg-gold {
            color: #a17c16;
        }

        .lg-gold-button {
            background: linear-gradient(135deg, #f4d779, #d4af37 55%, #bd941f);
            color: #211b0c;
            border: 1px solid #c9a32e;
        }

        .lg-gold-button:hover {
            filter: brightness(1.035);
        }

        .lg-white-button {
            background: #fff;
            color: #334155;
            border: 1px solid #e2e8f0;
        }

        .lg-white-button:hover {
            background: #faf8f0;
            border-color: #d4af37;
            color: #8a6a0a;
        }

        .lg-dark-button {
            background: #20252d;
            color: #f4d779;
            border: 1px solid #343a44;
        }

        .lg-dark-button:hover {
            background: #303641;
        }

        .lg-modal {
            background: #fff;
            border: 1px solid #e8e0c8;
            box-shadow: 0 28px 90px rgb(0 0 0 / .28);
        }

        .lg-field {
            width: 100%;
            border: 1px solid #e2e8f0;
            background: #fafaf9;
            color: #1e293b;
            border-radius: 12px;
            padding: 11px 13px;
            font-size: 12px;
            font-weight: 600;
            outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        .lg-field:focus {
            background: #fff;
            border-color: #d4af37;
            box-shadow: 0 0 0 3px rgb(212 175 55 / .15);
        }

        .lg-field::placeholder {
            color: #a1a1aa;
            font-weight: 500;
        }

        .lg-computer-card {
            border: 1px solid #e8eaf0;
            background: #fff;
            transition: border-color .18s, box-shadow .18s, transform .18s;
        }

        .lg-computer-card:hover {
            border-color: #d4af37;
            box-shadow: 0 10px 25px rgb(30 41 59 / .07);
            transform: translateY(-1px);
        }

        .lg-icon-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            min-width: 34px;
            border-radius: 9px;
            transition: .18s;
        }

        .lg-menu-item {
            display: flex;
            width: 100%;
            align-items: center;
            gap: .65rem;
            border-radius: .65rem;
            padding: .65rem .75rem;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
        }

        .lg-menu-item:hover {
            background: #faf8f0;
            color: #8a6a0a;
        }

        .lg-filter {
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #64748b;
            border-radius: 999px;
            padding: .45rem .8rem;
            font-size: 11px;
            font-weight: 800;
            transition: .18s;
        }

        .lg-filter[aria-pressed="true"] {
            border-color: #d4af37;
            background: #fcf7e6;
            color: #8a6a0a;
        }

        @keyframes lgMarquee {
            0% {
                transform: translateX(0%);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        .lg-marquee-track {
            display: inline-flex;
            width: max-content;
            animation: lgMarquee 11s linear infinite;
        }

        .lg-marquee-track:hover {
            animation-play-state: paused;
        }

        .lg-marquee-mask {
            mask-image: linear-gradient(90deg, transparent 0%, #000 6%, #000 94%, transparent 100%);
            -webkit-mask-image: linear-gradient(90deg, transparent 0%, #000 6%, #000 94%, transparent 100%);
        }

        @media (prefers-reduced-motion: reduce) {

            .lg-computer-card,
            .lg-marquee-track {
                transition: none;
                animation: none;
            }
        }
    </style>

    {{-- COMMAND CENTER ROOT --}}
    <div id="lab-command-center"
        class="mx-auto max-w-7xl px-3 py-5 sm:px-6 sm:py-8"
        x-data="labCommandCenter()"
        @keydown.escape.window="closeAllModals()"
        @click.outside="labActionsOpen = false">

        {{-- COMMAND CENTER HEADER --}}
        <section class="lg-panel relative mb-6 overflow-visible rounded-2xl sm:rounded-3xl">
            <div class="h-1.5 rounded-t-2xl bg-gradient-to-r from-[#9e7914] via-[#f0d779] to-[#d4af37] sm:rounded-t-3xl"></div>
            <div class="p-5 sm:p-7">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl border border-[#eadba8] bg-[#fbf7e8] sm:size-14">
                            <svg class="size-6 text-[#a17c16] sm:size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9.75 17 9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="mb-1 text-[9px] font-black uppercase tracking-[.22em] text-[#a17c16]">LabGuard / Remote Operations</p>
                            <h2 class="truncate text-xl font-black tracking-tight text-slate-900 sm:text-3xl">{{ $targetLabName }}</h2>
                            <p class="mt-1 text-xs text-slate-500">Monitor workstations and manage laboratory operations.</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex min-w-[88px] items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                            <span class="flex size-8 items-center justify-center rounded-lg bg-white text-slate-500">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 4h18v13H3zM8 21h8m-4-4v4" />
                                </svg>
                            </span>
                            <span><span class="block text-[9px] font-bold uppercase tracking-wider text-slate-500">Workstations</span><strong class="text-lg font-black text-slate-900">{{ $totalCount }}</strong></span>
                        </div>
                        <div class="flex min-w-[88px] items-center gap-3 rounded-xl border border-emerald-100 bg-emerald-50/70 px-3 py-2.5">
                            <span class="flex size-8 items-center justify-center rounded-lg bg-white text-emerald-600">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 12 4 4L19 6" />
                                </svg>
                            </span>
                            <span><span class="block text-[9px] font-bold uppercase tracking-wider text-emerald-700">Available</span><strong class="text-lg font-black text-emerald-800">{{ $availableCount }}</strong></span>
                        </div>
                        <div class="flex min-w-[88px] items-center gap-3 rounded-xl border border-rose-100 bg-rose-50/70 px-3 py-2.5">
                            <span class="flex size-8 items-center justify-center rounded-lg bg-white text-rose-600">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <circle cx="12" cy="12" r="8.5" stroke-width="1.8" />
                                    <path stroke-linecap="round" stroke-width="1.8" d="M12 7v5l3 2" />
                                </svg>
                            </span>
                            <span><span class="block text-[9px] font-bold uppercase tracking-wider text-rose-700">In Use</span><strong class="text-lg font-black text-rose-800">{{ $activeCount }}</strong></span>
                        </div>

                        <div class="relative" @click.outside="labActionsOpen = false">
                            <button type="button" @click="labActionsOpen = !labActionsOpen"
                                :aria-expanded="labActionsOpen.toString()"
                                class="lg-gold-button inline-flex min-h-[44px] items-center gap-2 rounded-xl px-4 py-3 text-[10px] font-black uppercase tracking-wide shadow-sm">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v12m-6-6h12" />
                                </svg>
                                Lab Actions
                                <svg class="size-3.5 transition-transform" :class="labActionsOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                            <div x-show="labActionsOpen" x-cloak x-transition.origin.top.right
                                class="absolute right-0 z-50 mt-2 w-64 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl">
                                <p class="px-3 pb-2 pt-1 text-[9px] font-black uppercase tracking-[.18em] text-slate-400">Communication</p>
                                <button type="button" class="lg-menu-item" @click="broadcastAllLabs = true; broadcastTarget = 'all'; broadcastModal = true; labActionsOpen = false">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-[#fcf7e6] text-[#98720c]"><svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M11 5.882V19.24a1.76 1.76 0 0 1-3.417.592l-2.147-6.15M18 13a3 3 0 1 0 0-6M5.436 13.683A4.001 4.001 0 0 1 7 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 0 1-1.564-.317Z" />
                                        </svg></span>
                                    <span><span class="block">Broadcast Message</span><span class="mt-0.5 block text-[10px] font-medium text-slate-400">Send an announcement</span></span>
                                </button>
                                <button type="button" class="lg-menu-item" @click="pushUrlAllLabs = true; pushUrlTarget = 'all'; pushUrlModal = true; labActionsOpen = false">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600"><svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                                        </svg></span>
                                    <span><span class="block">Push Classroom URL</span><span class="mt-0.5 block text-[10px] font-medium text-slate-400">Open a link on selected PCs</span></span>
                                </button>
                                <div class="my-2 border-t border-slate-100"></div>
                                <p class="px-3 pb-2 text-[9px] font-black uppercase tracking-[.18em] text-slate-400">Management</p>
                                <button type="button" class="lg-menu-item" @click="bannedAppsModal = true; labActionsOpen = false">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-slate-800 text-[#f4d779]"><svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <circle cx="12" cy="12" r="9" stroke-width="1.8" />
                                            <path stroke-linecap="round" stroke-width="1.8" d="m5.6 5.6 12.8 12.8" />
                                        </svg></span>
                                    <span class="flex-1">Application Restrictions</span>
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[10px]" x-text="typeof bannedAppsList !== 'undefined' ? bannedAppsList.length : {{ $myBannedApps->count() }}"></span>
                                </button>
                                <button type="button" class="lg-menu-item text-rose-700 hover:bg-rose-50 hover:text-rose-800" @click="shutdownAllLabs = true; shutdownTarget = 'all'; shutdownModal = true; labActionsOpen = false">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600"><svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 2v10m6.36-6.36a9 9 0 1 1-12.72 0" />
                                        </svg></span>
                                    <span><span class="block">Shutdown Workstations</span><span class="mt-0.5 block text-[10px] font-medium text-rose-400">Potentially interrupts active work</span></span>
                                </button>
                                @if($activeCount > 0)
                                <div class="my-2 border-t border-slate-100"></div>
                                <button type="button" class="lg-menu-item text-rose-700 hover:bg-rose-50 hover:text-rose-800"
                                    @click="labActionsOpen = false; confirmTerminateAll({{ $targetLabId }})">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-rose-100 text-rose-700"><svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                                        </svg></span>
                                    <span><span class="block">Terminate All Sessions</span><span class="mt-0.5 block text-[10px] font-medium text-rose-400">{{ $activeCount }} active session(s)</span></span>
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- WORKSTATION CONTROLS --}}
        <section class="mb-4">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-sm font-black tracking-tight text-slate-900 sm:text-base">Workstations</h3>
                    <p class="mt-1 text-xs text-slate-500">Search the lab or filter computers by current status.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-[10px] font-semibold text-slate-500">
                    <span class="inline-flex items-center gap-1.5"><span class="size-2 rounded-full bg-rose-500"></span>In use</span>
                    <span class="inline-flex items-center gap-1.5"><span class="size-2 rounded-full bg-emerald-500"></span>Available</span>
                    <span class="ml-1 rounded-lg border border-slate-200 bg-white px-3 py-2">{{ $totalCount }} registered</span>
                </div>
            </div>

            <div class="lg-panel mb-4 flex flex-col gap-3 rounded-2xl p-3 sm:flex-row sm:items-center sm:p-4">
                <div class="relative min-w-0 flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400 z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <circle cx="11" cy="11" r="7" stroke-width="1.8" />
                        <path stroke-linecap="round" stroke-width="1.8" d="m20 20-4-4" />
                    </svg>
                    <input type="search" x-model="searchQuery" placeholder="Search PC number, student, or application..." class="lg-field" style="padding-left: 2.35rem !important;">
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="lg-filter" :aria-pressed="(statusFilter === 'all').toString()" @click="statusFilter = 'all'">All PCs <span class="ml-1 text-slate-400">{{ $totalCount }}</span></button>
                    <button type="button" class="lg-filter" :aria-pressed="(statusFilter === 'active').toString()" @click="statusFilter = 'active'">In Use <span class="ml-1 text-slate-400">{{ $activeCount }}</span></button>
                    <button type="button" class="lg-filter" :aria-pressed="(statusFilter === 'available').toString()" @click="statusFilter = 'available'">Available <span class="ml-1 text-slate-400">{{ $availableCount }}</span></button>
                </div>
            </div>
        </section>

        {{-- WORKSTATION GRID --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse($computers as $pc)
            @php
            $isActive = $pc->status === 'active';
            $session = $isActive ? $pc->activeSession : null;
            $hasData = $isActive && $session;
            $name = $hasData ? $session->student_name : null;
            $initial = $name ? strtoupper(substr($name, 0, 1)) : '?';
            $rawApp = $pc->active_app ?? ($session->active_app ?? null);
            $activeApp = $rawApp ? trim(urldecode($rawApp)) : null;
            if ($activeApp === 'Desktop' || empty($activeApp)) { $activeApp = 'Desktop'; }
            $pcStatus = $isActive ? 'active' : 'available';
            $isLongApp = mb_strlen($activeApp) > 22;
            @endphp

            <article class="lg-computer-card relative rounded-2xl p-4"
                x-show="matchesPc(@js($pc->pc_number), @js($pcStatus), @js($name ?? ''), @js($activeApp ?? ''))"
                x-transition.opacity>
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl {{ $hasData ? 'border border-[#eadba8] bg-[#fbf4d9] text-[#8a6a0a]' : 'border border-slate-200 bg-slate-50 text-slate-400' }}">
                            @if($hasData)
                            <span class="text-lg font-black">{{ $initial }}</span>
                            @else
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9.75 17 9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z" />
                            </svg>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <h4 class="truncate text-sm font-black text-slate-900">{{ $pc->pc_number }}</h4>
                            <p class="mt-0.5 truncate text-[10px] text-slate-500">{{ $hasData ? ($session->student_id_number ?? $session->student_number ?? 'Student session') : 'No active session' }}</p>
                        </div>
                    </div>

                    {{-- 3-DOTS ACTIONS MENU --}}
                    <div class="relative shrink-0" @click.outside="if (openPcMenu === @js((string) $pc->id)) openPcMenu = null">
                        <button type="button" class="lg-icon-button border border-slate-200 bg-white text-slate-500 hover:border-[#d4af37] hover:text-[#8a6a0a]"
                            aria-label="Workstation actions"
                            :aria-expanded="(openPcMenu === @js((string) $pc->id)).toString()"
                            @click="openPcMenu = openPcMenu === @js((string) $pc->id) ? null : @js((string) $pc->id)">
                            <svg class="size-4" fill="currentColor" viewBox="0 0 24 24">
                                <circle cx="5" cy="12" r="1.7" />
                                <circle cx="12" cy="12" r="1.7" />
                                <circle cx="19" cy="12" r="1.7" />
                            </svg>
                        </button>
                        <div x-show="openPcMenu === @js((string) $pc->id)" x-cloak x-transition
                            class="absolute right-0 top-10 z-40 w-52 rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl">
                            @if($hasData)
                            <button type="button" class="lg-menu-item" @click="openMessageSingle(@js($pc->pc_number))"><svg class="size-4 text-[#a17c16]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 10h8m-8 4h5m-7 6-3 1 1-4a8 8 0 1 1 2 3Z" />
                                </svg>Send message</button>
                            <button type="button" class="lg-menu-item" @click="openPushUrlSingle(@js($pc->pc_number))"><svg class="size-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" />
                                </svg>Push URL</button>
                            <button type="button" class="lg-menu-item text-rose-700 hover:bg-rose-50 hover:text-rose-800" @click="openShutdownSingle(@js($pc->pc_number))"><svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 2v10m6.36-6.36a9 9 0 1 1-12.72 0" />
                                </svg>Shutdown PC</button>
                            <div class="my-1 border-t border-slate-100"></div>
                            <button type="button" class="lg-menu-item text-rose-700 hover:bg-rose-50 hover:text-rose-800"
                                @click="openPcMenu = null; confirmForceRelease({{ $pc->id }}, @js($pc->pc_number), @js($name))">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 7h3a2 2 0 0 1 2 2v10H4V9a2 2 0 0 1 2-2h3m3-4v12m0 0-4-4m4 4 4-4" />
                                </svg>Release session
                            </button>
                            @else
                            <div class="px-3 py-2 text-[11px] text-slate-500">No active session controls.</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex items-center justify-between gap-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-wide {{ $hasData ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">
                        <span class="size-1.5 rounded-full {{ $hasData ? 'bg-rose-500' : 'bg-emerald-500' }}"></span>
                        {{ $hasData ? 'In Use' : 'Available' }}
                    </span>
                    @if($hasData)
                    <span class="text-[10px] font-semibold text-slate-400">Started {{ \Carbon\Carbon::parse($session->time_in)->format('h:i A') }}</span>
                    @else
                    <span class="text-[10px] text-slate-400">Ready for use</span>
                    @endif
                </div>

                @if($hasData)
                <div class="mt-3 rounded-xl border border-slate-100 bg-slate-50/80 p-3">
                    <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Current user</p>
                    <p class="mt-1 truncate text-xs font-bold text-slate-800" title="{{ $name }}">{{ $name }}</p>

                    <div class="mt-3 flex items-center gap-2.5">
                        <span class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500 shadow-2xs">
                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <rect x="3" y="4" width="18" height="13" rx="2" stroke-width="1.7" />
                                <path stroke-linecap="round" stroke-width="1.7" d="M8 21h8m-4-4v4" />
                            </svg>
                        </span>

                        {{-- ACTIVE APP MARQUEE TICKER --}}
                        <div class="min-w-0 flex-1">
                            <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Active application</p>
                            @if($isLongApp)
                            <div class="lg-marquee-mask overflow-hidden whitespace-nowrap" title="{{ $activeApp }} (hover to pause)">
                                <div class="lg-marquee-track font-mono text-[10px] font-semibold text-slate-700">
                                    <span>{{ $activeApp }}</span>
                                    <span class="mx-3 text-[#d4af37]">•</span>
                                    <span>{{ $activeApp }}</span>
                                    <span class="mx-3 text-[#d4af37]">•</span>
                                </div>
                            </div>
                            @else
                            <p class="truncate font-mono text-[10px] font-semibold text-slate-700" title="{{ $activeApp }}">{{ $activeApp }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- CARD ACTION: FULL-WIDTH QUICK MESSAGE (MORE BUTTON REMOVED) --}}
                <div class="mt-3">
                    <button type="button" @click="openMessageSingle(@js($pc->pc_number))" class="lg-white-button flex w-full items-center justify-center gap-2 rounded-lg px-3 py-2 text-[10px] font-bold">
                        <svg class="size-3.5 text-[#a17c16]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M8 10h8m-8 4h5m-7 6-3 1 1-4a8 8 0 1 1 2 3Z" />
                        </svg>
                        <span>Send Message</span>
                    </button>
                </div>
                @else
                <div class="mt-3 rounded-xl border border-dashed border-slate-200 px-3 py-4 text-center">
                    <p class="text-[10px] text-slate-500">No student session is currently recorded.</p>
                </div>
                @endif
            </article>
            @empty
            <div class="col-span-full rounded-2xl border border-dashed border-[#e4d7ae] bg-white px-5 py-12 text-center">
                <svg class="mx-auto size-10 text-[#b18b19]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17 9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z" />
                </svg>
                <h4 class="mt-3 text-sm font-black text-slate-800">No Workstations Found</h4>
                <p class="mt-1 text-xs text-slate-500">No computers are registered for this laboratory.</p>
            </div>
            @endforelse
        </div>

        <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-[10px] text-slate-500" x-show="searchQuery !== '' || statusFilter !== 'all'">
            Filters are active. Clear the search or select <strong>All PCs</strong> to restore the full workstation list.
            <button type="button" class="ml-2 font-bold text-[#8a6a0a] underline" @click="searchQuery = ''; statusFilter = 'all'">Clear filters</button>
        </div>

        {{-- BROADCAST MESSAGE MODAL --}}
        <div x-show="broadcastModal" wire:ignore.self x-cloak class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="broadcast-heading">
            <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="broadcastModal = false" x-transition.opacity></div>
            <div class="flex min-h-full items-center justify-center p-3 sm:p-5">
                <section x-show="broadcastModal" x-transition @click.stop class="lg-modal relative w-full max-w-xl overflow-hidden rounded-2xl sm:rounded-3xl">
                    <div class="h-1.5 bg-gradient-to-r from-[#a17c16] via-[#f0d779] to-[#d4af37]"></div>
                    <header class="flex items-start justify-between gap-4 border-b border-[#eee7d3] px-5 py-5 sm:px-7">
                        <div>
                            <p class="text-[9px] font-black uppercase tracking-[.2em] text-[#a17c16]">Remote Communication</p>
                            <h3 id="broadcast-heading" class="mt-1 text-lg font-black tracking-tight text-slate-900">Broadcast Message</h3>
                            <p class="mt-1 text-xs text-slate-500">Send an announcement to selected workstations.</p>
                        </div>
                        <button type="button" @click="broadcastModal = false" aria-label="Close" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"><svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-width="2" d="m6 6 12 12M18 6 6 18" />
                            </svg></button>
                    </header>
                    <div class="space-y-5 p-5 sm:p-7">
                        <div class="rounded-xl border border-[#eee7d3] bg-[#fcfaf4] p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black text-slate-800">All workstations</p>
                                    <p class="mt-1 text-[10px] text-slate-500">Send to every workstation in this lab.</p>
                                </div>
                                <button type="button" role="switch" :aria-checked="broadcastAllLabs.toString()" @click="broadcastAllLabs = !broadcastAllLabs; broadcastTarget = broadcastAllLabs ? 'all' : ''" :class="broadcastAllLabs ? 'bg-[#d4af37]' : 'bg-slate-300'" class="relative h-[25px] w-[45px] rounded-full transition" aria-label="Toggle all workstations"><span class="absolute left-[3px] top-[3px] size-[19px] rounded-full bg-white shadow transition-transform" :class="broadcastAllLabs ? 'translate-x-5' : ''"></span></button>
                            </div>
                            <div x-show="!broadcastAllLabs" x-transition class="mt-4 border-t border-[#eee7d3] pt-4"><label for="broadcast-target" class="mb-1.5 block text-[10px] font-black uppercase text-slate-600">Specific PC numbers</label><input id="broadcast-target" type="text" x-model="broadcastTarget" placeholder="Example: 1, 3, 8" class="lg-field">
                                <p class="mt-1.5 text-[10px] text-slate-500">Separate multiple PC numbers with commas.</p>
                            </div>
                        </div>
                        <div>
                            <div class="mb-2 flex items-center justify-between"><label for="broadcast-message" class="text-[10px] font-black uppercase text-slate-600">Announcement</label><span class="font-mono text-[10px] text-slate-400" x-text="`${broadcastMsg.length}/180`"></span></div><textarea id="broadcast-message" x-model="broadcastMsg" maxlength="180" rows="4" placeholder="Type your announcement..." class="lg-field resize-none"></textarea>
                        </div>
                        <div>
                            <p class="mb-2 text-[9px] font-black uppercase tracking-wider text-slate-500">Quick messages</p>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" @click="setBroadcastPreset('5 minutes remaining for your practical laboratory exam. Wrap up your code.')" class="lg-white-button rounded-lg px-3 py-2 text-[10px] font-bold">5 minutes remaining</button>
                                <button type="button" @click="setBroadcastPreset('Please save all your open work. Class is concluding soon.')" class="lg-white-button rounded-lg px-3 py-2 text-[10px] font-bold">Save work</button>
                                <button type="button" @click="setBroadcastPreset('Please use your earphones or mute your workstation audio.')" class="lg-white-button rounded-lg px-3 py-2 text-[10px] font-bold">Mute audio</button>
                                <button type="button" @click="setBroadcastPreset('Please maintain silence in the laboratory and focus on your work.')" class="lg-white-button rounded-lg px-3 py-2 text-[10px] font-bold">Quiet please</button>
                            </div>
                        </div>
                    </div>
                    <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-[#eee7d3] bg-[#fcfaf4] px-5 py-4 sm:px-7">
                        <p class="text-[10px] text-slate-500">Target: <strong class="text-[#a17c16]" x-text="broadcastAllLabs ? 'ALL WORKSTATIONS' : (broadcastTarget || 'NOT SELECTED')"></strong></p>
                        <div class="flex gap-2">
                            <button type="button" @click="broadcastModal = false" class="lg-white-button rounded-xl px-4 py-2.5 text-[10px] font-black uppercase">Cancel</button>
                            <button type="button" @click="submitBroadcastMessage({{ $targetLabId }})" :disabled="isSubmitting || !broadcastMsg.trim() || (!broadcastAllLabs && !broadcastTarget.trim())" class="lg-gold-button inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-[10px] font-black uppercase disabled:cursor-not-allowed disabled:opacity-50">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m22 2-7 20-4-9-9-4 20-7ZM22 2 11 13" />
                                </svg>
                                <span x-text="isSubmitting ? 'Sending...' : 'Send message'"></span>
                            </button>
                        </div>
                    </footer>
                </section>
            </div>
        </div>

        {{-- PUSH URL MODAL --}}
        <div x-show="pushUrlModal" wire:ignore.self x-cloak class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="push-url-heading">
            <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="pushUrlModal = false" x-transition.opacity></div>
            <div class="flex min-h-full items-center justify-center p-3 sm:p-5">
                <section x-show="pushUrlModal" x-transition @click.stop class="lg-modal relative w-full max-w-xl overflow-hidden rounded-2xl sm:rounded-3xl">
                    <div class="h-1.5 bg-gradient-to-r from-indigo-500 via-[#d4af37] to-[#f0d779]"></div>
                    <header class="flex items-start justify-between gap-4 border-b border-[#eee7d3] px-5 py-5 sm:px-7">
                        <div>
                            <p class="text-[9px] font-black uppercase tracking-[.2em] text-[#a17c16]">Remote Browser Control</p>
                            <h3 id="push-url-heading" class="mt-1 text-lg font-black text-slate-900">Push Classroom URL</h3>
                            <p class="mt-1 text-xs text-slate-500">Open an educational link on selected workstations.</p>
                        </div><button type="button" @click="pushUrlModal = false" aria-label="Close" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"><svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-width="2" d="m6 6 12 12M18 6 6 18" />
                            </svg></button>
                    </header>
                    <div class="space-y-5 p-5 sm:p-7">
                        <div class="rounded-xl border border-[#eee7d3] bg-[#fcfaf4] p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black text-slate-800">All workstations</p>
                                    <p class="mt-1 text-[10px] text-slate-500">Send the link to every workstation in this lab.</p>
                                </div><button type="button" role="switch" :aria-checked="pushUrlAllLabs.toString()" @click="pushUrlAllLabs = !pushUrlAllLabs; pushUrlTarget = pushUrlAllLabs ? 'all' : ''" :class="pushUrlAllLabs ? 'bg-[#d4af37]' : 'bg-slate-300'" class="relative h-[25px] w-[45px] rounded-full transition" aria-label="Toggle all workstations"><span class="absolute left-[3px] top-[3px] size-[19px] rounded-full bg-white shadow transition-transform" :class="pushUrlAllLabs ? 'translate-x-5' : ''"></span></button>
                            </div>
                            <div x-show="!pushUrlAllLabs" x-transition class="mt-4 border-t border-[#eee7d3] pt-4"><label for="push-url-target" class="mb-1.5 block text-[10px] font-black uppercase text-slate-600">Specific PC numbers</label><input id="push-url-target" type="text" x-model="pushUrlTarget" placeholder="Example: 1, 3, 8" class="lg-field"></div>
                        </div>
                        <div><label for="push-url-input" class="mb-1.5 block text-[10px] font-black uppercase text-slate-600">Web address</label><input id="push-url-input" type="url" x-model="pushUrl" placeholder="https://classroom.google.com" class="lg-field"></div>
                        <div>
                            <p class="mb-2 text-[9px] font-black uppercase tracking-wider text-slate-500">Educational shortcuts</p>
                            <div class="flex flex-wrap gap-2"><button type="button" @click="setUrlPreset('https://classroom.google.com')" class="lg-white-button rounded-lg px-3 py-2 text-[10px] font-bold">Google Classroom</button><button type="button" @click="setUrlPreset('https://github.com')" class="lg-white-button rounded-lg px-3 py-2 text-[10px] font-bold">GitHub</button><button type="button" @click="setUrlPreset('https://forms.google.com')" class="lg-white-button rounded-lg px-3 py-2 text-[10px] font-bold">Google Forms</button><button type="button" @click="setUrlPreset('https://drive.google.com')" class="lg-white-button rounded-lg px-3 py-2 text-[10px] font-bold">Google Drive</button></div>
                        </div>
                    </div>
                    <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-[#eee7d3] bg-[#fcfaf4] px-5 py-4 sm:px-7">
                        <p class="text-[10px] text-slate-500">Target: <strong class="text-[#a17c16]" x-text="pushUrlAllLabs ? 'ALL WORKSTATIONS' : (pushUrlTarget || 'NOT SELECTED')"></strong></p>
                        <div class="flex gap-2">
                            <button type="button" @click="pushUrlModal = false" class="lg-white-button rounded-xl px-4 py-2.5 text-[10px] font-black uppercase">Cancel</button>
                            <button type="button" @click="submitPushUrl({{ $targetLabId }})" :disabled="isSubmitting || !pushUrl.trim() || (!pushUrlAllLabs && !pushUrlTarget.trim())" class="lg-gold-button rounded-xl px-4 py-2.5 text-[10px] font-black uppercase disabled:opacity-50">
                                <span x-text="isSubmitting ? 'Opening...' : 'Open URL'"></span>
                            </button>
                        </div>
                    </footer>
                </section>
            </div>
        </div>

        {{-- SHUTDOWN MODAL --}}
        <div x-show="shutdownModal" wire:ignore.self x-cloak class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="shutdown-heading">
            <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="shutdownModal = false" x-transition.opacity></div>
            <div class="flex min-h-full items-center justify-center p-3 sm:p-5">
                <section x-show="shutdownModal" x-transition @click.stop class="lg-modal relative w-full max-w-lg overflow-hidden rounded-2xl sm:rounded-3xl">
                    <div class="h-1.5 bg-gradient-to-r from-rose-500 via-[#d4af37] to-[#f0d779]"></div>
                    <header class="flex items-start justify-between gap-4 border-b border-[#eee7d3] px-5 py-5 sm:px-7">
                        <div>
                            <p class="text-[9px] font-black uppercase tracking-[.2em] text-rose-600">Power Management</p>
                            <h3 id="shutdown-heading" class="mt-1 text-lg font-black text-slate-900">Shutdown Workstations</h3>
                            <p class="mt-1 text-xs text-slate-500">Choose which computers receive the shutdown command.</p>
                        </div><button type="button" @click="shutdownModal = false" aria-label="Close" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"><svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-width="2" d="m6 6 12 12M18 6 6 18" />
                            </svg></button>
                    </header>
                    <div class="space-y-5 p-5 sm:p-7">
                        <div class="rounded-xl border border-[#eee7d3] bg-[#fcfaf4] p-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black text-slate-800">All workstations</p>
                                    <p class="mt-1 text-[10px] text-slate-500">Apply the command to every workstation in this lab.</p>
                                </div><button type="button" role="switch" :aria-checked="shutdownAllLabs.toString()" @click="shutdownAllLabs = !shutdownAllLabs; shutdownTarget = shutdownAllLabs ? 'all' : ''" :class="shutdownAllLabs ? 'bg-[#d4af37]' : 'bg-slate-300'" class="relative h-[25px] w-[45px] rounded-full transition" aria-label="Toggle all workstations"><span class="absolute left-[3px] top-[3px] size-[19px] rounded-full bg-white shadow transition-transform" :class="shutdownAllLabs ? 'translate-x-5' : ''"></span></button>
                            </div>
                            <div x-show="!shutdownAllLabs" x-transition class="mt-4 border-t border-[#eee7d3] pt-4"><label for="shutdown-target" class="mb-1.5 block text-[10px] font-black uppercase text-slate-600">Specific PC numbers</label><input id="shutdown-target" type="text" x-model="shutdownTarget" placeholder="Example: 1, 3, 8" class="lg-field"></div>
                        </div>
                        <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4"><svg class="mt-0.5 size-5 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v4m0 4h.01M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0Z" />
                            </svg>
                            <p class="text-[11px] leading-relaxed text-rose-800">Shutdown may interrupt unsaved student work. Verify the target before proceeding.</p>
                        </div>
                    </div>
                    <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-[#eee7d3] bg-[#fcfaf4] px-5 py-4 sm:px-7">
                        <p class="text-[10px] text-slate-500">Target: <strong class="text-rose-700" x-text="shutdownAllLabs ? 'ALL WORKSTATIONS' : (shutdownTarget || 'NOT SELECTED')"></strong></p>
                        <div class="flex gap-2">
                            <button type="button" @click="shutdownModal = false" class="lg-white-button rounded-xl px-4 py-2.5 text-[10px] font-black uppercase">Cancel</button>
                            <button type="button" @click="if (confirm('Are you sure you want to send the shutdown command?')) submitShutdownCommand({{ $targetLabId }})" :disabled="isSubmitting" class="rounded-xl border border-rose-700 bg-rose-600 px-4 py-2.5 text-[10px] font-black uppercase text-white hover:bg-rose-700 disabled:opacity-50">
                                <span x-text="isSubmitting ? 'Shutting down...' : 'Confirm shutdown'"></span>
                            </button>
                        </div>
                    </footer>
                </section>
            </div>
        </div>
        {{-- BANNED APPLICATIONS MODAL --}}
        <div x-show="bannedAppsModal" wire:ignore.self x-cloak class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="banned-apps-heading">
            <div class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm" @click="bannedAppsModal = false" x-transition.opacity></div>
            <div class="flex min-h-full items-center justify-center p-3 sm:p-5">
                <section x-show="bannedAppsModal" x-transition @click.stop class="lg-modal relative flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl sm:rounded-3xl">
                    <div class="h-1.5 bg-gradient-to-r from-[#9e7914] via-[#f0d779] to-[#d4af37]"></div>

                    <!-- Modal Header -->
                    <header class="flex shrink-0 items-start justify-between gap-4 border-b border-[#eee7d3] px-5 py-5 sm:px-7 bg-white">
                        <div>
                            <p class="text-[9px] font-black uppercase tracking-[.2em] text-[#a17c16]">Application Restrictions</p>
                            <h3 id="banned-apps-heading" class="mt-1 text-lg font-black text-slate-900">Software Policy Manager</h3>
                            <p class="mt-1 text-xs text-slate-500">Configure executable processes restricted by the terminal agent.</p>
                        </div>
                        <button type="button" @click="bannedAppsModal = false" aria-label="Close" class="rounded-lg p-2 text-slate-400 hover:bg-slate-100 transition">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-width="2" d="m6 6 12 12M18 6 6 18" />
                            </svg>
                        </button>
                    </header>

                    <!-- Modal Body Container (Split Layout with internal scrollability) -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 flex-1 overflow-y-auto lg:overflow-hidden bg-[#f8fafc]">

                        <!-- Left Column: Add Form & Presets (Width: 5 Cols) -->
                        <div class="lg:col-span-5 border-b lg:border-b-0 lg:border-r border-[#eee7d3] bg-[#fcfaf4] p-5 sm:p-6 overflow-y-auto">
                            <form @submit.prevent="submitBannedApp" class="space-y-4">
                                <div>
                                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-800 mb-3 flex items-center gap-2">
                                        <svg class="size-4 text-[#a17c16]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Add New Rule
                                    </h4>
                                    <div class="space-y-3">
                                        <div>
                                            <label for="banned-app-name" class="mb-1 block text-[10px] font-black uppercase text-slate-600">Application name</label>
                                            <input id="banned-app-name" type="text" x-model="appName" placeholder="e.g. Discord" required class="lg-field">
                                        </div>
                                        <div>
                                            <label for="banned-app-process" class="mb-1 block text-[10px] font-black uppercase text-slate-600">Executable process</label>
                                            <input id="banned-app-process" type="text" x-model="appProcess" placeholder="discord.exe" required class="lg-field">
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick Presets -->
                                <div>
                                    <span class="block mb-2 text-[9px] font-black uppercase tracking-wider text-slate-500">Quick Presets</span>
                                    <div class="flex flex-wrap gap-1.5">
                                        <button type="button" @click="setPreset('Roblox','robloxplayerbeta.exe')" class="lg-white-button rounded-lg px-2.5 py-1.5 text-[10px] font-bold">Roblox</button>
                                        <button type="button" @click="setPreset('Discord','discord.exe')" class="lg-white-button rounded-lg px-2.5 py-1.5 text-[10px] font-bold">Discord</button>
                                        <button type="button" @click="setPreset('Steam','steam.exe')" class="lg-white-button rounded-lg px-2.5 py-1.5 text-[10px] font-bold">Steam</button>
                                        <button type="button" @click="setPreset('Cheat Engine','cheatengine.exe')" class="lg-white-button rounded-lg px-2.5 py-1.5 text-[10px] font-bold">Cheat Engine</button>
                                        <button type="button" @click="setPreset('Spotify','spotify.exe')" class="lg-white-button rounded-lg px-2.5 py-1.5 text-[10px] font-bold">Spotify</button>
                                    </div>
                                </div>

                                <!-- Tip is now nested safely right above the submit button -->
                                <div class="bg-[#f6f2e4] border border-[#e8dfc5] p-3 rounded-xl">
                                    <p class="text-[10px] leading-relaxed text-slate-600">
                                        💡 <strong>Tip:</strong> If the filename does not end in <code class="text-slate-800 font-mono font-bold">.exe</code>, it will be automatically appended.
                                    </p>
                                </div>

                                <button type="submit" class="lg-gold-button w-full rounded-xl py-3 text-[10px] font-black uppercase shadow-sm tracking-wide" :disabled="isSubmitting">
                                    <span x-text="isSubmitting ? 'Saving Rule...' : 'Save Restriction Rule'"></span>
                                </button>
                            </form>
                        </div>

                        <!-- Right Column: Configured Rules List (Width: 7 Cols) -->
                        <div class="lg:col-span-7 flex flex-col min-h-[300px] lg:min-h-0 bg-white">
                            <!-- List Header Search Bar -->
                            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-[#eee7d3] px-5 py-3.5 bg-slate-50/50">
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-700">
                                    Active Rules (<span x-text="typeof bannedAppsList !== 'undefined' ? bannedAppsList.length : {{ $myBannedApps->count() }}"></span>)
                                </span>
                                <div class="relative w-full max-w-[210px]">
                                    <svg class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400 z-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <circle cx="11" cy="11" r="7" stroke-width="1.8" />
                                        <path stroke-linecap="round" stroke-width="1.8" d="m20 20-4-4" />
                                    </svg>
                                    <input type="text" x-model="searchBanned" placeholder="Filter rules..." class="lg-field py-1.5 text-[10px]" style="padding-left: 2.35rem !important;">
                                </div>
                            </div>

                            <!-- Scrollable List Items -->
                            <div class="min-h-0 flex-1 space-y-2.5 overflow-y-auto p-4 sm:p-5">
                                @forelse($myBannedApps as $app)
                                <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-3 transition hover:border-[#d4af37] shadow-2xs"
                                    x-show="searchBanned === '' || @js(strtolower($app->name.' '.$app->process_name)).includes(searchBanned.toLowerCase())"
                                    x-transition>
                                    <div class="flex min-w-0 items-center gap-3">
                                        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl border border-rose-100 bg-rose-50 text-rose-600">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <circle cx="12" cy="12" r="9" stroke-width="1.7" />
                                                <path stroke-linecap="round" stroke-width="1.7" d="m5.6 5.6 12.8 12.8" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="truncate text-[11px] font-black uppercase text-slate-800">{{ $app->name }}</h4>
                                            <p class="truncate font-mono text-[10px] text-slate-500">{{ $app->process_name }}</p>
                                        </div>
                                    </div>
                                    <form @submit.prevent="deleteBannedApp({{ $app->id }}, @js($app->process_name))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-[9px] font-black uppercase text-rose-700 transition hover:bg-rose-600 hover:text-white shrink-0">Remove</button>
                                    </form>
                                </div>
                                @empty
                                <div class="py-16 text-center">
                                    <div class="mx-auto flex size-11 items-center justify-center rounded-2xl border border-emerald-100 bg-emerald-50 text-emerald-600">
                                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6" />
                                        </svg>
                                    </div>
                                    <h4 class="mt-3 text-xs font-black uppercase text-slate-800">No restrictions configured</h4>
                                    <p class="mt-1 text-[10px] text-slate-500">Add an application rule on the left panel to begin.</p>
                                </div>
                                @endforelse
                            </div>
                        </div>

                    </div>

                    <!-- Modal Footer -->
                    <footer class="flex shrink-0 items-center justify-between gap-3 border-t border-[#eee7d3] bg-[#fcfaf4] px-5 py-3.5 sm:px-7">
                        <p class="text-[9px] text-slate-500">Enforcement depends on active connection with the terminal agent.</p>
                        <button type="button" @click="bannedAppsModal = false" class="lg-dark-button rounded-xl px-5 py-2 text-[10px] font-black uppercase">Done</button>
                    </footer>
                </section>
            </div>
        </div>

    </div>
</div>

{{-- JAVASCRIPT LOGIC & TOAST DISPATCHER --}}
<script>
    (function() {
        function registerLabCommandCenter() {
            if (typeof Alpine === 'undefined') return;

            Alpine.data('labCommandCenter', () => ({
                bannedAppsModal: false,
                broadcastModal: false,
                pushUrlModal: false,
                shutdownModal: false,
                labActionsOpen: false,
                isSubmitting: false,
                searchQuery: '',
                statusFilter: 'all',
                openPcMenu: null,
                searchBanned: '',
                appName: '',
                appProcess: '',
                broadcastMsg: '',
                broadcastAllLabs: true,
                broadcastTarget: 'all',
                pushUrl: '',
                pushUrlAllLabs: true,
                pushUrlTarget: 'all',
                shutdownAllLabs: true,
                shutdownTarget: 'all',
                shutdownSeconds: 15,

                bannedAppsList: @json($myBannedApps),

                init() {
                    this.$watch('bannedAppsModal || broadcastModal || pushUrlModal || shutdownModal', (value) => {
                        window.isModalOpen = value;
                    });
                },

                showToast(type, title, message) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            type: (type === 'error') ? 'danger' : type,
                            title: title,
                            message: message
                        }
                    }));
                },

                setPreset(name, proc) {
                    this.appName = name;
                    this.appProcess = proc;
                },
                setBroadcastPreset(msg) {
                    this.broadcastMsg = msg;
                },
                setUrlPreset(url) {
                    this.pushUrl = url;
                },

                openMessageSingle(pcNumber) {
                    this.broadcastAllLabs = false;
                    this.broadcastTarget = String(pcNumber);
                    this.broadcastModal = true;
                    this.openPcMenu = null;
                },
                openPushUrlSingle(pcNumber) {
                    this.pushUrlAllLabs = false;
                    this.pushUrlTarget = String(pcNumber);
                    this.pushUrlModal = true;
                    this.openPcMenu = null;
                },
                openShutdownSingle(pcNumber) {
                    this.shutdownAllLabs = false;
                    this.shutdownTarget = String(pcNumber);
                    this.shutdownModal = true;
                    this.openPcMenu = null;
                },
                closeAllModals() {
                    this.bannedAppsModal = false;
                    this.broadcastModal = false;
                    this.pushUrlModal = false;
                    this.shutdownModal = false;
                    this.labActionsOpen = false;
                    this.openPcMenu = null;
                },
                matchesPc(number, status, name, app) {
                    const query = this.searchQuery.trim().toLowerCase();
                    const matchesSearch = !query || [number, status, name || '', app || ''].some(function(val) {
                        return String(val).toLowerCase().includes(query);
                    });
                    const matchesStatus = this.statusFilter === 'all' ||
                        (this.statusFilter === 'active' && status === 'active') ||
                        (this.statusFilter === 'available' && status !== 'active');
                    return matchesSearch && matchesStatus;
                },

                getCsrfToken() {
                    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || @json(csrf_token());
                },

                async submitBroadcastMessage(labId) {
                    const target = this.broadcastAllLabs ? 'all' : (this.broadcastTarget || '').trim();
                    const msg = (this.broadcastMsg || '').trim();
                    if (!msg || !target) {
                        this.showToast('warning', 'Notice', 'Please enter a message and select target workstations.');
                        return;
                    }

                    this.isSubmitting = true;
                    try {
                        const endpoint = @json(route('personnel.broadcast', ['labId' => '__LAB_ID__'])).replace('__LAB_ID__', labId);

                        const response = await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken()
                            },
                            body: JSON.stringify({
                                message: msg,
                                target: target
                            })
                        });

                        const data = await response.json().catch(function() {
                            return {};
                        });
                        if (!response.ok) {
                            const errorMsg = data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Failed to send broadcast.');
                            this.showToast('danger', 'Broadcast Failed', errorMsg);
                            return;
                        }

                        this.broadcastModal = false;
                        this.broadcastMsg = '';
                        this.showToast('success', 'Broadcast Dispatched', data.message || 'Announcement sent to terminal workstations.');
                    } catch (error) {
                        console.error('Broadcast error:', error);
                        this.showToast('danger', 'System Error', 'An unexpected network error occurred.');
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                async submitPushUrl(labId) {
                    const target = this.pushUrlAllLabs ? 'all' : (this.pushUrlTarget || '').trim();
                    const urlVal = (this.pushUrl || '').trim();
                    if (!urlVal || !target) {
                        this.showToast('warning', 'Notice', 'Please enter a valid URL and target.');
                        return;
                    }

                    this.isSubmitting = true;
                    try {
                        const endpoint = @json(route('personnel.push-url', ['labId' => '__LAB_ID__'])).replace('__LAB_ID__', labId);

                        const response = await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken()
                            },
                            body: JSON.stringify({
                                url: urlVal,
                                target: target
                            })
                        });

                        const data = await response.json().catch(function() {
                            return {};
                        });
                        if (!response.ok) {
                            const errorMsg = data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Failed to push URL.');
                            this.showToast('danger', 'URL Push Failed', errorMsg);
                            return;
                        }

                        this.pushUrlModal = false;
                        this.pushUrl = '';
                        this.showToast('success', 'URL Dispatched', data.message || 'Classroom link sent to terminal workstations.');
                    } catch (error) {
                        console.error('Push URL error:', error);
                        this.showToast('danger', 'System Error', 'An unexpected network error occurred.');
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                async submitShutdownCommand(labId) {
                    const target = this.shutdownAllLabs ? 'all' : (this.shutdownTarget || '').trim();
                    if (!target) {
                        this.showToast('warning', 'Notice', 'Please specify the target workstations.');
                        return;
                    }

                    this.isSubmitting = true;
                    try {
                        const endpoint = @json(route('personnel.shutdown', ['labId' => '__LAB_ID__'])).replace('__LAB_ID__', labId);

                        const response = await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken()
                            },
                            body: JSON.stringify({
                                target: target
                            })
                        });

                        const data = await response.json().catch(function() {
                            return {};
                        });
                        if (!response.ok) {
                            const errorMsg = data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Failed to dispatch shutdown command.');
                            this.showToast('danger', 'Shutdown Failed', errorMsg);
                            return;
                        }

                        this.shutdownModal = false;
                        this.showToast('success', 'Shutdown Queued', data.message || 'Shutdown command dispatched to workstations.');
                    } catch (error) {
                        console.error('Shutdown error:', error);
                        this.showToast('danger', 'System Error', 'An unexpected network error occurred.');
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                async confirmTerminateAll(labId) {
                    if (!confirm('Are you sure you want to terminate all active sessions in this laboratory?')) return;
                    try {
                        const endpoint = @json(route('personnel.terminateAll', ['labId' => '__LAB_ID__'])).replace('__LAB_ID__', labId);

                        const response = await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken()
                            }
                        });

                        if (response.ok) {
                            this.showToast('success', 'Sessions Terminated', 'All workstation sessions have been closed.');
                        } else {
                            const data = await response.json().catch(function() {
                                return {};
                            });
                            this.showToast('danger', 'Operation Failed', data.message || 'Failed to terminate all sessions.');
                        }
                    } catch (error) {
                        console.error('Terminate all error:', error);
                        this.showToast('danger', 'System Error', 'An unexpected network error occurred.');
                    }
                },
                async submitBannedApp() {
                    if (!this.appName.trim() || !this.appProcess.trim()) {
                        this.showToast('warning', 'Notice', 'Please fill in both fields.');
                        return;
                    }

                    this.isSubmitting = true;
                    try {
                        const response = await fetch("{{ route('personnel.banned_apps.store') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken()
                            },
                            body: JSON.stringify({
                                name: this.appName,
                                process_name: this.appProcess
                            })
                        });

                        const data = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            const errorMsg = data.message || 'Failed to add application restriction.';
                            this.showToast('danger', 'Error', errorMsg);
                            return;
                        }

                        // Push new item directly into the reactive list array
                        if (data.app) {
                            this.bannedAppsList.unshift(data.app);
                        }

                        // Clear input fields
                        this.appName = '';
                        this.appProcess = '';

                        this.showToast('success', 'Restriction Added', data.message || 'Rule added successfully.');
                    } catch (error) {
                        console.error('Banned app error:', error);
                        this.showToast('danger', 'System Error', 'An unexpected network error occurred.');
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                async deleteBannedApp(appId) {
                    if (!confirm('Remove this application restriction?')) return;

                    try {
                        const endpoint = "{{ route('personnel.banned_apps.destroy', '__ID__') }}".replace('__ID__', appId);

                        const response = await fetch(endpoint, {
                            method: 'POST', // or 'DELETE' depending on your route setup; Laravel handles DELETE via @method('DELETE') spoofing, or regular POST
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken()
                            },
                            body: JSON.stringify({
                                _method: 'DELETE' // Method spoofing for Laravel
                            })
                        });

                        const data = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            this.showToast('danger', 'Error', data.message || 'Failed to remove restriction.');
                            return;
                        }

                        // Filter it out of the reactive array list instantly
                        this.bannedAppsList = this.bannedAppsList.filter(item => item.id !== appId);

                        this.showToast('success', 'Restriction Removed', data.message || 'The application restriction has been removed.');
                    } catch (error) {
                        console.error('Delete banned app error:', error);
                        this.showToast('danger', 'System Error', 'An unexpected network error occurred.');
                    }
                },

                async confirmForceRelease(pcId, pcNumber, studentName) {
                    const label = studentName ? `${pcNumber} (${studentName})` : pcNumber;
                    if (!confirm(`Are you sure you want to release the active session on ${label}?`)) return;
                    try {
                        const endpoint = @json(route('personnel.release', ['computer' => '__PC_ID__'])).replace('__PC_ID__', pcId);

                        const response = await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken()
                            }
                        });

                        if (response.ok) {
                            this.showToast('success', 'Session Released', `Workstation ${pcNumber} was released successfully.`);
                        } else {
                            const data = await response.json().catch(function() {
                                return {};
                            });
                            this.showToast('danger', 'Operation Failed', data.message || 'Failed to release workstation.');
                        }
                    } catch (error) {
                        console.error('Force release error:', error);
                        this.showToast('danger', 'System Error', 'An unexpected network error occurred.');
                    }
                }
            }));
        }

        if (window.Alpine) {
            registerLabCommandCenter();
        } else {
            document.addEventListener('alpine:init', registerLabCommandCenter);
        }
    })();
</script>