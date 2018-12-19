<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CountStaff extends Model
{
    use ListScopes;

    protected $table = 'count_staff';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = ['department_id', 'staff_sn', 'staff_name', 'month', 'paid_money', 'money', 'score', 'has_settle'];

    public function countHasPunish()
    {
        return $this->hasMany(CountHasPunish::class, 'count_id', 'id');
    }

    public function countDepartment()
    {
        return $this->hasOne(CountDepartment::class,'id','department_id');
    }
}