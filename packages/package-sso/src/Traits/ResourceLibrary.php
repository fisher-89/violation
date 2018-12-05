<?php

namespace Fisher\SSO\Traits;

trait ResourceLibrary
{
    /**
     * 获取员工信息.
     *
     * @param $params 非数组为员工主键
     * @return mixed
     */
    public function getStaff($params)
    {
        if (is_array($params)) {

            return $this->get('api/staff', $params);
        }

        return $this->get('api/staff/' . $params);
    }

    /**
     * 获取部门信息.
     *
     * @author 28youth
     * @param  $params 非数组为部门主键
     * @return mixed
     */
    public function getDepartmenets($params)
    {
        if (is_array($params)) {

            return $this->get('api/departments', $params);
        }

        return $this->get('api/departments/' . $params);
    }

    /**
     * 获取品牌信息.
     *
     * @author 28youth
     * @param  非数组为品牌主键
     * @return mixed
     */
    public function getBrands($params)
    {
        if (is_array($params)) {

            return $this->get('api/brands', $params);
        }

        return $this->get('api/brands/' . $params);
    }

    /**
     * 获取位置信息.
     *
     * @author 28youth
     * @param  非数组为主键
     * @return mixed
     */
    public function getPositions($params)
    {
        if (is_array($params)) {

            return $this->get('api/positions', $params);
        }

        return $this->get('api/positions/' . $params);
    }

    /**
     * 获取商品信息.
     *
     * @author 28youth
     * @param  非数组为商品主键
     * @return mixed
     */
    public function getShops($params)
    {
        if (is_array($params)) {

            return $this->get('api/shops', $params);
        }

        return $this->get('api/shops/' . $params);
    }

    /**
     * 获取用户角色信息.
     *
     * @author 28youth
     * @param  非数组为主键
     * @return mixed
     */
    public function getRoles($params)
    {
        if (is_array($params)) {

            return $this->get('api/roles', $params);
        }

        return $this->get('api/roles/' . $params);
    }

    /**
     * 获取钉钉access_token.
     *
     * @author 28youth
     * @return access_token
     */
    public function getAccessToken()
    {
        return $this->get('api/get_dingtalk_access_token');
    }

    /**
     * @param $params
     * @return mixed
     */
    public function getPoints($params)
    {
        return $this->get('/admin/point-log');
    }

    /**
     * 积分制添加数据
     *
     * @param $arr
     * @return mixed
     */
    public function postPoints($arr)
    {
        return $this->post('/admin/point-log', $arr, [], 1);
    }

    /**
     * 积分制删除数据
     *
     * @param $id
     * @return mixed
     */
    public function deletePoints($id)
    {
        return $this->delete('/admin/point-log/' . $id, [], [], 1);
    }

    /**
     * 获取部门包含所有上级
     *
     * @param $params
     * @return mixed
     */
    public function getDepartmenetAll($params)
    {
        if (is_array($params)) {

            return $this->get('api/department/get_tree', $params);
        }
        return $this->get('api/department/get_tree/' . $params);
    }

    public function pushingDing($arr)
    {
        return $this->postDing('/chat/send', $arr, [], 2);
    }

    public function pushingDingImage($data)
    {
        return $this->postDingImage('/media/upload', $data, [], 2);
    }
}