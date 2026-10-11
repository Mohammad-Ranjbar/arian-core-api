<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActionLog extends Model
{
    const string UPDATED_QC_STATUS = 'update_qc_status';

    const array UPDATED_GRADE_STATUS = ['update_grade_status_raw_body', 'update_grade_status_painted_body', 'update_grade_status_vehicle'];

    const string RETURN_CONTAINER_AT = 'return_container';

    const string REPORT_SHORTAGE = 'report_shortage';

    const string MAIN_SHORTAGE_ITEM = 'main_shortage_item';

    const string SPECIFIC_SHORTAGE_ITEM_CREATED = 'specific_shortage_item_created';

    const string SPECIFIC_SHORTAGE_ITEM_ACTIVATED = 'specific_shortage_item_activated';

    const string SPECIFIC_SHORTAGE_ITEM_DEACTIVATED = 'specific_shortage_item_deactivated';

    const string SPECIFIC_SHORTAGE_ITEM_DELETED = 'specific_shortage_item_deleted';

    const string MAIN_ITEM_SHORTAGE_CREATED = 'main_item_shortage_created';

    const string MAIN_ITEM_SHORTAGE_UPDATED = 'main_item_shortage_updated';

    const string MAIN_ITEM_SHORTAGE_FORKED = 'main_item_shortage_forked';

    const string MAIN_ITEM_SHORTAGE_DELETED = 'main_item_shortage_deleted';

    const string RELATED_ITEM_SHORTAGE_CREATED = 'related_item_shortage_created';

    const string RELATED_ITEM_SHORTAGE_UPDATED = 'related_item_shortage_updated';

    const string RELATED_ITEM_SHORTAGE_DELETED = 'related_item_shortage_deleted';

    /**
     * @var string[]
     */
    protected $fillable = ['user_id', 'action', 'model_type', 'model_id', 'data'];

    protected $casts = [
        'data' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
