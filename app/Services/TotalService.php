<?php

namespace App\Services;

use App\Models\Punish;
use App\Models\BillImage;
use App\Models\CountStaff;
use App\Models\CountHasPunish;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TotalService
{
    protected $billModel;
    protected $punishModel;
    protected $countStaffModel;
    protected $countHasPunishModel;

    public function __construct(Punish $punish, CountStaff $countStaff, CountHasPunish $countHasPunish, BillImage $billImage)
    {
        $this->punishModel = $punish;
        $this->billModel = $billImage;
        $this->countStaffModel = $countStaff;
        $this->countHasPunishModel = $countHasPunish;
    }

    /**
     * 获取员工统计数据
     *
     * @param $request
     * @return mixed
     */
    public function getStaff($request)
    {
        $departmentId = $request->department_id;
        $department = $departmentId == true ? app('api')->withRealException()->getDepartmenets($departmentId) : null;
        $id = $department == true ? $this->department(is_array($department) ? $department : $department->toArray()) : false;
        $list = $this->countStaffModel->with(['countHasPunish.punish'])->when($department == true, function ($query) use ($id) {
            $query->whereIn('department_id', $id);
        })->when(isset($request->area), function ($query) use ($request) {
            $query->where('area', $request->area);
        })->filterByQueryString()->SortByQueryString()->withPagination($request->get('pagesize', 10));
        if ($departmentId == false) {
            $list['money'] = DB::table('count_staff')->where('month', substr($request->filters, -6))
                ->when(isset($request->area), function ($query) use ($request) {
                    $query->where('area', $request->area);
                })->sum('money');
            $list['paid_money'] = DB::table('count_staff')->where('month', substr($request->filters, -6))
                ->when(isset($request->area), function ($query) use ($request) {
                    $query->where('area', $request->area);

                })->sum('paid_money');
            $list['score'] = DB::table('count_staff')->where('month', substr($request->filters, -6))
                ->when(isset($request->area), function ($query) use ($request) {
                    $query->where('area', $request->area);
                })->sum('score');
        }
        return $list;
    }

    public function showData($request)
    {
        $countData = $this->countStaffModel->where(['month' => $request->month, 'area' => $request->area])->get();
        $id = [];
        foreach ($countData as $value) {
            $id[] = $value['department_id'];
        }
        return array_unique($id);
    }

    /**
     * 递归提取所有部门id
     *
     * @param $array
     * @return array
     */
    protected function department($array, $id = []): array
    {
        $id[] = isset($array['id']) ? $array['id'] : '';
        if (!empty($array['children'])) {
            foreach ($array['children'] as $value) {
                $id[] = isset($value['id']) ? $value['id'] : '';
                if ($value['children'] != []) {
                    $id = $this->department($value, $id);
                }
            }
        }
        return $id;
    }

    /**
     * 同时改变多个人付款状态  全付
     *
     * @param $array
     * @return array
     */
    public function updateMoneyStatus($request)
    {
        $data = [];
        $all = $request->all();
        if (empty($all['paid_type'])) abort(404, '未找到支付类型');
        $key = $all['paid_type'] == 1 ? ('alipay') : ($all['paid_type'] == 2 ? 'wechat' : 'salary');
        try {
            DB::beginTransaction();
            foreach ($all['id'] as $k => $v) {
                $countStaff = $this->countStaffModel->find($v);
                if ($countStaff == false || $countStaff->has_settle == 1) continue;
                $countStaff->update([
                    'paid_money' => $all['paid_type'] > 2 ? ($all['paid_type'] + $countStaff->paid_money) :
                        ($countStaff->salary == 0 ? $countStaff->money : $this->valueOperation($countStaff)),
                    $key => $all['paid_type'] > 2 ? $all['paid_type'] : $countStaff->money - $countStaff->paid_money,
                    'has_settle' => 1]);
                $objId = $this->countHasPunishModel->where('count_id',$v)->get(['punish_id']);
                $arrayId = $objId == false ? [] : $objId->toArray();
                $this->punishModel->whereIn('id', array_column($arrayId,'punish_id'))->update([
                        'has_paid' => 1,
                        'action_staff_sn' => $request->user()->staff_sn,
                        'paid_type' => $all['paid_type'],
                        'paid_at' => date('Y-m-d H:i:s')]);
                $data[] = $this->countStaffModel->with(['countHasPunish.punish'])->find($v);
            }
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            abort(500, '操作失败，错误：' . $exception->getMessage());
        }
        return $data;
    }

    /**
     * 之前是否有工资扣款处理
     *
     * @param $count
     * @return int
     */
    protected function valueOperation($count)
    {
        $money = 0;
        foreach ($count->countHasPunish as $item) {
            if ($item->punish->paid_type > 2) {
                $money = isset($item->punish->money) ? $item->punish->money + $money : $money;
            }
        }
        return $count->money - $money + $count->paid_money;
    }

    /**
     * 数据监测被删除旧重新生成
     *
     * @param $request
     * @return mixed  own
     */
    public function billImage($request)
    {
        $all = $request->all();
        $clearInfo = $this->billModel->whereBetween('created_at', [date('Y-m-1'), date('Y-m-t')])->where('is_clear', 1)->get();
        $clear = is_array($clearInfo) ? $clearInfo : $clearInfo->toArray();
        if ($clear != []) {
            foreach ($clear as $key => $value) {
                $punish = $this->punishModel->whereBetween('billing_at', [date('Y-m-01 00:00:00', strtotime('-1 month')),
                    date("Y-m-d 23:59:59", strtotime(-date('d') . 'day'))])
                    ->where(['staff_sn' => $value['staff_sn'], 'has_paid' => 0])->with('rules')->get();
                $arr = is_array($punish) ? $punish : $punish->toArray();
                $savePath = $this->pushImageDispose($this->text($arr), 'individual/');//生成图片
                $this->billModel->where('id', $value['id'])->update([
                    'file_name' => $savePath['file_name'],
                    'file_path' => config('app.url') . '/storage/image/individual/' . $savePath['file_name'],
                    'is_clear' => 0
                ]);
            }
        }
        $staffSn = $request->user()->staff_sn;
        return $this->billModel->when($all['overdued'] == 1, function ($query) {
            $query->where('is_clear', 0);
        })->when($all['own'] == 1, function ($query) use ($staffSn) {
            $query->with(['bill' => function ($q) use ($staffSn) {
                $q->where(['staff_sn' => $staffSn]);
            }]);
        })->filterByQueryString()->SortByQueryString()->withPagination($request->get('pagesize', 10));
    }

    /**
     * 文字数据转图片
     *
     * @param $text
     * @return mixed
     */
    protected function pushImageDispose($text, $path = '')
    {
        $text[] = [];
        $params = [
            'row' => count($text),
            'file_name' => uniqid('xg') . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890'), 0, 6) . '.png',
            'title' => date('Y-m-d') . '大爱记录',
            'table_time' => date('Y-m-d H:i:s'),
            'data' => $text
        ];
        $base = [
            'border' => 30,//图片外边框
            'file_path' => '../storage/app/public/image/' . $path,//图片保存路径
            'title_height' => 35,//报表名称高度
            'title_font_size' => 16,//报表名称字体大小
            'font_ulr' => public_path() . '/assets/fonts/msyh.ttc',//字体文件路径
            'header_size' => 12,//表头文字大小
            'text_size' => 10,//正文字体大小
            'row_height' => 40,//每行数据行高
            'filed_staff_name_width' => 90,//序号被大爱员工的宽度
            'filed_department_name_width' => 300,//序号列被大爱部门的宽度
            'filed_billing_at_width' => 120,//违纪日期的宽度
            'filed_rules_width' => 300,//大爱原因的宽度
            'filed_violate_at_width' => 120,//开单日期的宽度
            'filed_quantity_width' => 100,//当前次数的宽度
            'filed_money_width' => 90,//金额宽度
            'table_header' => ['姓名', '部门', '开单日期', '大爱原因', '违纪日期', '当月次数', '大爱金额', '序号'],//表头文字
            'column_text_offset_arr' => [70, 300, 120, 300, 120, 100, 90, 0],//表头文字左偏移量
            'row_text_offset_arr' => [70, 300, 120, 300, 120, 100, 90, 0],//数据列文字左偏移量
        ];
        $base['img_width'] = $base['border'] * 2 + $base['filed_staff_name_width'] + $base['filed_department_name_width'] + $base['filed_billing_at_width'] +
            $base['filed_rules_width'] + $base['filed_violate_at_width'] + $base['filed_quantity_width'] + $base['filed_money_width'];//图片宽度
        $base['img_height'] = $params['row'] * $base['row_height'] + $base['border'] * 2 + $base['title_height'];//图片高度
        $border_top = $base['border'] + $base['title_height'];//表格顶部高度
        $border_bottom = $base['img_height'] - $base['border'];//表格底部高度
        $base['column_x_arr'] = [
            $base['border'] + $base['filed_staff_name_width'],//第一列边框线x轴像素
            $base['border'] + $base['filed_staff_name_width'] + $base['filed_department_name_width'],//第二列边框线x轴像素
            $base['border'] + $base['filed_staff_name_width'] + $base['filed_department_name_width'] + $base['filed_billing_at_width'],//第三列边框线x轴像素
            $base['border'] + $base['filed_staff_name_width'] + $base['filed_department_name_width'] + $base['filed_billing_at_width'] +
            $base['filed_rules_width'],//第四列边框线x轴像素
            $base['border'] + $base['filed_staff_name_width'] + $base['filed_department_name_width'] + $base['filed_billing_at_width'] +
            $base['filed_rules_width'] + $base['filed_violate_at_width'],//第五列边框线x轴像素
            $base['border'] + $base['filed_staff_name_width'] + $base['filed_department_name_width'] + $base['filed_billing_at_width'] +
            $base['filed_rules_width'] + $base['filed_violate_at_width'] + $base['filed_quantity_width'],//第六列边框线x轴像素
            $base['border'] + $base['filed_staff_name_width'] + $base['filed_department_name_width'] + $base['filed_billing_at_width'] +
            $base['filed_rules_width'] + $base['filed_violate_at_width'] + $base['filed_quantity_width'] + $base['filed_money_width'],//第七列边框线x轴像素
            $base['border'] + $base['filed_staff_name_width'] + $base['filed_department_name_width'] + $base['filed_billing_at_width'] +
            $base['filed_rules_width'] + $base['filed_violate_at_width'] + $base['filed_quantity_width'] + $base['filed_money_width'] +
            $base['filed_money_width'] + 100,//第八列边框线x轴像素
        ];
        $img = imagecreatetruecolor($base['img_width'], $base['img_height']);//创建指定尺寸图片
        $bg_color = imagecolorallocate($img, 255, 255, 255);//设定图片背景色
        $text_color = imagecolorallocate($img, 51, 51, 51);//设定文字颜色
        $border_color = imagecolorallocate($img, 204, 204, 204);//设定边框颜色
        $white_color = imagecolorallocate($img, 30, 80, 162);//设定边框颜色
        imagefill($img, 0, 0, $bg_color);//填充图片背景色
        $logo = 'image/bg.png';//水印图片
        $watermark = imagecreatefromstring(file_get_contents($logo));
        list($logoWidth, $logoHeight, $logoType) = getimagesize($logo);
        $w = imagesx($watermark);
        $h = imagesy($watermark);
        $cut = imagecreatetruecolor($w, $h);
        $white = imagecolorallocatealpha($cut, 255, 255, 255, 0);
        imagefill($cut, 0, 0, $white);
        imagecopy($cut, $watermark, 0, 0, 0, 0, $w, $h);
        for ($x = 0; $x < $base['img_width']; $x++) {
            for ($y = 0; $y < $base['img_height']; $y++) {
                imagecopymerge($img, $cut, $x, $y, 0, 0, $logoWidth, $logoHeight, 5);//pct  是水印色差深度
                $y += $logoHeight;
            }
            $x += $logoWidth;
        }
        //先填充一个黑色的大块背景
//        imagefilledrectangle($img, $base['border'], $base['border'] + $base['title_height'],
//            $base['img_width'] - $base['border'], $base['img_height'] - $base['border'], $border_color);//画矩形
        //再填充一个小两个像素的 背景色区域，形成一个两个像素的外边框         imagefill($img,20,0,imagecolorallocate($img, 30, 80, 162));
        imagefilledrectangle($img, $base['border'], $base['border'] + $base['title_height'],
            $base['img_width'] - $base['border'], $base['border'] + $base['title_height'] + $base['row_height'], $white_color);//画矩形
        //画表格纵线 及 写入表头文字
        foreach ($base['column_x_arr'] as $key => $x) {
//            imageline($img, $x, $border_top, $x, $border_bottom, $white_color);//画纵线
            imagettftext($img, $base['header_size'], 0, $x - $base['column_text_offset_arr'][$key] + 1,
                $border_top + $base['row_height'] - 14, $bg_color, $base['font_ulr'], $base['table_header'][$key]);//写入表头文字
        }
        //画表格横线
        foreach ($params['data'] as $key => $item) {
            $border_top += $base['row_height'];
            imageline($img, $base['border'], $border_top, $base['img_width'] - $base['border'], $border_top, $border_color);
            $sub = 0;
            foreach ($item as $value) {
                imagettftext($img, $base['text_size'], 0, $base['column_x_arr'][$sub] - $base['row_text_offset_arr'][$sub],
                    $border_top + $base['row_height'] - 15/*行高位置*/, $text_color, $base['font_ulr'], $value);//写入data数据
                $sub++;
            }
        }
        //计算标题写入起始位置
        $title_fout_box = imagettfbbox($base['title_font_size'], 0, $base['font_ulr'], $params['title']);//imagettfbbox() 返回一个含有 8 个单元的数组表示了文本外框的四个角：
        $title_fout_width = $title_fout_box[2] - $title_fout_box[0];//右下角 X 位置 - 左下角 X 位置 为文字宽度
        $title_fout_height = $title_fout_box[1] - $title_fout_box[7];//左下角 Y 位置- 左上角 Y 位置 为文字高度
        //居中写入标题
        imagettftext($img, $base['title_font_size'], 0, ($base['img_width'] - $title_fout_width) / 2, $base['title_height'] + 10, $text_color, $base['font_ulr'], $params['title']);
        //写入制表时间
        imagettftext($img, 8, 0, $base['border'] + 20, $base['img_height'] - 15, $text_color, $base['font_ulr'], '生成时间：' . $params['table_time']);
        $save_path = $base['file_path'] . $params['file_name'];
        if (!is_dir($base['file_path']))//判断存储路径是否存在，不存在则创建
        {
            mkdir($base['file_path'], 0777, true);//可创建多级目录
        }
        imagepng($img, $save_path);//输出图片，输出png使用imagepng方法，输出gif使用imagegif方法
        $data['save_path'] = $save_path;
        $data['file_name'] = $params['file_name'];
        return $data;
    }

    /**
     * 数据提取
     *
     * @param $array
     * @return array
     */
    protected function text($array)
    {
        foreach ($array as $key => $value) {
            $ex = explode('-', $value['department_name']);
            $arr[] = [
                'staff_name' => $value['staff_name'],
                'department_name' => count($ex) > 3 ? $this->takeDepartment($ex) : $value['department_name'],
                'billing_at' => $value['billing_at'],
                'rules' => $value['rules']['name'],
                'violate_at' => $value['violate_at'],
                'quantity' => '第' . $value['quantity'] . '次',
                'money' => $value['money']/* . '/' . $value['score']*/,
            ];
        }
        return $arr;
    }

    /**
     * 获取部门后三级
     *
     * @param $arr
     * @return string
     */
    protected function takeDepartment($arr)
    {
        $count = count($arr) - 4;
        $sum = 0;
        foreach ($arr as $key => $value) {
            if ($sum > $count) {
                $array[] = $value;
            }
            $sum++;
        }
        return implode('-', $array);
    }
}