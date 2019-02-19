<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PushCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
//        return parent::toArray($request);
        return $this->collection->map(function ($data) {
            return [
                'id' => $data->id,
                'staff_sn' => $data->staff_sn,
                'staff_name' => $data->staff_name,
                'flock_name' => $data->flock_name,
                'default_push' => $data->default_push,
            ];
        });
    }
}
