<?php

namespace App\Repositories;

use Illuminate\Http\Request;
use App\Models\EventLog as EventLogModel;
use App\Models\EventLogGroup as EventLogGroupModel;


class EventLogGroup
{
    /**
     * EventLog model
     */
    protected $group;

    /**
     * EventLogRepository constructor.
     * @param EventLogGroupModel $group
     */
    public function __construct(EventLogGroupModel $group)
    {
        $this->group = $group;
    }

    /**
     * 获取事件日志分页列表.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function getPaginateList(Request $request)
    {
        return $this->group->filterByQueryString()
            ->sortByQueryString()
            ->withPagination();
    }

    /**
     * 获取全部事件日志列表.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function getAllList(Request $request)
    {
        return $this->group->filterByQueryString()
            ->sortByQueryString()
            ->withPagination();
    }

    /**
     * 获取我参与的事件日志列表.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function getParticipantList(Request $request)
    {
        $user = $request->user();

        return EventLogModel::query()->filterByQueryString()
            ->with('participants')
            ->whereHas('participants', function ($query) use ($user) {
                $query->where('staff_sn', $user->staff_sn);
            })
            ->sortByQueryString()
            ->withPagination();
    }

    /**
     * 获取我记录的事件日志列表.
     *
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function getRecordedList(Request $request)
    {
        $user = $request->user();

        return $this->group->filterByQueryString()
            ->where('recorder_sn', $user->staff_sn)
            ->sortByQueryString()
            ->withPagination();
    }

    /**
     * 获取我审核的事件记录列表. 初审 终审 驳回 并且对应时间不为空
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function getApprovedList(Request $request)
    {
        $user = $request->user();
        $cate = $request->query('cate');
        $step = $request->query('step');

        $builder = $this->group->query();
        $builder->where(function ($query) use ($user, $step) {
            if ($step != 'final')
                $query->where(function ($query) use ($user) {
                $query->where('first_approver_sn', $user->staff_sn)
                    ->where('final_approver_sn', '!=', $user->staff_sn)
                    ->where('recorder_sn', '!=', $user->staff_sn)
                    ->where(function ($query) use ($user) {
                        $query->whereNotNull('first_approved_at')
                            ->orWhere('rejecter_sn', $user->staff_sn);
                    });
            });
            if ($step != 'first')
                $query->orWhere(function ($query) use ($user) {
                $query->where('final_approver_sn', $user->staff_sn)
                    ->where(function ($query) use ($user) {
                        $query->where('recorder_sn', '!=', $user->staff_sn)
                            ->orWhere('first_approver_sn', '!=', $user->staff_sn);
                    })->where(function ($query) use ($user) {
                        $query->whereNotNull('final_approved_at')
                            ->orWhere('rejecter_sn', $user->staff_sn);
                    });
            });
        });

        $builder->when($cate, function ($query) use ($cate, $user) {
            if ($cate == 'audit') {
                $query->whereNull('rejecter_sn');
            } else if ($cate == 'reject') {
                $query->where('rejecter_sn', $user->staff_sn);
            }
        });

        return $builder->sortByQueryString()->withPagination();
    }

    /**
     * 获取抄送我的事件记录列表.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function getAddresseeList(Request $request)
    {
        $user = $request->user();

        return $this->group->filterByQueryString()
            ->whereHas('addressees', function ($query) use ($user) {
                $query->where('staff_sn', $user->staff_sn);
            })
            ->where('status_id', 2)
            ->sortByQueryString()
            ->withPagination();
    }

    /**
     * 审核的事件日志列表.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function getProcessingList(Request $request)
    {
        $user = $request->user();
        $step = $request->query('step');

        return $this->group->filterByQueryString()
            ->where(function ($query) use ($user, $step) {
                if ($step != 'final'){
                    $query->where(function ($query) use ($user) {
                        $query->where('first_approver_sn', $user->staff_sn)->byAudit(0);
                    });
                }
                if ($step != 'first'){
                    $query->orWhere(function ($query) use ($user) {
                        $query->where('final_approver_sn', $user->staff_sn)->byAudit(1);
                    });
                }
            })
            ->sortByQueryString()
            ->withPagination();
    }

    public function getEventLogList($request)
    {
        return $this->group->filterByQueryString()
            ->sortByQueryString()
            ->withPagination();
    }

    public function getEventLogSingleness($id)
    {
        return $this->group->with('eventType')->with('event')->where('id', $id)->first();
    }
}