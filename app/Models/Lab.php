<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lab extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'location','status'];

    /**
     * Relationship: One Lab has many Computers
     */
    public function computers()
    {
        return $this->hasMany(Computer::class);
    }

    /**
     * Relationship: One Lab has many Schedules (for the teachers)
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

      // Helper method to check maintenance state
     public function isUnderMaintenance(): bool
    {
        return in_array(strtolower($this->status), ['maintenance', 'lockdown']);
    }
}
