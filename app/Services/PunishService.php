<?php

namespace App\Services;

use App\Models\Rules;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\CountDepartment;
use App\Models\CountHasPunish;
use App\Models\CountStaff;
use App\Models\Punish;

class PunishService
{
    protected $ruleModel;
    protected $punishModel;
    protected $countStaffModel;
    protected $countHasPunishModel;
    protected $countDepartmentModel;

    public function __construct(Punish $punish, CountHasPunish $countHasPunish, CountStaff $countStaff, CountDepartment $countDepartment,
                                Rules $rules)
    {
        $this->ruleModel = $rules;
        $this->punishModel = $punish;
        $this->countStaffModel = $countStaff;
        $this->countHasPunishModel = $countHasPunish;
        $this->countDepartmentModel = $countDepartment;
    }

    /**
     * 大爱信息录入
     *
     * @param $request
     * @param $OAData
     * @param $OADataPunish
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function receiveData($request, $OAData, $OADataPunish)
    {
        $paidDate = $request->has_paid == 1 ? $request->paid_at : null;
        $howNumber = $this->countData($request->staff_sn, $request->rule_id);
        $sql = $this->regroupSql($request, $OAData, $OADataPunish, $paidDate, $howNumber);
        DB::beginTransaction();
        $punish = $this->punishModel->create($sql);
        $rule = $this->ruleModel->find($request->rule_id);
        if ($request->sync_point == 1) {
            try {
                $point = $this->storePoint($this->regroupPointSql($rule, $request, $OAData, $punish->id));
                if (!isset($point['id'])) {
                    abort(500, '数据同步验证错误,请联系管理员');
                }
                $punish->update(['point_log_id' => $point['id']]);
                $this->updateCountData($request, $punish, 1);
            } catch (\Exception $exception) {
                DB::rollBack();
                abort(500, '添加失败，错误：' . $exception->getMessage());
            }
        }
        DB::commit();
        $punish->rules = $rule;
        return response($punish, 201);
    }

    /**
     * 同步积分制数据
     *
     * @param $rule
     * @param $request
     * @param $oa
     * @param $id
     * @return array
     */
    protected function regroupPointSql($rule, $request, $oa, $id)
    {
        return [
            'title' => $rule->name,
            'staff_sn' => $request->staff_sn,
            'staff_name' => $request->staff_name,
            'brand_id' => $oa['brand_id'],
            'brand_name' => $oa['brand']['name'],
            'department_id' => $oa['department_id'],
            'department_name' => $oa['department']['full_name'],
            'shop_sn' => $oa['shop_sn'],
            'shop_name' => $oa['shop']['name'],
            'point_a' => 0,
            'point_b' => $request->score,
            'changed_at' => $request->violate_at,
            'source_id' => 6,
            'source_foreign_key' => $id,
            'first_approver_sn' => null,
            'first_approver_name' => '',
            'final_approver_sn' => null,
            'final_approver_name' => '',
            'recorder_sn' => Auth::user()->staff_sn,
            'recorder_name' => Auth::user()->realname,
            'type_id' => 2,
            'is_revoke' => 0,
        ];
    }

    /**
     * 重组sql数据
     *
     * @param $request
     * @param $OAData
     * @param $OADataPunish
     * @param $paidDate
     * @param $howNumber
     * @return array
     */
    protected function regroupSql($request, $OAData, $OADataPunish, $paidDate, $howNumber)
    {
        return [
            'rule_id' => $request->rule_id,
            'staff_sn' => $OAData['staff_sn'],
            'staff_name' => $OAData['realname'],
            'brand_id' => $OAData['brand_id'],
            'brand_name' => $OAData['brand']['name'],
            'department_id' => $OAData['department_id'],
            'department_name' => $OAData['department']['full_name'],
            'position_id' => $OAData['position_id'],
            'position_name' => $OAData['position']['name'],//
            'shop_sn' => $OAData['shop_sn'],
            'quantity' => $howNumber,
            'money' => $request->money,
            'score' => $request->score,
            'billing_sn' => $OADataPunish['staff_sn'],
            'billing_name' => $OADataPunish['realname'],
            'billing_at' => $request->billing_at,
            'violate_at' => $request->violate_at,
            'has_paid' => $request->has_paid,
            'paid_at' => $paidDate,
            'sync_point' => $request->sync_point,
            'month' => date('Ym'),
            'remark' => $request->remark,
            'creator_sn' => Auth::user()->staff_sn,
            'creator_name' => Auth::user()->realname,
        ];
    }

    /**
     * 处理单个人数据
     *
     * @param $request
     * @param $punish
     * @param $yes
     */
    public function updateCountData($request, $punish, $yes)
    {
        $departmentId = $this->updateCountDepartment($request, $punish);
        $staffData = $this->countStaffModel->where(['month' => $punish->month, 'staff_sn' => $request->staff_sn])->first();
        if ($staffData == false) {
            $countId = $this->countStaffModel->insertGetId([
                'department_id' => $departmentId,
                'staff_sn' => $punish->staff_sn,
                'staff_name' => $punish->staff_name,
                'paid_money' => $request->has_paid == 1 ? $request->money : 0,
                'month' => $yes == 0 ? $punish->month : date('Ym'),
                'money' => $request->money,
                'score' => $request->score,
                'has_settle' => $request->has_paid >= 1 ? 1 : 0
            ]);
        } else {
            $staffData->update([
                'paid_money' => $request->has_paid == 1 ? $staffData->paid_money + $request->money : $staffData->paid_money,
                'money' => $request->money + $staffData->money,
                'score' => $request->score + $staffData->score,
                'has_settle' => $request->has_paid == 1 ? $request->money + $staffData->money <=
                $staffData->paid_money + $request->money ? 1 : 0 : 0
            ]);
        }
        if ($yes == 1) {
            $this->countHasPunishModel->insert([
                'count_id' => isset($countId) ? $countId : $staffData->id,
                'punish_id' => $punish->id
            ]);
        }
    }

    /**
     * 处理部门数据
     *
     * @param $request
     * @param $punish
     * @return mixed
     */
    protected function updateCountDepartment($request, $punish)
    {
        $department = $this->countDepartmentModel->where(['month' => $punish->month, 'full_name' => $punish->department_name])->first();
        if ($department == false) {
            foreach (explode('-', $punish->department_name) as $item) {
                $department = $this->countDepartmentModel->where([
                    'full_name' => isset($info) ? implode('-', $info) . '-' . $item : $item,
                    'month' => $punish->month
                ])->first();
                if ($department == false) {
                    $department = $this->countDepartmentModel->create([
                        'department_name' => $item,
                        'parent_id' => isset($arrId) ? end($arrId) : null,
                        'full_name' => isset($info) ? implode('-', $info) . '-' . $item : $item,
                        'month' => $punish->month,
                        'paid_money' => $request->has_paid == 1 ? $request->money : 0,
                        'money' => $request->money,
                        'score' => $request->score
                    ]);
                } else {
                    $department->update([
                        'paid_money' => $request->has_paid == 1 ? $department->paid_money + $request->money : $department->paid_money,
                        'money' => $department->money != 0 ? $department->money + $request->money : $request->money,
                        'score' => $department->score != 0 ? $department->score + $request->score : $request->score
                    ]);
                }
                $arrId[] = $department->id;
                $info[] = $item;
            }
        } else {
            foreach (explode('-', $department->full_name) as $items) {
                $department = $this->countDepartmentModel->where([
                    'month' => $punish->month,
                    'full_name' => isset($arrDepartment) ? implode('-', $arrDepartment) . '-' . $items : $items
                ])->first();
                $department->update([
                    'paid_money' => $request->has_paid == 1 ? $department->paid_money + $request->money : $department->paid_money,
                    'money' => $department->money != 0 ? $department->money + $request->money : $request->money,
                    'score' => $department->score != 0 ? $department->score + $request->score : $request->score
                ]);
                $arrDepartment[] = $items;
            }
        }
        return $department->id;
    }

    /**
     * 获取单条大爱记录
     *
     * @param $request
     * @return Punish|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|null|object
     */
    public function getFirst($request)
    {
        return $this->punishModel->with('rules')->where('id', $request->route('id'))->first();
    }

    /**
     * 编辑大爱
     *
     * @param $request
     * @param $staff
     * @param $billing
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function updatePunish($request, $staff, $billing)
    {
        $paidDate = $request->has_paid == 1 ? $request->paid_at : null;
        $howNumber = $this->countData($request->staff_sn, $request->rule_id);
        $punish = $this->punishModel->find($request->route('id'));
        $rule = $this->ruleModel->find($punish->rule_id);
        if ($punish == null) {
            abort(404, '未找到数据');
        }
        if ($punish->has_paid == 1) {
            abort(400, '已付款数据不能修改');
        }
        $this->updateBeforeDateVerify($request->route('id'), $punish['month'], $staff['staff_sn']);
        if ($this->hasUpdate($request, $staff, $billing, $paidDate, $howNumber, $punish) == 1) {
            $punish->rules = $rule;
            return $punish;
        }
        try {
            DB::beginTransaction();
            $this->reduceCount($punish);//todo 减原来的分,1.找已付款高于金额的问题，2.计算公式
            if ($punish->point_log_id == true) {
                $this->deletePoint($punish->point_log_id);//删除积分制   有返回数据  需要调用
            }
            $sql = $this->regroupSql($request, $staff, $billing, $paidDate, $howNumber);
            unset($sql['month'],$sql['creator_sn'],$sql['creator_name']);
            $punish->update($sql);
            if ($request->sync_point == 1) {
                $point = $this->storePoint($this->regroupPointSql($rule, $request, $staff, $punish->id));//重新添加  返回全
                if (!isset($point['id'])) {
                    abort(500, '数据同步验证错误,请联系管理员');
                }
                $punish->update(['point_log_id' => $point['id']]);
            }
            $this->updateCountData($request, $punish, 0);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            abort(500, '修改失败，错误：' . $exception->getMessage());
        }
        $punish->rules = $rule;
        return response($punish, 201);
    }

    /**
     * 修改数据监测
     *
     * @param $request
     * @param $staff
     * @param $billing
     * @param $paidDate
     * @param $howNumber
     * @param $model
     * @return int
     */
    protected function hasUpdate($request, $staff, $billing, $paidDate, $howNumber, $model)
    {
        $arr = $this->regroupSql($request, $staff, $billing, $paidDate, $howNumber);
        unset($arr['month'],$arr['creator_sn'], $arr['creator_name']);
        $array = array_diff_assoc($arr, $model->toArray());
        if ($array == []) {
            return 1;
        }
    }

    /**
     * 最后数据监测
     *
     * @param $id
     * @param $month
     * @param $staff_sn
     */
    protected function updateBeforeDateVerify($id, $month, $staff_sn)
    {
        $data = $this->punishModel->where(['month' => $month, 'staff_sn' => $staff_sn])->orderBy('id', 'desc')->first();
        if ($data->id != $id) {
            abort(400, '不能修改之前的数据');
        }
    }

    /**
     *  单向多条未付款修改已付款
     *
     * @param $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function listPaymentUpdate($arr)
    {
        $data = [];
        try {
            DB::beginTransaction();
            foreach ($arr as $item) {
                $punish = $this->punishModel->find($item);
                if ($punish->has_paid == 1) {
                    continue;
                }
                $punish->update(['has_paid' => 1, 'paid_at' => date('Y-m-d H:i:s')]);
                $countStaff = $this->countStaffModel->where(['staff_sn' => $punish->staff_sn, 'month' => $punish->month])->first();
                $countStaff->update([
                    'paid_money' => $countStaff->paid_money + $punish->money,
                    'has_settle' => $countStaff->paid_money + $punish->money >= $countStaff->money ? 1 : 0
                ]);
                $department = $this->countDepartmentModel->find($countStaff->department_id);
                foreach (explode('-',$department->full_name) as $value){
                    $department = $this->countDepartmentModel->where([
                        'month' => $punish->month,
                        'full_name' => isset($arrDepartment) ? implode('-', $arrDepartment) . '-' . $value : $value
                    ])->first();
                    $department->update([
                        'paid_money' => $department->paid_money + $punish->money
                    ]);
                    $arrDepartment[] = $value;
                }
                $data[] = $punish;
            }
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            abort(500, '操作失败，错误：' . $exception->getMessage());
        }
        return response($data, 201);
    }

    /**
     * @param $request
     * @return array
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function punishList($request)
    {
        return $this->punishModel->with('rules')->filterByQueryString()->SortByQueryString()->withPagination($request->get('pagesize', 10));
    }

    /**
     * 详细页面的支付状态双向改变
     *
     * @param $request
     * @return array
     */
    public function detailedPagePayment($request)
    {
        $punish = $this->punishModel->find($request->route('id'));
        if ((bool)$punish == false) {
            abort(404, '未找到数据');
        }
        try {
            DB::beginTransaction();
            $countStaff = $this->countStaffModel->where(['staff_sn' => $punish->staff_sn, 'month' => $punish->month])->first();
            $department = $this->countDepartmentModel->find($countStaff->department_id);
            if ($punish->has_paid == 1) {
                $punish->update(['has_paid' => 0, 'paid_at' => NULL]);
                $countStaff->update([
                    'paid_money' => $countStaff->paid_money - $punish->money,
                    'has_settle' => 0
                ]);
                foreach (explode('-',$department->full_name) as $value){
                    $department = $this->countDepartmentModel->where([
                        'month' => $punish->month,
                        'full_name' => isset($arrDepartment) ? implode('-', $arrDepartment) . '-' . $value : $value
                    ])->first();
                    $department->update(['paid_money' => $department->paid_money - $punish->money]);
                    $arrDepartment[] = $value;
                }
            } else {
                $punish->update(['has_paid' => 1, 'paid_at' => date('Y-m-d H:i:s')]);
                $countStaff->update([
                    'paid_money' => $countStaff->paid_money + $punish->money,
                    'has_settle' => $countStaff->paid_money + $punish->money >= $countStaff->money ? 1 : 0
                ]);
                foreach (explode('-',$department->full_name) as $value){
                    $department = $this->countDepartmentModel->where([
                        'month' => $punish->month,
                        'full_name' => isset($arrDepartment) ? implode('-', $arrDepartment) . '-' . $value : $value
                    ])->first();
                    $department->update(['paid_money' => $department->paid_money + $punish->money]);
                    $arrDepartment[] = $value;
                }
            }
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            abort(500, '操作失败，错误：' . $exception->getMessage());
        }
        return response($punish, 201);
    }

    /**
     *大爱软删除
     */
    public function softRemove($request)
    {
        $punish = $this->punishModel->find($request->route('id'));
        if ((bool)$punish == false) {
            abort(404, '不存在的数据');
        }
        if ($punish->has_paid == 1) {
            abort(400, '已支付数据不能删除');
        }
        $this->reduceCount($punish);
        if ($punish->point_log_id == true) {
            $this->deletePoint($punish->point_log_id);
        }
        $punish->delete();
        return response('', 204);
    }

    /**
     * 先减去原来值
     *
     * @param $punish
     */
    protected function reduceCount($punish)
    {
        $countStaff = $this->countStaffModel->where(['staff_sn' => $punish->staff_sn, 'month' => $punish->month])->first();
        $countStaff->update([
            'money' => $countStaff->money - $punish->money,
            'score' => $countStaff->score - $punish->score,
            'has_settle' => $countStaff->paid_money + $punish->money >= $countStaff->money ? 1 : 0
        ]);
        $department = $this->countDepartmentModel->find($countStaff->department_id);
        foreach (explode('-', $department->full_name) as $item) {
            $department = $this->countDepartmentModel->where([
                'month' => $punish->month,
                'full_name' => isset($arrDepartment) ? implode('-', $arrDepartment) . '-' . $item : $item
            ])->first();
            $department->update([
                'money' => $department->money - $punish->money,
                'score' => $department->score - $punish->score
            ]);
            $arrDepartment[] = $item;
        }
    }

    public function countData($staffSn, $ruleId)
    {
        $where = [
            'staff_sn' => $staffSn,
            'rule_id' => $ruleId,
            'month' => date('Ym'),
        ];
        return $this->punishModel->where($where)->count() + 1;
    }

    /**
     * 大爱执行添加
     *
     * @param $sql
     * @return mixed
     */
    public function excelSave($sql)
    {
        return $this->punishModel->create($sql);
    }

    /**
     * 调用point 接口并返回id
     */
    public function storePoint($sql)
    {
        return app('api')->withRealException()->postPoints($sql);
    }

    /**
     * @param $id
     */
    protected function deletePoint($id)
    {
        return app('api')->withRealException()->deletePoints($id);
    }
}