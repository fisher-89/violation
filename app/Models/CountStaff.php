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

    protected $fillable = ['department_id','staff_sn', 'staff_name', 'month', 'paid_money', 'money', 'score'];

    public function countHasPunish()
    {
        return $this->hasOne(CountHasPunish::class, 'id', 'count_id');
    }
}