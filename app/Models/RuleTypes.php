<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RuleTypes extends Model
{
    use ListScopes,SoftDeletes;

    protected $table = 'rule_types';

    protected $primaryKey = 'id';

//    public $timestamps = false;

    protected $fillable = ['name'];
}