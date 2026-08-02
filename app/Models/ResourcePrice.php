<?php

namespace Pterodactyl\Models;

class ResourcePrice extends Model
{
    protected $table = 'resource_prices';

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    protected $fillable = [
        'name',
        'resource_key',
        'unit_label',
        'price_kes',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'price_kes' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
}
