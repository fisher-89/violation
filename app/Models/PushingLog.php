<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class PushingLog extends Model
{
    use ListScopes, SoftDeletes;

    protected $table = 'pushing_log';

    protected $primaryKey = 'id';

    protected $fillable = [
        'sender_staff_sn', 'sender_staff_name', 'ding_flock_sn', 'ding_flock_name', 'staff_sn','pushing_type', 'states', 'error_message', 'pushing_info'
    ];
}