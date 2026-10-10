<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TerminalCommand extends Model
{
    protected $fillable = [
        'computer_id',
        'type',
        'payload',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
        ];
    }

    public function computer()
    {
        return $this->belongsTo(Computer::class);
    }
}
