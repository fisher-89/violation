<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Services\PunishService;
use App\Services\CountService;
use Illuminate\Http\Request;

class PunishController extends Controller
{
    protected $punishService;
    protected $produceMoneyService;

    public function __construct(PunishService $punishService, CountService $produceMoneyService)
    {
        $this->punishService = $punishService;
        $this->produceMoneyService = $produceMoneyService;
    }
    /**
     * 2018/10/9 refactor 大爱列表
     *
     * @param Request $request
     * @return array
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function punishList(Request $request)
    {
        // todo 权限筛选
        return $this->punishService->punishList($request);
    }

    /**
     * 大爱获取单条
     *
     * @param Request $request
     * @return PunishService|\Illuminate\Database\Eloquent\Model|null|object
     */
    public function getPunishFirst(Request $request)
    {
        return $this->punishService->getFirst($request);
    }

    /**
     * 2018年10月9日17:49:20 大爱添加
     *
     * @param Request $request
     * @return array|void
     */
    public function store(Request $request)
    {
        if ((bool)$request->staff_sn == true) {
            $staff = app('api')->withRealException()->getStaff(trim($request->staff_sn));
        } else {
            $staff = null;
        }
        if ((bool)$request->billing_sn == true) {
            $billing = app('api')->withRealException()->getStaff(trim($request->billing_sn));
        } else {
            $billing = null;
        }
        $this->punishStoreVerify($request, $staff, $billing);
        return $this->punishService->receiveData($request, $staff, $billing);
    }

    /**
     * 大爱修改
     *
     * @param Request $request
     */
    public function editPunish(Request $request)
    {
        if ((bool)$request->staff_sn == true) {
            $staff = app('api')->withRealException()->getStaff($request->staff_sn);
        } else {
            $staff = null;
        }
        if ((bool)$request->billing_sn == true) {
            $billing = app('api')->withRealException()->getStaff($request->billing_sn);
        } else {
            $billing = null;
        }
        $this->punishStoreVerify($request, $staff, $billing);
        return $this->punishService->updatePunish($request, $staff, $billing);
    }

    /**
     * 删除
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function delete(Request $request)
    {//todo 操作权限
        return $this->punishService->softRemove($request);
    }

    /**
     * 2018年10月9日17:49:39 表单验证
     *
     * @param $request
     * @param $staff
     * @param $punisher
     */
    protected function punishStoreVerify($request, $staff, $punisher)
    {
        $id = $request->route('id');
        $data['staff_sn'] = $request->staff_sn;
        $data['rule_id'] = $request->rule_id;
        $this->validate($request,
            [
                'rule_id' => 'required|numeric|exists:rules,id',//制度表I
                'staff_sn' => ['required', 'numeric', function ($attribute, $value, $event) use ($staff,$id) {
                    if ($staff == null) {
                        return $event('被大爱员工编号未找到');
                    }
                    if ($staff['status_id'] == '-1') {
                        return $event('当前人员属于离职状态');
                    }
                    $staffInfo = $id == false ? $staff['staff_sn'] : DB::table('punish')->where('id',$id)->value('staff_sn') ;
                    if($staffInfo != $staff['staff_sn']){
                        return $event('被大爱员工不能被修改');
                    }
                }],//被大爱者编号
                'staff_name' => 'required|max:10',//被大爱者名字
                'billing_at' => 'required|date|after:start_date',//开单时间
                'billing_sn' => ['required', 'numeric',
                    function ($attribute, $value, $event) use ($punisher) {
                        if ($punisher == null) {
                            return $event('开单人编号未找到');
                        }
                    }
                ],//开单人编号
                'billing_name' => 'required|max:10',
                'violate_at' => 'required|date|after:start_date',//违纪日期
                'money' => ['required', 'numeric',
//                    function ($attribute, $value, $event) use ($data) {
//                        $now = $this->produceMoneyService->generate($data,'money'); todo 无法使用
//                        if ($now != $value) {
//                            return $event('金额被改动');
//                        }
//                    }
                ],//大爱金额
                'score' => ['required', 'numeric',
//                    function ($attribute, $value, $event) use ($data) {
//                        $score = $this->produceMoneyService->generate($data, 'score');  todo  暂时无法使用
//                        if ($score != $value) {
//                            return $event('分值被改动');
//                        }
//                    }
                ],//分值
                'has_paid' => 'required|boolean|max:1|min:0',
                'paid_at' => 'date|nullable',
                'sync_point' => ['boolean','numeric',function($attribute, $value, $event)use($id){
                    if($id == true){
                        $point = DB::table('punish')->where('id',$id)->value('sync_point');
                        if($point != $value){
                            return $event('积分同步状态不能修改');
                        }
                    }
                }]
            ], [], [
                'rule_id' => '制度表id',
                'staff_sn' => '被大爱者编号',
                'staff_name' => '被大爱者名字',
                'billing_at' => '开单时间',
                'billing_sn' => '开单人编号',
                'billing_name' => '开单人姓名',
                'violate_at' => '违纪日期',
                'money' => '金额',
                'score' => '分值',
                'has_paid' => '是否支付',
                'paid_at' => '付款时间',
                'sync_point' => '是否同步积分制'
            ]
        );
    }

    /**
     * 付款状态单向
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function listPaymentMoney(Request $request)
    {// todo 操作权限
        return $this->punishService->listPaymentUpdate($request->all());
    }

    /**
     * 付款状态双休改变
     *
     * @param Request $request
     * @return array
     */
    public function detailedPagePayment(Request $request)
    {// todo 操作权限
        return $this->punishService->detailedPagePayment($request);
    }
}
