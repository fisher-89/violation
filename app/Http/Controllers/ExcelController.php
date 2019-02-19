<?php

namespace App\Http\Controllers;

use App\Models\DingGroup;
use App\Models\PunishHasAuth;
use App\Models\Pushing;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\PunishService;
use App\Models\CountHasPunish;
use App\Services\CountService;
use Illuminate\Http\Request;
use App\Models\CountStaff;
use App\Http\Requests;
use App\Models\Punish;
use App\Models\Rules;
use Excel;


class ExcelController extends Controller
{
    protected $error;
    protected $RulesModel;
    protected $punishModel;
    protected $pushingModel;
    protected $punishHasAuth;
    protected $punishService;
    protected $dingGroupModel;
    protected $countStaffModel;
    protected $produceMoneyService;
    protected $countHasPunishModel;

    public function __construct(PunishService $punishService, CountService $countService, Punish $punish, Rules $rules, PunishHasAuth $punishHasAuth,
                                CountStaff $countStaff, CountHasPunish $countHasPunish, DingGroup $dingGroup, Pushing $pushing)
    {
        $this->RulesModel = $rules;
        $this->punishModel = $punish;
        $this->pushingModel = $pushing;
        $this->dingGroupModel = $dingGroup;
        $this->countStaffModel = $countStaff;
        $this->punishHasAuth = $punishHasAuth;
        $this->punishService = $punishService;
        $this->produceMoneyService = $countService;
        $this->countHasPunishModel = $countHasPunish;
    }

    /**
     * Excel导出
     *
     * @param Request $request
     * @return mixed
     */
    public function export(Request $request)
    {
        $this->authority($request->user()->authorities['oa'], 205);
        $model = $this->punishModel->with('rules.ruleTypes');
        return $this->excelData($request, $model);
    }

    /**
     * 导入
     *
     * @param Request $request
     * @return mixed
     */
    public function import(Request $request)
    {
        $this->authority($request->user()->authorities['oa'], 201);
        $this->getExcelFileError($request);
        $excelPath = $this->receive($request);
        $res = [];
        try {
            Excel::selectSheets('主表')->load($excelPath, function ($matter) use (&$res) {
                $matter = $matter->getSheet();
                $res = $matter->toArray();
            });
        } catch (\Exception $exception) {
            abort(404, '未找到主表');
        }
        if (!isset($res[1]) || implode($res[1]) == '') {
            abort(404, '未找到导入数据');
        }
        $header = $res[0];
        $count = count($res);
        DB::beginTransaction();
        for ($i = 1; $i < $count; $i++) {
            $this->error = [];
            if (is_numeric(trim($res[$i][0]))) {
                try {
                    $oaData = app('api')->withRealException()->getStaff(trim((int)$res[$i][0]));
                } catch (\Exception $exception) {
                    $this->error['员工编号'][] = '未找到';
                }
            } else {
                $this->error['员工编号'][] = '不正确';
            }
            if (is_numeric(trim($res[$i][5]))) {
                try {
                    $punish = app('api')->withRealException()->getStaff(trim($res[$i][5]));
                } catch (\Exception $exception) {
                    $this->error['开单人编号'][] = '未找到';
                }
            } else {
                $this->error['开单人编号'][] = '不正确';
            }
            $rule = $this->RulesModel->where('name', $res[$i][3])->first();
            $check = $rule != null ? $rule->id : null;
            if ((bool)$check === false) {
                $this->error['大爱原因'][] = '错误';
            }
            $msg['staffSn'] = isset($oaData['staff_sn']) ? $oaData['staff_sn'] : null;
            $msg['violateAt'] = $res[$i][4];
            $msg['ruleId'] = $check;
            $sql = [
                'rule_id' => $check,
                'staff_sn' => isset($oaData['staff_sn']) ? $oaData['staff_sn'] : null,
                'staff_name' => isset($oaData['realname']) ? $oaData['realname'] : null,
                'brand_id' => isset($oaData['brand_id']) ? $oaData['brand_id'] : null,
                'brand_name' => isset($oaData['brand']['name']) ? $oaData['brand']['name'] : null,
                'department_id' => isset($oaData['department_id']) ? $oaData['department_id'] : null,
                'department_name' => isset($oaData['department']['full_name']) ? $oaData['department']['full_name'] : null,
                'position_id' => isset($oaData['position_id']) ? $oaData['position_id'] : null,
                'position_name' => isset($oaData['position']['name']) ? $oaData['position']['name'] : null,
                'shop_sn' => isset($oaData['shop_sn']) ? $oaData['shop_sn'] : null,
                'billing_sn' => isset($punish['staff_sn']) ? $punish['staff_sn'] : null,
                'billing_name' => isset($punish['realname']) ? $punish['realname'] : null,
                'billing_at' => $res[$i][2],
                'quantity' => isset($oaData['staff_sn']) ? $this->punishService->countData($msg) : null,
                'money' => $msg['ruleId'] != null && $msg['staffSn'] != null && $msg['violateAt'] != null ? $this->produceMoneyService->generate($oaData, $msg, 'money') : null,
                'score' => $msg['ruleId'] != null && $msg['staffSn'] != null && $msg['violateAt'] != null ? $this->produceMoneyService->generate($oaData, $msg, 'score') : null,
                'violate_at' => $res[$i][4],
                'has_paid' => is_numeric($res[$i][7]) ? (int)$res[$i][7] : $res[$i][7],
                'action_staff_sn' => $res[$i][7] == 1 ? $request->user()->realname : null,
                'paid_at' => $res[$i][7] == 1 ? $res[$i][8] == true ? $res[$i][8] : date('Y-m-d H:i:s') : null,
                'month' => date('Ym'),
                'remark' => $res[$i][9],
                'pushing' => $this->pushingDispose($res[$i][10]),
                'sync_point' => is_numeric($res[$i][11]) ? (int)$res[$i][11] : $res[$i][11],
                'creator_sn' => $request->user()->staff_sn,
                'creator_name' => $request->user()->realname,
            ];
            $object = new Requests\Admin\PunishRequest($sql);
            $this->excelDataVerify($object);
            if ($this->error == []) {
                $data = $this->punishService->excelSave($sql);
                $hasSql = [];
                foreach ($sql['pushing'] as $punishValue) {
                    $hasSql[] = [
                        'punish_id' => $data->id,
                        'auth_id' => $punishValue
                    ];
                }
                $this->punishHasAuth->insert($hasSql);
                if (substr($data->billing_at, 0, 7) != date('Y-m')) {
                    $this->punishService->eliminateUltimoBill($data);
                }
                $object->brand_name = $oaData['brand']['name'];
                $this->punishService->updateCountData($object, $data, 1);
                if ($res[$i][11] == 1) {
                    $point[] = $this->pointSql($rule, $object, $oaData, $data->id);
                }
                if ($data == true) {
                    $success[] = $data;
                }
            } else {
                $errors['row'] = $i + 1;
                $errors['rowData'] = $res[$i];
                $errors['message'] = $this->error;
                $mistake[] = $errors;
                continue;
            }
        }
        if (isset($point)) {
            try {
                $arr = app('api')->withRealException()->postPoints($point);
                if (!isset($arr[0]['source_foreign_key'])) {
                    abort(500, '数据同步验证错误,请联系管理员');
                }
            } catch (\Exception $exception) {
                DB::rollBack();
                abort(500, '数据同步失败，错误：' . $exception->getMessage());
            }
            foreach ($arr as $item) {
                $this->punishModel->where('id', $item['source_foreign_key'])->update([
                    'point_log_id' => $item['id']
                ]);
            }
        }
        DB::commit();
        $info['data'] = isset($success) ? $success : [];
        $info['headers'] = isset($header) ? $header : [];
        $info['errors'] = isset($mistake) ? $mistake : [];
        return $info;
    }

    protected function pushingDispose($string)
    {
        $pushingArray = explode(',', $string);
        $array = [];
        foreach ($pushingArray as $value) {
            $id = $this->pushingModel->where(['flock_name' => $value, 'staff_sn' => Auth::user()->staff_sn])->value('id');
            if ($id == false) {
                $this->error['推送群'][] = $value . '没找到';
            }
            $array[] = $id;
        }
        return $array;
    }

    public function staffExcel(Request $request)
    {
        $model = $this->punishModel;
        return $this->excelData($request, $model);
    }

    /**
     * Excel 重组数组
     *
     * @param $rule
     * @param $request
     * @param $oa
     * @param $id
     * @return array
     */
    protected function pointSql($rule, $request, $oa, $id)
    {
        return [
            'title' => $rule->name,
            'staff_sn' => $request->staff_sn,
            'staff_name' => $request->staff_name,
            'brand_id' => $oa['brand_id'],
            'brand_name' => $oa['brand']['name'],
            'department_id' => $oa['department_id'],
            'department_name' => $oa['department']['full_name'],
            'shop_sn' => isset($oa['shop_sn']) ? $oa['shop_sn'] : '',
            'shop_name' => isset($oa['shop']['name']) ? $oa['shop']['name'] : null,
            'point_a' => 0,
            'point_b' => $request->score,
            'changed_at' => $request->billing_at,
            'source_id' => 6,
            'source_foreign_key' => $id,
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
     * Excel 文件接收验证
     *
     * @param $request
     */
    protected function getExcelFileError($request)
    {
        if (!$request->hasFile('file')) {
            abort(400, '未选择文件');
        }
        $excelPath = $request->file('file');
        if (!$excelPath->isValid()) {
            abort(400, '文件上传出错');
        }
    }

    public function getDingGroup()
    {
        return $this->dingGroupModel->get();
    }

    /**
     * Excel 导入模板
     */
    public function example(Request $request)
    {
        $assist = DB::table('rules')->get();
        $pushingObj = DB::table('pushing_authority')->where('staff_sn', $request->user()->staff_sn)->get();
        $rule = array_column($assist == null ? [] : $assist->toArray(), 'name');
        $flockName = array_column($pushingObj == null ? [] : $pushingObj->toArray(), 'flock_name');
        $cellData[] = ['员工编号', '员工姓名', '开单日期', '大爱名称', '违纪时间', '开单人编号', '开单人姓名', '是否付款', '付款时间', '备注', '推送的群', '同步积分制'];
        $cellData[] = ['例：100000（被大爱编号）', '例：张三（被大爱姓名）', '例：2018-01-01（开单时间）', '例：迟到30分钟内（制度名称全写）', '例：2018-01-01', '例：100000（开单人编号）', '例：李四', '例：0（0：表示没有付款，1：表示已经付款）', '例：2018-01-01（没有付款这里为空）', '默认为空', '例：喜歌实业重要通知群(多个群用英文逗号分开)', '默认不同步，1:同步'];
        $data[] = ['大爱名称', '能推送的群'];
        for ($i = 0; $i < count(max($rule, $flockName)); $i++) {
            $data[] = [
                isset($rule[$i]) ? $rule[$i] : '',
                isset($flockName[$i]) ? $flockName[$i] : ''
            ];
        }
        Excel::create('大爱录入范例文件', function ($excel) use ($cellData, $data) {
            $excel->sheet('辅助表', function ($sheet) use ($data) {
                $sheet->cells('A1:B1', function ($cells) {
                    $cells->setAlignment('center');
                    $cells->setBackground('#D2E9FF');
                });
                $sheet->rows($data);
            });
            $excel->sheet('主表', function ($sheet) use ($cellData) {
                $sheet->rows($cellData);
                $sheet->cells('A1:L1', function ($cells) {
                    $cells->setAlignment('center');
                    $cells->setBackground('#D2E9FF');
                });
                $sheet->setColumnFormat(array(
                    'A' => '@',
                    'B' => '@',
                    'C' => '@',
                    'D' => '@',
                    'E' => '@',
                    'F' => '@',
                    'G' => '@',
                    'H' => '@',
                    'I' => '@'
                ));
            });
        })->export('xlsx');
    }

    /**
     * Excel 文件验证
     *
     * @param $request
     */
    protected function excelVerify($request)
    {
        $this->validate($request,
            [
                'file' => 'required|file|mimes:xls,xlsx'
            ], [], [
                'file' => '文件'
            ]
        );
    }

    /**
     * Excel 内容验证
     *
     * @param $request
     */
    protected function excelDataVerify($request)
    {
        try {
            $this->validate($request,
                [
                    'staff_sn' => 'required|numeric',
                    'billing_sn' => 'required|numeric',
                    'billing_at' => 'required|date|before:' . date('Y-m-d H:i:s') . '|after_or_equal:' . $request->violate_at,//开单时间
                    'violate_at' => 'required|date|before:' . date('Y-m-d H:i:s'),//违纪日期
                    'has_paid' => 'required|boolean|digits:1,0|nullable',//支付状态
                    'paid_at' => ['date', 'nullable', 'after_or_equal:' . $request->billing_at, function ($attribute, $value, $event) use ($request) {
                        if ((bool)trim($request->has_paid) == false) {
                            if (trim($value) == true) {
                                $this->error['未付款'][] = '付款时间应为空';
                            }
                        } else {
                            if ((bool)trim($value) == false) {
                                $this->error['已付款'][] = '付款时间不能为空';
                            }
                        }
                    }],
                    'remark' => '',
                    'sync_point' => 'boolean|nullable|numeric'
                ]
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->validator->errors()->getMessages() as $key => $value) {
                $this->error[$this->conversion($key)] = $this->conversionValue($value);
            }
        } catch (\Exception $e) {
            $this->error['message'] = '系统异常：' . $e->getMessage();
        }
    }

    /**
     * Excel错误返回转换
     *
     * @param $str
     * @return mixed
     */
    protected function conversion($str)
    {
        $arr = [
            'rule_id' => '制度id',
            'staff_sn' => '员工编号',
            'staff_name' => '员工名字',
            'brand_id' => '品牌id',
            'brand_name' => '品牌名称',
            'department_id' => '部门id',
            'department_name' => '部门名称',
            'shop_sn' => '店铺代码',
            'position_id' => '被大爱者职位id',
            'position_name' => '职位名称',
            'billing_sn' => '开单人编号',
            'billing_name' => '开单人名字',
            'violate_at' => '违纪日期',
            'money' => '金额',
            'score' => '分值',
            'has_paid' => '是否付款',
            'billing_at' => '开单日期',
            'paid_at' => '付款日期',
            'remark' => '备注',
            'sync_point' => '是否同步积分制',
        ];
        return $arr[$str];
    }

    /**
     * Excel错误返回拆分重组
     *
     * @param $value
     * @return array
     */
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

    /**
     * Excel 文件接收验证
     *
     * @param $request
     * @return mixed
     */
    protected function receive($request)
    {
        if (!$request->hasFile('file')) {
            abort(400, '未选择文件');
        }
        $excelPath = $request->file('file');
        if (!$excelPath->isValid()) {
            abort(400, '文件上传出错');
        }
        return $excelPath;
    }

    public function countStaffExcel(Request $request)
    {
        $model = $this->countStaffModel;
        return $this->excelData($request, $model);
    }

    protected function excelData($request, $model)
    {
        $all = $request->all();
        if (array_key_exists('page', $all) || array_key_exists('pagesize', $all)) {
            abort(400, '传递无效参数');
        }
        $response = $model->SortByQueryString()->filterByQueryString()->withPagination();
        if (false == (bool)$response) {
            abort(404, '没有找到符号条件的数据');
        } else {
            return $response;
        }
    }

    protected function authority($oa, $code)
    {
        if (!in_array($code, $oa)) {
            abort(401, '你没有权限操作');
        }
    }
}