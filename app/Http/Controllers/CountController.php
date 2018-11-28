<?php

namespace App\Http\Controllers;

use App\Services\CountService;
use Illuminate\Http\Request;

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
    {   return 20;
        $this->moneyVerify($request);
        $arr = ['staff_sn' => $request->staff_sn, 'rule_id' => $request->rule_id, 'violate_at' => $request->violate_at];
        return $this->countService->generate($arr, 'money');
    }

    /**
     * 2018年10月10日16:24:24 分值
     * @param Request $request
     * @return array|float|int
     */
    public function score(Request $request)
    {   return 10;
        $this->moneyVerify($request);
        $arr = ['staff_sn' => $request->staff_sn, 'rule_id' => $request->rule_id, 'violate_at' => $request->violate_at];
        return $this->countService->generate($arr, 'score');
    }

    public function moneyVerify($request)
    {
        $this->validate($request, [
            'staff_sn' => ['required', 'numeric', 'digits:6', function ($attribute, $value, $event) {
                if ((bool)trim($value) == true) {
                    try {
                        $staffInfo = app('api')->withRealException()->getStaff($value);
                        if ((bool)$staffInfo === false) {
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
}