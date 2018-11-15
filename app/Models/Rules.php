<?php
namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rules extends Model{

    use SoftDeletes,ListScopes;

    protected $table='rules';

    protected $primaryKey='id';

    protected $fillable = ['type_id','name','description','money','score','sort'];

    public function ruleTypes()
    {
        return $this->belongsTo(RuleTypes::class,'type_id','id');
    }
}