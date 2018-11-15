<?php

namespace App\Repositories;

use Illuminate\Http\Request;
use App\Models\Event;
use Illuminate\Database\Eloquent\Model;

class EventRepository
{
    use Traits\Filterable;

    /**
     * Event model
     */
    protected $event;

    /**
     * EventRepository constructor.
     * @param Event $event
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * 获取事件分页列表.
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function getPaginateList(Request $request)
    {
        return $this->event->filterByQueryString()->withPagination($request->get('pagesize', 10));
    }

    /**
     * 获取全部事件列表.
     * 无分页
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function getList(Request $request)
    {
        $builder = ($this->event instanceof Model) ? $this->event->query() : $this->event;
        $sort = explode('-', $request->sort);
        $filters = $request->query('filters', '');
        if ($filters && $filters !== null) {
            $maps = $this->formatFilter($filters);
            foreach ($maps['maps'] as $k => $map) {
                $curKey = $maps['fields'][$k];
                $builder->when($curKey, $map[$curKey]);
            }
        }
        $builder->when(($sort && !$sort), function ($query) use ($sort) {
            $query->orderBy($sort[0], $sort[1]);
        });
        $items = $builder->get();
        return $items;
    }

    /**
     * @param $arr
     * @return mixed
     * 获取事件列表
     */
    public function getEventData($id)
    {
        return Event::where('id', $id)->first()->toArray();
    }

    /**
     * @param $request
     * @return mixed
     * 添加事件
     */
    public function addEventData($request)
    {
        $event = $this->event;
        $event->name = $request->name;
        $event->type_id = $request->type_id;
        $event->point_a_min = $request->point_a_min;
        $event->point_a_max = $request->point_a_max;
        $event->point_a_default = $request->point_a_default;
        $event->point_b_min = $request->point_b_min;
        $event->point_b_max = $request->point_b_max;
        $event->point_b_default = $request->point_b_default;
        $event->first_approver_sn = $request->first_approver_sn ?: null;
        $event->first_approver_name = $request->first_approver_sn > 0 ? $request->first_approver_name : null;
        $event->final_approver_sn = $request->final_approver_sn ?: null;
        $event->final_approver_name = $request->final_approver_sn > 0 ? $request->final_approver_name : null;
        $event->first_approver_locked = $request->first_approver_sn > 0 ? $request->first_approver_locked : 0;
        $event->final_approver_locked = $request->final_approver_sn > 0 ? $request->final_approver_locked : 0;
        $event->default_cc_addressees = $request->default_cc_addressees ?: "";
        $event->is_active = $request->is_active;
        $event->save();
        return $event;
    }

    /**
     * @param $request
     * @return mixed
     * 更新事件
     */
    public function updateEventData($request)
    {
        $id = $request->route('id');
        $event = $this->event->find($id);
        if (empty($event)) {
            abort(404, '未找到原始数据');
        }
        $sql = [
            'name' => $request->name,
            'type_id' => $request->type_id,
            'point_a_min' => $request->point_a_min,
            'point_a_max' => $request->point_a_max,
            'point_a_default' => $request->point_a_default,
            'point_b_min' => $request->point_b_min,
            'point_b_max' => $request->point_b_max,
            'point_b_default' => $request->point_b_default,
            'first_approver_sn' => $request->first_approver_sn ?: null,
            'first_approver_name' => $request->first_approver_sn > 0 ? $request->first_approver_name : null,
            'final_approver_sn' => $request->final_approver_sn ?: null,
            'final_approver_name' => $request->final_approver_sn > 0 ? $request->final_approver_name : null,
            'first_approver_locked' => $request->first_approver_locked,
            'final_approver_locked' => $request->final_approver_locked,
            'default_cc_addressees' => $request->default_cc_addressees,
            'is_active' => $request->is_active,
        ];
        $event->update($sql);
        return $event;
    }

    /**
     * @param $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     * 删除事件
     */
    public function deleteEvents($request)
    {
        $event = $this->event->find($request->route('id'));
        $event->delete();
        if ($event->trashed()) {
            return response('', 204);
        } else {
            return response()->json([
                'message' => '删除失败'
            ], 400);
        }
    }
}