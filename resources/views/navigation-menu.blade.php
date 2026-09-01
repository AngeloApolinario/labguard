@php
$userRole = Auth::user()?->role;
$homeRoute = match($userRole) {
'super-admin' => route('super-admin.index'),
'admin' => route('dashboard.index'),
'personnel' => route('personnel.index'),
'student' => route('profile.show'),
default => Route::has('login') ? route('login') : '#',
};
@endphp

<div x-data="{ open: false }" class="relative z-50">

    {{-- MOBILE TOP HEADER / TRIGGER BAR --}}
    <div class="lg:hidden fixed top-0 left-0 right-0 h-14 bg-white/90 backdrop-blur-md border-b border-slate-200/80 px-4 flex items-center justify-between z-40 shadow-sm">
        <a href="{{ $homeRoute }}" class="flex items-center space-x-2">
            <x-application-mark class="block h-8 w-auto p-1 bg-slate-900 rounded-lg" />
            <span class="text-sm font-black uppercase tracking-wider text-slate-800">
                Lab <span class="text-[#D4AF37]">Guard</span>
            </span>
        </a>

        <button @click="open = !open"
            type="button"
            class="p-2 rounded-xl text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none transition-all">
            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path x-show="!open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- MOBILE BACKDROP OVERLAY --}}
    <div x-show="open"
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="open = false"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm lg:hidden z-40"
        x-cloak>
    </div>

    {{-- SIDEBAR NAVIGATION CONTAINER --}}
    <nav :class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="flex flex-col w-64 h-screen bg-slate-900 text-white fixed left-0 top-0 overflow-y-auto border-r border-slate-800 shadow-2xl transition-transform duration-300 ease-in-out z-50">

        {{-- BRAND HEADER --}}
        <div class="flex items-center p-5 space-x-3 border-b border-slate-800 bg-slate-950/40">
            <div class="shrink-0 flex items-center">
                <a href="{{ $homeRoute }}">
                    <x-application-mark class="block h-10 w-auto bg-white p-1.5 rounded-xl shadow-[0_0_15px_rgba(212,175,55,0.2)]" />
                </a>
            </div>
            <div>
                <h1 class="text-sm font-black leading-tight uppercase tracking-[0.15em] text-white">
                    Lab <span class="text-[#D4AF37]">Guard</span>
                </h1>
                <p class="text-[10px] text-slate-400 font-medium">Araullo University</p>
            </div>
        </div>

        {{-- MENU ITEMS --}}
        <div class="flex-1 px-4 mt-6 space-y-2 overflow-y-auto">

            @auth
            {{-- SUPER ADMIN SECTION --}}
            @if(Auth::user()->role === 'super-admin')
            <p class="text-[10px] px-3 font-bold text-yellow-500 uppercase tracking-widest mb-1">Master Control</p>

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

            <x-sidebar-link href="{{ route('super-admin.analytics') }}" :active="request()->routeIs('super-admin.analytics')" icon="heroicon-o-chart-bar">
                {{ __('Analytics') }}
            </x-sidebar-link>
            @endif

            {{-- ADMIN ONLY SECTION --}}
            @if(Auth::user()->role === 'admin')
            <p class="text-[10px] px-3 font-bold text-[#D4AF37]/80 uppercase tracking-widest mb-1">Admin Control</p>
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
            <p class="text-[10px] px-3 font-bold text-cyan-400/80 uppercase tracking-widest mb-1">Station Terminal</p>
            <x-sidebar-link href="{{ route('personnel.index') }}" :active="request()->routeIs('personnel.index')" icon="heroicon-o-cpu-chip">
                {{ __('Assigned Lab') }}
            </x-sidebar-link>
            <x-sidebar-link href="{{ route('personnel.labs') }}" :active="request()->routeIs('personnel.labs')" icon="heroicon-o-beaker">
                {{ __('Lab Overview') }}
            </x-sidebar-link>
            <x-sidebar-link href="{{ route('personnel.alerts') }}" :active="request()->routeIs('personnel.alerts')" icon="heroicon-o-bell">
                {{ __('Alerts History') }}
            </x-sidebar-link>
            <x-sidebar-link href="{{ route('personnel.sessions') }}" :active="request()->routeIs('personnel.sessions')" icon="heroicon-o-clock">
                {{ __('Session History') }}
            </x-sidebar-link>
            @endif

            {{-- STUDENT ONLY SECTION --}}
            @if(Auth::user()->role === 'student')
            <p class="text-[10px] px-3 font-bold text-indigo-400 uppercase tracking-widest mb-1">Student Portal</p>
            <x-sidebar-link href="{{ route('profile.show') }}" :active="request()->routeIs('profile.show')" icon="heroicon-o-user-circle">
                {{ __('My Profile') }}
            </x-sidebar-link>
            @endif
            @endauth

        </div>

        {{-- FOOTER / USER PROFILE --}}
        <div class="mt-auto p-4 space-y-3 border-t border-slate-800 bg-slate-950/30">
            @auth
            <div class="relative px-1">
                <x-dropdown align="up" width="full">
                    <x-slot name="trigger">
                        <button class="group flex items-center w-full p-2.5 rounded-xl bg-slate-800/80 border border-slate-700/60 hover:border-[#D4AF37]/60 hover:bg-slate-800 transition-all duration-300">
                            <div class="relative shrink-0">
                                <img class="size-9 rounded-lg object-cover mr-3 ring-2 ring-slate-700 group-hover:ring-[#D4AF37] transition-all" src="{{ Auth::user()->profile_photo_url }}" alt="">
                                <div class="absolute -bottom-1 -right-1 size-3 bg-green-500 border-2 border-slate-900 rounded-full"></div>
                            </div>
                            <div class="text-left flex-1 min-w-0">
                                <p class="text-xs font-bold text-white truncate">{{ Auth::user()->name }}</p>
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
                            <svg class="size-4 text-slate-400 group-hover:text-[#D4AF37] transition-colors shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2 border-b border-slate-100">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                @if(Auth::user()->role === 'super-admin') Master Access @else Account Access @endif
                            </p>
                        </div>

                        <x-dropdown-link href="{{ route('profile.show') }}">
                            User Settings
                        </x-dropdown-link>

                        <div class="border-t border-slate-100"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full px-4 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition-all">
                                Sign Out
                            </button>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
            @endauth

            <p class="text-[8px] text-slate-500 uppercase tracking-[0.2em] text-center opacity-70">
                Araullo University
            </p>
        </div>
    </nav>
</div>