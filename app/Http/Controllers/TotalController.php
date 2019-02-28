<?php

namespace App\Http\Controllers;

use App\Services\TotalService;
use Illuminate\Http\Request;

class TotalController extends Controller
{
    protected $totalService;

    /**
     * TotalController constructor.
     * @param TotalService $totalService
     */
    public function __construct(TotalService $totalService)
    {
        $this->totalService = $totalService;
    }

    /**
     * 员工统计
     *
     * @param Request $request
     * @return mixed
     */
    public function getStaffTotal(Request $request)
    {
        $this->authority($request->user()->authorities['oa'], 199);
        return $this->totalService->getStaff($request);
    }

    public function show(Request $request)
    {
        return $this->totalService->showData($request);
    }
    /**
     * 单个人支付状态改变
     *
     * @param Request $request
     * @return array
     */
    public function payStatus(Request $request)
    {
        $this->authority($request->user()->authorities['oa'], 203);
        return $this->totalService->updateMoneyStatus($request);
    }

    public function billImage(Request $request)
    {
        $this->authority($request->user()->authorities['oa'], 212);
        return $this->totalService->billImage($request);
    }

    protected function authority($oa, $code)
    {
        if (!in_array($code, $oa)) {
            abort(401, '你没有权限操作');
        }
    }
}