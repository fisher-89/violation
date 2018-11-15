<?php

namespace App\Services;

use App\Models\CountHasDepartment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Models\Punish;
use App\Models\Rules;
use Illuminate\Support\Facades\DB;

class PunishService
{
    protected $punishModel;
    protected $produceMoneyService;

    public function __construct(Punish $punish, CountService $produceMoneyService)
    {
        $this->punishModel = $punish;
        $this->produceMoneyService = $produceMoneyService;
    }

    /**
     * 大爱信息录入  todo 添加统计表的值、同时录入多员工信息，Excel 没有员工编号
     *
     * @param $request
     * @param $OAData
     * @param $OADataPunish
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function receiveData($request, $OAData, $OADataPunish)
    {
        $hasPaid = $request->has_paid;
        $paidDate = $hasPaid == 1 ? $request->paid_at : null;
        $howNumber = $this->countData($request->criminal_sn, $request->rule_id);
        $sql = [
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
            'billing_at' => $request->billing_sn,
            'violate_at' => $request->violate_at,
            'has_paid' => $hasPaid,
            'paid_at' => $paidDate,
            'sync_point' => $request->sync_point,
            'month' => date('Ym'),
            'remark' => $request->remark,
            'creator_sn' => Auth::user()->staff_sn,
            'creator_name' => Auth::user()->realname,
        ];
        if($request->sync_point == 1){
            $this->storePoint();
        }
        $punish = $this->punishModel->create($sql);
        return response($punish, 201);
    }

    public function getFirst($request)
    {
        return $this->punishModel->with('rules')->where('id',$request->route('id'))->first();
    }

    public function updatePunish($request, $staff, $billing)
    {

    }
    /**
     * @param $request   todo 改变单个人的
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function listPaymentUpdate($request)
    {
        $punish = $this->punishModel->find($request->route('id'));
        if (empty($punish)) {
            abort(404, '不存在的记录');
        };
        if ($punish->paid_at == true) {
            abort(500, '付款状态属于已付款');
        }
        $sql = [
            'has_paid' => 1,
            'paid_at' => date('Y-m-d H:i:s')
        ];
        $punish->update($sql);
        return response($punish, 201);
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
     * @param $request
     * @return array   todo 改变单个人的
     * 详细页面的支付状态双向改变
     */
    public function detailedPagePayment($request)
    {
        $punish = $this->punishModel->find($request->route('id'));
        if ((bool)$punish == false) {
            abort(404, '未找到数据');
        }
        if ($punish->has_paid == 1) {
            $sql = [
                'has_paid' => 0,
                'paid_at' => NULL
            ];
        } else {
            $sql = [
                'has_paid' => 1,
                'paid_at' => date('Y-m-d H:i:s')
            ];
        }
        $punish->update($sql);
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
        $punish->delete();
        return response('', 204);
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

    public function excelSave($sql)
    {
        return $this->punishModel->create($sql);
    }

    /**
     * 调用point 接口并返回id
     */
    public function storePoint()
    {

    }
}