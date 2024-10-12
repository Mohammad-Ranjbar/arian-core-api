<?php

namespace App\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionLineActivity extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string[]
     */
    protected $fillable = [
        'production_line_id',
        'station_id',
        'stop_reason_id',
        'stop_type_id',
        'description',
        'note',
        'start_at',
        'stop_at',
        'effective_time',
        'notify_on_start',
        'notify_on_stop',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'start_at' => 'datetime',
        'stop_at' => 'datetime'
    ];

    /**
     * @param array $data
     * @return LengthAwarePaginator|Builder[]|Collection
     */
    public static function getReportData(array $data): Collection|LengthAwarePaginator|array
    {
        $query = self::query();
        if (!empty($data['from'])) {
            $query = $query->where('created_at', '>=', $data['from']);
        }
        if (!empty($data['to'])) {
            $query = $query->where('created_at', '<=', $data['to']);
        }

        if (!empty($data['paginate'])) {
            return $query->paginate($data['per_page'] ?? 15);
        }
        return $query->get();
    }

    /**
     * @return BelongsTo
     */
    public function productionLine(): BelongsTo
    {
        return $this->belongsTo(ProductionLine::class);
    }

    /**
     * @return BelongsTo
     */
    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /**
     * @return BelongsTo
     */
    public function stopReason(): BelongsTo
    {
        return $this->belongsTo(StopReason::class,'stop_reason_id');
    }

    /**
     * @return BelongsTo
     */
    public function stopType(): BelongsTo
    {
        return $this->belongsTo(StopType::class);
    }

    /**
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->logs()->latest()->where('action', 'approved')->exists();
    }

    /**
     * @return HasMany
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ProductionLineActivityLogs::class);
    }

    /**
     * @return BelongsToMany
     */
    public function productItems(): BelongsToMany
    {
        return $this->belongsToMany(ErpProductItem::class, 'erp_product_item_production_line_activity',
            'production_line_activity_id', 'erp_product_item_id');
    }
}
