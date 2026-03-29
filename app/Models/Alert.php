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
        'remarks',
        'status',
        'resolved_at'
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
}
