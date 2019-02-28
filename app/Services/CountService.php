<?php

namespace App\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use App\Models\RuleTypes;
use App\Models\Variables;
use App\Models\Signs;
use App\Models\Punish;
use App\Models\Rules;

class CountService
{
    protected $ruleModel;
    protected $punishModel;
    protected $variableModel;
    protected $signsModel;
    protected $quantity;

    public function __construct(Signs $signs, Punish $punishModel, Rules $rules, Variables $variable)
    {
        $this->ruleModel = $rules;
        $this->signsModel = $signs;
        $this->variableModel = $variable;
        $this->punishModel = $punishModel;
    }

    /**
     * 分解数据
     *
     * @param $ruleId
     * @param $staffSn
     * @param $type
     * @return array|mixed
     */
    public function generate($staff, $arr, $type, $quantity = '')
    {
        $info = [];
        $this->quantity = $quantity;
        $signs = $this->signsModel->get();
        $equation = $this->ruleModel->where('id', $arr['ruleId'])->first();
        $str = $type . '_custom_settings';
        $info['states'] = $equation->$str == '1' ? 1 : 0;
        $info['number'] = $this->countRuleNum($arr);
        $variable = $this->variableModel->get();
        $systemArray = explode(',', $equation->$type);
        $repeatedly = $this->operator($signs, implode($systemArray));
        $SystemVariables = $this->parameters($variable, $repeatedly, $arr, $staff);
        $output = $this->variable($SystemVariables);
        if (preg_match('/\A-Za-z/', $output)) {
            abort(500, '公式运算出错：包含非可运算数据');
        }
        $info['data'] = eval('return ' . $output . ';');
        return $info;
    }

    /**
     * 运算符替换
     *
     * @param $calculations
     * @param $v
     * @return null|string|string[]
     */
    protected function operator($signs, $v)
    {
        return preg_replace_callback('/{<(\d+)>}/', function ($query) use ($signs, $v) {
            preg_match_all('/{<(\d+)>}/', $v, $operation);
            foreach ($signs as $key) {
                if ($key['id'] == $query[1]) {
                    return $key['code'];
                }
            }
        }, $v);
    }

    /**
     * 系统变量替换  执行返回结果
     *
     * @param $variable
     * @param $repeatedly
     * @return null|string|string[]
     */
    protected function parameters($variable, $repeatedly, $arr, $staff)
    {
        return preg_replace_callback('/{{(\w+)}}/', function ($query) use ($variable, $repeatedly, $arr, $staff) {
            preg_match_all('/{{(\w+)}}/', $repeatedly, $operation);
            foreach ($variable as $items) {
                if ($items['key'] === $query[1]) {
                    return eval('return ' . $items['code'] . ';');
                }
            }
        }, $repeatedly);
    }

    /**
     * 基数解析
     *
     * @param $str
     * @return null|string|string[]
     */
    protected function variable($str)
    {
        return preg_replace_callback('/{!(\d+)!}/', function ($query) {
            return $query[1];
        }, $str);
    }

    /**
     * 下面为预定义函数，备数据库使用
     * 当前制度/本月/次数统计
     *
     * @param $ruleId
     * @param $staffSn
     * @return int
     */
    public function countRuleNum($parameter)
    {
        return $this->quantity != '' ? $this->quantity : $this->punishModel->where(['staff_sn' => $parameter['staffSn'],
                'rule_id' => $parameter['ruleId'], 'month' => date('Ym', strtotime($parameter['violateAt']))])->count() + 1;
    }

    public function getBrandValue($staffInfo)
    {
        return $staffInfo['brand_id'] ? $staffInfo['brand_id'] : $staffInfo['brand']['id'];
    }

    public function getPositionValue($staffInfo)
    {
        return $staffInfo['position_id'] ? $staffInfo['position_id'] : $staffInfo['position']['id'];
    }

    public function getDepartmentValue($staffInfo)
    {
        return $staffInfo['department_id'] ? $staffInfo['department_id'] : $staffInfo['department']['id'];
    }

    public function getShopValue($staffInfo)
    {
        return $staffInfo['shop_sn'] ? $staffInfo['shop_sn'] : '';
    }

    public function inArrayData($string, $array)
    {
        return in_array($string, $array) ? '1==1' : '1==2';
    }
}
