<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'client_id',
        'currency',
    ];

    /**
     * @return BelongsTo<Client, self>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return HasMany<Transaction, self>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
