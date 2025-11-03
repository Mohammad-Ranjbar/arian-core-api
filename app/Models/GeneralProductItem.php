<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GeneralProductItem extends Model
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'title',
        'priority',
        'height',
        'type',
        'part_type',
        'label_status',
        'hack_status',
        'count_lable',
        ];

    /**
     * @var string[]
     */
    protected $with = ['carModels'];

    /**
     * @return BelongsToMany
     */
    public function carModels(): BelongsToMany
    {
        return $this->BelongsToMany(CarModel::class);
    }

    public function stations(): BelongsToMany
    {
        return $this->BelongsToMany(Station::class);
    }

    public static function getReportData(array $data)
    {
        $query = self::query();
        if (!empty($data['from'])) {
            $query = $query->where('created_at', '>=', $data['from']);
        }

        if (!empty($data['to'])) {
            $query = $query->where('created_at', '<=', $data['to']);
        }
        if (!empty($data['priority'])) {
            $query = $query->where('priority', $data['priority']);
        }
        if (!empty($data['height'])) {
            $query = $query->where('height', $data['height']);
        }

        if (!empty($data['type'])) {
            $query = $query->where('type', $data['type']);
        }


        if (!empty($data['part_type'])) {
            $query = $query->where('part_type', $data['part_type']);
        }


        if (!empty($data['label_status'])) {
            $query = $query->where('label_status', $data['label_status']);
        }

        if (!empty($data['hack_status'])) {
            $query = $query->where('hack_status', $data['hack_status']);
        }

        if (!empty($data['count_lable'])) {
            $query = $query->where('count_lable', $data['count_lable']);
        }

        return $query->get();
    }
}
