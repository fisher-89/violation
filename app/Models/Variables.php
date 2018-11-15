<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Variables extends Model
{
    protected $table = 'variables';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = ['key', 'name', 'code'];
}