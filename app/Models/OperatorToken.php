<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OperatorToken extends Model
{
    use SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'expires_at',
    ];

    protected $casts = [
        'revoked_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (OperatorToken $operatorToken) {
            $operatorToken->token ??= Str::random(40);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->where(function (Builder $query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function history(): HasMany
    {
        return $this->hasMany(OperationHistory::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function revoke(): void
    {
        $this->forceFill(['revoked_at' => now()])->save();
    }

    /**
     * Archive (soft-delete) links that have been revoked or expired for at
     * least $days. Active links are never touched. Uses <= rather than <
     * so that $days = 0 (an immediate manual archive, right after a revoke)
     * reliably catches a revoked_at from the same second - DATETIME columns
     * don't carry sub-second precision, so a strict < can miss it.
     */
    public static function pruneInactive(int $days): int
    {
        $cutoff = now()->subDays($days);

        return static::where(function (Builder $query) use ($cutoff) {
            $query->where('revoked_at', '<=', $cutoff)
                ->orWhere('expires_at', '<=', $cutoff);
        })->delete();
    }
}
