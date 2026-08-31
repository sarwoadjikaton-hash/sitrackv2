<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_name',
        'pic_name',
        'phone',
        'email',
        'address',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function letters()
    {
        return $this->hasMany(Letter::class, 'recipient_unit_id');
    }

    public function letterNumbers()
    {
        return $this->hasMany(LetterNumber::class, 'unit_id');
    }
}
