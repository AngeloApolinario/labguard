<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionChecklist extends Model
{
    use HasFactory;
    protected $guarded = [];



    protected $casts = [
        'monitor_ok' => 'boolean',
        'keyboard_ok' => 'boolean',
        'mouse_ok' => 'boolean',
        'avr_ok' => 'boolean',
        'pc_case_ok' => 'boolean',
        'headset_ok' => 'boolean',
        'all_operational' => 'boolean',
        'items_payload' => 'array',
        'verified_at' => 'datetime',
    ];

    /**
     * Relationship back to the session (e.g., PcSession)
     */
    public function labSession()
    {
        return $this->belongsTo(LabSession::class, 'lab_session_id');
    }
}
