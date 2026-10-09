@php
// Safe fallback so the view works instantly even before updating the controller
$archivedUsers = $archivedUsers ?? \App\Models\User::onlyTrashed()->latest('deleted_at')->get();
@endphp

<x-app-layout>
    <!-- Page Header Slot -->
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-black text-2xl sm:text-3xl md:text-4xl text-slate-800 tracking-tighter uppercase">
                    User <span class="text-[#D4AF37]">Management</span>
                </h2>
                <div class="flex items-center space-x-2 mt-1">
                    <div class="size-2 bg-green-500 rounded-full animate-pulse"></div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">
                        User Records Overview
                    </p>
                </div>
            </div>

            {{-- Header Action Badges --}}
            <div class="flex items-center gap-2">
                <button @click="archiveModal = true"
                    type="button"
                    class="group px-4 py-2.5 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:border-[#D4AF37]/50 hover:shadow-md transition-all flex items-center gap-2.5 cursor-pointer">
                    <div class="size-2 rounded-full {{ $archivedUsers->count() > 0 ? 'bg-amber-500 animate-pulse' : 'bg-slate-300' }}"></div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-600 group-hover:text-slate-900">
                        Archive Vault
                    </span>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black font-mono {{ $archivedUsers->count() > 0 ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-500' }}">
                        {{ $archivedUsers->count() }}
                    </span>
                </button>
            </div>
        </div>
    </x-slot>

    {{-- Root Container: Automatically re-opens modals if validation errors exist --}}
    <div class="py-6 sm:py-12 min-h-screen" x-data="{ 
        addModal: {{ $errors->hasAny(['name', 'email', 'student_number', 'role', 'phone', 'password']) ? 'true' : 'false' }}, 
        editModal: false, 
        massEnrollModal: {{ $errors->has('file') ? 'true' : 'false' }},
        archiveModal: false,
        currentUser: {},
        search: '',
        selectedRole: '',
        archiveSearch: '',
        archiveRole: ''
    }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <!-- Controls Header: Search, Role Filter, Action Buttons -->
            <div class="flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-4 mb-6 sm:mb-8">
                <div class="flex flex-col sm:flex-row flex-1 items-stretch sm:items-center gap-3 w-full">
                    <!-- Search Input -->
                    <div class="relative w-full sm:w-72 md:w-80">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </span>
                        <input type="text"
                            x-model="search"
                            placeholder="Search name, email, or ID..."
                            class="block w-full pl-10 pr-3 py-2.5 border-none rounded-xl bg-white shadow-sm focus:ring-2 focus:ring-[#D4AF37] text-sm transition-all">
                    </div>

                    <!-- Role Filter Dropdown -->
                    <select x-model="selectedRole" class="py-2.5 px-4 border-none rounded-xl bg-white shadow-sm focus:ring-2 focus:ring-[#D4AF37] text-sm font-semibold text-slate-600 transition-all cursor-pointer w-full sm:w-auto">
                        <option value="">All Roles</option>
                        <option value="student">Student</option>
                        <option value="personnel">Personnel</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                    <!-- Archive Vault Trigger Button -->
                    <button @click="archiveModal = true" type="button" class="w-full sm:w-auto bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl font-bold flex items-center justify-center gap-2 transition-all shadow-xs active:scale-95 text-sm cursor-pointer border border-slate-200/80">
                        <svg class="w-4 h-4 text-[#D4AF37]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        <span>Archive Vault</span>
                        <span class="bg-amber-500/15 text-amber-800 text-[10px] font-black px-2 py-0.5 rounded-full font-mono">
                            {{ $archivedUsers->count() }}
                        </span>
                    </button>

                    <!-- Mass Enroll Button -->
                    <button @click="massEnrollModal = true" class="w-full sm:w-auto bg-slate-800 hover:bg-slate-900 text-white px-5 py-2.5 rounded-xl font-bold flex items-center justify-center gap-2 transition-all shadow-md active:scale-95 text-sm cursor-pointer">
                        <svg class="w-5 h-5 text-[#D4AF37]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        Mass Enroll
                    </button>

                    <!-- Add User Button -->
                    <button @click="addModal = true" class="w-full sm:w-auto bg-[#D4AF37] hover:bg-[#b8962d] text-white px-5 py-2.5 rounded-xl font-bold flex items-center justify-center gap-2 transition-all shadow-md active:scale-95 text-sm cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add New User
                    </button>
                </div>
            </div>

            <!-- Main Data Table Container -->
            <div class="bg-white rounded-2xl sm:rounded-[2.5rem] p-4 sm:p-6 md:p-10 shadow-sm border border-slate-100">
                <div class="flex items-center justify-between mb-6 sm:mb-8">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg sm:text-xl font-black text-slate-800 tracking-tight">Active Users</h3>
                        <span class="bg-slate-100 text-slate-600 px-3 py-1 rounded-full text-xs font-black">
                            {{ $users->count() }} Total
                        </span>
                        @if($archivedUsers->count() > 0)
                        <button @click="archiveModal = true" class="text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200/60 px-3 py-1 rounded-full text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer">
                            <span class="size-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                            {{ $archivedUsers->count() }} Soft-Deleted in Archive
                        </button>
                        @endif
                    </div>
                </div>

                <div class="w-full">
                    <table class="w-full text-left border-separate border-spacing-y-3 md:border-spacing-y-2 block md:table">
                        <thead class="hidden md:table-header-group">
                            <tr class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                <th class="pb-4 px-4">Name</th>
                                <th class="pb-4 px-4">Email</th>
                                <th class="pb-4 px-4">Role</th>
                                <th class="pb-4 px-4 text-right pr-10">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="block md:table-row-group space-y-3 md:space-y-0">
                            @foreach($users as $user)
                            <tr class="group bg-white md:bg-transparent rounded-2xl border border-slate-100 md:border-none p-4 md:p-0 shadow-sm md:shadow-none hover:bg-slate-50/80 transition-all block md:table-row"
                                x-show="(search === '' || $el.innerText.toLowerCase().includes(search.toLowerCase())) && (selectedRole === '' || '{{ $user->role }}' === selectedRole)"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform scale-[0.98]"
                                x-transition:enter-end="opacity-100 transform scale-100">

                                <!-- Name Column / Header Block on Mobile -->
                                <td class="py-2 md:py-5 px-0 md:px-4 rounded-l-2xl block md:table-cell">
                                    <div class="flex items-center justify-between md:justify-start gap-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 md:w-8 md:h-8 rounded-full bg-slate-100 flex items-center justify-center font-bold text-slate-400 text-xs shrink-0">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-slate-700">{{ $user->name }}</span>
                                                <span class="text-[10px] text-slate-400 font-medium">{{ $user->student_number }}</span>
                                            </div>
                                        </div>

                                        <!-- Role Badge (Visible on mobile header row) -->
                                        <div class="md:hidden">
                                            <span class="px-2.5 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider
                                                {{ $user->role == 'admin' ? 'bg-rose-500 text-white' : '' }}
                                                {{ $user->role == 'student' ? 'bg-[#D4AF37] text-white' : '' }}
                                                {{ $user->role == 'personnel' ? 'bg-slate-200 text-slate-600' : '' }}">
                                                {{ $user->role }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Email Column -->
                                <td class="py-1 md:py-5 px-0 md:px-4 text-xs md:text-sm text-slate-500 block md:table-cell mt-2 md:mt-0">
                                    <span class="inline-block md:hidden text-[10px] font-bold uppercase text-slate-400 mr-2">Email:</span>
                                    <span class="break-all">{{ $user->email }}</span>
                                </td>

                                <!-- Role Column (Desktop Only) -->
                                <td class="py-5 px-4 hidden md:table-cell">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider
                                        {{ $user->role == 'admin' ? 'bg-rose-500 text-white' : '' }}
                                        {{ $user->role == 'student' ? 'bg-[#D4AF37] text-white' : '' }}
                                        {{ $user->role == 'personnel' ? 'bg-slate-200 text-slate-600' : '' }}">
                                        {{ $user->role }}
                                    </span>
                                </td>

                                <!-- Action Column -->
                                <td class="py-2 md:py-5 px-0 md:px-4 rounded-r-2xl text-left md:text-right md:pr-10 block md:table-cell mt-3 md:mt-0 pt-3 md:pt-5 border-t border-slate-100 md:border-none">
                                    <div x-data="{ dropdown: false }" class="relative inline-block w-full md:w-auto">
                                        <div class="flex md:block justify-end">
                                            <button @click="dropdown = !dropdown" class="text-slate-400 hover:text-slate-800 transition-colors text-xl md:text-2xl px-2 py-1 bg-slate-50 md:bg-transparent rounded-lg">⋮</button>
                                        </div>

                                        <div x-show="dropdown"
                                            x-cloak
                                            @click.away="dropdown = false"
                                            x-transition
                                            class="absolute right-0 z-50 mt-2 w-48 bg-white rounded-2xl shadow-2xl border border-slate-100 py-2 text-left">

                                            <button @click="editModal = true; currentUser = {{ $user }}; dropdown = false"
                                                class="w-full text-left px-5 py-3 text-[10px] font-black uppercase text-slate-600 hover:bg-slate-50 transition-colors">
                                                Edit Profile
                                            </button>

                                            <hr class="border-slate-50">

                                            <form method="POST" action="{{ route('dashboard.users.destroy', $user->id) }}" onsubmit="return confirm('Soft-delete this account? The user will be moved to the Archive Vault.')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="w-full text-left px-5 py-3 text-[10px] font-black uppercase text-rose-500 hover:bg-rose-50 transition-colors">
                                                    Delete Account
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ========================================================================= --}}
        {{-- ARCHIVE VAULT MODAL (AWARD-WINNING LUXURY INTERFACE)                     --}}
        {{-- ========================================================================= --}}
        <div x-show="archiveModal"
            x-cloak
            class="fixed inset-0 z-[110] flex items-center justify-center p-3 sm:p-6 overflow-y-auto"
            role="dialog" aria-modal="true">

            {{-- Backdrop --}}
            <div x-show="archiveModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="archiveModal = false"
                class="fixed inset-0 bg-slate-950/80 backdrop-blur-md transition-opacity"></div>

            {{-- Modal Dialog --}}
            <div x-show="archiveModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                @click.stop
                class="relative transform overflow-hidden rounded-[2.5rem] bg-slate-900 border border-slate-800 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl max-h-[92vh] flex flex-col">

                {{-- Header --}}
                <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 border-b border-slate-800 p-6 sm:p-8 shrink-0 relative">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5 mb-1.5">
                            <span class="size-2 rounded-full bg-amber-400 animate-pulse"></span>
                            <span class="text-[9px] font-black uppercase tracking-[0.3em] text-[#D4AF37]">Quarantine Repository</span>
                        </div>
                        <button type="button" @click="archiveModal = false"
                            class="text-slate-400 hover:text-white transition-colors size-8 rounded-xl bg-slate-800/80 hover:bg-slate-800 flex items-center justify-center cursor-pointer">
                            ✕
                        </button>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mt-1">
                        <div>
                            <h3 class="text-xl sm:text-2xl font-black text-white uppercase tracking-tight">
                                Archived User <span class="text-[#D4AF37]">Vault</span>
                            </h3>
                            <p class="text-xs text-slate-400 font-medium mt-0.5">
                                Soft-deleted student and personnel records. Accounts can be restored to active service at any time.
                            </p>
                        </div>

                        {{-- Search within Archive --}}
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <div class="relative w-full sm:w-64">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-500">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </span>
                                <input type="text"
                                    x-model="archiveSearch"
                                    placeholder="Filter archived records..."
                                    class="w-full bg-slate-800/90 border border-slate-700/80 text-white rounded-xl text-xs py-2 pl-9 pr-3 focus:ring-2 focus:ring-[#D4AF37]/40 focus:border-[#D4AF37] placeholder:text-slate-500">
                            </div>

                            <select x-model="archiveRole"
                                class="bg-slate-800/90 border border-slate-700/80 text-white rounded-xl text-xs py-2 px-3 focus:ring-2 focus:ring-[#D4AF37]/40 focus:border-[#D4AF37] cursor-pointer">
                                <option value="">All</option>
                                <option value="student">Student</option>
                                <option value="personnel">Personnel</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Archive Body List --}}
                <div class="p-6 sm:p-8 overflow-y-auto space-y-3 custom-scroll flex-1">
                    @forelse($archivedUsers as $archived)
                    <div class="p-4 sm:p-5 rounded-2xl bg-slate-800/60 border border-slate-700/60 hover:border-slate-600 transition-all flex flex-col md:flex-row md:items-center justify-between gap-4"
                        x-show="(archiveSearch === '' || '{{ strtolower($archived->name . ' ' . $archived->email . ' ' . $archived->student_number) }}'.includes(archiveSearch.toLowerCase())) && (archiveRole === '' || '{{ $archived->role }}' === archiveRole)"
                        x-transition>

                        {{-- Left: User Meta --}}
                        <div class="flex items-start sm:items-center gap-3.5">
                            <div class="size-11 rounded-2xl bg-slate-900 border border-slate-700/80 flex items-center justify-center font-bold text-slate-400 text-xs shrink-0 shadow-inner">
                                <span class="relative">
                                    {{ substr($archived->name, 0, 1) }}
                                    <span class="absolute -bottom-1 -right-1 size-2 rounded-full bg-rose-500 ring-2 ring-slate-900"></span>
                                </span>
                            </div>

                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="text-sm font-black text-white tracking-tight">{{ $archived->name }}</h4>
                                    <span class="px-2 py-0.5 rounded-md text-[8px] font-black uppercase tracking-wider
                                        {{ $archived->role == 'admin' ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : '' }}
                                        {{ $archived->role == 'student' ? 'bg-[#D4AF37]/20 text-[#D4AF37] border border-[#D4AF37]/30' : '' }}
                                        {{ $archived->role == 'personnel' ? 'bg-slate-700 text-slate-300 border border-slate-600' : '' }}">
                                        {{ $archived->role }}
                                    </span>
                                </div>

                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-[11px] font-medium text-slate-400">
                                    <span class="font-mono text-slate-300">{{ $archived->student_number ?? 'No ID Assigned' }}</span>
                                    <span class="text-slate-600">•</span>
                                    <span>{{ $archived->email }}</span>
                                    @if($archived->phone)
                                    <span class="text-slate-600">•</span>
                                    <span class="font-mono">{{ $archived->phone }}</span>
                                    @endif
                                </div>

                                <p class="text-[9px] font-mono text-rose-400/90 mt-1.5 flex items-center gap-1.5">
                                    <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span>Deleted {{ $archived->deleted_at?->diffForHumans() }} ({{ $archived->deleted_at?->format('M d, Y h:i A') }})</span>
                                </p>
                            </div>
                        </div>

                        {{-- Right: Actions --}}
                        <div class="flex items-center justify-end gap-2 shrink-0 pt-2 md:pt-0 border-t border-slate-700/40 md:border-t-0">
                            {{-- Restore Action --}}
                            <form method="POST" action="{{ Route::has('dashboard.users.restore') ? route('dashboard.users.restore', $archived->id) : url('/dashboard/users/' . $archived->id . '/restore') }}"
                                onsubmit="return confirm('Restore this user account? The user will immediately be able to login and access workstations.')">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-4 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-slate-950 font-black uppercase text-[10px] tracking-wider rounded-xl transition-all shadow-md active:scale-95 cursor-pointer">
                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    <span>Restore Account</span>
                                </button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="py-16 text-center">
                        <div class="size-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 mx-auto mb-3">
                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                        </div>
                        <h4 class="text-sm font-black text-white uppercase tracking-wider">Archive Vault Empty</h4>
                        <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                            No soft-deleted student or personnel accounts are currently quarantined in the archive.
                        </p>
                    </div>
                    @endforelse
                </div>

                {{-- Footer --}}
                <div class="p-5 sm:p-6 bg-slate-950/70 border-t border-slate-800 flex items-center justify-between shrink-0">
                    <p class="text-[9px] text-slate-400 font-mono uppercase tracking-wider">
                        Archived Total: <span class="text-[#D4AF37] font-bold">{{ $archivedUsers->count() }} accounts</span>
                    </p>
                    <button type="button" @click="archiveModal = false"
                        class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-[10px] font-black uppercase tracking-wider transition-all cursor-pointer">
                        Close Vault
                    </button>
                </div>
            </div>
        </div>

        <!-- Add User Modal -->
        <div x-show="addModal" class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
            <div class="bg-white rounded-2xl sm:rounded-[2rem] p-6 sm:p-10 max-w-xl w-full shadow-2xl border border-white max-h-[90vh] flex flex-col" @click.away="addModal = false">
                <div class="mb-4 shrink-0">
                    <h3 class="text-xl sm:text-2xl font-black text-slate-800 uppercase tracking-tight">System Enrollment</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Add Personnel or Student</p>
                </div>

                {{-- Prominent Validation Alert inside the Modal --}}
                @if($errors->hasAny(['name', 'email', 'student_number', 'role', 'phone', 'password']))
                <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 flex items-start gap-2.5 mb-4 text-xs font-semibold shrink-0">
                    <svg class="size-4 shrink-0 text-rose-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <span class="font-black uppercase tracking-wider block text-[10px] text-rose-800">Enrollment Error</span>
                        <span>{{ $errors->first() }}</span>
                    </div>
                </div>
                @endif

                <form action="{{ route('dashboard.users.store') }}" method="POST" class="space-y-4 sm:space-y-5 overflow-y-auto pr-1">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Full Name</label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                class="w-full rounded-xl @error('name') border-red-500 @else border-slate-200 @enderror bg-slate-50 text-sm py-3 px-4 focus:ring-[#D4AF37]"
                                placeholder="Juan Dela Cruz" required>
                            @error('name') <p class="text-[10px] text-red-500 font-bold uppercase mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                class="w-full rounded-xl @error('email') border-red-500 @else border-slate-200 @enderror bg-slate-50 text-sm py-3 px-4 focus:ring-[#D4AF37]"
                                placeholder="juan@phinmaed.com" required>
                            @error('email') <p class="text-[10px] text-red-500 font-bold uppercase mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">ID Number (XX-XXXX-XXXX...)</label>
                            <input type="text" name="student_number" value="{{ old('student_number') }}"
                                class="w-full rounded-xl @error('student_number') border-red-500 @else border-slate-200 @enderror bg-slate-50 text-sm py-3 px-4 focus:ring-[#D4AF37]"
                                placeholder="01-2324-048389" required>
                            @error('student_number') <p class="text-[10px] text-red-500 font-bold uppercase mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Role</label>
                            <select name="role" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm py-3 px-4 focus:ring-[#D4AF37]">
                                <option value="student" {{ old('role') == 'student' ? 'selected' : '' }}>Student</option>
                                <option value="personnel" {{ old('role') == 'personnel' ? 'selected' : '' }}>Personnel (Teacher)</option>
                                <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>System Admin</option>
                            </select>
                            @error('role') <p class="text-[10px] text-red-500 font-bold uppercase mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Contact (09XXXXXXXXX)</label>
                            <input type="text" name="phone" value="{{ old('phone') }}"
                                class="w-full rounded-xl @error('phone') border-red-500 @else border-slate-200 @enderror bg-slate-50 text-sm py-3 px-4 focus:ring-[#D4AF37]"
                                placeholder="09123456789" required>
                            @error('phone') <p class="text-[10px] text-red-500 font-bold uppercase mt-1">{{ $message }}</p> @enderror
                        </div>

                        {{-- Subtle Interactive Password Suite --}}
                        <div class="space-y-1" x-data="{
                            show: false,
                            password: '',
                            get minLength() { return this.password.length >= 8; },
                            get hasUpper() { return /[A-Z]/.test(this.password); },
                            get hasLower() { return /[a-z]/.test(this.password); },
                            get hasNumber() { return /[0-9]/.test(this.password); },
                            get hasSpecial() { return /[^A-Za-z0-9]/.test(this.password); },
                            get score() {
                                let s = 0;
                                if (this.minLength) s++;
                                if (this.hasUpper && this.hasLower) s++;
                                if (this.hasNumber) s++;
                                if (this.hasSpecial) s++;
                                return s;
                            },
                            get label() {
                                if (!this.password) return '';
                                if (this.score <= 1) return 'Weak';
                                if (this.score === 2) return 'Fair';
                                if (this.score === 3) return 'Good';
                                return 'Strong';
                            },
                            get barColor() {
                                if (this.score <= 1) return 'bg-rose-500/80';
                                if (this.score === 2) return 'bg-amber-400/80';
                                if (this.score === 3) return 'bg-sky-400/80';
                                return 'bg-emerald-500';
                            },
                            get textColor() {
                                if (this.score <= 1) return 'text-rose-500';
                                if (this.score === 2) return 'text-amber-500';
                                if (this.score === 3) return 'text-sky-500';
                                return 'text-emerald-600';
                            }
                        }">
                            <div class="flex items-center justify-between ml-1">
                                <label class="text-[10px] font-black text-slate-400 uppercase">Password</label>
                                <span :class="textColor" x-text="label" class="uppercase text-[8px] font-black tracking-wider transition-colors"></span>
                            </div>
                            <div class="relative">
                                <input :type="show ? 'text' : 'password'"
                                    name="password"
                                    x-model="password"
                                    class="w-full rounded-xl @error('password') border-red-500 @else border-slate-200 @enderror bg-slate-50 text-sm py-3 px-4 pr-11 focus:ring-[#D4AF37]"
                                    placeholder="Min. 8 characters"
                                    required>
                                <button type="button" @click="show = !show" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none">
                                    <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>

                            {{-- 2px Progress Line --}}
                            <div class="grid grid-cols-4 gap-1 h-[2px] w-full bg-slate-200 rounded-full overflow-hidden mt-1.5">
                                <div class="h-full rounded-full transition-all duration-300" :class="score >= 1 ? barColor : 'bg-transparent'"></div>
                                <div class="h-full rounded-full transition-all duration-300" :class="score >= 2 ? barColor : 'bg-transparent'"></div>
                                <div class="h-full rounded-full transition-all duration-300" :class="score >= 3 ? barColor : 'bg-transparent'"></div>
                                <div class="h-full rounded-full transition-all duration-300" :class="score >= 4 ? barColor : 'bg-transparent'"></div>
                            </div>

                            {{-- Inline Requirements --}}
                            <div class="justify-between flex flex-wrap items-center gap-x-2.5 gap-y-0.5 text-[8px] font-medium tracking-wide text-slate-500 pt-0.5">
                                <span :class="minLength ? 'text-emerald-600 font-bold' : 'text-slate-400'" class="transition-colors flex items-center gap-1">
                                    <span class="size-1 rounded-full" :class="minLength ? 'bg-emerald-500' : 'bg-slate-300'"></span> 8+ chars
                                </span>
                                <span :class="(hasUpper && hasLower) ? 'text-emerald-600 font-bold' : 'text-slate-400'" class="transition-colors flex items-center gap-1">
                                    <span class="size-1 rounded-full" :class="(hasUpper && hasLower) ? 'bg-emerald-500' : 'bg-slate-300'"></span> Aa mixed
                                </span>
                                <span :class="hasNumber ? 'text-emerald-600 font-bold' : 'text-slate-400'" class="transition-colors flex items-center gap-1">
                                    <span class="size-1 rounded-full" :class="hasNumber ? 'bg-emerald-500' : 'bg-slate-300'"></span> 0-9 digit
                                </span>
                                <span :class="hasSpecial ? 'text-emerald-600 font-bold' : 'text-slate-400'" class="transition-colors flex items-center gap-1">
                                    <span class="size-1 rounded-full" :class="hasSpecial ? 'bg-emerald-500' : 'bg-slate-300'"></span> Symbol
                                </span>
                            </div>

                            @error('password') <p class="text-[10px] text-red-500 font-bold uppercase mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row gap-3 sm:gap-4 pt-4 shrink-0">
                        <button type="button" @click="addModal = false"
                            class="w-full sm:flex-1 py-3.5 sm:py-4 text-xs font-black text-slate-400 uppercase tracking-widest hover:text-slate-600 transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                            class="w-full sm:flex-1 py-3.5 sm:py-4 bg-[#D4AF37] text-white rounded-2xl text-xs font-black uppercase tracking-[0.2em] shadow-lg shadow-[#D4AF37]/30 hover:scale-[1.02] transition-transform">
                            Enroll User
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div x-show="editModal" class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
            <div class="bg-white rounded-2xl sm:rounded-[2.5rem] p-6 sm:p-10 max-w-2xl w-full shadow-2xl border border-white max-h-[90vh] flex flex-col" @click.away="editModal = false">

                <div class="mb-6 shrink-0">
                    <h3 class="text-xl sm:text-2xl font-black text-slate-800 uppercase tracking-tight">Edit Personal Information</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Updating Account: <span x-text="currentUser.name" class="text-[#D4AF37]"></span></p>
                </div>

                <form :action="'/dashboard/users/' + currentUser.id" method="POST" class="space-y-4 sm:space-y-6 overflow-y-auto pr-1">
                    @csrf @method('PATCH')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Full Legal Name</label>
                            <input type="text" name="name" x-model="currentUser.name" class="w-full rounded-xl sm:rounded-2xl border-slate-200 bg-slate-50 text-sm py-3 sm:py-4 px-4 sm:px-5 focus:ring-[#D4AF37] focus:border-[#D4AF37]" required>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Email Address</label>
                            <input type="email" name="email" x-model="currentUser.email" class="w-full rounded-xl sm:rounded-2xl border-slate-200 bg-slate-50 text-sm py-3 sm:py-4 px-4 sm:px-5 focus:ring-[#D4AF37] focus:border-[#D4AF37]" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Student / Employee ID</label>
                            <input type="text" name="student_number" x-model="currentUser.student_number" class="w-full rounded-xl sm:rounded-2xl border-slate-200 bg-slate-50 text-sm py-3 sm:py-4 px-4 sm:px-5 focus:ring-[#D4AF37] focus:border-[#D4AF37]" required>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Contact Number</label>
                            <input type="text" name="phone" x-model="currentUser.phone" class="w-full rounded-xl sm:rounded-2xl border-slate-200 bg-slate-50 text-sm py-3 sm:py-4 px-4 sm:px-5 focus:ring-[#D4AF37] focus:border-[#D4AF37]" required>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-1">System Permissions (Role)</label>
                        <select name="role" x-model="currentUser.role" class="w-full rounded-xl sm:rounded-2xl border-slate-200 bg-slate-50 text-sm py-3 sm:py-4 px-4 sm:px-5 focus:ring-[#D4AF37] focus:border-[#D4AF37]">
                            <option value="student">Student</option>
                            <option value="personnel">Personnel (Teacher)</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row gap-3 sm:gap-4 pt-4 shrink-0">
                        <button type="button" @click="editModal = false" class="w-full sm:flex-1 py-3.5 sm:py-4 text-xs font-black text-slate-400 uppercase tracking-[0.2em] hover:text-slate-600 transition-colors">
                            Discard Changes
                        </button>
                        <button type="submit" class="w-full sm:flex-1 py-3.5 sm:py-4 bg-black text-white rounded-xl sm:rounded-[1.5rem] text-xs font-black uppercase tracking-[0.2em] shadow-xl hover:bg-slate-800 hover:scale-[1.02] transition-all">
                            Save Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Mass Enroll Modal -->
        <div x-show="massEnrollModal" class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
            <div class="bg-white rounded-2xl sm:rounded-[2.5rem] p-6 sm:p-8 max-w-2xl w-full shadow-2xl border border-white max-h-[90vh] flex flex-col" @click.away="massEnrollModal = false">

                <div class="mb-4 sm:mb-6 shrink-0">
                    <h3 class="text-xl sm:text-2xl font-black text-slate-800 uppercase tracking-tight">Mass User Enrollment</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Upload Excel or CSV file to enroll multiple users</p>
                </div>

                <div class="overflow-y-auto pr-1">
                    <!-- Step-by-Step Instructions & Format Guide -->
                    <div class="bg-slate-50 rounded-2xl p-4 sm:p-5 mb-6 border border-slate-200/80">
                        <h4 class="text-xs font-black uppercase text-[#D4AF37] tracking-wider mb-2 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Excel / CSV File Format Instructions
                        </h4>

                        <ol class="list-decimal list-inside text-xs text-slate-600 space-y-1 font-medium mb-4">
                            <li>The first row of your spreadsheet <strong>must</strong> contain exact column header names.</li>
                            <li>Valid roles are: <code class="bg-slate-200 px-1.5 py-0.5 rounded text-slate-800 font-bold">student</code>, <code class="bg-slate-200 px-1.5 py-0.5 rounded text-slate-800 font-bold">personnel</code>, or <code class="bg-slate-200 px-1.5 py-0.5 rounded text-slate-800 font-bold">admin</code>.</li>
                            <li>Format file as <code class="bg-slate-200 px-1.5 py-0.5 rounded text-slate-800 font-bold">.csv</code> or <code class="bg-slate-200 px-1.5 py-0.5 rounded text-slate-800 font-bold">.xlsx</code>.</li>
                        </ol>

                        <p class="text-[10px] font-black text-slate-400 uppercase mb-2">Required Columns & Sample Header:</p>

                        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                            <table class="w-full text-left text-[11px] font-mono text-slate-700 min-w-[500px]">
                                <thead class="bg-slate-100 text-[10px] font-black uppercase text-slate-500">
                                    <tr>
                                        <th class="p-2 border-r border-slate-200">name</th>
                                        <th class="p-2 border-r border-slate-200">email</th>
                                        <th class="p-2 border-r border-slate-200">student_number</th>
                                        <th class="p-2 border-r border-slate-200">phone</th>
                                        <th class="p-2 border-r border-slate-200">role</th>
                                        <th class="p-2">password</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="p-2 border-r border-slate-100">Juan Dela Cruz</td>
                                        <td class="p-2 border-r border-slate-100">juan@phinmaed.com</td>
                                        <td class="p-2 border-r border-slate-100">01-2324-048389</td>
                                        <td class="p-2 border-r border-slate-100">09123456789</td>
                                        <td class="p-2 border-r border-slate-100">student</td>
                                        <td class="p-2">password123</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- File Upload Form -->
                    <form action="{{ route('dashboard.users.import') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Upload File (.csv, .xlsx)</label>
                            <input type="file" name="file" accept=".csv, .xlsx, .xls" required
                                class="block w-full text-xs sm:text-sm text-slate-500 file:mr-2 sm:file:mr-4 file:py-2.5 sm:file:py-3 file:px-4 sm:file:px-6 file:rounded-xl file:border-0 file:text-[10px] sm:file:text-xs file:font-black file:uppercase file:bg-slate-800 file:text-white hover:file:bg-slate-900 file:cursor-pointer border border-slate-200 rounded-2xl bg-slate-50 p-2">
                            @error('file') <p class="text-[10px] text-red-500 font-bold uppercase mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row gap-3 sm:gap-4 pt-2">
                            <button type="button" @click="massEnrollModal = false"
                                class="w-full sm:flex-1 py-3.5 sm:py-4 text-xs font-black text-slate-400 uppercase tracking-widest hover:text-slate-600 transition-colors">
                                Cancel
                            </button>
                            <button type="submit"
                                class="w-full sm:flex-1 py-3.5 sm:py-4 bg-slate-800 text-white rounded-2xl text-xs font-black uppercase tracking-[0.2em] shadow-lg hover:bg-slate-900 hover:scale-[1.02] transition-transform">
                                Import & Enroll Users
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Error Toast Notification --}}
    @if($errors->any())
    <div id="error-toast" class="fixed bottom-4 right-4 sm:bottom-auto sm:top-6 sm:right-6 z-[150] max-w-sm sm:max-w-md w-[calc(100%-2rem)] sm:w-auto bg-slate-900 border border-rose-500 text-white p-4 rounded-2xl shadow-2xl flex items-start gap-3 transition-all duration-500 ease-out translate-y-0 opacity-100">
        <div class="size-8 rounded-xl bg-rose-500/20 border border-rose-500/40 flex items-center justify-center shrink-0 mt-0.5">
            <svg class="size-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <div>
            <h4 class="text-xs font-black uppercase tracking-widest text-rose-400">Enrollment Error</h4>
            <p class="text-xs font-semibold text-slate-300 mt-0.5 leading-relaxed">
                {{ $errors->first() }}
            </p>
        </div>
    </div>

    <script>
        setTimeout(() => {
            const toast = document.getElementById('error-toast');
            if (toast) {
                toast.classList.add('opacity-0', '-translate-y-4');
                setTimeout(() => toast.remove(), 500);
            }
        }, 6000);
    </script>
    @endif

    {{-- Success Toast Notification --}}
    @if(session('success'))
    <div id="success-toast" class="fixed bottom-4 right-4 sm:bottom-auto sm:top-6 sm:right-6 z-[150] max-w-sm sm:max-w-md w-[calc(100%-2rem)] sm:w-auto bg-slate-900 border border-[#D4AF37] text-white p-4 rounded-2xl shadow-2xl flex items-start gap-3 transition-all duration-500 ease-out translate-y-0 opacity-100">
        <div class="size-8 rounded-xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center shrink-0 mt-0.5">
            <svg class="size-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <div>
            <h4 class="text-xs font-black uppercase tracking-widest text-[#D4AF37]">Success</h4>
            <p class="text-xs font-semibold text-slate-300 mt-0.5 leading-relaxed">
                {{ session('success') }}
            </p>
        </div>
    </div>

    <script>
        setTimeout(() => {
            const toast = document.getElementById('success-toast');
            if (toast) {
                toast.classList.add('opacity-0', '-translate-y-4');
                setTimeout(() => toast.remove(), 500);
            }
        }, 5000);
    </script>
    @endif
</x-app-layout>