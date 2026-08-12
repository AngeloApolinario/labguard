<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Computer extends Model
{
    use HasFactory;

    protected $fillable = [
        'lab_id',
        'pc_number',
        'serial_number',
        'asset_tag',
        'status',
        'last_ping_at',
    ];

    /**
     * Relationship: A computer belongs to a specific Lab
     */
    public function lab()
    {
        return $this->belongsTo(Lab::class);
    }

    /**
     * Relationship: A computer has many attendance sessions
     */
    public function sessions()
    {
        return $this->hasMany(LabSession::class);
    }

    /**
     * Relationship: Get the one student currently assigned to this PC
     */
    public function activeSession()
    {
        return $this->hasOne(LabSession::class)->whereNull('time_out')->latestOfMany();
    }

    /**
     * Relationship: Fallback for the last person who used this PC
     */
    public function lastSession()
    {
        return $this->hasOne(LabSession::class)->latestOfMany();
    }

    /**
     * Relationship: A computer can have many technical reports/remarks
     */
    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Helper: Check if this terminal currently has an unaddressed issue
     */
    public function hasPendingAlert(): bool
    {
        return $this->alerts()->where('status', '!=', 'resolved')->exists();
    }

    public function getEffectiveStatusAttribute(): string
    {
        if ($this->lab && $this->lab->isUnderMaintenance()) {
            return 'maintenance';
        }

        return $this->status ?? 'available';
    }


    /**
     * Helper: Automatically releases any PC that hasn't pinged in > 30 seconds
     */
    public static function cleanupStaleSessions()
    {
        $staleThreshold = now()->subSeconds(30);

        $staleComputers = self::where('status', 'active')
            ->where(function ($query) use ($staleThreshold) {
                $query->whereNull('last_ping_at')
                    ->orWhere('last_ping_at', '<', $staleThreshold);
            })
            ->get();

        foreach ($staleComputers as $pc) {
            // 1. Fetch active session to get student details
            $activeSession = LabSession::where('computer_id', $pc->id)
                ->whereNull('time_out')
                ->latest()
                ->first();

            $studentDetails = $activeSession
                ? "{$activeSession->student_name} ({$activeSession->student_id_number})"
                : "Unknown Student";

            // 2. Close active session timestamp
            LabSession::where('computer_id', $pc->id)
                ->whereNull('time_out')
                ->update(['time_out' => now()]);

            // 3. Set PC status back to available
            $pc->update(['status' => 'available']);

            // 4. Log the auto-release event with the student's name & ID
            Log::info("Auto-released offline PC: [{$pc->pc_number}] — Last Active User: {$studentDetails}");
        }
    }
}
