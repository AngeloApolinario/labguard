<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabSession extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * Use these to prevent "Mass Assignment" errors.
     */
    protected $fillable = [
        'computer_id',
        'student_name',
        'student_id_number',
        'time_in',
        'time_out',
        'teacher_id',
    ];

    /**
     * Get the computer associated with this session.
     */
    public function computer()
    {
        return $this->belongsTo(Computer::class);
    }

    /**
     * Get the teacher who recorded this session.
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
