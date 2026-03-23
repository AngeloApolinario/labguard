<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'lab_id',
        'user_id',
        'subject_code',
        'day',
        'start_time',
        'end_time',
    ];

    /**
     * Relationship: A schedule belongs to a User (Instructor)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: A schedule belongs to a Lab
     */
    public function lab()
    {
        return $this->belongsTo(Lab::class);
    }
}
