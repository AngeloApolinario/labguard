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
        </div>
    </x-slot>

    <div class="py-6 sm:py-12 min-h-screen" x-data="{ 
        addModal: false, 
        editModal: false, 
        massEnrollModal: false,
        currentUser: {},
        search: '',
        selectedRole: ''
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
                    <!-- Mass Enroll Button -->
                    <button @click="massEnrollModal = true" class="w-full sm:w-auto bg-slate-800 hover:bg-slate-900 text-white px-5 py-2.5 rounded-xl font-bold flex items-center justify-center gap-2 transition-all shadow-md active:scale-95 text-sm">
                        <svg class="w-5 h-5 text-[#D4AF37]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        Mass Enroll
                    </button>

                    <!-- Add User Button -->
                    <button @click="addModal = true" class="w-full sm:w-auto bg-[#D4AF37] hover:bg-[#b8962d] text-white px-5 py-2.5 rounded-xl font-bold flex items-center justify-center gap-2 transition-all shadow-md active:scale-95 text-sm">
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
                        <h3 class="text-lg sm:text-xl font-black text-slate-800 tracking-tight">All Users</h3>
                        <span class="bg-slate-100 text-slate-600 px-3 py-1 rounded-full text-xs font-black">
                            {{ $users->count() }} Total
                        </span>
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

                                            <form method="POST" action="{{ route('dashboard.users.destroy', $user->id) }}" onsubmit="return confirm('Permanently remove this user?')">
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

        <!-- Add User Modal -->
        <div x-show="addModal" class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
            <div class="bg-white rounded-2xl sm:rounded-[2rem] p-6 sm:p-10 max-w-xl w-full shadow-2xl border border-white max-h-[90vh] flex flex-col" @click.away="addModal = false">
                <div class="mb-6 shrink-0">
                    <h3 class="text-xl sm:text-2xl font-black text-slate-800 uppercase tracking-tight">System Enrollment</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Add Personnel or Student</p>
                </div>

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
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">ID Number (01-XXXX-XXXXXX)</label>
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
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Password</label>
                            <input type="password" name="password"
                                class="w-full rounded-xl @error('password') border-red-500 @else border-slate-200 @enderror bg-slate-50 text-sm py-3 px-4 focus:ring-[#D4AF37]"
                                required>
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
                            <li>Format file as <code class="bg-slate-200 px-1.5 py-0.5 rounded text-slate-800 font-bold">.csv</code> or <code class="bg-slate-200 px-1.5 py-0.5 rounded text-slate-800 font-bold">.TXT</code>.</li>
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

    @if(session('success'))
    <div id="success-toast" class="fixed bottom-4 right-4 sm:bottom-auto sm:top-6 sm:right-6 z-50 max-w-sm sm:max-w-md w-[calc(100%-2rem)] sm:w-auto bg-slate-900 border border-[#D4AF37] text-white p-4 rounded-2xl shadow-2xl flex items-start gap-3 transition-all duration-500 ease-out translate-y-0 opacity-100">
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