<?php 

namespace Fisher\Schedule\Services\Drivers;

/**
 * 部门相关接口.
 * 
 * @docs https://open-doc.dingtalk.com/microapp/serverapi2/dubakq
 */
class DepartmentDriver extends DriverAbstract
{
    /**
     * 获取部门列表.
     *
     * @author 28youth
     * @param int $id 父部门id
     * @return array
     */
    public function list(int $id = 0)
    {
        if ($id === 0) {
            return $this->httpGet('department/list');
        }

        return $this->httpGet('department/list', compact('id'));
    }

    /**
     * 获取部门详情.
     *
     * @author 28youth
     * @param int $id 部门id
     * @return array
     */
    public function get(int $id)
    {
        return $this->httpGet('department/get', compact('id'));
    }

    /**
     * 创建部门.
     *
     * @author 28youth
     * @param array $data
     * @return array
     */
    public function create(array $data)
    {
        return $this->httpPostJson('department/create', $data);
    }

    /**
     * 更新部门.
     *
     * @author 28youth
     * @param array $data
     * @return array
     */
    public function update(array $data)
    {
        return $this->httpPostJson('department/update', $data);
    }

    /**
     * 删除部门.
     *
     * @author 28youth
     * @param int $id
     * @return array
     */
    public function delete(int $id)
    {
        return $this->httpGet('department/delete', compact('id'));
    }

    /**
     * 查询部门的所有上级父部门路径.
     *
     * @author 28youth
     * @param int $id 希望查询的部门的id，包含查询的部门本身
     * @return array
     */
    public function parent(int $id)
    {
        return $this->httpGet('department/list_parent_depts_by_dept', compact('id'));
    }

    /**
     * 查询指定用户的所有上级父部门路径.
     *
     * @author 28youth
     * @param string $userId 希望查询的用户的id
     * @return array
     */
    public function userParent(string $userId)
    {
        return $this->httpGet('department/list_parent_depts', compact('userId'));
    }
	
}