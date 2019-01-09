<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class Pushing extends Model
{
    use ListScopes;

    protected $table = 'pushing_authority';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'staff_sn', 'staff_name', 'flock_name', 'flock_sn', 'default_push'
    ];
}