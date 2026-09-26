<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disposition extends Model
{
    use HasFactory;

    protected $fillable = [
        'letter_id',
        'parent_disposition_id',
        'from_name',
        'to_unit_id',
        'to_name',
        'to_phone',
        'instruction',
        'due_date',
        'status',
        'follow_up_note',
        'is_koordinator',
        'attachment_path',
        'created_by',
        'disposition_date',
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'disposition_date' => 'datetime',
        'is_koordinator' => 'boolean',
    ];

    public function letter()
    {
        return $this->belongsTo(Letter::class, 'letter_id');
    }

    public function parent()
    {
        return $this->belongsTo(Disposition::class, 'parent_disposition_id');
    }

    public function children()
    {
        return $this->hasMany(Disposition::class, 'parent_disposition_id');
    }

    public function toUnit()
    {
        return $this->belongsTo(Unit::class, 'to_unit_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
