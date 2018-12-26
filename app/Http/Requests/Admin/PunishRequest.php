<?php
namespace App\Http\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;

class PunishRequest extends FormRequest
{
    /**
     * @return bool
     * 授权
     */
    public function authorize()
    {
        return $this->data();
    }

    /**
     * @return array
     * 表单验证规则
     */
    public function rules()
    {
        return [
            'rule_id'=>'required|numeric',//制度表I
            'criminal_sn'=>'required|numeric',//被大爱者编号
            'criminal_name'=>'required',//被大爱者名字
            'criminal_brand_id'=>'required|integer',//被大爱者品牌id
            'criminal_brand'=>'required',//被大爱者品牌
            'criminal_department_id'=>'required|numeric',//被大爱者部门id
            'criminal_department'=>'required',//被大爱者部门
            'criminal_position_id'=>'required|numeric',//被大爱者职位id
            'criminal_shop_sn'=>'alpha_num',//被大爱者店铺代码  含有字母
            'order_form_date'=>'required|date|after:start_date',//开单时间
            'criminal_position'=>'required',//被大爱者职位
            'punisher_sn'=>'required|numeric',//开单人编号
            'punisher_name'=>'required',
            'disciplined_at'=>'required|date|after:start_date',//违纪日期
            'price'=>'required|numeric',//大爱金额
            'has_paid'=>'required|boolean'
        ];
    }

    /**
     * 获取被定义验证规则的错误消息
     *
     * @return array
     * @translator laravelacademy.org
     */
    public function attributes(){
        return [
            'ruleId'=>'违反的制度',//制度表ID
            'criminalSn'=>'被大爱编号',//被大爱者编号
            'criminalName'=>'被大爱者名字',//被大爱者名字
            'criminalBrandId'=>'被大爱者品牌id',//被大爱者品牌id
            'criminalBrand'=>'被大爱者品牌',//被大爱者品牌
            'criminalDepartmentId'=>'被大爱者部门id',//被大爱者部门id
            'criminalDepartment'=>'被大爱者部门',//被大爱者部门
            'criminalPositionId'=>'被大爱者职位id',//被大爱者职位id
            'criminalShopSn'=>'被大爱者店铺代码',//被大爱者店铺代码  含有字母
            'criminalPosition'=>'被大爱者职位',
            'punisherSn'=>'开单人编号',//开单人编号
            'punisherName'=>'开单人名字',
            'disciplinedAt'=>'违纪时间',//违纪日期
            'price'=>'大爱金额',//大爱金额
            'hasPaid'=>'支付状态',
            'orderFormDate'=>'开单时间'
        ];
    }

    public function messages()
    {
        return [

        ];
    }

    protected function data(){
        return true;
    }
}