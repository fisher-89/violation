<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PunishCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->collection->map(function ($data) {
            $arr = [];
            foreach ($data->pushing as $items) {
                $arr[] = [
                    'id' => $items->auth_id,
                    'flock_name' => $items->pushingAuthority['flock_name']
                ];
            }
            return [
                'id' => $data->id,
                'rule_id' => $data->rule_id,
                'point_log_id' => $data->point_log_id,
                'staff_sn' => $data->staff_sn,
                'staff_name' => $data->staff_name,
                'brand_id' => $data->brand_id,
                'brand_name' => $data->brand_name,
                'department_id' => $data->department_id,
                'department_name' => $data->department_name,
                'position_id' => $data->position_id,
                'position_name' => $data->position_name,
                'shop_sn' => $data->shop_sn,
                'billing_sn' => $data->billing_sn,
                'billing_name' => $data->billing_name,
                'billing_at' => $data->billing_at,
                'quantity' => $data->quantity,
                'money' => $data->money,
                'score' => $data->score,
                'violate_at' => $data->violate_at,
                'has_paid' => $data->has_paid,
                'action_staff_sn' => $data->action_staff_sn,
                'paid_type' => $data->paid_type,
                'paid_at' => $data->paid_at,
                'sync_point' => $data->sync_point,
                'month' => $data->month,
                'area' => $data->area,
                'remark' => $data->remark,
                'creator_sn' => $data->creator_sn,
                'creator_name' => $data->creator_name,
                'rules' => $data->rules,
                'pushing' => $arr
            ];
        })->toArray();
    }
}
