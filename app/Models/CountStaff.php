<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CountStaff extends Model
{
    use ListScopes,SoftDeletes;

    protected $table = 'count_staff';

    protected $primaryKey = 'id';

    protected $fillable = ['department_id','brand_name', 'staff_sn', 'staff_name', 'month', 'paid_money', 'money', 'score', 'has_settle'];

    public function countHasPunish()
    {
        return $this->hasMany(CountHasPunish::class, 'count_id', 'id');
    }
}