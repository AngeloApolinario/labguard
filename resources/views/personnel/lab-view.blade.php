<x-app-layout>
    <div class="py-12 px-6 min-h-screen bg-[#F8FAFC]">
        {{-- Pass the 'id' instead of the 'name' --}}
        @livewire('lab-monitor', ['labId' => $lab->id])
    </div>
</x-app-layout>