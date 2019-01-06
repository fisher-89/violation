<?php

namespace App\Services;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Models\RuleTypes;
use App\Models\Variables;
use App\Models\Signs;
use App\Models\Punish;
use App\Models\Rules;

class RuleService
{
    protected $signsModel;
    protected $rulesModel;
    protected $punishModel;
    protected $ruleTypeModel;
    protected $collocateService;

    public function __construct(CollocateService $collocateService, Signs $signs, Punish $punish, Rules $rules, RuleTypes $ruleTypes)
    {
        $this->signsModel = $signs;
        $this->rulesModel = $rules;
        $this->punishModel = $punish;
        $this->ruleTypeModel = $ruleTypes;
        $this->collocateService = $collocateService;
    }

    public function seeAbout($request)  //读取配置->SortByQueryString()
    {
        return $this->rulesModel->with('ruleTypes')->filterByQueryString()->withPagination($request->get('pagesize', 10));
    }

    public function readIn($request)   //写入配置
    {
        $all = $request->all();
        $all['creator_sn'] = Auth::user()->staff_sn;
        $all['creator_name'] = Auth::user()->realname;
        $rule = $this->rulesModel->create($all);
        return response($rule, 201);
    }

    public function editRule($request)  //修改配置
    {
        $id = $request->route('id');
        $rule = $this->rulesModel->find($id);
        if (false === (bool)$rule) {
            abort(404, '不存在的数据');
        }
        $all = $request->all();
        $all['editor_sn'] = Auth::user()->staff_sn;
        $all['editor_name'] = Auth::user()->realname;
        $rule->update($all);
        return response($rule, 201);
    }

    public function remove($request)   //删除配置
    {
        $id = $request->route('id');
        $rule = $this->rulesModel->find($id);
        if ($rule == null) {
            abort(404, '不存在的数据');
        }
        $rule->delete();
        return response('', 204);
    }

    public function onlyRecord($request)      //一条详细记录
    {
        $id = $request->route('id');
        return $this->rulesModel->find($id);
    }

    public function getCalculations()
    {
        return $this->signsModel->get();
    }

    public function getTypes($request)//->SortByQueryString()
    {
        return $this->ruleTypeModel->filterByQueryString()->withPagination($request->get('pagesize', 10));
    }

    public function storeType($request)
    {
        $type = $this->ruleTypeModel->create($request->all());
        return response($type, 201);
    }

    public function editType($request)
    {
        $types = $this->ruleTypeModel->find($request->route('id'));
        if($types == false){
            abort(404,'未找到数据');
        }
        $types->update($request->all());
        return response($types,201);
    }

    public function deleteRuleType($request)
    {
        $typeData = $this->ruleTypeModel->find($request->route('id'));
        if($typeData == false){
            abort(404,'未找到数据');
        }
        $typeData->delete($typeData);
        return response('',204);
    }
}