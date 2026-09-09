<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Letter extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_code',
        'agenda_number',
        'letter_number',
        'letter_type',
        'letter_source',
        'process_lane',
        'sender_unit',
        'category_id',
        'letter_number_type_id',
        'sender_name',
        'sender_phone',
        'recipient_unit_id',
        'subject',
        'letter_date',
        'received_date',
        'priority',
        'security_level',
        'status',
        'current_position',
        'requested_actions',
        'notes',
        'attachment_path',
        'pdf_content',
        'created_by',
        'archive_classification_code',
        'signatory_name',
        'technical_officer',
        'cover_letter_number',
    ];

    protected $casts = [
        'letter_date' => 'date',
        'received_date' => 'date',
    ];

    public function category()
    {
        return $this->belongsTo(LetterCategory::class, 'category_id');
    }

    public function recipientUnit()
    {
        return $this->belongsTo(Unit::class, 'recipient_unit_id');
    }

    public function letterNumberType()
    {
        return $this->belongsTo(\App\Models\LetterNumberType::class, 'letter_number_type_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dispositions()
    {
        return $this->hasMany(Disposition::class, 'letter_id')->orderBy('disposition_date', 'asc');
    }

    public function statusLogs()
    {
        return $this->hasMany(LetterStatusLog::class, 'letter_id')->orderBy('changed_at', 'desc');
    }

    public function sourceRelations()
    {
        return $this->hasMany(LetterRelation::class, 'source_letter_id');
    }

    public function targetRelations()
    {
        return $this->hasMany(LetterRelation::class, 'target_letter_id');
    }
}
