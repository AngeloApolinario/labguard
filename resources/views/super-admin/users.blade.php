<x-app-layout>
    <div class="p-8 bg-[#f8fafc] min-h-screen">

        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">User Management</h1>
                <p class="text-slate-500 text-sm">Manage all system users and permissions</p>
            </div>
            <div class="flex items-center space-x-4">
                <span class="flex items-center px-4 py-2 bg-green-100 text-green-700 rounded-full text-xs font-bold border border-green-200">
                    <span class="size-2 bg-green-500 rounded-full mr-2"></span> System Active
                </span>
                <div class="px-4 py-2 bg-slate-200 text-slate-700 rounded-lg text-xs font-bold uppercase tracking-widest flex items-center">
                    <x-heroicon-o-shield-check class="size-4 mr-2" /> Super Admin
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center mb-8">
            <div class="relative w-1/3">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <x-heroicon-o-magnifying-glass class="size-5" />
                </span>
                <input type="text" placeholder="Search User" class="block w-full pl-10 pr-3 py-2 border border-slate-200 rounded-lg leading-5 bg-white placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-[#D4AF37] sm:text-sm transition">
            </div>
            <button class="flex items-center px-6 py-2.5 bg-[#D4AF37] text-white rounded-lg font-bold text-sm shadow-lg hover:bg-[#b8962d] transition">
                <x-heroicon-o-plus class="size-5 mr-2" /> Add New User
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
            <x-user-mini-card label="Total Users" :value="$stats['total']" color="bg-blue-50" text="text-blue-600" />
            <x-user-mini-card label="Active" :value="$stats['active']" color="bg-green-50" text="text-green-600" />
            <x-user-mini-card label="Inactive" :value="$stats['inactive']" color="bg-slate-50" text="text-slate-400" />
            <x-user-mini-card label="Locked" :value="$stats['locked']" color="bg-red-50" text="text-red-600" />
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-50">
                <h3 class="text-lg font-bold text-slate-800">All Users</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-slate-900 text-sm font-black border-b border-slate-50">
                            <th class="px-8 py-5">Name</th>
                            <th class="px-8 py-5">Email</th>
                            <th class="px-8 py-5">Role</th>
                            <th class="px-8 py-5">Status</th>
                            <th class="px-8 py-5">Labs</th>
                            <th class="px-8 py-5">Last Login</th>
                            <th class="px-8 py-5">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($users as $user)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-8 py-5 font-bold text-slate-800">{{ $user->name }}</td>
                            <td class="px-8 py-5 text-slate-500 text-sm">{{ $user->email }}</td>
                            <td class="px-8 py-5">
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase {{ $user->role === 'admin' ? 'bg-red-500 text-white' : 'bg-slate-200 text-slate-600' }}">
                                    {{ $user->role }}
                                </span>
                            </td>
                            <td class="px-8 py-5">
                                <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase {{ $user->status === 'active' ? 'bg-black text-white' : 'bg-slate-100 text-slate-400' }}">
                                    {{ $user->status ?? 'Active' }}
                                </span>
                            </td>
                            <td class="px-8 py-5 text-xs text-slate-500 italic">All Labs</td>
                            <td class="px-8 py-5 text-xs text-slate-400">2 hours ago</td>
                            <td class="px-8 py-5">
                                <button class="text-slate-400 hover:text-[#D4AF37]">
                                    <x-heroicon-o-ellipsis-vertical class="size-6" />
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-6">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</x-app-layout>