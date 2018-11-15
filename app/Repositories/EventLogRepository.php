<?php

namespace App\Repositories;

use Illuminate\Http\Request;
use App\Models\EventLog as EventLogModel;


class EventLogRepository
{
    /**
     * EventLog model
     */
    protected $eventlog;

    /**
     * EventLogRepository constructor.
     * @param EventLog $eventlog
     */
    public function __construct(EventLogModel $eventlog)
    {
        $this->eventlog = $eventlog;
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
        return $this->eventlog->filterByQueryString()
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
        return $this->eventlog->filterByQueryString()
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

        return $this->eventlog->filterByQueryString()
            ->with('participants')
            ->whereHas('participants', function ($query) use ($user) {
                return $query->where('staff_sn', $user->staff_sn);
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

        return $this->eventlog->filterByQueryString()
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

        return $this->eventlog->filterByQueryString()
            ->where(function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
                    $query->where('first_approver_sn', $user->staff_sn)
                        ->whereNotNull('first_approved_at');
                })
                    ->orWhere(function ($query) use ($user) {
                        $query->where('final_approver_sn', $user->staff_sn)
                            ->whereNotNull('final_approved_at');
                    })
                    ->orWhere(function ($query) use ($user) {
                        $query->where('rejecter_sn', $user->staff_sn)
                            ->whereNotNull('rejected_at');
                    });
            })
            ->sortByQueryString()
            ->withPagination();
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

        return $this->eventlog->filterByQueryString()
            ->whereHas('addressee', function ($query) use ($user) {
                $query->where('staff_sn', $user->staff_sn);
            })
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

        return $this->eventlog->filterByQueryString()
            ->where(function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
                    $query->where('first_approver_sn', $user->staff_sn)->byAudit(0);

                })
                    ->orWhere(function ($query) use ($user) {
                        $query->where('final_approver_sn', $user->staff_sn)->byAudit(1);
                    });
            })
            ->sortByQueryString()
            ->withPagination();
    }

    public function getEventLogList($request)
    {
        return $this->eventlog
            ->with('participants')
            ->filterByQueryString()
            ->sortByQueryString()
            ->withPagination($request->get('pagesize', 10));
    }

    public function getEventLogSingleness($id)
    {
        return $this->eventlog->with('eventType')->with('event')->where('id', $id)->first();
    }
}