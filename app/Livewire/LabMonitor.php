<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Computer;

class LabMonitor extends Component
{
    public $labName;

    public function mount($labName)
    {
        $this->labName = $labName;
    }

    public function render()
    {
        return view('components.lab-monitor', [
            'computers' => \App\Models\Computer::with(['activeSession', 'lastSession'])
                ->where('lab_name', $this->labName)
                ->get()
        ]);
    }
}
