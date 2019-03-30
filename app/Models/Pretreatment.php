<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class Pretreatment extends Model
{
    use ListScopes;

    protected $table = 'pretreatment';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'create_sn', 'token', 'staff_sn', 'month', 'rules_id', 'state'
    ];
}