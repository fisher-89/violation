<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StatisticResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = app('api')->withRealException()->getStaff($request->query('staff_sn', $request->user()->staff_sn));
        
        return [
            'staff_sn' => $user['staff_sn'],
            'staff_name' => $user['realname'],
            'brand_name' => $user['brand']['name'],
            'department_name' => $user['department']['full_name'],
            'point_a_monthly' => $this->point_a ?? 0,
            'point_b_monthly' => $this->point_b_monthly ?? 0,
            'point_b_total' => $this->point_b_total ?? 0,
            'source_b_monthly' => $this->source_b_monthly ?? null,
            'source_b_total' => $this->source_b_total ?? null,
            'point_a_total' => $this->point_a_total ?? 0,
            'source_a_monthly' => $this->source_a_monthly ?? null,
            'source_a_total' => $this->source_a_total ?? null
        ];
    }
}
