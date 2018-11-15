<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CountHasPunish extends Model
{

    protected $table = 'count_has_punish';

    protected $fillable = ['count_id', 'punish_id'];

    public $timestamps = false;

    public function punish()
    {
        return $this->belongsTo(Punish::class, 'punish_id', 'id');
    }
}