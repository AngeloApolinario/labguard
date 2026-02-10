<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Computer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'lab_name',      // e.g., Lab 1
        'pc_number',     // e.g., PC-01
        'serial_number', // Hardware ID
        'asset_tag',     // School Sticker ID
        'status',        // available, active, or maintenance
    ];

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
        return $this->hasOne(LabSession::class)->whereNull('time_out');
    }
}
