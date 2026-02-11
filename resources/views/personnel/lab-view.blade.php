<x-app-layout>
    <div class="py-12 px-6 min-h-screen bg-[#F8FAFC]">
        {{-- Pass the $name from the controller into the Livewire component --}}
        @livewire('lab-monitor', ['labName' => $name])
    </div>
</x-app-layout>