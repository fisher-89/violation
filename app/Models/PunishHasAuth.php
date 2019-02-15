<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PunishHasAuth extends Model
{
    protected $table = 'punish_has_auth';

    protected $fillable = [
        'punish_id', 'auth_id'
    ];

    public function pushingAuthority()
    {
        return $this->hasOne(PushingAuthority::class,'id','auth_id');
    }
}