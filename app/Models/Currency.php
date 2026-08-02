<?php

namespace Pterodactyl\Models;

class Currency extends Model
{
    protected $table = 'currencies';

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    protected $fillable = [
        'code',
        'symbol',
        'rate_to_kes',
        'is_active',
    ];

    protected $casts = [
        'rate_to_kes' => 'decimal:6',
        'is_active' => 'boolean',
    ];

    /**
     * "rate_to_kes" means: how many KES equal 1 unit of this currency.
     * e.g. USD rate_to_kes = 130 means 1 USD = 130 KES.
     *
     * Convert a KES amount into this currency.
     */
    public function convertFromKes(float $amountInKes): float
    {
        if ((float) $this->rate_to_kes <= 0) {
            return $amountInKes;
        }

        return round($amountInKes / (float) $this->rate_to_kes, 2);
    }
}
