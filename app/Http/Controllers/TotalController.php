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
        return $this->totalService->getStaff($request);
    }

    /**
     * 单个人支付状态改变
     *
     * @param Request $request
     * @return array
     */
    public function payStatus(Request $request)
    {
        return $this->totalService->updateMoneyStatus($request->all());
    }
}