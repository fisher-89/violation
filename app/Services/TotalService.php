<?php

namespace App\Services;

use App\Http\Controllers\ImageController;
use App\Models\BillImage;
use App\Models\CountHasPunish;
use App\Models\CountStaff;
use App\Models\Punish;
use Illuminate\Support\Facades\DB;

class TotalService
{
    protected $billModel;
    protected $punishModel;
    protected $countStaffModel;
    protected $countHasPunishModel;

    public function __construct(Punish $punish, CountStaff $countStaff, CountHasPunish $countHasPunish,BillImage $billImage)
    {
        $this->punishModel = $punish;
        $this->billModel = $billImage;
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
        return $this->countStaffModel->with(['countHasPunish.punish'])->when($department == true, function ($query) use ($id) {
            $query->whereIn('department_id', $id);
        })->filterByQueryString()->SortByQueryString()->withPagination($request->get('pagesize', 10));
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

    /**
     * 临时调用加数据
     */

    public function insertData()
    {
        set_time_limit(100);
        for ($sum = 110001; $sum < 110201; $sum++) {
            $staff = app('api')->withRealException()->getStaff($sum);
            if ($staff == false) {continue;}
            $arr[] = [
                'rule_id' => 1, 'point_log_id' => null, 'staff_sn' => $staff['staff_sn'],
                'staff_name' => $staff['realname'], 'brand_id' => $staff['brand_id'], 'brand_name' => $staff['brand']['name'],
                'department_id' => $staff['department_id'], 'department_name' => $staff['department']['full_name'],
                'position_id' => $staff['position_id'], 'position_name' => $staff['position']['name'],
                'shop_sn' => $staff['shop_sn'], 'billing_sn' => 110104, 'billing_name' => '刘勇01',
                'billing_at' => '2018-12-30', 'quantity' => 4, 'money' => 20, 'score' => 20,
                'violate_at' => '2018-12-29', 'has_paid' => 0, 'paid_at' => null, 'sync_point' => null,
                'month' => 201812, 'remark' => null, 'creator_sn' => 119462, 'creator_name' => '唐骄'
            ];
        }
        $this->punishModel->insert(isset($arr) ? $arr : abort(500,'未发现数据'));
    }
}