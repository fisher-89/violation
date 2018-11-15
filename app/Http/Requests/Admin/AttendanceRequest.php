<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'userOnTime' => 'date',
            'userOffTime' => 'date',
            'worktime' => 'numeric|max:510',
            'latetime' => 'numeric|min:0',
            'overtime' => 'numeric|min:0',
            'leavetime' => 'numeric|min:0',
            'earlytime' => 'numeric|min:0'
        ];
    }

    /**
     * Get rule messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'userOnTime.date' => '上班时间格式不正确',
            'userOffTime.date' => '下班时间格式不正确',
            'worktime.max' => '工作时间不能大于:max分钟',
        ];
    }

}