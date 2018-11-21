<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Services\PunishService;
use App\Models\CountDepartment;
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
    protected $punishService;
    protected $countStaffModel;
    protected $produceMoneyService;
    protected $countHasPunishModel;
    protected $countDepartmentModel;

    public function __construct(PunishService $punishService, CountService $countService, Punish $punish, Rules $rules,
                                CountStaff $countStaff, CountDepartment $countDepartment, CountHasPunish $countHasPunish)
    {
        $this->RulesModel = $rules;
        $this->punishModel = $punish;
        $this->countStaffModel = $countStaff;
        $this->punishService = $punishService;
        $this->produceMoneyService = $countService;
        $this->countHasPunishModel = $countHasPunish;
        $this->countDepartmentModel = $countDepartment;
    }

    /**
     * 导出
     *
     * @param Request $request
     * @return mixed
     */
    public function export(Request $request)
    {
        $all = $request->all();
        if (array_key_exists('page', $all) || array_key_exists('pagesize', $all)) {
            abort(400, '传递无效参数');
        }
        $response = $this->punishModel->with('rules')
//            ->whereHas('brands', function ($query) use ($arr) {
//                $query->whereIn('brand_id', $arr);
//            })
            ->SortByQueryString()->filterByQueryString()->withPagination();
        if (false == (bool)$response) {
            abort(404,'没有找到符号条件的数据');
        }else{
            return $response;
        }
//        $data[] = ['工号', '姓名', '部门', '大爱人', '大爱日期', '第几次', '扣分', '大爱金额', '付款时间', '大爱原因'];
//        foreach ($response as $key => $value) {
//            $data[] = [$value->criminal_sn, $value->criminal_name, $value->criminal_department, $value->punisher_name,
//                $value->Billing_at, $value->degree, $value->deduct_marks, $value->price, $value->paid_at, $value->rules->name
//            ];
//        }
//        Excel::create('大爱信息资料', function ($excel) use ($data) {
//            $excel->sheet('score', function ($query) use ($data) {
//                $query->setColumnFormat(array(
//                    'D' => 'yyyy-mm-dd',
//                ));
//                $query->rows($data);
//                $query->cells('A1:J' . count($data), function ($cells) {
//                    $cells->setAlignment('center');
//                });
//            });
//        })->export('xlsx');
    }

    /**
     * 导入
     *
     * @param Request $request
     * @return mixed
     */
    public function import(Request $request)
    {
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
        for ($i = 1; $i < count($res); $i++) {
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

            $check = $this->RulesModel->where('name', $res[$i][3])->value('id');
            if ((bool)$check === false) {
                $this->error['大爱原因'][] = '错误';
            }
            $msg['staff_sn'] = isset($oaData['staff_sn']) ? $oaData['staff_sn'] : null;
            $msg['rule_id'] = $check;
            $sql = [
                'rule_id' => $check,
                'staff_sn' => isset($oaData['staff_sn']) ? $oaData['staff_sn'] : 999999,
                'staff_name' => isset($oaData['realname']) ? $oaData['realname'] : null,
                'brand_id' => isset($oaData['brand_id']) ? $oaData['brand_id'] : null,
                'brand_name' => isset($oaData['brand']['name']) ? $oaData['brand']['name'] : null,
                'department_id' => isset($oaData['department_id']) ? $oaData['department_id'] : null,
                'department_name' => isset($oaData['department']['full_name']) ? $oaData['department']['full_name'] : null,
                'position_id' => isset($oaData['position_id']) ? $oaData['position_id'] : null,
                'position_name' => isset($oaData['position']['name']) ? $oaData['position']['name'] : null,
                'shop_sn' => isset($oaData['shop_sn']) ? $oaData['shop_sn'] : null,
                'billing_sn' => isset($punish['staff_sn']) ? $punish['staff_sn'] : 999999,
                'billing_name' => isset($punish['realname']) ? $punish['realname'] : null,
                'billing_at' => $res[$i][2],
                'quantity' => isset($oaData['staff_sn']) ? $this->punishService->countData($oaData['staff_sn'], $check) : null,
                'money' => $msg['rule_id'] != null && $msg['staff_sn'] != null ? $this->produceMoneyService->generate($msg, 'money', $oaData) : null,
                'score' => $msg['rule_id'] != null && $msg['staff_sn'] != null ? $this->produceMoneyService->generate($msg, 'score', $oaData) : null,
                'violate_at' => $res[$i][4],
                'has_paid' => is_numeric($res[$i][7]) ? (int)$res[$i][7] : $res[$i][7],
                'paid_at' => $res[$i][7] == 1 ? $res[$i][8] : null,
                'month' => date('Ym'),
                'remark' => $res[$i][9],
                'sync_point' => is_numeric($res[$i][10]) ? (int)$res[$i][10] : $res[$i][10],
                'creator_sn' => $request->user()->staff_sn,
                'creator_name' => $request->user()->realname,
            ];
            $object = new Requests\Admin\PunishRequest($sql);
            $this->excelDataVerify($object);
            if ($this->error == []) {
                $data = $this->punishService->excelSave($sql);
                $this->punishService->updateCountData($object, $data,1);
                if ($res[$i][10] == 1) {
//                    app('api')->postPoints($arr);   todo  调接口同步数据
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
        $info['data'] = isset($success) ? $success : [];
        $info['headers'] = isset($header) ? $header : [];
        $info['errors'] = isset($mistake) ? $mistake : [];
        return $info;
    }

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

    public function example()
    {
        $assist = DB::table('rules')->get();
        $rule = array_column($assist == null ? [] : $assist->toArray(), 'name');
        $cellData[] = ['员工编号', '员工姓名', '开单日期', '大爱名称', '违纪时间', '开单人编号', '开单人姓名', '是否付款', '付款时间', '备注', '同步积分制'];
        $cellData[] = ['例：100000（被大爱编号）', '例：张三（被大爱姓名）', '例：2018-01-01（开单时间）', '例：迟到30分钟内（制度名称全写）', '例：2018-01-01', '例：100000（开单人编号）', '例：李四', '例：0（0：表示没有付款，1：表示已经付款）', '例：2018-01-01（没有付款这里为空）', '默认为空', '默认同步，1:不同步'];
        $data[] = ['大爱名称'];
        for ($i = 0; $i < count($rule); $i++) {
            $data[] = [
                isset($rule[$i]) ? $rule[$i] : ''
            ];
        }
        Excel::create('大爱录入范例文件', function ($excel) use ($cellData, $data) {
            $excel->sheet('辅助表', function ($sheet) use ($data) {
                $sheet->rows($data);
            });
            $excel->sheet('主表', function ($sheet) use ($cellData) {
                $sheet->rows($cellData);
                $sheet->cells('A1', function ($cells) {
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

    protected function receive($request)
    {
//        $this->excelVerify($request);
        if (!$request->hasFile('file')) {
            abort(400, '未选择文件');
        }
        $excelPath = $request->file('file');
        if (!$excelPath->isValid()) {
            abort(400, '文件上传出错');
        }
        return $excelPath;
    }
}