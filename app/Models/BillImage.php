<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillImage extends Model
{
    use SoftDeletes,ListScopes;

    protected $table = 'bill_image';

    protected $primaryKey = 'id';

    protected $fillable = [
        "staff_sn", "staff_name", "department_name", "file_name", "file_path", "push_number", "is_clear",
    ];
}
