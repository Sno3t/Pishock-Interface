<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationHistory extends Model
{
    const UPDATED_AT = null;

    protected $table = 'operation_history';

    /**
     * @var string[]
     */
    protected $fillable = [
        'operation',
        'type',
        'value',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
