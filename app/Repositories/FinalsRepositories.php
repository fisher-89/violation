<?php

namespace App\Repositories;

use App\Models\FinalApprover;
use Illuminate\Database\Eloquent\Model;

class FinalsRepositories
{
    use Traits\Filterable;
    protected $finalModel;

    public function __construct(FinalApprover $final)
    {
        $this->finalModel = $final;
    }

    /**
     * @param $request
     * @return array
     */
    public function getFinalsAll()
    {
        return $this->finalModel->filterByQueryString()->withPagination();
    }

    public function repetition($staff_sn)
    {
        return $this->finalModel->where('staff_sn', $staff_sn)->first();
    }

    public function delRepetition($staff_sn)
    {
        return $this->finalModel->withTrashed()->where('staff_sn', $staff_sn)->value('id');
    }

    public function addFinals($request)
    {
        $this->finalModel->staff_sn = $request->staff_sn;
        $this->finalModel->staff_name = $request->staff_name;
        $this->finalModel->point_a_awarding_limit = $request->point_a_awarding_limit;
        $this->finalModel->point_a_deducting_limit = $request->point_a_deducting_limit;
        $this->finalModel->point_b_awarding_limit = $request->point_b_awarding_limit;
        $this->finalModel->point_b_deducting_limit = $request->point_b_deducting_limit;
        return $this->finalModel->save() ? $this->finalModel->id : false;
    }

    public function getFinals($bool)
    {
        return $this->finalModel->find($bool);
    }

    public function updateFinals($request)
    {
        $finals = $this->finalModel->find($request->route('id'));
        if ((bool)$finals == false) {
            abort(404, '提供无效参数');
        }
        $finals->update($request->all());
        return $finals;
    }

    public function deleteFinalsData($id)
    {
        $fin = $this->finalModel->find($id);
        return $fin->delete();
    }

    /**
     * @param $id
     * @return bool|null
     * 恢复软删除
     */
    public function restoreDeleteFinals($id)
    {
        return $this->finalModel->where('id', $id)->restore();
    }

    public function deleteUpdateRestore($id, $request)
    {
        $finals = $this->finalModel->find($id);
        if ((bool)$finals == false) {
            abort(500, '恢复数据失败');
        }
        $finals->update($request->all());
        return $finals;
    }
}