<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $type_id
 * @property int $number_year
 * @property int $sequence_number
 * @property string $status
 * @property string|null $signer_code
 * @property string|null $security_access
 * @property string|null $classification_code
 * @property int|null $month_number
 * @property string|null $number_text
 * @property \Illuminate\Support\Carbon|null $incoming_date
 * @property int|null $unit_id
 * @property string|null $processing_unit_text
 * @property string|null $signatory
 * @property string|null $request_type
 * @property string|null $destination
 * @property \Illuminate\Support\Carbon|null $letter_date
 * @property string|null $subject
 * @property string|null $technical_officer
 * @property string|null $scan_result
 * @property string|null $nd_pengantar
 * @property int|null $linked_letter_id
 * @property string|null $reserved_for
 * @property \Illuminate\Support\Carbon|null $reserved_at
 * @property \Illuminate\Support\Carbon|null $used_at
 * @property int|null $created_by
 * @property-read LetterNumberType $type
 * @property-read Unit|null $unit
 * @property-read User|null $creator
 * @property-read Letter|null $letter
 */
class LetterNumber extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_id',
        'number_year',
        'sequence_number',
        'status',
        'signer_code',
        'security_access',
        'classification_code',
        'month_number',
        'number_text',
        'incoming_date',
        'unit_id',
        'processing_unit_text',
        'signatory',
        'request_type',
        'destination',
        'letter_date',
        'subject',
        'technical_officer',
        'scan_result',
        'nd_pengantar',
        'linked_letter_id',
        'reserved_for',
        'reserved_at',
        'used_at',
        'created_by',
    ];

    protected $casts = [
        'type_id' => 'integer',
        'number_year' => 'integer',
        'sequence_number' => 'integer',
        'month_number' => 'integer',
        'incoming_date' => 'date',
        'letter_date' => 'date',
        'reserved_at' => 'datetime',
        'used_at' => 'datetime',
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

    public function letter()
    {
        return $this->belongsTo(Letter::class, 'linked_letter_id');
    }

    public function scopeAvailableOrReserved($query)
    {
        return $query->whereIn('status', ['available', 'reserved']);
    }
}