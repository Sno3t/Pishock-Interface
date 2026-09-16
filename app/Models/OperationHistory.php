<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class OperationHistory extends Model
{
    const UPDATED_AT = null;

    /**
     * @var string[]
     */
    protected $fillable = [
        'operation',
        'value',
        'user_id',
    ];
}
