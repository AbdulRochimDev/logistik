<?php

namespace App\Domain\Outbound\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 */
class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';

    protected $guarded = [];

    public function salesOrders(): HasMany
    {
        return $this->hasMany(SalesOrder::class);
    }

    protected static function newFactory()
    {
        return \Database\Factories\CustomerFactory::new();
    }
}
