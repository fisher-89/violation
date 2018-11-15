<?php

namespace App\Repositories;

use App\Models\PointManagementTargets;
use App\Models\PointManagementTargetLogs;
use App\Models\PointManagementTargetHasStaff;
use App\Models\PointManagementTargetLogHasStaff;
use Illuminate\Database\Eloquent\Model;

class PointTargetRepository
{
    protected $targetModel;
    protected $targetLogs;
    protected $logHasStaff;
    protected $hasStaff;

    public function __construct(PointManagementTargets $targets, PointManagementTargetLogs $targetLogs,
                                PointManagementTargetLogHasStaff $logHasStaff, PointManagementTargetHasStaff $hasStaff)
    {
        $this->targetModel = $targets;
        $this->targetLogs = $targetLogs;
        $this->logHasStaff = $logHasStaff;
        $this->hasStaff = $hasStaff;
    }

    /**
     * @return mixed
     * 获取奖扣列表  id 和 name
     */
    public function pointList()
    {
        return $this->targetModel->get(['id', 'name']);
    }

    /**
     * @param $request
     * @return PointManagementTargets|\Illuminate\Database\Eloquent\Builder|Model|null|object
     * 获取单条详细
     */
    public function targetDetails($request)
    {
        return $this->targetModel->with(['nextMonth' => function ($query) {
            $query->whereYear('date', date('Y'))->whereMonth('date', date('m'));
        }])->with('nextMonthStaff')->with(['thisMonthStaff' => function ($quest) {
            $quest->whereYear('date', date('Y'))->whereMonth('date', date('m'));
        }])->where('id', $request->route('id'))->first();
    }

    /**
     * @param $id
     * @return mixed
     * 删除人员
     */
    public function deleteStaff($id)
    {
        return $this->hasStaff->where('target_id', $id)->delete();
    }

    /**
     * @param $id
     * @return mixed
     * 删除任务
     */
    public function deleteTarget($id)
    {
        return $this->targetModel->where('id', $id)->delete();
    }

    /**
     * @param $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * 数据进库
     */
    public function addTargetData($request)
    {
        $this->targetModel->name = $request->name;
        $this->targetModel->point_b_awarding_target = $request->point_b_awarding_target;
        $this->targetModel->point_b_deducting_target = $request->point_b_deducting_target;
        $this->targetModel->event_count_target = $request->event_count_target;
        $this->targetModel->deducting_percentage_target = $request->deducting_percentage_target;
        $this->targetModel->point_b_awarding_coefficient = $request->point_b_awarding_coefficient;
        $this->targetModel->point_b_deducting_coefficient = $request->point_b_deducting_coefficient;
        $this->targetModel->event_count_mission = $request->event_count_mission;
        $this->targetModel->deducting_percentage_ratio = $request->deducting_percentage_ratio;
        $bool = $this->targetModel->save();
        return true == (bool)$bool ? response($this->targetModel, 201) : response('添加失败', 400);
    }

    /**
     * @param $request
     * @return mixed
     * 修改奖扣指标
     */
    public function updateTarget($request)
    {
        $target = $this->targetModel->find($request->route('id'));
        if (null == $target) {
            abort(404, '提供无效的参数');
        }
        $target->update($request->all());
        return $target;
    }

    public function updateStaff($id, $v)
    {
        $sql = [
            'target_id' => $id,
            'staff_sn' => $v['staff_sn'],
            'staff_name' => $v['staff_name']
        ];
        if ($this->hasStaff->create($sql) == false) {
            abort(400, $v['staff_sn'] . '更新出错');
        };
    }

    public function getNextStaff($id)
    {
        return $this->hasStaff->where('target_id', $id)->get();
    }

    public function firstThisTargets($id)
    {
        return $this->targetModel->where('id', $id)->first();
    }
}