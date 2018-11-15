<?php

namespace App\Repositories;

use App\Models\Event;
use App\Models\EventType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventTypeRepository
{
    public function __construct(EventType $eventType)
    {
        $this->eventType=$eventType;
    }
    public function evenTypeListGetData()
    {
//        return $this->eventType->filterByQueryString()->withPagination($request->get('pagesize', 10));
        return EventType::orderBy('sort')->get()->toArray();
    }

    public function getEventTypeData($id)
    {
        return EventType::where('id',$id)->first();
    }

    public function getEventTypeDataToArray($id)
    {
        return $this->eventType->where('id',$id)->first(['id','name','parent_id','sort'])->toArray();
    }
    /**
     * @param $updateData
     * @return mixed
     * 更新事件分类
     */
    public function updateEventTypeData($arr)
    {
        foreach ($arr as $k=>$v){
            $getEventType = $this->eventType->find($v['id']);
            $eventType[$v['name']] = $getEventType ? EventType::where('id',$v['id'])->update($v) : false;
        }
        return $eventType;
    }

    /**
     * @param $arr
     * @return mixed
     * 添加事件分类
     */
    public function addEventType($arr)
    {
        $type=$this->eventType;
        $type->name=$arr['name'];
        $type->parent_id=$arr['parent_id'];
        $type->sort=$arr['sort'];
        $type->save();
        return $type->find($type->id);
    }

    /**
     * @param $arr
     * @return mixed
     * 更新事件分类数据
     */
    public function updateEventTypeRepository($request)
    {
        $id=$request->route('id');
        $eventType = $this->eventType->find($id);
        return (bool)$eventType->update($request->all()) == false ? false : $this->getEventTypeData($id);
    }

    /**
     * @param $id
     * 删除所有子分类的数据包括事件表
     */
    public function deleteEventType($id)
    {
        $sql=[
            'deleted_at'=>date('Y-m-d H:i:s')
        ];
        Event::whereIn('type_id',$id)->update($sql);
        EventType::whereIn('id',$id)->update($sql);
        return response('',204);
    }

    public function getEventType($id)
    {
        return EventType::where('parent_id',$id)->get()->toArray();
    }

    public function nameGetData($name)
    {
        return EventType::where('name',$name)->first();
    }

    public function nameGetNotData($id,$name)
    {
        return EventType::whereNotIn('id',explode(',',$id))->where('name',$name)->first();
    }
}