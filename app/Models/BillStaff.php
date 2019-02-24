<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class BillStaff extends Model
{
    use ListScopes;

    protected $table = 'bill_staff';

    protected $primaryKey = 'id';

    protected $fillable = [
        "bill_id", "staff_sn",
    ];
}
