<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{

    private string $name;

    private string $shareCode;

    /**
     * @var string[]
     */
    protected $fillable = [
        'device_name',
        'share_code',
    ];

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getShareCode(): string
    {
        return $this->shareCode;
    }

    /**
     * @param string $shareCode
     * @return void
     */
    public function setShareCode(string $shareCode): void
    {
        $this->shareCode = $shareCode;
    }
}
