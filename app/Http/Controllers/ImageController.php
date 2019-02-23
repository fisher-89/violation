<?php

namespace App\Http\Controllers;

use App\Http\Resources\PushCollection;
use App\Jobs\SendImage;
use Illuminate\Http\Request;
use App\Models\PushingLog;
use App\Models\CountStaff;
use App\Models\Pushing;
use App\Models\Punish;

class ImageController extends Controller
{
    protected $punishModel;
    protected $pushingModel;
    protected $countStaffModel;
    protected $pushingLogModel;

    public function __construct(Punish $punish, CountStaff $countStaff, Pushing $pushing, PushingLog $pushingLog)
    {
        $this->punishModel = $punish;
        $this->pushingModel = $pushing;
        $this->countStaffModel = $countStaff;
        $this->pushingLogModel = $pushingLog;
    }

    public function punishImage(Request $request)
    {
        $staffSn = $request->user()->staff_sn;
        $this->validate($request, [
            'push_type' => 'between:1,1|present',
            'push_id' => 'array|required',
            'push_id.*' => ['numeric', $request->push_type == 2 ? 'present' : 'required', function ($attribute, $value, $event) use ($staffSn) {
                $string = $this->pushingModel->where('id', $value)->value('staff_sn');
                if ($string == false) {
                    return $event('未找到推送群');
                } else if ($staffSn != $string) {
                    return $event('你没有推送权限');
                }
            }],
        ], [], [
            'push_type' => '推送类型',
            'push_id' => '推送群',
            'push_id.*' => '推送群',
        ]);
        $all = $request->all();
        $pushType = $all['push_type'] == 2 ? false : true;// 1:群推送，2:单人推送，3:群和单人
        $data = isset($all['push_id']) ? $all['push_id'] : abort(400, '数据格式错误');//推送到哪个群的id
        $punishArrId = isset($all['id']) ? $all['id'] : abort(400, '未找到操作人员id');//人员选取的id
        $punish = $this->punishModel->when($all['id'] == false, function ($query) {
            $query->whereDate('created_at', date('Y-m-d'))->where('has_paid', 0);
        })->when($all['id'] == true, function ($query) use ($punishArrId) {
            $query->whereIn('id', $punishArrId);
        })->with('rules')->filterByQueryString()->withPagination($request->get('pagesize', 10));
        $text = $punish->all() == true ? $this->text($punish->toArray()) : abort(404, '没有找到默认操作数据');
        $date = date('Y-m-d H:i:s');
        if ($data != [] && $pushType === true) {
            $dingSn = [];
            foreach ($data as $k => $v) {
                $push = $this->pushingModel->where('id', $v)->first();
                $dingSn[] = ['flock_sn' => $push['flock_sn'], 'flock_name' => $push['flock_name']];
            }
            $save_path = $this->pushImageDispose($text, 'individual/');//推送的图片处理
            dd($save_path);
            $pushImage = app('api')->withRealException()->pushingDingImage(storage_path() . '/' . $save_path['save_path']);//图片存储到钉钉
            $array = [];
            foreach ($dingSn as $item) {
                $dataInfo = app('api')->withRealException()->pushingDing([
                    'chatid' => $item['flock_sn'],
                    'data' => isset($pushImage['media_id']) ? $pushImage['media_id'] : abort(500, '图片存储失败,错误：' . $pushImage['errmsg']),
                ]);
                $array[] = [
                    'sender_staff_sn' => $staffSn,
                    'sender_staff_name' => $request->user()->realname,
                    'ding_flock_sn' => $item['flock_sn'],
                    'ding_flock_name' => $item['flock_name'],
                    'staff_sn' => null,
                    'pushing_type' => 1,
                    'states' => $dataInfo['errmsg'] == 'ok' ? 1 : 0,
                    'error_message' => $dataInfo['errmsg'] == 'ok' ? null : $dataInfo['errmsg'],
                    'pushing_info' => config('app.url') . '/storage/image/individual/' . $save_path['file_name'],
                    'is_clear' => $dataInfo['errmsg'] == 'ok' ? 0 : 1,
                    'created_at' => $date,
                    'updated_at' => $date,
                ];
            }
            $this->pushingLogModel->insert($array);
        }
        if ($pushType != 1 && $punish != false) {
            SendImage::dispatch($punish->toArray())->delay(now()->addMinutes(1));
        } else {
            return response('', 200);
        }
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
            'font_ulr' => public_path() . 'assets/msyh.ttc',//字体文件路径
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

    /**
     * 所有推送记录
     *
     * @param Request $request
     * @return mixed
     */
    public function pushingLog(Request $request)
    {
        $this->authority($request->user()->authorities['oa'], 213);
        return $this->pushingLogModel->filterByQueryString()->SortByQueryString()->withPagination($request->get('pagesize', 10));
    }

    public function myPushingLog(Request $request)
    {
        return $this->pushingModel->where('sender_staff_sn', $request->user()->staff_sn)->filterByQueryString()->SortByQueryString()->withPagination($request->get('pagesize', 10));
    }

    /**
     * 写入推送记录
     *
     * @param $arr
     */
    protected function storePushingLog($arr)
    {
        return [
            'staff_sn' => isset($arr['staff_sn']) ? $arr['staff_sn'] : null,
            'staff_name' => isset($arr['staff_name']) ? $arr['staff_name'] : '定时推送',
            'ding_flock_sn' => $arr['ding_flock_sn'],
            'ding_flock_name' => $arr['ding_flock_name'],
            'pushing_type' => $arr['type'],
            'states' => $arr['errmsg'] == 'ok' ? 1 : 0,
            'error_message' => $arr['errmsg'] == 'ok' ? null : $arr['errmsg'],
            'pushing_info' => $arr['pushing_info'],
        ];
    }

    /**
     * 配置用户推送列表
     *
     * @param Request $request
     * @return mixed
     */
    public function pushingAuthList(Request $request)
    {
        $list = $this->pushingModel->where(['staff_sn' => $request->user()->staff_sn])->get();
        if (isset($list['data'])) {
            $list['data'] = new PushCollection(collect($list['data']));
            return $list;
        } else {
            return new PushCollection($list);
        }
    }

    public function updatePush(Request $request)
    {
        $all = $request->all();
        if ($all == false) {
            abort(404, '未找到数据');
        }
        $pushingObj = $this->pushingModel->where(['staff_sn' => $request->user()->staff_sn, 'default_push' => 1])->get();
        $pushingArray = is_array($pushingObj) ? [] : $pushingObj->toArray();
        foreach ($pushingArray as $val) {
            $this->pushingModel->where('id', $val['id'])->update(['default_push' => null]);
        }
        $i = 1;
        foreach ($all as $value) {
            $push = $this->pushingModel->find($value);
            if ($push == false) {
                abort(404, '第' . $i . '个推送群未找到');
            }
            if ($request->user()->staff_sn != $push->staff_sn) {
                abort(500, '操作错误');
            }
            $push->update(['default_push' => 1]);
            $i++;
            $pushArr[] = $push;
        }
        return response($pushArr, 201);
    }

    protected function authority($oa, $code)
    {
        if (!in_array($code, $oa)) {
            abort(401, '你没有权限操作');
        }
    }
}