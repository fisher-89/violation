<?php 

namespace Fisher\Schedule\Services\Drivers;

class ProcessDriver extends DriverAbstract
{

    /**
     * @author 28youth
     * Get the provider.
     *
     * @return string
     */
    public function provider(): string
    {
        return 'process';
    }

	/**
     * 获取审批实例列表.
     *
     * @author 28youth
     * @param string $processCode  流程模板唯一标识 可在oa后台编辑审批表单部分查询
     * @param int    $startTime    时间戳 可以传秒或者毫秒
     * @param int    $endTime      时间戳 可以传秒或者毫秒
     * @param array  $useridList   数组形式的用户列表
     * @param int    $cursor
     * @param int    $size
     * @docs https://open-doc.dingtalk.com/docs/api.htm?spm=a219a.7386797.0.0.2pIQa7&source=search&apiId=29833 
     * 
     * @return array|\GuzzleHttp\Psr7\Response
     */
    public function list(string $processCode, int $startTime, int $endTime = 0, array $useridList = [], $cursor = 0, $size = 10)
    {
        $startTime = strlen($startTime) === 10 ? $startTime.'000' : $startTime;
        $endTime = strlen($endTime) === 10 ? $endTime.'000' : $endTime;
        $useridList = implode(',', $useridList);
        $params = [
            'process_code' => $processCode,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'userid_list' => $useridList,
            'size' => $size,
        ];
        $params = array_filter($params);
        $params['cursor'] = $cursor;

        return $this->httpGetMethod('dingtalk.smartwork.bpms.processinstance.list', $params);
    }

    /**
     * 获取单个审批实例.
     *
     * @author 28youth
     * @param  string $processInstanceID 审批实例id
     * @docs https://open-doc.dingtalk.com/microapp/serverapi2/xgqkvx
     * 
     * @return array
     */
    public function show(string $processInstanceID)
    {
        return $this->httpPostJson('topapi/processinstance/get', [
            'process_instance_id' => $processInstanceID
        ]);
    }
}