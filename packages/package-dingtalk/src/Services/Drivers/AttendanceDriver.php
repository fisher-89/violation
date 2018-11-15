<?php

namespace Fisher\Schedule\Services\Drivers;


class AttendanceDriver extends DriverAbstract
{
	/**
     * @author 28youth
     * Get the provider.
     *
     * @return string
     */
    public function provider(): string
    {
        return 'attendance';
    }

    /**
     * 获取打卡详情.
     *
     * @author 28youth
     * @param array  $userIds
     * @param string $from
     * @param string $to
     * @docs https://open-doc.dingtalk.com/microapp/serverapi2/oek45u
     *
     * @return array
     */
    public function record(array $userIds, string $from, string $to)
    {
        return $this->httpPostJson('attendance/listRecord', [
            'userIds' => $userIds,
            'checkDateFrom' => $from,
            'checkDateTo' => $to,
        ]);
    }

    /**
     * 获取打卡结果.
     *
     * @author 28youth
     * @param string $userId
     * @param string $from
     * @param string $to
     * @docs https://open-doc.dingtalk.com/microapp/serverapi2/kbsdmi
     *
     * @return array
     */
    public function list($userId, string $from, string $to, $offset = 0, $limit = 10)
    {
        if (is_array($userId)) {
            return $this->httpPostJson('attendance/list', [
                'userIdList' => $userId,
                'workDateFrom' => $from,
                'workDateTo' => $to,
                'offset' => $offset,
                'limit' => $limit
            ]);
        }
        
        return $this->httpPostJson('attendance/list', [
            'userId' => $userId,
            'workDateFrom' => $from,
            'workDateTo' => $to
        ]);
    }

    /**
     * 获取请假时长.
     *
     * @author 28youth
     * @param string $userId
     * @param string $from
     * @param string $to
     * @docs https://open-doc.dingtalk.com/microapp/serverapi2/kbsdmi
     *
     * @return array
     */
    public function leavetime(string $userId, string $from, string $to)
    {
        return $this->httpPostJson('topapi/attendance/getleaveapproveduration', [
            'userid' => $userId,
            'from_date' => $from,
            'to_date' => $to,
        ]);
    }

    /**
     * 获取用户考勤组.
     * 
     * @author 28youth
     * @param  string $userId
     * @docs https://open-doc.dingtalk.com/microapp/serverapi2/noz9o3
     * 
     * @return array
     */
    public function userGroup(string $userId)
    {
        return $this->httpPostJson('topapi/attendance/getusergroup', [
            'userid' => $userId
        ]);
    }

    /**
     * 获取考勤排版详情.
     * 
     * @author 28youth
     * @param  string $from
     * @return array
     */
    public function schdule($from)
    {
        return $this->httpPostJson('topapi/attendance/listschedule', [
            'workDate' => $from
        ]);
    }
}
