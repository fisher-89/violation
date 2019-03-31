<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\PunishRequest;
use App\Models\Pretreatment;
use App\Models\PunishHasAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\PunishService;
use App\Services\CountService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PunishController extends Controller
{
    protected $punishService;
    protected $pretreatmentModel;
    protected $punishHasAuthModel;
    protected $produceMoneyService;
    protected $error;

    public function __construct(PunishService $punishService, CountService $produceMoneyService, PunishHasAuth $punishHasAuth, Pretreatment $pretreatment)
    {
        $this->punishService = $punishService;
        $this->pretreatmentModel = $pretreatment;
        $this->punishHasAuthModel = $punishHasAuth;
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
        $this->authority($request->user()->authorities['oa'], 197);
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
        $this->authority($request->user()->authorities['oa'], 197);
        return $this->punishService->getFirst($request);
    }

    /**
     * 2018年10月9日17:49:20 大爱添加
     *
     * @param Request $request
     * @return array|void  3194492428
     */
    public function store(Request $request)
    {
        $this->authority($request->user()->authorities['oa'], 200);
        $staff = (bool)$request->staff_sn == true ? app('api')->withRealException()->getStaff(trim($request->staff_sn)) : null;
        $billing = (bool)$request->billing_sn == true ? app('api')->withRealException()->getStaff(trim($request->billing_sn)) : null;
        $this->punishStoreVerify($request, $staff, $billing);
        $this->pretreatmentModel->where(['create_sn' => $request->user()->staff_sn, 'staff_sn' => $request->staff_sn, 'month' => date('Ym', strtotime($request->violate_at)), 'rules_id' => $request->rule_id])->delete();
        return $this->punishService->receiveData($request, $staff, $billing);
    }

    /**
     * 大爱修改
     *
     * @param Request $request
     */
    public function editPunish(Request $request)
    {
        $this->authority($request->user()->authorities['oa'], 204);
        $staff = (bool)$request->staff_sn == true ? app('api')->withRealException()->getStaff($request->staff_sn) : null;
        $billing = (bool)$request->billing_sn == true ? app('api')->withRealException()->getStaff($request->billing_sn) : null;
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
    {
        $this->authority($request->user()->authorities['oa'], 202);
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
        $data['staffSn'] = $request->staff_sn;
        $data['violateAt'] = $request->violate_at;
        $data['ruleId'] = $request->rule_id;
        $data['token'] = $request->token;
        $punish = DB::table('punish')->where('id', $id)->first();
        $quantity = isset($request->quantity) ? $request->quantity : DB::table('punish')->where(['rule_id' => $request['rule_id'], 'month' => $request['violate_at']])->count() + 1;
        $this->validate($request,
            [
                'rule_id' => ['required', 'numeric', 'exists:rules,id'],//制度表I
                'pushing' => ['required', 'array', function ($attribute, $value, $event) use ($id) {
                    if ($id == true) {
                        $hasObj = $this->punishHasAuthModel->where('punish_id', $id)->get();
                        $hasArray = empty($hasObj) ? [] : array_column($hasObj->toArray(), 'auth_id');
                        $boole = date('Y-m-d H:i:s') > date('Y-m-d 21:i:s') ?
                            (array_diff_assoc($value, $hasArray) != [] ? true : false) : false;
                        if ($boole) {
                            return $event('不能修改已过推送时间的群组');
                        }
                    }
                }],
                'pushing.*' => ['required', Rule::exists('pushing_authority', 'id')->where('staff_sn', $request->user()->staff_sn),],
                'staff_sn' => ['required', 'numeric', function ($attribute, $value, $event) use ($staff, $id, $punish) {
                    if ($staff == null) {
                        return $event('被大爱员工编号未找到');
                    }
                    if ($staff['status_id'] == '-1') {
                        return $event('当前人员属于离职状态');
                    }
                    $staffInfo = $id == false ? $staff['staff_sn'] : $punish->staff_sn;
                    if ($staffInfo != $staff['staff_sn']) {
                        return $event('被大爱员工不能被修改');
                    }
                }],//被大爱者编号
                'staff_name' => 'required|max:10',//被大爱者名字
                'billing_at' => ['required', 'date', 'after_or_equal:violate_at', function ($attribute, $value, $event) use ($id, $punish) {
                    if (substr($value, 0, 7) != date('Y-m')) {
                        return $event('开单时间不能跨月');
                    }
                }],//开单时间
                'billing_sn' => ['required', 'numeric',
                    function ($attribute, $value, $event) use ($punisher) {
                        if ($punisher == null) {
                            return $event('开单人编号未找到');
                        }
                    }
                ],//开单人编号
                'billing_name' => 'required|max:10',
                'violate_at' => ['required', 'date', function ($attribute, $value, $event) use ($id, $punish) {
                    if ($id == true) {
                        if (substr($value, 0, 7) != substr($punish->violate_at, 0, 7)) {
                            return $event('违纪时间不能跨月修改');
                        }
                    }
                }],//违纪日期
                'area' => 'required|max:3',
                'money' => ['required', 'numeric',
                    function ($attribute, $value, $event) use ($data, $staff, $quantity) {
                        $now = $this->produceMoneyService->generate($staff, $data, 'money', $quantity);
                        if ($now['data'] != $value && $now['states'] != 1) {
                            return $event('金额被改动');
                        }
                    }
                ],//大爱金额
                'score' => ['required', 'numeric',
                    function ($attribute, $value, $event) use ($data, $staff, $quantity) {
                        $score = $this->produceMoneyService->generate($staff, $data, 'score', $quantity);
                        if ($score['data'] != $value && $score['states'] != 1) {
                            return $event('分值被改动');
                        }
                    }
                ],//分值
//                'has_paid' => 'required|boolean|max:1|min:0',
//                'paid_at' => 'date|nullable|after_or_equal:billing_at',
                'sync_point' => ['boolean', 'numeric', function ($attribute, $value, $event) use ($id, $punish) {
                    if ($id == true) {
                        if ($punish->sync_point != $value) {
                            return $event('积分同步状态不能修改');
                        }
                    }
                }]
            ], [], [
                'rule_id' => '制度表id',
                'staff_sn' => '被大爱者编号',
                'staff_name' => '被大爱者名字',
                'pushing' => '推送群',
                'billing_at' => '开单时间',
                'billing_sn' => '开单人编号',
                'billing_name' => '开单人姓名',
                'violate_at' => '违纪日期',
                'money' => '金额',
                'score' => '分值',
                'area' => '地区',
//                'has_paid' => '是否支付',
//                'paid_at' => '付款时间',
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
    {
        $this->authority($request->user()->authorities['oa'], 203);
        $this->validate($request, [
            'id' => 'required|array',
            'id.*' => 'required|numeric',
        ]);
        return $this->punishService->listPaymentUpdate($request);
    }

    /**
     * 付款状态双向改变
     *
     * @param Request $request
     * @return array
     */
    public function detailedPagePayment(Request $request)
    {
        $this->authority($request->user()->authorities['oa'], 203);
        return $this->punishService->detailedPagePayment($request);
    }

    protected function authority($oa, $code)
    {
        if (!in_array($code, $oa)) {
            abort(401, '你没有权限操作');
        }
    }

    public function batchStore(Request $request)
    {
        $this->authority($request->user()->authorities['oa'], 200);
        $all = $request->all();
        if (count($all) == count($all, 1)) abort(400, '数据格式不正确');
        $this->validate($request, [
            'area' => 'required|numeric|max:3',
            'pushing' => 'required|array',
            'pushing.*' => ['required', Rule::exists('pushing_authority', 'id')->where('staff_sn', $request->user()->staff_sn),],
            'sync_point' => 'required|numeric|max:1'
        ], [], [
            'area' => '地区',
            'pushing' => '推送群',
            'pushing.*' => '推送',
            'sync_point' => '积分同步',
        ]);
        $key = 0;
        DB::beginTransaction();
        foreach ($all['data'] as $value) {
            $key++;
            $this->error = [];
            if (empty($value['staff_sn']) || !is_int($value['staff_sn'])) {
                $this->error['staff_sn'][] = '员工编号错误';
                $staff = null;
            } else {
                $staff = app('api')->withRealException()->getStaff(trim($value['staff_sn']));
            }
            if (empty($value['billing_sn']) || !is_int($value['billing_sn'])) {
                $this->error['billing_sn'][] = '开单人编号错误';
                $billing = null;
            } else {
                $billing = app('api')->withRealException()->getStaff(trim($value['billing_sn']));
            }
            $punishObject = new PunishRequest($value);
            $this->verifyBatch($punishObject, $staff, $billing);
            if ($this->error != []) {
                $info['errors'][$key] = $this->error;
                continue;
            }
            $point[] = $this->punishService->storePunishData($request, $punishObject, $staff, $billing);
        }
        if ($all['sync_point'] == 1 && isset($point)) {
            try {
                $pointArr = $this->punishService->storePoint($point);
                if (!isset($pointArr[0]['source_foreign_key'])) {
                    DB::rollBack();
                    abort(500, '数据同步验证错误,请联系管理员');
                }
                foreach ($pointArr as $item) {
                    DB::table('punish')->where('id', $item['source_foreign_key'])->update([
                        'point_log_id' => $item['id']
                    ]);
                }
            } catch (\Exception $exception) {
                DB::rollBack();
                abort(500, '添加失败，错误：' . $exception->getMessage());
            }
        }
        DB::commit();
//        $this->pretreatmentModel->where('create_sn', $request->user()->staff_sn)->delete();
        if (isset($info)) return response($info, 422);
    }

    protected function verifyBatch($object, $staff, $billing)
    {
        $data['staffSn'] = $object->staff_sn;
        $data['violateAt'] = $object->violate_at;
        $data['ruleId'] = $object->rule_id;
        $data['token'] = $object->token;
        $rule = DB::table('rules')->where('id', $object->rule_id)->first();
        try {
            $this->validate($object, [
                'rule_id' => ['required', 'numeric', function ($attribute, $value, $event) use ($rule) {
                    if ($rule == false) {
                        $this->error['rule_id'][] = '大爱原因错误';
                    }
                }],
                'staff_sn' => ['required', 'numeric', function ($attribute, $value, $event) use ($staff) {
                    if ($staff == null) {
                        $this->error['staff_sn'][] = '编号错误';
                    }
                    if ($staff['status_id'] == '-1') {
                        $this->error['staff_sn'][] = '当前人员属于离职状态';
                    }
                }],//被大爱者编号
                'staff_name' => 'required|max:10',//被大爱者名字
                'billing_sn' => ['required', 'numeric',
                    function ($attribute, $value, $event) use ($billing) {
                        if ($billing == null) {
                            $this->error['billing_sn'][] = '开单人编号未找到';
                        }
                    }
                ],//开单人编号
                'billing_at' => ['required', 'date', 'before:' . date('Y-m-d H:i:s'), 'after_or_equal:' . $object->violate_at,
                    function ($attribute, $value, $event) {
                        if (substr($value, 0, 7) != date('Y-m')) {
                            $this->error['billing_at'][] = '开单时间不能跨月';
                        }
                    }],//开单时间
                'billing_name' => 'required|max:10',
                'violate_at' => 'required|date|before:' . date('Y-m-d H:i:s'),//违纪日期
                'quantity' => ['required', 'numeric', function ($attribute, $value, $event) use ($data, $rule) {
                    if ($rule != false) {
                        $quantity = $rule->money_custom_settings == 1 || $rule->score_custom_settings == 1 ?
                            $value : DB::table('punish')->where(['staff_sn' => $data['staffSn'], 'rule_id' => $data['ruleId'], 'month' => date('Ym', strtotime($data['violateAt']))])->count() + 1;
                        if ($value != $quantity) {
                            $this->error['quantity'][] = '违纪次数错误';
                        }
                    }
                }],
                'money' => ['required', 'numeric',
                    function ($attribute, $value, $event) use ($data, $staff, $object, $rule) {
                        if ($rule != false) {
                            $quantity = $rule->money_custom_settings == 1 ? $object->quantity : '';
                            $now = $this->produceMoneyService->generate($staff, $data, 'money', $quantity, 1);
                            if ($now['data'] != $value && $now['states'] != 1) {
                                $this->error['money'][] = '金额被改动';
                            }
                        }
                    }
                ],//大爱金额
                'score' => ['required', 'numeric',
                    function ($attribute, $value, $event) use ($data, $staff, $object, $rule) {
                        if ($rule != false) {
                            $quantity = $rule->score_custom_settings == 1 ? $object->quantity : '';
                            $score = $this->produceMoneyService->generate($staff, $data, 'score', $quantity, 1);
                            if ($score['data'] != $value && $score['states'] != 1) {
                                $this->error['score'][] = '分值被改动';
                            }
                        }
                    }
                ],//分值
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->validator->errors()->getMessages() as $k => $val) {
                $this->error[$k] = $this->conversionValue($val);
            }
        } catch (\Exception $exception) {
            $this->error['message'] = '系统异常：' . $exception->getMessage();
        }
    }

    protected function conversionValue($value)
    {
        $array = [];
        foreach ($value as $item) {
            $arr = explode(' ', $item);
            if (count($arr) > 2) {
                $clean = [];
                foreach ($arr as $items) {
                    if (preg_match('/^[A-Za-z]+$/', $items)) {
                        unset($items);
                    } else {
                        $clean[] = $items;
                    }
                }
                $array[] = implode($clean);
            } else {
                $array[] = isset($arr[1]) ? $arr[1] : $arr[0];
            }
        }
        return $array;
    }
}
