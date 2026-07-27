<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'computer_id',
        'issue_type',
        'lab_id',
        'remarks',
        'status',
        'resolved_at',
        'reported_by',
    ];

    /**
     * Get the computer that generated the alert.
     */
    public function computer(): BelongsTo
    {
        return $this->belongsTo(Computer::class);
    }


    /**
     * Scope to quickly get only unresolved issues for the HUD.
     */
    public function scopeUnresolved($query)
    {
        return $query->where('status', '!=', 'resolved');
    }
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
    public function laboratory()
    {
        return $this->belongsTo(Lab::class);
    }
    public function lab()
    {
        return $this->belongsTo(Lab::class);
    }
    public function user()
    {
        // withTrashed() ensures past session history still shows user details in UI tables
        return $this->belongsTo(User::class)->withTrashed();
    }
}
