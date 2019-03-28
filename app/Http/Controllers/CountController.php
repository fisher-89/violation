<?php

namespace App\Http\Controllers;

use App\Services\CountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CountController extends Controller
{
    protected $countService;

    public function __construct(CountService $countService)
    {
        $this->countService = $countService;
    }

    /**
     * 2018年10月10日16:22:35 金额
     *
     * @param Request $request
     * @return mixed
     */
    public function money(Request $request)
    {
        $staff = isset($request->staff_sn) && $request->staff_sn == true ? app('api')->withRealException()->getStaff($request->staff_sn) : false;
        $this->moneyVerify($request, $staff);
        $arr = ['staffSn' => $request->staff_sn, 'ruleId' => $request->rule_id, 'violateAt' => $request->violate_at, 'token' => $request->token];
        $all = $request->all();
        $quantity = isset($all['quantity']) && $all['quantity'] != false ? $all['quantity'] : '';
        return $this->countService->generate($staff, $arr, 'money', (string)$quantity);
    }

    /**
     * 2018年10月10日16:24:24 分值
     * @param Request $request
     * @return array|float|int
     */
    public function score(Request $request)
    {
        $staff = isset($request->staff_sn) && $request->staff_sn == true ? app('api')->withRealException()->getStaff($request->staff_sn) : false;
        $this->moneyVerify($request, $staff);
        $arr = ['staffSn' => $request->staff_sn, 'ruleId' => $request->rule_id, 'violateAt' => $request->violate_at, 'token' => $request->token];
        $all = $request->all();
        $quantity = empty($all['quantity']) ? '' : $all['quantity'];
        return $this->countService->generate($staff, $arr, 'score', (string)$quantity);
    }

    public function moneyVerify($request, $staff)
    {
        $this->validate($request, [
            'staff_sn' => ['required', 'numeric', 'digits:6', function ($attribute, $value, $event) use ($staff) {
                if ((bool)trim($value) == true) {
                    try {
                        if ((bool)$staff === false) {
                            return $event('不存在');
                        }
                    } catch (\Exception $exception) {
                        abort(500, '连接错误');
                    }
                }
            }],
            'violate_at' => 'required|date|before:' . date('Y-m-d H:i:s'),
            'rule_id' => 'required|numeric|exists:rules,id',
        ], [], [
            'staff_sn' => '被大爱员工编号',
            'violate_at' => '违纪日期',
            'rule_id' => '制度id',
        ]);
    }

    public function delMoney(Request $request)
    {
        return $this->countService->delMoney($request);
    }
}