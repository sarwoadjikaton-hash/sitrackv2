<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $type_code
 * @property string $type_name
 * @property string $workbook_name
 * @property bool $uses_security_access
 * @property string $extra_field
 * @property string $number_pattern
 * @property int $sequence_padding
 * @property string $default_signer_code
 * @property bool $is_active
 * @property int $display_order
 */

class LetterNumberType extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_code',
        'type_name',
        'workbook_name',
        'uses_security_access',
        'extra_field',
        'number_pattern',
        'sequence_padding',
        'default_signer_code',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'uses_security_access' => 'boolean',
        'is_active' => 'boolean',
        'sequence_padding' => 'integer',
        'display_order' => 'integer',
    ];

    public function batches()
    {
        return $this->hasMany(LetterNumberAvailabilityBatch::class, 'type_id');
    }

    public function letterNumbers()
    {
        return $this->hasMany(LetterNumber::class, 'type_id');
    }
}
