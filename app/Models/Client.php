<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{
    protected $fillable = [
        'name',
    ];

    /**
     * @return HasOne<Account, $this>
     */
    public function account(): HasOne
    {
        return $this->hasOne(Account::class);
    }
}
