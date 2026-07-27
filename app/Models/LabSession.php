<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'computer_id',
        'lab_id',
        'student_name',
        'student_id_number', // Ensure this matches your field #4 exactly
        'time_in',
        'time_out',
        'teacher_id',
    ];

    protected $casts = [
        'time_in' => 'datetime',
        'time_out' => 'datetime',
    ];

    /**
     * The Student (User) who is using the PC.
     */
    public function student(): BelongsTo
    {
        // Points to User model using 'user_id'
        return $this->belongsTo(User::class, 'user_id');
    }

    public function computer(): BelongsTo
    {
        return $this->belongsTo(Computer::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();;
    }
}
