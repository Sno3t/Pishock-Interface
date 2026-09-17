<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OperationHistory extends Model
{
    use SoftDeletes;

    const UPDATED_AT = null;

    protected $table = 'operation_history';

    /**
     * @var string[]
     */
    protected $fillable = [
        'operation',
        'type',
        'value',
        'succeeded',
        'devices',
        'user_id',
        'operator_token_id',
    ];

    protected $casts = [
        'succeeded' => 'boolean',
        'devices' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function operatorToken(): BelongsTo
    {
        // withTrashed so a command's history keeps showing who sent it even
        // after that operator's link has since been pruned/archived.
        return $this->belongsTo(OperatorToken::class)->withTrashed();
    }

    /**
     * Archive (soft-delete) entries older than $days.
     */
    public static function pruneOld(int $days): int
    {
        return static::where('created_at', '<', now()->subDays($days))->delete();
    }
}
