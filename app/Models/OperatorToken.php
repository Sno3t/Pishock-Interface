<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class OperatorToken extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'revoked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (OperatorToken $operatorToken) {
            $operatorToken->token ??= Str::random(40);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public function history(): HasMany
    {
        return $this->hasMany(OperationHistory::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function revoke(): void
    {
        $this->forceFill(['revoked_at' => now()])->save();
    }
}
