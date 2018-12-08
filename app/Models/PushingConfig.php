<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class PushingConfig extends Model
{
    use ListScopes;

    protected $table = 'pushing_config';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'staff_sn', 'staff_name', 'action', 'action_name', 'action_at', 'is_open',
    ];
}