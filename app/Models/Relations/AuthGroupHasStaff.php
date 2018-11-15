<?php

namespace App\Models\Relations;

use Illuminate\Support\Facades\Cache;
use App\Models\AuthorityGroupHasStaff;
use App\Models\AuthorityGroupHasDepartment;

trait AuthGroupHasStaff
{
    /**
     * 分组员工.
     * 
     * @author 28youth
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function staff()
    {
        return $this->hasMany(AuthorityGroupHasStaff::class, 'authority_group_id', 'id');
    }

    /**
     * 分组部门.
     *
     * @author 28youth
     * @return \Illuminate\Database\Eloquent\Relations\hasmany
     */
    public function departments()
    {
        return $this->hasMany(AuthorityGroupHasDepartment::class, 'authority_group_id', 'id');
    }

    /**
     * 获取分组下的员工缓存10分钟.
     *
     * @return void
     */
    public function stafflist()
    {
        $cacheKey = sprintf('auth-group-staff:%s', $this->id);
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        
        $staff = $this->staff()->pluck('staff_sn');
        $department = $this->departments()->pluck('department_id');
        
        $staffSns = collect(app('api')->getStaff([
            'filters' => "department_id={$department};status_id>=0"
        ]))->pluck('staff_sn')->merge($staff)->unique()->toArray();

        $expiresAt = now()->addMinutes(10);
        Cache::put($cacheKey, $staffSns, $expiresAt);

        return $staffSns;
    }

    /**
     * 清除分组员工缓存
     *
     * @return void
     */
    public function forgetStaff()
    {
        $cacheKey = sprintf('auth-group-staff:%s', $this->id);

        Cache::forget($cacheKey);
    }
}
