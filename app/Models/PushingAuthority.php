<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushingAuthority extends Model
{
    protected $table = 'pushing_authority';

    protected $timestamps = false;

    protected $fillable = [
        'staff_sn', 'staff_name', 'flock_name', 'flock_sn', 'default_push'
    ];
}