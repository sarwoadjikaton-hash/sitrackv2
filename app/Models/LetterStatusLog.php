<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LetterStatusLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'letter_id',
        'status',
        'position',
        'note',
        'attachment_path',
        'attachment_name',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function letter()
    {
        return $this->belongsTo(Letter::class, 'letter_id');
    }
}
