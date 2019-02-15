<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DingGroup extends Model
{
    use SoftDeletes;

    protected $table = 'ding_group';

    protected $primaryKey = 'id';

    protected $fillable = [
        'group_name', 'group_sn'
    ];
}