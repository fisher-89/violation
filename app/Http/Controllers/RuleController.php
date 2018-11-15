<?php

namespace App\Http\Controllers;

use App\Models\RuleTypes;
use App\Models\Variables;
use App\Services\CollocateService;
use App\Services\RuleService;
use Illuminate\Http\Request;
use App\Models\Punish;
use Illuminate\Validation\Rule;

class RuleController extends Controller
{
    protected $punishModel;
    protected $variableModel;
    protected $ruleService;
    protected $calculationModel;
    protected $collocateService;

    public function __construct(RuleService $ruleService, CollocateService $collocateService, Punish $punish, RuleTypes $calculations, Variables $variable)
    {
        $this->punishModel = $punish;
        $this->variableModel = $variable;
        $this->ruleService = $ruleService;
        $this->calculationModel = $calculations;
        $this->collocateService = $collocateService;
    }

    /**
     * 列表
     *
     * @param Request $request
     * @return mixed
     */
    public function getList(Request $request)    //查询配置
    {//todo 权限
        return $this->ruleService->seeAbout($request);
    }

    /**
     * 添加
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function store(Request $request)   //写入配置
    {//todo 权限
        $this->verify($request);
        return $this->ruleService->readIn($request);
    }

    /**
     * 编辑
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function edit(Request $request)     //修改配置
    {//todo 权限
        if ($this->punishModel->where('rule_id', $request->route('id'))->first() == true) {
            abort(400, '当前制度被使用，不能修改');
        }
        $this->verify($request);
        return $this->ruleService->editRule($request);
    }

    /**
     * 删除
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     *
     */
    public function delete(Request $request)        //删除配置
    {//todo 权限
        if ($this->punishModel->where('rule_id', $request->route('id'))->first() == true) {
            abort(400, '当前制度被使用，不能删除');
        }
        return $this->ruleService->remove($request);
    }

    /**
     * 获取单条
     *
     * @param Request $request
     * @return mixed
     */
    public function getFirst(Request $request)    //单条记录
    {
        //todo 权限
        return $this->ruleService->onlyRecord($request);
    }

    public function configuration()
    {
        return $this->collocateService->configuration();//公式从数据库拿取数据

    }

    public function calculations()
    {
        return $this->ruleService->getCalculations();
    }

    /**
     * 添加验证
     * 运算规则组成：运算符:{< d+ >}+系统函数{{w+}} 例如：次数(自动求当前员工在此条记录的次数)+数字  {!20!}:基数
     * @param $request
     */
    protected function verify($request)
    {
        $id = $request->route('id');
        $this->validate($request, [
            'type_id' => 'required|numeric|exists:rule_types,id',
            'name' => ['required', 'max:10', $id === null ? 'unique:rules,name' : Rule::unique('rules', 'name')->whereNotIn('id', explode(' ', $id))],
            'description' => 'max:300',
            'money' => ['required', function ($attribute, $value, $event) {
                $variable = $this->variableModel->get();
                $calculation = $this->calculationModel->get();
                $variable = $variable == null ? [] : $variable->toArray();//系统变量
                $calculation = $calculation == null ? [] : $calculation->toArray();//运算符
                $base = preg_match_all('/{!(\d+)!}/', $value);
                if ($base == false) {
                    return $event('扣钱公式缺少基数');
                }elseif ($base > 1){
                    return $event('扣钱公式基数必须是唯一的');
                }
                preg_match_all('/{{(\w+)}}/', $value, $func);
                foreach ($func[1] as $key => $value) {
                    $str = in_array($value, array_column($variable, 'key'));
                    if ($str == false) {
                        return $event('找到非系统函数:' . $func[1][$key]);
                    }
                }
                preg_match_all('/{<(\w+)>}/', $value, $operator);
                foreach ($operator[1] as $k => $val) {
                    if (in_array($val, array_column($calculation, 'id')) == false) {
                        return $event('找到非系统运算符:' . $operator[1][$k]);
                    }
                }
            }],
            'score' => ['required', function ($attribute, $value, $event) {
                $variable = $this->variableModel->get();
                $calculation = $this->calculationModel->get();
                $variable = $variable == null ? [] : $variable->toArray();//系统变量
                $calculation = $calculation == null ? [] : $calculation->toArray();//运算符
                $subtraction = preg_match_all('/{!(\d+)!}/', $value);
                if ($subtraction == false) {
                    return $event('扣分公式缺少基数');
                }elseif ($subtraction > 1){
                    return $event('扣钱公式基数必须是唯一的');
                }
                preg_match_all('/{{(\w+)}}/', $value, $func);
                foreach ($func[1] as $i => $item) {
                    $str = in_array($item, array_column($variable, 'key'));
                    if ($str == false) {
                        return $event('找到非系统函数:' . $func[1][$i]);
                    }
                }
                preg_match_all('/{<(\w+)>}/', $value, $operator);
                foreach ($operator[1] as $it => $items) {
                    if (in_array($items, array_column($calculation, 'id')) == false) {
                        return $event('找到非系统运算符:' . $operator[1][$it]);
                    }
                }
            }],
            'sort' => 'numeric|max:32767',
        ], [], [
            'type_id'=>'分类ID',
            'name' => '名称',
            'description' => '描述',
            'money' => '扣钱公式',
            'score' => '扣分公式',
            'sort' => '排序',
        ]);
    }

    public function getTypeList(Request $request)
    {
        return $this->ruleService->getTypes($request);
    }

    public function storeType(Request $request)
    {
        $this->ruleTypeVerify($request);
        return $this->ruleService->storeType($request);
    }

    public function editType(Request $request)
    {
        $this->ruleTypeVerify($request);
        return $this->ruleService->editType($request);
    }

    public function delType(Request $request)
    {
        return $this->ruleService->deleteRuleType($request);
    }

    protected function ruleTypeVerify($request)
    {
        $id = $request->route('id');
        $this->validate($request,[
            'name'=>[$id == false ? 'unique:rule_types,name' : Rule::unique('rule_types','name')
                ->whereNotIn('id',explode(' ', $id)) ,'required','max:10'],
            'district'=>'required|numeric|boolean',
            'sort'=>'numeric|max:32767',
            ],[],[
            'name'=>'名字',
            'district'=>'实用范围',
            'sort'=>'排序',
            ]);
    }
}