<?php

namespace App\Services;

use App\Models\CountHasPunish;
use App\Models\CountStaff;
use App\Models\Punish;
use Illuminate\Support\Facades\DB;

class TotalService
{
    protected $punishModel;
    protected $countStaffModel;
    protected $countHasPunishModel;

    public function __construct(Punish $punish, CountStaff $countStaff, CountHasPunish $countHasPunish)
    {
        $this->punishModel = $punish;
        $this->countStaffModel = $countStaff;
        $this->countHasPunishModel = $countHasPunish;
    }

    /**
     * 获取员工统计数据
     *
     * @param $request
     * @return mixed
     */
    public function getStaff($request)
    {
        $department = $request->department_id == true ? app('api')->withRealException()->getDepartmenets($request->department_id) : null;
        $id = $department == true ? $this->department(is_array($department) ? $department : $department->toArray()) : false;
        $arr = $this->countStaffModel->with(['countHasPunish.punish'])->when($department == true, function ($query) use ($id) {
            $query->whereIn('department_id', $id);
        })->filterByQueryString()->SortByQueryString()->withPagination($request->get('pagesize', 10));
        $arr['where'] = $request->all();
        return $arr;
    }

    /**
     * 递归提取所有部门id
     *
     * @param $array
     * @return array
     */
    protected function department($array, $id = []): array
    {
        $id[] = isset($array['id']) ? $array['id'] : '';
        if ($array['children'] != []) {
            foreach ($array['children'] as $value) {
                $id[] = isset($value['id']) ? $value['id'] : '';
                if ($value['children'] != []) {
                    $id = $this->department($value, $id);
                }
            }
        }
        return $id;
    }

    /**
     * 同时改变多个人付款状态  全付
     *
     * @param $array
     * @return array
     */
    public function updateMoneyStatus($array)
    {
        $data = [];
        try {
            DB::beginTransaction();
            foreach ($array as $k => $v) {
                $countStaff = $this->countStaffModel->find($v);
                if ($countStaff->paid_money == $countStaff->money || $countStaff->has_settle == 1) {
                    continue;
                }
                $countStaff->update(['paid_money' => $countStaff->money, 'has_settle' => 1]);
                $this->punishModel->where(['month' => $countStaff->month, 'staff_sn' => $countStaff->staff_sn])->update(['has_paid' => 1, 'paid_at' => date('Y-m-d H:i:s')]);
                $data[] = $countStaff;
            }
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            abort(500, '操作失败，错误：' . $exception->getMessage());
        }
        return $data;
    }
}