<?php

namespace Pterodactyl\Models;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'status',
        'reference',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'user_id' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
