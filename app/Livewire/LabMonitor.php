<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Lab;
use App\Models\Computer;

class LabMonitor extends Component
{
    public $labId;
    public $labName;

    public function mount($labId)
    {
        $this->labId = $labId;
        // Fetch the name once during mount to display in header
        $lab = Lab::find($labId);
        $this->labName = $lab ? $lab->name : 'Unknown Lab';
    }

    public function render()
    {
        return view('components.lab-monitor', [
            'computers' => Computer::with(['activeSession'])
                ->where('lab_id', $this->labId)
                ->orderBy('pc_number', 'asc')
                ->get()
        ]);
    }
}
