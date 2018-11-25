<?php

namespace App\Services;


use App\Models\CountDepartment;
use App\Models\CountHasPunish;
use App\Models\CountStaff;
use App\Models\Punish;
use Illuminate\Support\Facades\DB;

class TotalService
{
    protected $punishModel;
    protected $countStaffModel;
    protected $countHasPunishModel;
    protected $countDepartmentModel;

    public function __construct(Punish $punish, CountDepartment $countDepartment, CountStaff $countStaff, CountHasPunish $countHasPunish)
    {
        $this->punishModel = $punish;
        $this->countStaffModel = $countStaff;
        $this->countHasPunishModel = $countHasPunish;
        $this->countDepartmentModel = $countDepartment;
    }

    /**
     * 获取员工统计数据
     *
     * @param $request
     * @return mixed
     */
    public function getStaff($request)
    {
        return $this->countStaffModel->with('countHasPunish.punish')->filterByQueryString()->SortByQueryString()->withPagination($request->get('pagesize', 10));
    }

    /**
     * 获取部门统计数据
     *
     * @param $request
     * @return mixed
     */
    public function getDepartment($request)
    {
        return $this->countDepartmentModel->filterByQueryString()->SortByQueryString()->withPagination($request->get('pagesize', 10));
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
                $countStaff->update(['paid_money' => $countStaff->money, 'has_settle' => 1]);
                $this->punishModel->where(['month' => $countStaff->month, 'staff_sn' => $countStaff->staff_sn])->update(['has_paid' => 1, 'paid_at' => date('Y-m-d H:i:s')]);
                $department = $this->countDepartmentModel->find($countStaff->department_id);
                $department->update(['paid_money' => $department->paid_money + $countStaff->money]);
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