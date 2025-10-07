<?php

namespace App\Domain\Inbound\Models;

use App\Domain\Inventory\Models\ItemLot;
use App\Domain\Inventory\Models\Location;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrnLine extends Model
{
    use HasFactory;

    protected $table = 'grn_lines';

    protected $guarded = [];

    protected $casts = [
        'received_qty' => 'float',
        'rejected_qty' => 'float',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(GrnHeader::class, 'grn_header_id');
    }

    public function poItem(): BelongsTo
    {
        return $this->belongsTo(PoItem::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(ItemLot::class, 'item_lot_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'putaway_location_id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\GrnLineFactory::new();
    }
}
