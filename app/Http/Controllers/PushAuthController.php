<?php

namespace App\Http\Controllers;

use App\Services\PushAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PushAuthController extends Controller
{
    protected $pushAuthService;

    public function __construct(PushAuthService $pushAuth)
    {
        $this->pushAuthService = $pushAuth;
    }

    public function index(Request $request)
    {
        $this->authority($request);
        return $this->pushAuthService->index($request);
    }

    public function start(Request $request)
    {
        $this->authority($request);
        $this->verifyPush($request);
        return $this->pushAuthService->start($request);
    }

    public function edit(Request $request)
    {
        $this->authority($request);
        $this->verifyPush($request);
        return $this->pushAuthService->editPush($request);
    }

    public function delete(Request $request)
    {
        $this->authority($request);
        return $this->pushAuthService->delPush($request);
    }

    protected function authority($request)
    {
        $oa = $request->user()->authorities['oa'];
        if (!in_array('211', $oa)) {
            abort(401, '你没有权限操作');
        }
    }

    protected function verifyPush($request)
    {
        $this->validate($request, [
            'staff_sn' => 'required|numeric|max:999999',
            'staff_name' => 'required|max:10',
            'flock_sn' => ['required', 'max:50', function ($attribute, $value, $event) use ($request) {
                $push = DB::table('pushing_authority')->where(['staff_sn' => $request->staff_sn, 'flock_sn' => $value])->first();
                if ($push == true) {
                    return $event('人员存在重复，重复名称为:' . $push->flock_name);
                }
            }],//防止重复，并返回重复的群名
            'flock_name' => 'required|max:20',
            'default_push' => 'max:1'
        ], [], [
            'staff_sn' => '员工编号',
            'staff_name' => '员工姓名',
            'flock_sn' => '推送地址编号',
            'flock_name' => '推送地址名称',
            'default_push' => '是否默认选中'
        ]);
    }
}
