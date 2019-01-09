<?php

namespace App\Http\Controllers;

use App\Models\Signs;
use App\Models\Punish;
use App\Models\Variables;
use Illuminate\Http\Request;
use App\Services\RuleService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Services\CollocateService;


class RuleController extends Controller
{
    protected $signsModel;
    protected $ruleService;
    protected $punishModel;
    protected $variableModel;
    protected $collocateService;

    public function __construct(RuleService $ruleService, CollocateService $collocateService, Punish $punish, Signs $signs, Variables $variable)
    {
        $this->signsModel = $signs;
        $this->punishModel = $punish;
        $this->variableModel = $variable;
        $this->ruleService = $ruleService;
        $this->collocateService = $collocateService;
    }

    /**
     * 列表
     *
     * @param Request $request
     * @return mixed
     */
    public function getList(Request $request)    //查询配置
    {
        $this->authority($request->user()->authorities['oa'],198);
        return $this->ruleService->seeAbout($request);
    }

    /**
     * 添加
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function store(Request $request)   //写入配置
    {
        $this->authority($request->user()->authorities['oa'],206);
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
    {
        $this->authority($request->user()->authorities['oa'],207);
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
    {
        $this->authority($request->user()->authorities['oa'],208);
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
        $this->authority($request->user()->authorities['oa'],198);
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
        $variable = $this->variableModel->get();
        $calculation = $this->signsModel->get();
        $variable = $variable == null ? [] : $variable->toArray();//系统变量
        $calculation = $calculation == null ? [] : $calculation->toArray();//运算符
        $this->validate($request, [
            'type_id' => 'required|numeric|exists:rule_types,id',
            'name' => ['required', 'max:20', $id === null ? 'unique:rules,name' : Rule::unique('rules', 'name')->whereNotIn('id', explode(' ', $id))],
            'description' => 'max:300',
            'money' => ['required', function ($attribute, $value, $event)use($variable ,$calculation){
                $base = preg_match_all('/(\d+)/', $value);
                if ($base == false) {
                    return $event('缺少基础数值');
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
            'score' => ['required', function ($attribute, $value, $event)use($variable ,$calculation) {
                $subtraction = preg_match_all('/(\d+)/', $value);
                if ($subtraction == false) {
                    return $event('缺少基础数值');
                }
                preg_match_all('/{{(\w+)}}/', $value, $func);
                foreach ($func[1] as $i => $item) {
                    $str = in_array($item, array_column($variable, 'key'));
                    if ($str == false) {
                        return $event('找到非系统函数:' . $func[1][$i]);
                    }
                }
                preg_match_all('/{<(\w+)>}/', $value, $operator);
                foreach ($operator[1] as $k => $val) {
                    if (in_array($val, array_column($calculation, 'id')) == false) {
                        return $event('找到非系统运算符:' . $operator[1][$k]);
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
        $this->authority($request->user()->authorities['oa'],209);
        return $this->ruleService->getTypes($request);
    }

    public function storeType(Request $request)
    {
        $this->authority($request->user()->authorities['oa'],209);
        $this->ruleTypeVerify($request);
        return $this->ruleService->storeType($request);
    }

    public function editType(Request $request)
    {
        $this->authority($request->user()->authorities['oa'],209);
        $this->ruleTypeVerify($request);
        return $this->ruleService->editType($request);
    }

    public function delType(Request $request)
    {
        $this->authority($request->user()->authorities['oa'],209);
        if(DB::table('rules')->where('type_id',$request->route('id'))->first() == true){
            abort(400,'当前分类被使用，无法删除');
        };
        return $this->ruleService->deleteRuleType($request);
    }

    protected function ruleTypeVerify($request)
    {
        $id = $request->route('id');
        $this->validate($request,[
            'name'=>[$id == false ? 'unique:rule_types,name' : Rule::unique('rule_types','name')
                ->whereNotIn('id',explode(' ', $id)) ,'required','max:10'],
            ],[],[
            'name'=>'名字',
            ]);
    }

    protected function authority($oa,$code)
    {
        if (!in_array($code, $oa)) {
            abort(401, '你没有权限操作');
        }
    }
}