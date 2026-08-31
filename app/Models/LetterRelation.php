<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LetterRelation extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_letter_id',
        'target_letter_id',
        'relation_type',
        'notes',
        'created_by',
    ];

    public function sourceLetter()
    {
        return $this->belongsTo(Letter::class, 'source_letter_id');
    }

    public function targetLetter()
    {
        return $this->belongsTo(Letter::class, 'target_letter_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
