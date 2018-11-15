<?php 

namespace Fisher\Schedule\Services\Drivers;

class ReportDriver extends DriverAbstract
{
	/**
     * 查询企业员工发出的日志列表.
     *
     * @param int    $startTime
     * @param int    $endTime
     * @param int    $cursor
     * @param int    $size
     * @param string $templateName
     * @param string $userId
     *
     * @return array
     */
    public function list(int $startTime, int $endTime, $cursor = 0, $size = 10, $templateName = null, $userId = null)
    {
        return $this->httpGetMethod('dingtalk.corp.report.list', [
            'start_time' => $startTime,
            'end_time' => $endTime,
            'cursor' => $cursor,
            'size' => $size,
            'template_name' => $templateName,
            'userid' => $userId,
        ]);
    }

    /**
     * 根据用户 ID 获取可见的日志模板列表.
     *
     * @param string $userId
     * @param int    $offset
     * @param int    $size
     *
     * @return array
     */
    public function templates($userId, $offset = null, $size = null)
    {
        return $this->httpGetMethod('dingtalk.oapi.report.template.listbyuserid', [
            'userid' => $userId,
            'offset' => $offset,
            'size' => $size,
        ]);
    }

    /**
     * 查询企业员工的日志未读数.
     *
     * @param string $userId
     *
     * @return array
     */
    public function getUnreadCount($userId = null)
    {
        return $this->httpGetMethod('dingtalk.oapi.report.getunreadcount', [
            'userid' => $userId,
        ]);
    }

}