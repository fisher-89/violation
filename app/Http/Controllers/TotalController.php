<?php

namespace App\Http\Controllers;

use App\Services\TotalService;
use Illuminate\Http\Request;

class TotalController extends Controller
{
    protected $totalService;

    public function __construct(TotalService $totalService)
    {
        $this->totalService = $totalService;
    }

    public function getStaffTotal(Request $request)
    {
        return $this->totalService->getStaff($request);
    }

    public function getDepartmentTotal(Request $request)
    {
        return $this->totalService->getDepartment($request);
    }

    public function payStatus(Request $request)
    {
        return $this->totalService->updateMoneyStatus($request->all());
    }
}