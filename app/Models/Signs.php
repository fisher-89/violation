<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class Signs extends Model
{
    protected $table = 'signs';

    public $timestamps = false;

    protected $primaryKey = 'id';

    protected $fillable = ['code'];
}