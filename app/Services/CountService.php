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
    protected $additionModel;
    protected $calculationsModel;

    public function __construct(Signs $addition, Punish $punishModel, RuleTypes $calculations, Rules $rules, Variables $variable)
    {
        $this->ruleModel = $rules;
        $this->additionModel = $addition;
        $this->variableModel = $variable;
        $this->punishModel = $punishModel;
        $this->calculationsModel = $calculations;
    }

    /**
     * @param $request
     * @return array|float|int
     * 计算扣钱方式   更新
     * $data  包含被大爱人名字,Sn,大爱id
     * $class  是计算类别   有扣钱和扣分两种
     */
    public function generate($arr, $type,$staffInfo)
    {
        $personal = $this->personage($arr['rule_id'], $arr['staff_sn'], $type, $staffInfo);
        return $personal;
//        return $this->publicity($arr['rule_id'], $personal, 4, $staffInfo['shop_sn']);
    }

    /**
     * 分解数据
     *
     * @param $ruleId
     * @param $staffSn
     * @param $type
     * @return array|mixed
     */
    private function personage($ruleId, $staffSn, $type, $staffInfo)
    {
        $calculations = $this->calculationsModel->get();
        $equation = $this->ruleModel->where('id', $ruleId)->value($type);//获取公式.
        if ($equation == '') {
            return ['status' => 'defined', 'msg' => '请自定义数据'];
        }
        $variable = $this->variableModel->get();//系统函数
        $systemArray = explode(',', $equation);
        $repeatedly = $this->operator($calculations, implode($systemArray));
        $SystemVariables = $this->parameters($variable, $repeatedly, $ruleId, $staffSn, $staffInfo);
        $output = $this->variable($SystemVariables);
        if (preg_match('/\A-Za-z/', $output)) {
            abort(500, '公式运算出错：包含非可运算数据');
        }
        return eval('return ' . $output . ';');
    }

    /**
     * 运算符替换
     *
     * @param $calculations
     * @param $v
     * @return null|string|string[]
     */
    protected function operator($calculations, $v)
    {
        return preg_replace_callback('/{<(\d+)>}/', function ($query) use ($calculations, $v) {
            preg_match_all('/{<(\d+)>}/', $v, $operation);
            foreach ($calculations as $calculationsK) {
                if ($calculationsK['id'] == $query[1]) {
                    return $calculationsK['code'];
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
    protected function parameters($variable, $repeatedly, $ruleId, $staffSn, $staffInfo)
    {
        return preg_replace_callback('/{{(\w+)}}/', function ($query) use ($variable, $repeatedly, $ruleId, $staffSn, $staffInfo) {
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
     * 当前制度/本月/次数统计
     *
     * @param $ruleId
     * @param $staffSn
     * @return int
     */
    public function countRuleNum($ruleId, $staffSn)
    {
        $where = [
            'staff_sn' => $staffSn,
            'rule_id' => $ruleId,
            'month' => date('Ym'),
        ];
        return $this->punishModel->where($where)->count() + 1;
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
}
