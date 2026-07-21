<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-black text-4xl text-slate-800 tracking-tighter uppercase">
                    Facility <span class="text-[#D4AF37]">Inventory</span>
                </h2>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.3em] mt-1">Database-Synced Lab Management</p>
            </div>

            {{-- TRIGGER: New Lab Modal --}}
            <button onclick="openLabModal()" type="button" class="flex items-center space-x-2 bg-slate-800 text-white px-6 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-[#D4AF37] transition-all shadow-xl shadow-slate-200 active:scale-95">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" />
                </svg>
                <span>New Lab</span>
            </button>
        </div>
    </x-slot>

    <div class="py-12 px-6 bg-[#F8FAFC] min-h-screen">
        <div class="max-w-7xl mx-auto">

            {{-- Flash Success Message --}}
            @if(session('success'))
            <div id="flash-message" class="mb-8 p-4 bg-emerald-50 border border-emerald-100 rounded-2xl flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="size-2 bg-emerald-500 rounded-full animate-pulse"></div>
                    <p class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">{{ session('success') }}</p>
                </div>
                <button onclick="document.getElementById('flash-message').remove()" class="text-emerald-400">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M6 18L18 6M6 6l12 12" stroke-width="2" />
                    </svg>
                </button>
            </div>
            @endif

            {{-- Grid Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                @forelse($labs as $lab)
                <div class="group relative bg-white border border-slate-200 rounded-[3rem] p-10 transition-all duration-500 hover:shadow-[0_30px_60px_rgba(0,0,0,0.05)] hover:-translate-y-2">

                    <div class="flex justify-between items-start mb-12">
                        <div class="p-4 bg-slate-50 rounded-2xl group-hover:bg-[#D4AF37]/10 transition-colors">
                            <svg class="size-8 text-slate-400 group-hover:text-[#D4AF37] transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div class="text-right">
                            <span class="text-[9px] font-black text-slate-300 uppercase tracking-widest">FACILITY ID</span>
                            <p class="text-xs font-black text-slate-700 uppercase">#{{ str_pad($lab->id, 3, '0', STR_PAD_LEFT) }}</p>
                        </div>
                    </div>

                    <h3 class="text-4xl font-black text-slate-800 mb-2 tracking-tighter uppercase">{{ $lab->name }}</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-6">{{ $lab->location }}</p>

                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-tighter">Total Units</p>
                                <p class="text-xl font-black text-slate-800">{{ $lab->computers->count() }}</p>
                            </div>
                            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-tighter">Active Now</p>
                                <p class="text-xl font-black text-[#D4AF37]">{{ $lab->computers->where('status', 'active')->count() }}</p>
                            </div>
                        </div>

                        @php
                        $total = $lab->computers->count();
                        $active = $lab->computers->where('status', 'active')->count();
                        $percent = $total > 0 ? round(($active / $total) * 100) : 0;
                        @endphp

                        <div class="space-y-2">
                            <div class="flex justify-between text-[9px] font-black uppercase tracking-widest text-slate-500">
                                <span>Utilization</span>
                                <span>{{ $percent }}%</span>
                            </div>
                            <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-slate-800 transition-all duration-1000 group-hover:bg-[#D4AF37]" style="width: {{ $percent }}%"></div>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-slate-50 grid grid-cols-2 gap-4">
                            <button type="button"
                                data-lab-id="{{ $lab->id }}"
                                data-lab-name="{{ $lab->name }}"
                                data-lab-location="{{ $lab->location }}"
                                data-lab-count="{{ $lab->computers->count() }}"
                                data-lab-computers="{{ json_encode($lab->computers) }}"
                                class="inspect-btn w-full py-4 bg-slate-50 text-slate-600 text-[10px] flex items-center justify-center font-black uppercase tracking-[0.2em] rounded-2xl hover:bg-slate-800 hover:text-white transition-all cursor-pointer">
                                Inspect
                            </button>
                            <a href="{{ route('dashboard.labs.schedule', $lab->id) }}" class="w-full py-4 bg-[#D4AF37]/10 text-[#D4AF37] text-[10px] flex items-center justify-center font-black uppercase tracking-[0.2em] rounded-2xl hover:bg-[#D4AF37] hover:text-white transition-all">
                                Schedule
                            </a>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full py-20 text-center border-2 border-dashed border-slate-200 rounded-[3rem] bg-white">
                    <p class="text-slate-400 font-black uppercase tracking-widest">No Facility Data detected.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- CREATE LAB MODAL --}}
    <div id="labModal" class="hidden fixed inset-0 z-[10000] items-center justify-center p-6 bg-slate-900/90 backdrop-blur-md">
        <div class="bg-white w-full max-w-lg rounded-[3rem] p-10 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-[#D4AF37] to-amber-200"></div>

            <h3 class="text-4xl font-black text-slate-900 uppercase tracking-tighter mb-2">Initialize <span class="text-[#D4AF37]">Laboratory</span></h3>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-10">Deploying new facility to central server</p>

            <form action="{{ route('dashboard.labs.store') }}" method="POST" onsubmit="setLoadingState(this, 'submitBtn', 'Initializing...')">
                @csrf
                <div class="space-y-8">
                    <div>
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] ml-2">Laboratory Name</label>
                        <input type="text" name="name" required placeholder="e.g., Computer Lab 1" class="w-full mt-2 bg-slate-50 border-slate-100 border rounded-2xl p-4 text-sm font-black focus:ring-2 focus:ring-[#D4AF37] transition-all outline-none uppercase">
                    </div>

                    <div>
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] ml-2">Facility Location</label>
                        <input type="text" name="location" required placeholder="e.g., 3rd Floor" class="w-full mt-2 bg-slate-50 border-slate-100 border rounded-2xl p-4 text-sm font-black focus:ring-2 focus:ring-[#D4AF37] transition-all outline-none">
                    </div>

                    <div>
                        <label class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] ml-2">PC Capacity</label>
                        <input type="number" name="pc_count" min="1" max="60" required placeholder="Units" class="w-full mt-2 bg-slate-50 border-slate-100 border rounded-2xl p-4 text-sm font-black focus:ring-2 focus:ring-[#D4AF37] outline-none">
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button type="button" onclick="closeLabModal()" class="flex-1 py-5 text-[10px] font-black uppercase text-slate-400">Cancel</button>
                        <button type="submit" id="submitBtn" class="flex-1 py-5 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-[#D4AF37] shadow-xl transition-all">
                            Create Facility
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- INSPECT & EDIT MODAL --}}
    <div id="inspectModal" class="hidden fixed inset-0 z-[10000] items-center justify-center p-6 bg-slate-900/90 backdrop-blur-md">
        <div class="bg-white w-full max-w-2xl rounded-[3rem] p-10 shadow-2xl relative overflow-hidden max-h-[90vh] flex flex-col">
            <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-slate-800 to-[#D4AF37]"></div>

            <div class="flex justify-between items-start mb-6">
                <div>
                    <h3 id="inspectTitle" class="text-3xl font-black text-slate-900 uppercase tracking-tighter">LAB INSPECTION</h3>
                    <p id="inspectSubtitle" class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Manage unit inventory & facility parameters</p>
                </div>
                <button onclick="closeInspectModal()" class="text-slate-400 hover:text-slate-700 font-black p-2">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Tab Switcher --}}
            <div class="flex gap-2 border-b border-slate-100 pb-4 mb-6">
                <button type="button" onclick="switchInspectTab('units')" id="tabUnitsBtn" class="px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-slate-900 text-white transition-all">
                    Units List
                </button>
                <button type="button" onclick="switchInspectTab('edit')" id="tabEditBtn" class="px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all">
                    Edit Facility
                </button>
            </div>

            {{-- TAB 1: UNITS LIST --}}
            <div id="inspectUnitsTab" class="overflow-y-auto flex-1 pr-2">
                <div id="computersListContainer" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    {{-- Populated dynamically via JavaScript --}}
                </div>
            </div>

            {{-- TAB 2: EDIT FORM --}}
            <div id="inspectEditTab" class="hidden overflow-y-auto flex-1 pr-2">
                <form id="editLabForm" method="POST" onsubmit="setLoadingState(this, 'updateBtn', 'Updating...')">
                    @csrf
                    @method('PUT')
                    <div class="space-y-6">
                        <div>
                            <label class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] ml-2">Laboratory Name</label>
                            <input type="text" id="editName" name="name" required class="w-full mt-2 bg-slate-50 border-slate-100 border rounded-2xl p-4 text-sm font-black focus:ring-2 focus:ring-[#D4AF37] outline-none uppercase">
                        </div>

                        <div>
                            <label class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] ml-2">Facility Location</label>
                            <input type="text" id="editLocation" name="location" required class="w-full mt-2 bg-slate-50 border-slate-100 border rounded-2xl p-4 text-sm font-black focus:ring-2 focus:ring-[#D4AF37] outline-none">
                        </div>

                        <div>
                            <label class="text-[9px] font-black text-slate-500 uppercase tracking-[0.2em] ml-2">Adjust PC Capacity (Adds/Removes units)</label>
                            <input type="number" id="editCount" name="pc_count" min="1" max="60" required class="w-full mt-2 bg-slate-50 border-slate-100 border rounded-2xl p-4 text-sm font-black focus:ring-2 focus:ring-[#D4AF37] outline-none">
                        </div>

                        <div class="flex gap-4 pt-4">
                            <button type="button" onclick="closeInspectModal()" class="flex-1 py-5 text-[10px] font-black uppercase text-slate-400">Cancel</button>
                            <button type="submit" id="updateBtn" class="flex-1 py-5 bg-[#D4AF37] text-white rounded-2xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-600 shadow-xl transition-all">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Attach listeners to inspect buttons
            document.querySelectorAll('.inspect-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-lab-id');
                    const name = this.getAttribute('data-lab-name');
                    const location = this.getAttribute('data-lab-location');
                    const totalCount = this.getAttribute('data-lab-count');

                    let computers = [];
                    try {
                        computers = JSON.parse(this.getAttribute('data-lab-computers') || '[]');
                    } catch (e) {
                        console.error('Failed to parse computers JSON', e);
                    }

                    openInspectModal(id, name, location, totalCount, computers);
                });
            });
        });

        // Create Modal Controls
        function openLabModal() {
            const modal = document.getElementById('labModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeLabModal() {
            const modal = document.getElementById('labModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        // Inspect Modal Controls
        function openInspectModal(id, name, location, totalCount, computers) {
            document.getElementById('inspectTitle').innerText = name;
            document.getElementById('inspectSubtitle').innerText = `${location} — ${totalCount} Registered Units`;

            // Populate update form fields
            document.getElementById('editName').value = name;
            document.getElementById('editLocation').value = location;
            document.getElementById('editCount').value = totalCount;
            document.getElementById('editLabForm').action = `/dashboard/labs/${id}`;

            // Render PC list inside inspector
            const container = document.getElementById('computersListContainer');
            container.innerHTML = '';

            if (!computers || computers.length === 0) {
                container.innerHTML = '<p class="col-span-full text-center text-xs font-bold text-slate-400 py-10 uppercase tracking-widest">No computer units found in this facility.</p>';
            } else {
                computers.forEach(pc => {
                    const isActive = (pc.status || 'active').toLowerCase() === 'active';
                    const statusColor = isActive ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-slate-50 text-slate-400 border-slate-100';
                    const dotColor = isActive ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300';
                    const pcLabel = pc.pc_number || pc.name || `PC-${pc.id}`;

                    container.innerHTML += `
                        <div class="p-4 rounded-2xl border ${statusColor} flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="size-2.5 rounded-full ${dotColor}"></div>
                                <div>
                                    <p class="text-xs font-black uppercase text-slate-800">${pcLabel}</p>
                                    <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">Status: ${pc.status || 'offline'}</p>
                                </div>
                            </div>
                            <span class="text-[9px] font-black uppercase tracking-widest px-3 py-1 bg-white rounded-lg shadow-sm">
                                ${(pc.status || 'OFFLINE').toUpperCase()}
                            </span>
                        </div>
                    `;
                });
            }

            switchInspectTab('units');
            const modal = document.getElementById('inspectModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeInspectModal() {
            const modal = document.getElementById('inspectModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function switchInspectTab(tab) {
            const unitsTab = document.getElementById('inspectUnitsTab');
            const editTab = document.getElementById('inspectEditTab');
            const unitsBtn = document.getElementById('tabUnitsBtn');
            const editBtn = document.getElementById('tabEditBtn');

            if (tab === 'units') {
                unitsTab.classList.remove('hidden');
                editTab.classList.add('hidden');
                unitsBtn.className = "px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-slate-900 text-white transition-all";
                editBtn.className = "px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all";
            } else {
                unitsTab.classList.add('hidden');
                editTab.classList.remove('hidden');
                editBtn.className = "px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-slate-900 text-white transition-all";
                unitsBtn.className = "px-5 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-widest bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all";
            }
        }

        function setLoadingState(form, btnId, loadingText) {
            const btn = document.getElementById(btnId);
            btn.disabled = true;
            btn.innerHTML = loadingText;
            btn.classList.add('opacity-50', 'animate-pulse');
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeLabModal();
                closeInspectModal();
            }
        });
    </script>
</x-app-layout>