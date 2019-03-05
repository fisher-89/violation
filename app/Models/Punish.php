<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class Punish extends Model
{

    use SoftDeletes, ListScopes;

    protected $table = 'punish';

    protected $primaryKey = 'id';

    protected $fillable = [
        'rule_id', 'point_log_id', 'staff_sn', 'staff_name', 'brand_id', 'brand_name', 'department_id', 'department_name',
        'position_id', 'position_name', 'shop_sn', 'billing_sn', 'billing_name', 'billing_at', 'quantity', 'money',
        'score', 'violate_at', 'has_paid', 'action_staff_sn', 'paid_at', 'paid_type', 'area', 'sync_point', 'month',
        'remark', 'creator_sn', 'creator_name'
    ];

    /**
     * 2018年10月10日14:01:11
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function rules()
    {
        return $this->belongsTo(Rules::class, 'rule_id', 'id')->withTrashed();
    }

    public function pushing()
    {
        return $this->hasMany(PunishHasAuth::class, 'punish_id', 'id');
    }
}