<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{
    public function account(): HasOne
    {
        return $this->hasOne(Account::class);
    }
}
