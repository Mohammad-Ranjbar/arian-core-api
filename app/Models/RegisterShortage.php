<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RegisterShortage extends Model
{
    use HasFactory;

    public const string TYPE_MAIN = 'main';

    public const string TYPE_SINGLE = 'single';

    public const string STATUS_HAS_SHORTAGE = 'has_shortage';

    public const string STATUS_RESOLVED = 'resolve_shortage';

    protected $with = ['vehicle', 'carModel', 'makeCode', 'user', 'children'];

    protected $fillable = [
        'main_item_shortage_id',
        'vin',
        'car_model_id',
        'make_code_id',
        'item_no',
        'item_description',
        'type',
        'qty',
        'work_center',
        'status',
        'user_id',
        'register_shortage_id',
        'is_certified',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'is_certified' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_HAS_SHORTAGE,
    ];

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by', 'id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vin', 'vin');
    }

    public function carModel(): BelongsTo
    {
        return $this->belongsTo(CarModel::class);
    }

    public function makeCode(): BelongsTo
    {
        return $this->belongsTo(MakeCode::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function children()
    {
        return $this->hasMany(self::class, 'register_shortage_id', 'id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'register_shortage_id', 'id');
    }

    public function mainItemShortage(): BelongsTo
    {
        return $this->belongsTo(MainItemShortage::class);
    }

    public static function getReportData(array $filters): Collection
    {
        return self::query()
            ->withOnly([
                'carModel:id,title,reference',
                'user:id,name',
                'resolvedBy:id,name',
            ])
            ->when(
                filled($filters['item_no'] ?? null),
                function ($query) use ($filters) {
                    $query->where('item_no', trim($filters['item_no']));
                },
                function ($query) {
                    $query->where(function ($query) {
                        $query->whereNotNull('main_item_shortage_id')
                            ->orWhere(function ($query) {
                                $query->whereNotNull('register_shortage_id')
                                    ->whereHas('parent', function ($query) {
                                        $query->whereNotNull('main_item_shortage_id');
                                    });
                            });
                    });
                }
            )
            ->when(filled($filters['vin'] ?? null), function ($query) use ($filters) {
                $query->where('vin', $filters['vin']);
            })
            ->when(filled($filters['from'] ?? null), function ($query) use ($filters) {
                $query->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
            })
            ->when(filled($filters['to'] ?? null), function ($query) use ($filters) {
                $query->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }
}
