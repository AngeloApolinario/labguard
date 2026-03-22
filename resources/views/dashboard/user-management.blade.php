<x-app-layout>
    <div class="py-12 bg-slate-50 min-h-screen" x-data="{ 
        addModal: false, 
        editModal: false, 
        currentUser: {},
        search: '' 
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="flex justify-between items-center mb-8">
                <div class="relative w-1/3">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </span>
                    <input type="text"
                        x-model="search"
                        placeholder="Search name, email, or ID Number..."
                        class="block w-full pl-10 pr-3 py-2 border-none rounded-lg bg-white shadow-sm focus:ring-2 focus:ring-[#D4AF37] text-sm transition-all">
                </div>

                <button @click="addModal = true" class="bg-[#D4AF37] hover:bg-[#b8962d] text-white px-6 py-2 rounded-lg font-bold flex items-center gap-2 transition-all shadow-md active:scale-95">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add New User
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8 text-slate-800">
                <div class="bg-blue-50 border border-blue-100 p-6 rounded-2xl shadow-sm">
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Total Users</p>
                    <h3 class="text-4xl font-black text-blue-600 mt-2">{{ $users->count() }}</h3>
                </div>
                <div class="bg-green-50 border border-green-100 p-6 rounded-2xl shadow-sm">
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Active</p>
                    <h3 class="text-4xl font-black text-green-600 mt-2"></h3>
                </div>
                <div class="bg-slate-50 border border-slate-200 p-6 rounded-2xl shadow-sm">
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Inactive</p>
                    <h3 class="text-4xl font-black text-slate-400 mt-2"></h3>
                </div>
                <div class="bg-red-50 border border-red-100 p-6 rounded-2xl shadow-sm">
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Locked</p>
                    <h3 class="text-4xl font-black text-red-600 mt-2"></h3>
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] p-10 shadow-sm border border-slate-100">
                <h2 class="text-xl font-black text-slate-800 mb-8 tracking-tight">All Users</h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-separate border-spacing-y-2">
                        <thead>
                            <tr class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em]">
                                <th class="pb-4 px-4">Name</th>
                                <th class="pb-4 px-4">Email</th>
                                <th class="pb-4 px-4">Role</th>
                                <th class="pb-4 px-4 text-right pr-10">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr class="group bg-white hover:bg-slate-50 transition-all"
                                x-show="(search === '' || $el.innerText.toLowerCase().includes(search.toLowerCase()))"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform scale-[0.98]"
                                x-transition:enter-end="opacity-100 transform scale-100">

                                <td class="py-5 px-4 rounded-l-2xl">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center font-bold text-slate-400 text-xs">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-700">{{ $user->name }}</span>
                                            <span class="text-[10px] text-slate-400 font-medium">{{ $user->student_number }}</span>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-5 px-4 text-sm text-slate-500">{{ $user->email }}</td>

                                <td class="py-5 px-4">
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider
            {{ $user->role == 'admin' ? 'bg-rose-500 text-white' : '' }}
            {{ $user->role == 'student' ? 'bg-[#D4AF37] text-white' : '' }}
            {{ $user->role == 'personnel' ? 'bg-slate-200 text-slate-600' : '' }}">
                                        {{ $user->role }}
                                    </span>
                                </td>

                                <td class="py-5 px-4 rounded-r-2xl text-right pr-10">
                                    <div x-data="{ dropdown: false }" class="relative inline-block">
                                        <button @click="dropdown = !dropdown" class="text-slate-300 hover:text-slate-800 transition-colors text-2xl px-2">⋮</button>

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

        <div x-show="addModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
            <div class="bg-white rounded-[2rem] p-10 max-w-xl w-full shadow-2xl border border-white" @click.away="addModal = false">
                <div class="mb-8">
                    <h3 class="text-2xl font-black text-slate-800 uppercase tracking-tight">System Enrollment</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Add Personnel or Student</p>
                </div>

                <form action="{{ route('dashboard.users.store') }}" method="POST" class="space-y-5">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Full Name</label>
                            <input type="text" name="name" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm py-3 px-4 focus:ring-[#D4AF37]" placeholder="Juan Dela Cruz" required>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Email</label>
                            <input type="email" name="email" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm py-3 px-4 focus:ring-[#D4AF37]" placeholder="juan@phinmaed.com" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Student ID</label>
                            <input type="text" name="student_number" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm py-3 px-4 focus:ring-[#D4AF37]" placeholder="01-XXXX-XXXXXX" required>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Role</label>
                            <select name="role" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm py-3 px-4 focus:ring-[#D4AF37]">
                                <option value="student">Student</option>
                                <option value="personnel">Personnel (Teacher)</option>
                                <option value="admin">System Admin</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Password</label>
                        <input type="password" name="password" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm py-3 px-4 focus:ring-[#D4AF37]" required>
                    </div>

                    <div class="flex gap-4 pt-6">
                        <button type="button" @click="addModal = false" class="flex-1 py-4 text-xs font-black text-slate-400 uppercase tracking-widest hover:text-slate-600">Cancel</button>
                        <button type="submit" class="flex-1 py-4 bg-[#D4AF37] text-white rounded-2xl text-xs font-black uppercase tracking-[0.2em] shadow-lg shadow-[#D4AF37]/30 hover:scale-[1.02] transition-transform">Enroll User</button>
                    </div>
                </form>
            </div>
        </div>

        <div x-show="editModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" x-cloak>
            <div class="bg-white rounded-[2.5rem] p-10 max-w-2xl w-full shadow-2xl border border-white" @click.away="editModal = false">

                <div class="mb-8">
                    <h3 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Edit Personal Information</h3>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Updating Account: <span x-text="currentUser.name" class="text-[#D4AF37]"></span></p>
                </div>

                <form :action="'/dashboard/users/' + currentUser.id" method="POST" class="space-y-6">
                    @csrf @method('PATCH')

                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Full Legal Name</label>
                            <input type="text" name="name" x-model="currentUser.name" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-sm py-4 px-5 focus:ring-[#D4AF37] focus:border-[#D4AF37]" required>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Email Address</label>
                            <input type="email" name="email" x-model="currentUser.email" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-sm py-4 px-5 focus:ring-[#D4AF37] focus:border-[#D4AF37]" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Student / Employee ID</label>
                            <input type="text" name="student_number" x-model="currentUser.student_number" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-sm py-4 px-5 focus:ring-[#D4AF37] focus:border-[#D4AF37]" required>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-black text-slate-400 uppercase ml-1">Contact Number</label>
                            <input type="text" name="phone" x-model="currentUser.phone" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-sm py-4 px-5 focus:ring-[#D4AF37] focus:border-[#D4AF37]" required>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[10px] font-black text-slate-400 uppercase ml-1">System Permissions (Role)</label>
                        <select name="role" x-model="currentUser.role" class="w-full rounded-2xl border-slate-200 bg-slate-50 text-sm py-4 px-5 focus:ring-[#D4AF37] focus:border-[#D4AF37]">
                            <option value="student">Student</option>
                            <option value="personnel">Personnel (Teacher)</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>

                    <div class="flex gap-4 pt-6">
                        <button type="button" @click="editModal = false" class="flex-1 py-4 text-xs font-black text-slate-400 uppercase tracking-[0.2em] hover:text-slate-600 transition-colors">
                            Discard Changes
                        </button>
                        <button type="submit" class="flex-1 py-4 bg-black text-white rounded-[1.5rem] text-xs font-black uppercase tracking-[0.2em] shadow-xl hover:bg-slate-800 hover:scale-[1.02] transition-all">
                            Save Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>