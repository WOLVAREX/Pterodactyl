<?php

namespace Pterodactyl\Models;

class Plan extends Model
{
    protected $table = 'plans';

    protected $fillable = [
        'egg_id',
        'nest_id',
        'name',
        'description',
        'price',
        'currency',
        'billing_period',
        'memory',
        'disk',
        'cpu',
        'databases',
        'backups',
        'allocations',
        'features',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'memory' => 'integer',
        'disk' => 'integer',
        'cpu' => 'integer',
        'databases' => 'integer',
        'backups' => 'integer',
        'allocations' => 'integer',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    public function egg()
    {
        return $this->belongsTo(\Pterodactyl\Models\Egg::class);
    }

    public function getFeaturesListAttribute(): array
    {
        if (empty($this->features)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode("\n", $this->features))));
    }
}
