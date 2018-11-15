<?php

declare(strict_types=1);

namespace Fisher\Schedule\API\Controllers;

use Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Fisher\Schedule\Services\DingtalkManager;
use Fisher\Schedule\Services\Contracts\Dingtalk;
use Fisher\Schedule\Models\AttendanceRecord;
use App\Models\CommonConfig;
use App\Models\PointLog as PointLogModel;
use App\Models\AuthorityGroupHasStaff as GroupStaff;

class ScheduleController
{

    /**
     * dingtalk manager instance.
     * 
     * @var \Fisher\Schedule\Services\DingtalkManager
     */
    protected $dingtalk;

    /**
     * Provider maps.
     *
     * @var array
     */
    protected $providerMap = [
        'user' => 'User',
        'auth' => 'Auth',
        'attendance' => 'Attendance'
    ];

    public function __construct(DingtalkManager $manager)
    {
        $this->dingtalk = $manager;
    }

    public function index($page = 1)
    {
        $start = now()->subDay(1)->startOfDay()->toDateTimeString();
        $end = now()->subDay(1)->endOfDay()->toDateTimeString();
        $users = $this->getUsers($page, 5);
        $dingdingIds = array_column((array)$users, 'dingtalk_number');
        if (!$dingdingIds) {
            return false;
        }
        $response = $this->provider('attendance')->list($dingdingIds, $start, $end);
        if ($response['errcode'] === 0) {
            if (!$response['recordresult']) {
                return response()->json(['message' => '没有考勤记录了~']);
            }
            $tmp = [];
            foreach ($response['recordresult'] as $key => $val) {
                $uid = $val['userId'];
                
                if (!isset($tmp[$uid])) {
                    $tmp[$uid] = $this->makeUserGroup($uid, $val['groupId']);
                }
                // 基准打卡时间
                $baseTime = Carbon::createFromTimestamp($val['baseCheckTime']/1000);
                //  实际打卡时间 
                $checkTime = Carbon::createFromTimestamp($val['userCheckTime']/1000);
                //格式化休息时间为考勤日期
                $toRestStime = Carbon::create($baseTime->year, $baseTime->month,$baseTime->day,
                    $tmp[$uid]['restStime']->hour, 
                    $tmp[$uid]['restStime']->minute, 
                    $tmp[$uid]['restStime']->second
                );
                $toRestEtime = Carbon::create($baseTime->year, $baseTime->month, $baseTime->day,
                    $tmp[$uid]['restEtime']->hour, 
                    $tmp[$uid]['restEtime']->minute, 
                    $tmp[$uid]['restEtime']->second
                );
                $tmp[$uid]['restBeginTime'] = $toRestStime;
                $tmp[$uid]['restEndTime'] = $toRestEtime;
                $tmp[$uid]['workDate'] = Carbon::createFromTimestamp($val['workDate']/1000)->toDateTimeString();
                if ($val['checkType'] === 'OnDuty') {
                    $tmp[$uid]['userOnTime'] = $checkTime;
                    $tmp[$uid]['baseOnTime'] = $baseTime;

                    switch ($val['timeResult']) {
                        case 'Early':
                            $tmp[$uid]['earlytime'] += ceil(($toRestStime->timestamp - $checkTime->timestamp)/60);
                            break;
                        case 'Late':
                            $tmp[$uid]['latetime'] += ceil(($checkTime->timestamp - $baseTime->timestamp)/60);
                            break;
                        case 'NotSigned':
                            // 忘记打卡不计入工作时间
                            // $tmp[$uid]['worktime'] -= ceil(($toRestStime->timestamp - $baseTime->timestamp)/60);
                            $tmp[$uid]['userOnTime'] = null;
                            break;
                    }
                } elseif ($val['checkType'] === 'OffDuty') {
                    $tmp[$uid]['userOffTime'] = $checkTime;
                    $tmp[$uid]['baseOffTime'] = $baseTime;
                    
                    switch ($val['timeResult']) {
                        case 'Early':
                            $tmp[$uid]['earlytime'] += ceil(($baseTime->timestamp - $checkTime->timestamp)/60);
                            break;
                        case 'Late':
                            $tmp[$uid]['latetime'] += ceil(($checkTime->timestamp - $toRestEtime->timestamp)/60);
                            break;
                        case 'NotSigned':
                            // 忘记打卡不计入工作时间
                            // $tmp[$uid]['worktime'] -= ceil(($baseTime->timestamp - $toRestEtime->timestamp)/60);
                            $tmp[$uid]['userOffTime'] = null;
                            break;
                        case 'Normal':
                            // 计算加班时间
                             $tmp[$uid]['overtime'] += ceil(($checkTime->timestamp - $baseTime->timestamp)/60);
                            break;
                    }
                }
                // 判断是否有审批记录
                if (isset($val['procInstId'])) {
                    // 获取请假时间
                    $process = $this->provider('process')->show($val['procInstId']);
                    $leave = json_decode($process['process_instance']['form_component_values'][0]['value']);
                    if ($leave[2] < 8) {
                        $tmp[$uid]['leavetime'] += ($leave[2] * 60);
                        $tmp[$uid]['worktime'] -= $tmp[$uid]['leavetime'];
                    } else {
                        $tmp[$uid]['userOnTime'] = null;
                        $tmp[$uid]['userOffTime'] = null;
                    }
                }
                if (empty($tmp[$uid]['userOnTime']) && empty($tmp[$uid]['userOffTime'])) 
                {
                    $tmp[$uid]['worktime'] = 0;
                }
            }
            array_walk($tmp, [$this, 'storeRecord']);

            $page++;
            $this->index($page);
        } else {
            return response()->json(['message' => $response['errmsg']], $response['errcode']);
        }
    }

    /**
     * 存储考勤记录.
     * 
     * @author 28youth
     * @param  array $val
     * @param  string $userId
     */
    public function storeRecord($val, $userId)
    {
        $model = AttendanceRecord::byUserId($userId)
            ->where('workDate', $val['workDate'])
            ->first();

        if ($model === null) {
            $model = new AttendanceRecord;
        }
        $model->fill($val);
        $model->userId = $userId;
        $model->save();

        // 考勤转积分
        $this->transformAttendance($model);
    }

    /**
     * 考勤转积分.
     * 
     * @author 28youth
     * @param  [type] $data 
     */
    public function transformAttendance($data)
    {
        $config = $this->getConfig();
        if ($data['worktime'] < $config->time) {
            return false;
        }
        $staff = app('api')->getStaff(['filters' => "dingding={$data['userId']};status_id>=0"]);
        $point = intval($data['worktime'] / $config->time) * $config->point;
        
        $model = new PointLogModel();
        $model->title = $data['workDate']. '考勤转积分';
        $model->staff_sn = $staff[0]['staff_sn'];
        $model->staff_name = $staff[0]['realname'];
        $model->brand_id = $staff[0]['brand']['id'];
        $model->brand_name = $staff[0]['brand']['name'];
        $model->department_id = $staff[0]['department_id'];
        $model->department_name = $staff[0]['department']['full_name'];
        $model->shop_sn = $staff[0]['shop_sn'];
        $model->shop_name = $staff[0]['shop']['name'];
        $model->point_b = $point;
        $model->source_id = 4;
        $model->changed_at = now();
        $model->type_id = 0;
        $model->save();
    }
    /**
     * 获取考勤转积分配置.
     * 
     * @author 28youth
     * @return array
     */
    protected function getConfig()
    {
        $key = "attendance_radio_config";

        if (Cache::has($key)) {
            return Cache::get($key);
        }

        $config = CommonConfig::byNamespace('basepoint')->byName('attendance_radio')->first();
        $config = json_decode($config->value);

        Cache::put($key, $config);

        return $config;   
    }

    /**
     * 缓存用户分组.
     * 
     * @author 28youth
     * @param  string $userId
     * @return array
     */
    protected function makeUserGroup($userId, $groupId)
    {
        $key = "attendance_user_group_".$groupId;
        if (Cache::has($key)) {
            return Cache::get($key);
        }

        $group = $this->provider('attendance')->userGroup($userId);
        // 上班时间
        $workBeginTime = Carbon::parse($group['result']['classes'][0]['sections'][0]['times']['0']['check_time']);
        $workEndTime = Carbon::parse($group['result']['classes'][0]['sections'][0]['times']['1']['check_time']);
        $totalWorkTime = ($workEndTime->timestamp - $workBeginTime->timestamp)/60;
        //休息时间
        $restBeginTime = Carbon::parse($group['result']['classes'][0]['setting']['rest_begin_time']['check_time']);
        $restEndTime = Carbon::parse($group['result']['classes'][0]['setting']['rest_end_time']['check_time']);
        $restTime = ($restEndTime->timestamp - $restBeginTime->timestamp)/60;
        // 有效工作时长
        $worktime = $totalWorkTime - $restTime;

        $data = [
            'groupId' => $group['result']['group_id'],
            'restStime' => $restBeginTime,
            'restEtime' => $restEndTime,
            'worktime' => $worktime, // 工作时长
            'latetime' => 0, // 迟到时间
            'overtime' => 0, // 加班时间
            'leavetime' => 0, // 请假时间
            'earlytime' => 0 // 早退时间
        ];

        Cache::put($key, $data, now()->addMonth());

        return $data;
    }

    protected function getUsers($page, $count = 50)
    {
        $start = ($page - 1) * $count;

        $staff_sns = GroupStaff::pluck('staff_sn')->unique()->values();
        $users = app('api')->getStaff(['filters' => "staff_sn={$staff_sns};status_id>=0"]);

        return array_slice($users, $start, $count);
    }
    /**
     * Get provider name.
     *
     * @param string $provider
     * @return string
     */
    protected function getProviderName(string $provider): string
    {
        return $this->providerMap[strtolower($provider)] ?? $provider;
    }

    /**
     * Get provider driver.
     *
     * @param string $provider
     * @return \Fisher\Schedule\Services\Contracts\Dingtalk
     */
    protected function provider(string $provider): Dingtalk
    {
        return $this->dingtalk->driver(
            $this->getProviderName($provider)
        );
    }

}
