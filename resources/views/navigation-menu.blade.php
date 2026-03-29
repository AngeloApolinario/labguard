<nav class="flex flex-col w-64 h-screen bg-gradient-to-br from-[#1e2945] via-[#1a233a] to-[#0f172a] text-white fixed left-0 top-0 overflow-y-auto border-r border-[#D4AF37]/30 shadow-2xl">

    <div class="flex items-center p-6 space-x-3 border-b border-[#D4AF37]/40 bg-white/5 backdrop-blur-md">
        <div class="shrink-0 flex items-center">
            {{-- Dynamic Logo Link --}}
            @php
            $homeRoute = match(Auth::user()->role) {
            'super-admin' => route('super-admin.index'),
            'admin' => route('dashboard.index'),
            'personnel' => route('personnel.index'),
            default => route('profile.show'),
            };
            @endphp
            <a href="{{ $homeRoute }}">
                <x-application-mark class="block h-10 w-auto bg-white p-1.5 rounded-xl shadow-[0_0_15px_rgba(212,175,55,0.3)]" />
            </a>
        </div>
        <div>
            <h1 class="text-sm font-black leading-tight uppercase tracking-[0.15em] text-white">
                Lab <span class="text-[#D4AF37]">Guard</span>
            </h1>
            <p class="text-[10px] text-slate-400 font-medium">Araullo University</p>
        </div>
    </div>

    <div class="flex-1 px-4 mt-8 space-y-3">

        {{-- SUPER ADMIN SECTION --}}
        @if(Auth::user()->role === 'super-admin')
        <p class="text-[10px] px-3 font-bold text-yellow-500 uppercase tracking-widest">Master Control</p>

        <x-sidebar-link href="{{ route('super-admin.index') }}" :active="request()->routeIs('super-admin.index')" icon="heroicon-o-shield-check">
            {{ __('System Overview') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('super-admin.users') }}" :active="request()->routeIs('super-admin.users')" icon="heroicon-o-user-group">
            {{ __('User Management') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('super-admin.labs') }}" :active="request()->routeIs('super-admin.labs')" icon="heroicon-o-building-office-2">
            {{ __('Computer Labs') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('super-admin.sessions') }}" :active="request()->routeIs('super-admin.sessions')" icon="heroicon-o-clock">
            {{ __('Session History') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('super-admin.alerts') }}" :active="request()->routeIs('super-admin.alerts')" icon="heroicon-o-bell-alert">
            {{ __('Alert History') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('super-admin.security') }}" :active="request()->routeIs('super-admin.security')" icon="heroicon-o-lock-closed">
            {{ __('System Security') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('super-admin.analytics') }}" :active="request()->routeIs('super-admin.analytics')" icon="heroicon-o-chart-bar">
            {{ __('Analytics') }}
        </x-sidebar-link>

        <x-sidebar-link href="{{ route('super-admin.settings') }}" :active="request()->routeIs('super-admin.settings')" icon="heroicon-o-adjustments-horizontal">
            {{ __('Settings') }}
        </x-sidebar-link>


        @endif

        {{-- ADMIN ONLY SECTION --}}
        @if(Auth::user()->role === 'admin')
        <p class="text-[10px] px-3 font-bold text-[#D4AF37]/60 uppercase tracking-widest">Admin Control</p>
        <x-sidebar-link href="{{ route('dashboard.index') }}" :active="request()->routeIs('dashboard.index')" icon="heroicon-o-squares-2x2">
            {{ __('Dashboard') }}
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('dashboard.labs') }}" :active="request()->routeIs('dashboard.labs')" icon="heroicon-o-computer-desktop">
            {{ __('Computer Labs') }}
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('dashboard.alerts.index') }}" :active="request()->routeIs('dashboard.alerts.index')" icon="heroicon-o-bell">
            {{ __('Alert History') }}
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('dashboard.sessions.index') }}" :active="request()->routeIs('dashboard.sessions.index')" icon="heroicon-o-clock">
            {{ __('Session History') }}
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('dashboard.users') }}" :active="request()->routeIs('dashboard.users')" icon="heroicon-o-users">
            {{ __('User Management') }}
        </x-sidebar-link>


        @endif

        {{-- PERSONNEL ONLY SECTION --}}
        @if(Auth::user()->role === 'personnel')
        <p class="text-[10px] px-3 font-bold text-cyan-400/60 uppercase tracking-widest">Station Terminal</p>
        <x-sidebar-link href="{{ route('personnel.index') }}" :active="request()->routeIs('personnel.index')" icon="heroicon-o-cpu-chip">
            {{ __('Assigned Lab') }}
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('personnel.labs') }}" :active="request()->routeIs('personnel.labs')" icon="heroicon-o-beaker">
            {{ __('Lab Overview') }}
        </x-sidebar-link><x-sidebar-link href="{{ route('personnel.alerts') }}" :active="request()->routeIs('personnel.alerts')" icon="heroicon-o-bell">
            {{ __('Alerts History') }}
        </x-sidebar-link>
        <x-sidebar-link href="{{ route('personnel.sessions') }}" :active="request()->routeIs('personnel.sessions')" icon="heroicon-o-clock">
            {{ __('Session History') }}
        </x-sidebar-link>
        @endif

        {{-- STUDENT ONLY SECTION --}}
        @if(Auth::user()->role === 'student')
        <p class="text-[10px] px-3 font-bold text-indigo-400 uppercase tracking-widest">Student Portal</p>
        <x-sidebar-link href="{{ route('profile.show') }}" :active="request()->routeIs('profile.show')" icon="heroicon-o-user-circle">
            {{ __('My Profile') }}
        </x-sidebar-link>
        @endif

    </div>

    <div class="mt-auto p-4 space-y-4">

        <div class="relative px-1">
            <x-dropdown align="up" width="full">
                <x-slot name="trigger">
                    <button class="group flex items-center w-full p-3 rounded-xl bg-white/5 border border-[#D4AF37]/20 hover:border-[#D4AF37]/60 hover:bg-white/10 transition-all duration-500 shadow-lg">
                        <div class="relative">
                            <img class="size-10 rounded-lg object-cover mr-3 ring-2 ring-[#D4AF37]/30 group-hover:ring-[#D4AF37] transition-all" src="{{ Auth::user()->profile_photo_url }}" alt="">
                            <div class="absolute -bottom-1 -right-1 size-3.5 bg-green-500 border-2 border-[#1a233a] rounded-full shadow-sm"></div>
                        </div>
                        <div class="text-left flex-1 min-w-0">
                            <p class="text-sm font-bold text-white truncate">{{ Auth::user()->name }}</p>
                            {{-- Dynamic Role Badge --}}
                            <p class="text-[10px] text-slate-400 truncate uppercase tracking-tighter">
                                @if(Auth::user()->role === 'super-admin')
                                <span class="text-yellow-500 font-bold">Super Admin</span>
                                @elseif(Auth::user()->role === 'admin')
                                System Admin
                                @elseif(Auth::user()->role === 'personnel')
                                Lab Personnel
                                @else
                                Student
                                @endif
                            </p>
                        </div>
                        <svg class="size-4 text-[#D4AF37] group-hover:translate-y-[-2px] transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                        </svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="px-4 py-3 border-b border-[#D4AF37]/20">
                        <p class="text-[10px] font-bold text-[#D4AF37] uppercase tracking-widest">
                            @if(Auth::user()->role === 'super-admin') Master Access @else Account Access @endif
                        </p>
                    </div>

                    <x-dropdown-link href="{{ route('profile.show') }}" class="text-slate-200 hover:bg-[#D4AF37]/10">
                        User Settings
                    </x-dropdown-link>

                    <div class="border-t border-[#D4AF37]/20"></div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full px-4 py-3 text-sm text-rose-400 hover:bg-rose-500/10 transition-all">
                            Sign Out
                        </button>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>

        <p class="text-[8px] text-slate-500 uppercase tracking-[0.3em] text-center opacity-60">
            Laboratory Management
        </p>
    </div>
</nav>