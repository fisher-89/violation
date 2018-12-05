<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class PushingLog extends Model
{
    protected $table = 'pushing_log';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'staff_sn','staff_name','ding_flock_sn', 'ding_flock_name', 'is_success', 'pushing_info'
    ];
}