<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CountDepartment extends Model
{
    use ListScopes;

    protected $table = 'count_department';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = ['department_name','parent_id','full_name', 'month', 'paid_money',  'money', 'score'];
}