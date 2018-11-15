<?php

namespace App\Repositories;

use App\Models\AuthorityGroup;
use App\Models\StatisticCheckingAuthorities;
use App\Models\TaskPublishingAuthorities;

class StatisticRepository
{
    protected $statisticModel;
    protected $authorityModel;

    public function __construct(StatisticCheckingAuthorities $statisticCheckingAuthorities, AuthorityGroup $authorityGroup)
    {
        $this->statisticModel = $statisticCheckingAuthorities;
        $this->authorityModel = $authorityGroup;
    }

    public function getTaskList($request)
    {
        $authorityGroups = $this->authorityModel->get()->mapWithKeys(function ($group) {
            return [$group->id => $group];
        });
        $response = [];
        $this->statisticModel->get()->each(function ($item) use (&$response, $authorityGroups) {
            $staffSn = $item->admin_sn;
            if (!empty($response[$staffSn])) {
                array_push($response[$staffSn]['groups'], $authorityGroups[$item->group_id]);
            } else {
                $response[$staffSn] = [
//                    'id'=>$item->id,
                    'admin_sn' => $staffSn,
                    'admin_name' => $item->admin_name,
                    'groups' => [
                        $authorityGroups[$item->group_id],
                    ]
                ];
            }
        });
        return array_values($response);
    }

    public function deleteStaff($admin_sn)
    {
        return $this->statisticModel->where('admin_sn', $admin_sn)->delete();
    }

    public function addTaskData($groupId, $adminSn, $adminName)
    {
        $sql = [
            'group_id' => $groupId,
            'admin_sn' => $adminSn,
            'admin_name' => $adminName
        ];
        $save = $this->statisticModel->create($sql);
        return $save;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function selectTask($id)
    {
        return $this->authorityModel->where('id', $id)->first();
    }

    public function getTaskFirst($adminSn)
    {
        $authorityGroups = $this->authorityModel->get()->mapWithKeys(function ($group) {
            return [$group->id => $group];
        });
        $response = [];
        $this->statisticModel->where('admin_sn', $adminSn)->get()->each(function ($item) use (&$response, $authorityGroups) {
            $staffSn = $item->admin_sn;
            if (!empty($response[$staffSn])) {
                array_push($response[$staffSn]['groups'], $authorityGroups[$item->group_id]);
            } else {
                $response[$staffSn] = [
//                    'id'=>$item->id,
                    'admin_sn' => $staffSn,
                    'admin_name' => $item->admin_name,
                    'groups' => [
                        $authorityGroups[$item->group_id],
                    ]
                ];
            }
        });
        foreach (array_values($response) as $items) {
            return $items;
        }
    }

    public function getTask($adminSn)
    {
        return $this->statisticModel->where('admin_sn', $adminSn)->first();
    }

    public function deleteTaskData($adminSn)
    {
        return $this->statisticModel::where('admin_sn', $adminSn)->delete();
    }
}