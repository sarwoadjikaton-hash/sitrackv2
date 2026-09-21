<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LetterNumberAvailabilityBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_id',
        'number_year',
        'purpose',
        'start_sequence',
        'end_sequence',
        'period_month',
        'letter_date',
        'unit_id',
        'unit_text',
        'pic_name',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'type_id' => 'integer',
        'number_year' => 'integer',
        'start_sequence' => 'integer',
        'end_sequence' => 'integer',
        'period_month' => 'date:Y-m-d',
        'letter_date' => 'date:Y-m-d',
    ];

    public function type()
    {
        return $this->belongsTo(LetterNumberType::class, 'type_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
