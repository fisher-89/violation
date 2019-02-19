<?php

namespace App\Console\Commands;

use App\Models\Punish;
use App\Models\BillImage;
use App\Models\PushingLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PushCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:pushCommand';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    protected $billModel;
    protected $punishModel;
    protected $pushingLogModel;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(Punish $punish, BillImage $billImage, PushingLog $pushingLog)
    {
        parent::__construct();
        $this->punishModel = $punish;
        $this->billModel = $billImage;
        $this->pushingLogModel = $pushingLog;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $punish = $this->punishModel->whereBetween('created_at', [date('Y-m-d 20:00:00', strtotime('-4 day')),
            date('Y-m-d 19:59:59')])->where('has_paid', 0)->with(['rules', 'pushing.pushingAuthority'])->get();
        $arr = is_array($punish) ? [] : $punish->toArray();
        if ($arr != []) {
            $flock = [];
            foreach ($arr as $items) {
                foreach ($items['pushing'] as $value) {
                    $array = [];
                    if (in_array($value['pushing_authority']['flock_sn'], $flock)) {
                        $info[$value['pushing_authority']['flock_sn']][] = $this->text($items);
                    } else {
                        $array[] = $this->text($items);
                        $info[$value['pushing_authority']['flock_sn']] = $array;
                        $flock[] = $value['pushing_authority']['flock_sn'];
                    }
                }
            }
//            unset($punish, $flock, $array);
//            ini_set('memory_limit', '1024M');
            foreach ($info as $key => $val) {
                try {
                    $fileData = $this->pushImageDispose($val, 'individual/');
                    $pushImage = app('api')->withRealException()->taskPushingDingImage($fileData['save_path']);
                    $dataInfo = app('api')->withRealException()->taskPushingDing([
                        'chatid' => $key,
                        'data' => isset($pushImage['media_id']) ? $pushImage['media_id'] : $this->errorDispose($pushImage['errmsg'], $key, $fileData['file_name']),
                    ]);
                }catch (\Exception $exception){
                    $this->pushingLogModel->create([
                        'sender_staff_sn' => null,
                        'sender_staff_name' => '定时20:00推送',
                        'ding_flock_sn' => isset($key) && $key!=false ? $key : null,
                        'ding_flock_name' => isset($key) && $key!=false ? DB::table('ding_group')->where('group_sn', $key)->value('group_name') : '无法推送',
                        'staff_sn' => null,
                        'pushing_type' => 3,
                        'states' => 0,
                        'error_message' => '错误:'.$exception->getMessage(),
                        'pushing_info' => config('app.url') . '/storage/image/individual/' . $fileData['file_name'],
                        'is_clear' => 1,
                    ]);
                }
//                unset($pushImage);
                $date = date('Y-m-d H:i:s');
                $array[] = [
                    'sender_staff_sn' => null,
                    'sender_staff_name' => '定时20:00推送',
                    'ding_flock_sn' => $key,
                    'ding_flock_name' => DB::table('ding_group')->where('group_sn', $key)->value('group_name'),
                    'staff_sn' => null,
                    'pushing_type' => 3,
                    'states' => $dataInfo['errmsg'] == 'ok' ? 1 : 0,
                    'error_message' => $dataInfo['errmsg'] == 'ok' ? null : $dataInfo['errmsg'],
                    'pushing_info' => config('app.url') . '/storage/image/individual/' . $fileData['file_name'],
                    'is_clear' => $dataInfo['errmsg'] == 'ok' ? 0 : 1,
                    'created_at' => $date,
                    'updated_at' => $date,
                ];

            }
            $this->pushingLogModel->insert($array);
        }else{
            $this->pushingLogModel->create([
                'sender_staff_sn' => null,
                'sender_staff_name' => '定时20:00推送',
                'ding_flock_sn' => null,
                'ding_flock_name' => '无法推送',
                'staff_sn' => null,
                'pushing_type' => 3,
                'states' => 0,
                'error_message' => '没有找到推送的数据',
                'pushing_info' => null,
                'is_clear' => 1,
            ]);
        }
    }

    protected function errorDispose($err, $key, $file)
    {
        $this->pushingLogModel->create([
            'sender_staff_sn' => null,
            'sender_staff_name' => '定时20:00推送',
            'ding_flock_sn' => $key,
            'ding_flock_name' => DB::table('ding_group')->where('group_sn', $key)->value('group_name'),
            'staff_sn' => null,
            'pushing_type' => 3,
            'states' => 0,
            'error_message' => '向钉钉存储图片失败,错误：' . $err,
            'pushing_info' => config('app.url') . '/storage/image/individual/' . $file,
            'is_clear' => 1
        ]);
    }

    protected function pushImageDispose($text, $path = '')
    {
        $text[] = [];
        $params = [
            'row' => count($text),
            'file_name' => uniqid('xg') . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890'), 0, 6) . '.png',
            'title' => date('Y年m月d日') . '大爱记录',
            'table_time' => date('Y-m-d H:i:s'),
            'data' => $text
        ];
        $base = [
            'border' => 30,//图片外边框
            'file_path' => storage_path() . '/app/public/image/' . $path,//图片保存路径
            'title_height' => 35,//报表名称高度
            'title_font_size' => 16,//报表名称字体大小
            'font_ulr' => 'c:/windows/fonts/msyh.ttc',//字体文件路径
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
        $logo = public_path() . '/image/bg.png';//水印图片
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
        imagettftext($img, 8, 0, $base['border'] + 20, $base['img_height'] - 15, $text_color, $base['font_ulr'], '生成时间：' . $params['table_time'] . '   说明：当前生成为昨天20:00-今天19:59被大爱且未付款人员');
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

    protected function text($value)
    {
        $ex = explode('-', $value['department_name']);
        return [
            'staff_name' => $value['staff_name'],
            'department_name' => count($ex) > 3 ? $this->takeDepartment($ex) : $value['department_name'],
            'billing_at' => $value['billing_at'],
            'rules' => $value['rules']['name'],
            'violate_at' => $value['violate_at'],
            'quantity' => '第' . $value['quantity'] . '次',
            'money' => $value['money']/* . '/' . $value['score']*/,
        ];
    }

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
