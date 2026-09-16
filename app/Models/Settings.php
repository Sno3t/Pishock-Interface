<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    const UPDATED_AT = null;

    /**
     * @var string[]
     */
    protected $fillable = [
        'operation',
        'type',
        'max_value',
    ];

}
