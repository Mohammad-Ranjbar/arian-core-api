<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActionLog extends Model
{

    const string UPDATED_QC_STATUS = 'update_qc_status';
    const  array UPDATED_GRADE_STATUS = ['update_grade_status_raw_body', 'update_grade_status_painted_body', 'update_grade_status_vehicle'];

    const string RETURN_CONTAINER_AT = 'return_container';
    const string REPORT_SHORTAGE = 'report_shortage';
    const string MAIN_SHORTAGE_ITEM = 'main_shortage_item';

    /**
     * @var string[]
     */
    protected $fillable = ['user_id', 'action', 'model_type', 'model_id', 'data'];
    protected $casts = [
        'data' => 'json',
    ];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

}
