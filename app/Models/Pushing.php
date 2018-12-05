<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Pushing extends Model
{
    protected $table = 'pushing';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'staff_sn', 'flock_name', 'flock_sn', 'is_lock'
    ];
}