<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Pushing extends Model
{
    protected $table = 'pushing_authority';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'staff_sn', 'staff_name', 'flock_name', 'flock_sn', 'is_lock'
    ];
}