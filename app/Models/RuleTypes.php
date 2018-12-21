<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class RuleTypes extends Model
{
    use ListScopes;

    protected $table = 'rule_types';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = ['name'];
}