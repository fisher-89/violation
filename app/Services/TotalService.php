<?php

namespace App\Services;


use App\Models\CountDepartment;
use App\Models\CountHasPunish;
use App\Models\CountStaff;
use App\Models\Punish;

class TotalService
{
    protected $punishModel;
    protected $countStaffModel;
    protected $countHasPunishModel;
    protected $countDepartmentModel;

    public function __construct(Punish $punish, CountDepartment $countDepartment, CountStaff $countStaff,CountHasPunish $countHasPunish)
    {
        $this->punishModel = $punish;
        $this->countStaffModel = $countStaff;
        $this->countHasPunishModel = $countHasPunish;
        $this->countDepartmentModel = $countDepartment;
    }

    public function getStaff($request)
    {
        $this->countInfo();
//        return $this->countStaffModel->with('countHasPunish.punish')->filterByQueryString()->SortByQueryString()->withPagination($request->get('pagesize', 10));
    }

    public function getDepartment($request)
    {

    }

    protected function countInfo()
    {
        $info = $this->punishModel->where(['month' => date('Ym')])->get();
        $infoArray = $info !== null ? $info->toArray() : [];
        foreach ($infoArray as $key => $value) {
            $money = 0;
            $score = 0;
            foreach ($infoArray as $k => $v) {
                if ($value['staff_sn'] == $v['staff_sn']) {
                    $money = $money + $v['money'];
                    $score = $score + $v['score'];
                }
            }
            $date = $this->countDepartmentModel->where(['month' => date('Ym'), 'full_name' => $value['department_name']])->first();
            if ($date == null) {
                $ex = explode('-', $value['department_name']);
                $num = count($ex);
                $departmentSql = [
                    'department_name' => $ex[$num - 1],
                    'parent_id' => isset($id) ? $id : null,
                    'full_name' => $value['department_name'],
                    'month' => date('Ym'),
                    'money' => isset($money) ? $money : 0,
                    'score' => isset($score) ? $score : 0
                ];
                $id = $this->countDepartmentModel->insertGetId($departmentSql);
            } else {
                $departmentSql = [
                    'money' => $date->money + $money,
                    'score' => $date->score + $score
                ];
                $date->update($departmentSql);
                $id = $date->id;
            }
            $staffDate = $this->countStaffModel->where(['month'=>date('Ym'),'staff_sn'=>$value['staff_sn']])->first();
            if($staffDate == null){
                $countSql = [
                    'department_id' => $id,
                    'staff_sn' => $value['staff_sn'],
                    'staff_name' => $value['staff_name'],
                    'month' => date('Ym'),
                    'money' => $money,
                    'score' => $score
                ];
                $this->countStaffModel->insert($countSql);
                $hasSql = [
                    'count_id'=>$id,
                    'punish_id'=>$value['id']
                ];
                $this->countHasPunishModel->insert($hasSql);
            }else{
                $countSql = [
                    'money' => $money,
                    'score' => $score
                ];
                $staffDate->update($countSql);
            }
        }
    }
}