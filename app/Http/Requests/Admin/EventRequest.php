<?php

namespace App\Http\Requests\Admin;

use App\Models\FinalApprover;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->jurisdiction();
    }

    /**
     * Get the validation rules that apply to the request.
     * @return array
     */
    public function rules()
    {
        $finalApproverSn = $this->final_approver_sn == true ? $this->final_approver_sn : 0;
        $final = FinalApprover::where('staff_sn', $finalApproverSn)->first();
        return [
            'name' => ['required', 'max:40',
                Rule::unique('events', 'name')
                    ->where('type_id', $this->get('type_id'))
                    ->whereNull('deleted_at')
                    ->ignore($this->route('id', 0))
            ],
            'type_id' => 'required|numeric',
            'point_a_min' => 'required|numeric',
            'point_a_max' => ['required',
                function ($attribute, $value, $event) use ($final) {
                    if ($value < $this->point_a_min) {
                        return $event('A分默认最大值小于最小值');
                    }
//                    if ($this->final_approver_sn != '' && $final != null) {
//                        if (strstr($value, '-')) {
//                            $pointAMax = str_replace('-', ' ', $value);
//                            if ($pointAMax > $final['point_a_deducting_limit']) {
//                                return $event('A分最大扣分值超过终审人上限');
//                            }
//                        } else {
//                            if ($value > $final['point_a_awarding_limit']) {
//                                return $event('A分最大加分值超过终审人上限');
//                            }
//                        }
//                    }
                },
            ],
            'point_a_default' => 'required|numeric|between:' .
                ($this->point_a_min < $this->point_a_max ? $this->point_a_min : $this->point_a_max) .
                ',' . ($this->point_a_min < $this->point_a_max ? $this->point_a_max : $this->point_a_min),
            'point_b_min' => 'required|numeric',
            'point_b_max' => ['required', 'numeric',
                function ($attribute, $value, $fail) use ($final) {
                    if ($value < $this->point_b_min) {
                        return $fail('B分默认最大值小于最小值');
                    }
//                    if ($this->final_approver_sn != '' && $final != null) {
//                        if (strstr($value, '-')) {
//                            $pointAMax = str_replace('-', ' ', $value);
//                            if ($pointAMax > $final['point_b_deducting_limit']) {
//                                return $fail('B分最大扣分大于终审人上限');
//                            }
//                        } else {
//                            if ($value > $final['point_b_awarding_limit']) {
//                                return $fail('B分最大加分值大于终审人上限');
//                            }
//                        }
//                    }
                }
            ],
            'point_b_default' => 'required|numeric|between:' .
                ($this->point_b_min < $this->point_b_max ? $this->point_b_min : $this->point_b_max) .
                ',' . ($this->point_b_min < $this->point_b_max ? $this->point_b_max : $this->point_b_min),
            'first_approver_sn' => [
                function ($attribute, $value, $event) {
                    if ($value != '') {
                        try {
                            $oaData = app('api')->withRealException()->getStaff($value);
                            if ((bool)$oaData == false) {
                                return $event('初审人错误');
                            }
                        } catch (\Exception $e) {
                            return $event('初审人错误');
                        }
                    }
                }
            ],
//            'first_approver_name'=>'',
            'final_approver_sn' => [
                function ($attribute, $value, $event) use ($final) {
                    if ($final == null) {
                        return $event('终审人：' . $final . '不存在');
                    }
                }
            ],
//            'final_approver_name'=>'',
            'first_approver_locked' => 'required|min:0|max:1',//0未锁定1锁定
            'final_approver_locked' => 'required|min:0|max:1',//0未锁定1锁定
            'default_cc_addressees' => [
                function ($attribute, $value, $event) {
                    if (count($value) > 5) {
                        return $event('默认抄送人不超过5个');
                    }
                }
            ],
            'is_active' => 'required|min:0|max:1'//0未激活1激活
        ];
    }

    public function attributes()
    {
        return [
            'name' => '事件名称',
            'type_id' => '事件类型',
            'point_a_min' => 'A分最小值',
            'point_a_max' => 'A分最大值',
            'point_a_default' => 'A分默认值',
            'point_b_min' => 'B分最小值',
            'point_b_max' => 'B分最大值',
            'point_b_default' => 'B分默认值',
            'first_approver_sn' => '初审人编号',
//            'first_approver_name'=>'初审人姓名',
            'final_approver_sn' => '终审人编号',
//            'final_approver_name'=>'终审人姓名',
            'first_approver_locked' => '初审人锁定',//0未锁定1锁定
            'final_approver_locked' => '终审人锁定',//0未锁定1锁定
            'default_cc_addressees' => '默认抄送人',
            'is_active' => '是否激活'//0未激活1激活
        ];
    }

    /**
     * 权限
     */
    public function jurisdiction()
    {
        return true;
    }
}
