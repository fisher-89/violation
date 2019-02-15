<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rules extends Model
{

    use SoftDeletes, ListScopes;

    protected $table = 'rules';

    protected $primaryKey = 'id';

    protected $fillable = ['type_id', 'name', 'remark', 'money', 'money_custom_settings', 'score', 'score_custom_settings'];

    public function ruleTypes()
    {
        return $this->belongsTo(RuleTypes::class, 'type_id', 'id');
    }
}