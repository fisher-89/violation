<?php

namespace App\Repositories;

use App\Models\AuthorityGroup;
use App\Models\TaskPublishingAuthorities;

class TaskAuthorityRepositories
{
//    use Traits\Filterable;
    protected $taskModel;
    protected $authorityModel;

    public function __construct(TaskPublishingAuthorities $authorityTaskPublish, AuthorityGroup $group)
    {
        $this->taskModel = $authorityTaskPublish;
        $this->authorityModel = $group;
    }

    public function getTaskList($request)
    {
        $authorityGroups = $this->authorityModel->get()->mapWithKeys(function ($group) {
            return [$group->id => $group];
        });
        $response = [];
        $this->taskModel->get()->each(function ($item) use (&$response, $authorityGroups) {
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
        return $this->taskModel->where('admin_sn', $admin_sn)->delete();
    }

    public function addTaskData($groupId, $adminSn, $adminName)
    {
        $sql = [
            'group_id' => $groupId,
            'admin_sn' => $adminSn,
            'admin_name' => $adminName
        ];
        $save = $this->taskModel->create($sql);
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
        $this->taskModel->where('admin_sn', $adminSn)->get()->each(function ($item) use (&$response, $authorityGroups) {
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
        return $this->taskModel->where('admin_sn', $adminSn)->first();
    }

    public function deleteTaskData($adminSn)
    {
        return $this->taskModel::where('admin_sn', $adminSn)->delete();
    }
}