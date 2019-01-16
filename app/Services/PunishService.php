<?php

namespace App\Services;

use App\Models\Rules;
use App\Models\Punish;
use App\Models\BillImage;
use App\Models\RuleTypes;
use App\Models\CountStaff;
use App\Models\CountHasPunish;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PunishService
{
    protected $ruleModel;
    protected $punishModel;
    protected $billImageModel;
    protected $ruleTypesModel;
    protected $countStaffModel;
    protected $countHasPunishModel;

    public function __construct(Punish $punish, CountHasPunish $countHasPunish, CountStaff $countStaff, Rules $rules,
                                RuleTypes $ruleTypes, BillImage $billImage)
    {
        $this->ruleModel = $rules;
        $this->punishModel = $punish;
        $this->billImageModel = $billImage;
        $this->ruleTypesModel = $ruleTypes;
        $this->countStaffModel = $countStaff;
        $this->countHasPunishModel = $countHasPunish;
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
        $paidDate = $request->has_paid == 1 ? $request->paid_at == false ? date('Y-m-d H:i:s') : $request->paid_at : null;
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
            } catch (\Exception $exception) {
                DB::rollBack();
                abort(500, '添加失败，错误：' . $exception->getMessage());
            }
        }
        $request->brand_name = $OAData['brand']['name'];
        $request->department_id = $OAData['department_id'];
        $this->updateCountData($request, $punish, 1);
        if (substr($request->billing_at, 0, 7) != date('Y-m')) {
            $this->eliminateUltimoBill($punish, 0);
        }
        DB::commit();
        $rule->rule_types = $this->ruleTypesModel->where('id', $rule['type_id'])->first();
        $punish->rules = $rule;
        return response($punish, 201);
    }

    public function eliminateUltimoBill($staff, $n)//1  是修改  todo 支付也要删除图片
    {
        $monthData = $this->billImageModel->where('staff_sn', $staff->staff_sn)->whereBetween('created_at',
            [date('Y-m-01 00:00:00'), date("Y-m-d 23:59:59")])->first();
        if ($monthData != false) {
            $filePath = 'image/individual' . basename($monthData['file_path']);
            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            $monthData->update(['is_clear' => 1]);
        }
        $countData = $this->countStaffModel->where(['staff_sn' => $staff->staff_sn, 'month' => $staff->month])->first();
        if ($countData == true) {
            if ($n == 1) {

            } else {
                $countData->update([
                    'paid_money' => $staff->has_paid == 1 ? $countData->money + $staff->money : $countData->money,
                    'money=>' => $staff->money + $countData->money,
                    'score' => $staff->score + $countData->score,
                    'has_settle' => $staff->has_paid == 1 ? $staff->money + $countData->paid_money >= $countData->money ? 1 : 0 : 0
                ]);
            }
        } else {

        }
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
            'title' => isset($rule->name) ? $rule->name : abort(500, '未找到标题'),
            'staff_sn' => isset($request->staff_sn) ? $request->staff_sn : abort(500, '未找到员工编号'),
            'staff_name' => isset($request->staff_name) ? $request->staff_name : abort(500, '未找到员工姓名'),
            'brand_id' => isset($oa['brand_id']) ? $oa['brand_id'] : abort(500, '未找到品牌id'),
            'brand_name' => isset($oa['brand']['name']) ? $oa['brand']['name'] : abort(500, '未找到品牌名称'),
            'department_id' => isset($oa['department_id']) ? $oa['department_id'] : abort(500, '未找到部门id'),
            'department_name' => isset($oa['department']['full_name']) ? $oa['department']['full_name'] : abort(500, '未找到部门名称'),
            'shop_sn' => isset($oa['shop_sn']) ? $oa['shop_sn'] : null,
            'shop_name' => isset($oa['shop']['name']) ? $oa['shop']['name'] : null,
            'point_a' => 0,
            'point_b' => $request->score,
            'changed_at' => isset($request->billing_at) ? $request->billing_at : null,
            'source_id' => 6,
            'source_foreign_key' => isset($id) ? $id : null,
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
            'rule_id' => isset($request->rule_id) ? $request->rule_id : abort(500, '未找到制度id'),
            'staff_sn' => isset($OAData['staff_sn']) ? $OAData['staff_sn'] : abort(500, '未找到员工编号'),
            'staff_name' => isset($OAData['realname']) ? $OAData['realname'] : abort(500, '未找到员工姓名'),
            'brand_id' => isset($OAData['brand_id']) ? $OAData['brand_id'] : abort(500, '未找到品牌id'),
            'brand_name' => isset($OAData['brand']['name']) ? $OAData['brand']['name'] : abort(500, '未找到品牌名称'),
            'department_id' => isset($OAData['department_id']) ? $OAData['department_id'] : abort(500, '未找到部门id'),
            'department_name' => isset($OAData['department']['full_name']) ? $OAData['department']['full_name'] : abort(500, '未找到部门名称'),
            'position_id' => isset($OAData['position_id']) ? $OAData['position_id'] : abort(500, '未找到职位id'),
            'position_name' => isset($OAData['position']['name']) ? $OAData['position']['name'] : abort(500, '未找到职位名称'),
            'shop_sn' => isset($OAData['shop_sn']) ? $OAData['shop_sn'] : null,
            'quantity' => isset($howNumber) ? $howNumber : abort(500, '当前次数为找到'),
            'money' => isset($request->money) ? $request->money : abort(500, '罚款金额未找到'),
            'score' => isset($request->score) ? $request->score : abort(500, '扣分分值未找到'),
            'billing_sn' => isset($OADataPunish['staff_sn']) ? $OADataPunish['staff_sn'] : null,
            'billing_name' => isset($OADataPunish['realname']) ? $OADataPunish['realname'] : null,
            'billing_at' => isset($request->billing_at) ? $request->billing_at : abort(500, '未找到开单日期'),
            'violate_at' => isset($request->violate_at) ? $request->violate_at : abort(500, '违纪日期'),
            'has_paid' => $request->has_paid == 1 ? 1 : 0,
            'paid_at' => $paidDate,
            'sync_point' => isset($request->sync_point) ? $request->sync_point : null,
            'month' => isset($request->billing_at) ? substr($request->billing_at, 0, 4) . substr($request->billing_at, 5, 2) : date('Ym'),
            'remark' => isset($request->remark) ? $request->remark : null,
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
        $staffData = $this->countStaffModel->where(['month' => $punish->month, 'staff_sn' => $request->staff_sn])->first();
        if ($staffData == false) {
            $countId = $this->countStaffModel->insertGetId([
                'department_id' => $request->department_id,
                'brand_name' => $request->brand_name,
                'staff_sn' => $punish->staff_sn,
                'staff_name' => $punish->staff_name,
                'paid_money' => $request->has_paid == 1 ? $request->money : 0,
                'month' => $yes == 0 ? $punish->month : substr($request->billing_at, 0, 4) . substr($request->billing_at, 5, 2),
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
        $paidDate = $request->has_paid == 1 ? $request->paid_at == false ? date('Y-m-d H:i:s') : $request->paid_at : null;
        $howNumber = $this->countData($request->staff_sn, $request->rule_id);
        $punish = $this->punishModel->find($request->route('id'));
        if ($punish == null) {
            abort(404, '未找到数据');
        }
        if ($punish->has_paid == 1) {
            abort(400, '已付款数据不能修改');
        }
        $rule = $this->ruleModel->find($punish->rule_id);
        $rule->rule_types = $this->ruleTypesModel->where('id', $rule['type_id'])->first();
        $this->updateBeforeDateVerify($request->route('id'), $punish['month'], $staff['staff_sn']);
        if ($this->hasUpdate($request, $staff, $billing, $paidDate, $howNumber, $punish) == 1) {
            $punish->rules = $rule;
            return $punish;
        }
        try {
            DB::beginTransaction();
            $this->reduceCount($punish);
            if ($punish->point_log_id == true) {
                $this->deletePoint($punish->point_log_id);//删除积分制   有返回数据  需要调用
            }
            $sql = $this->regroupSql($request, $staff, $billing, $paidDate, $howNumber);
            unset($sql['month'], $sql['creator_sn'], $sql['creator_name']);
            $punish->update($sql);
            if ($request->sync_point == 1) {
                $point = $this->storePoint($this->regroupPointSql($rule, $request, $staff, $punish->id));//重新添加  返回全
                if (!isset($point['id'])) {
                    abort(500, '数据同步验证错误,请联系管理员');
                }
                $punish->update(['point_log_id' => $point['id']]);
            }
            $request->brand_name = $staff['brand']['name'];
            $request->department_id = $staff['department_id'];
            $this->updateCountData($request, $punish, 0);
            if (substr($request->billing_at, 0, 7) != date('Y-m')) {
                $this->eliminateUltimoBill($punish, 1);
            }
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
        unset($arr['month'], $arr['creator_sn'], $arr['creator_name']);
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
                $punish = $this->punishModel->with('rules.ruleTypes')->find($item);
                if ($punish->has_paid == 1) {
                    continue;
                }
                $punish->update(['has_paid' => 1, 'paid_at' => date('Y-m-d H:i:s')]);
                $countStaff = $this->countStaffModel->where(['staff_sn' => $punish->staff_sn, 'month' => $punish->month])->first();
                $countStaff->update([
                    'paid_money' => $countStaff->paid_money + $punish->money,
                    'has_settle' => $countStaff->paid_money + $punish->money >= $countStaff->money ? 1 : 0
                ]);
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
        return $this->punishModel->with('rules.ruleTypes')->filterByQueryString()->SortByQueryString()->withPagination($request->get('pagesize', 10));
    }

    /**
     * 详细页面的支付状态双向改变
     *
     * @param $request
     * @return array
     */
    public function detailedPagePayment($request)
    {
        $punish = $this->punishModel->with('rules.ruleTypes')->find($request->route('id'));
        if ((bool)$punish == false) {
            abort(404, '未找到数据');
        }
        try {
            DB::beginTransaction();
            $countStaff = $this->countStaffModel->where(['staff_sn' => $punish->staff_sn, 'month' => $punish->month])->first();
            if ($punish->has_paid == 1) {
                $punish->update(['has_paid' => 0, 'paid_at' => NULL]);
                $countStaff->update([
                    'paid_money' => $countStaff->paid_money - $punish->money,
                    'has_settle' => 0
                ]);

            } else {
                $punish->update(['has_paid' => 1, 'paid_at' => date('Y-m-d H:i:s')]);
                $countStaff->update([
                    'paid_money' => $countStaff->paid_money + $punish->money,
                    'has_settle' => $countStaff->paid_money + $punish->money >= $countStaff->money ? 1 : 0
                ]);
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
        if ($countStaff == true) {
            $countStaff->update([
                'money' => $countStaff->money - $punish->money,
                'score' => $countStaff->score - $punish->score,
                'has_settle' => $countStaff->paid_money + $punish->money >= $countStaff->money ? 1 : 0
            ]);
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