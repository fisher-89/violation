<?php

namespace App\Http\Controllers;

use App\Models\CountDepartment;
use Illuminate\Http\Request;
use App\Models\CountStaff;
use App\Models\Punish;

class ImageController extends Controller
{
    protected $punishModel;
    protected $countStaffModel;
    protected $countDepartmentModel;

    public function __construct(Punish $punish, CountStaff $countStaff, CountDepartment $countDepartment)
    {
        $this->punishModel = $punish;
        $this->countStaffModel = $countStaff;
        $this->countDepartmentModel = $countDepartment;
    }

    public function punishImage(Request $request)
    {
        $punish = $this->punishModel->when($request->all() == false, function ($query) {
            $query->whereDate('created_at', date('Y-m-d'));
        })->with('rules')->filterByQueryString()->withPagination($request->get('pagesize', 10));
        if (count($punish) == 0) {
            $punish = $this->punishModel->when($request->all() == false, function ($query) {
                $query->whereDate('created_at', date('Y-m-d', strtotime("-1 day")));
            })->with('rules')->filterByQueryString()->withPagination($request->get('pagesize', 10));
        }
        $text = $punish->all() == true ? $this->text($punish->toArray()) : abort(404, '没有找到默认操作数据');
        $text[] = [];
        $params = [
            'row' => count($text),
            'file_name' => date('Y-m-d') . '大爱记录.png',
            'title' => date('Y-m-d') . '大爱记录',
            'table_time' => date('Y-m-d H:i:s'),
            'data' => $text
        ];
        $base = [
            'border' => 30,//图片外边框
            'file_path' => 'image/',//图片保存路径
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
        $text_coler = imagecolorallocate($img, 51, 51, 51);//设定文字颜色
        $border_coler = imagecolorallocate($img, 204, 204, 204);//设定边框颜色
        $white_coler = imagecolorallocate($img, 30, 80, 162);//设定边框颜色
        imagefill($img, 0, 0, $bg_color);//填充图片背景色
        //先填充一个黑色的大块背景
//        imagefilledrectangle($img, $base['border'], $base['border'] + $base['title_height'],
//            $base['img_width'] - $base['border'], $base['img_height'] - $base['border'], $border_coler);//画矩形
        //再填充一个小两个像素的 背景色区域，形成一个两个像素的外边框         imagefill($img,20,0,imagecolorallocate($img, 30, 80, 162));
        imagefilledrectangle($img, $base['border'], $base['border'] + $base['title_height'],
            $base['img_width'] - $base['border'], $base['border'] + $base['title_height'] + $base['row_height'], $white_coler);//画矩形
        //画表格纵线 及 写入表头文字
        foreach ($base['column_x_arr'] as $key => $x) {
//            imageline($img, $x, $border_top, $x, $border_bottom, $white_coler);//画纵线
            imagettftext($img, $base['header_size'], 0, $x - $base['column_text_offset_arr'][$key] + 1,
                $border_top + $base['row_height'] - 14, $bg_color, $base['font_ulr'], $base['table_header'][$key]);//写入表头文字
        }
        //画表格横线
        foreach ($params['data'] as $key => $item) {
            $border_top += $base['row_height'];
            imageline($img, $base['border'], $border_top, $base['img_width'] - $base['border'], $border_top, $border_coler);
            $sub = 0;
            foreach ($item as $value) {
                imagettftext($img, $base['text_size'], 0, $base['column_x_arr'][$sub] - $base['row_text_offset_arr'][$sub],
                    $border_top + $base['row_height'] - 15/*行高位置*/, $text_coler, $base['font_ulr'], $value);//写入data数据
                $sub++;
            }
        }

        //计算标题写入起始位置
        $title_fout_box = imagettfbbox($base['title_font_size'], 0, $base['font_ulr'], $params['title']);//imagettfbbox() 返回一个含有 8 个单元的数组表示了文本外框的四个角：
        $title_fout_width = $title_fout_box[2] - $title_fout_box[0];//右下角 X 位置 - 左下角 X 位置 为文字宽度
        $title_fout_height = $title_fout_box[1] - $title_fout_box[7];//左下角 Y 位置- 左上角 Y 位置 为文字高度
        //居中写入标题
        imagettftext($img, $base['title_font_size'], 0, ($base['img_width'] - $title_fout_width) / 2, $base['title_height']+10, $text_coler, $base['font_ulr'], $params['title']);
        //写入制表时间
        imagettftext($img, 8, 0, $base['border'], $base['img_height'] - 15, $text_coler, $base['font_ulr'], '生成时间：' . $params['table_time']);
        $save_path = $base['file_path'] . $params['file_name'];
        if (!is_dir($base['file_path']))//判断存储路径是否存在，不存在则创建
        {
            mkdir($base['file_path'], 0777, true);//可创建多级目录
        }
        imagepng($img, $save_path);//输出图片，输出png使用imagepng方法，输出gif使用imagegif方法
    }

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

    public function countStaffImage(Request $request)
    {

    }

    public function countDepartmentImage(Request $request)
    {
        $font = "c:/windows/fonts/msyh.ttc";//字体类型
        $img = imagecreate(620, 15);//创建图片
        imagecolorallocate($img, 0xff, 0xff, 0xff);//背景颜色
        $black = imagecolorallocate($img, 0x00, 0x00, 0x00);//字体颜色
        imagettftext($img, 12, 0, 0, 16, $black, $font, '123456');//将ttf文字写到图片中
        header('Content-Type: image/png');//发送头信息
        imagepng($img, 'image.png', '100');//输出图片
        imagedestroy($img);
    }
}