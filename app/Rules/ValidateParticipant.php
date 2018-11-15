<?php

namespace App\Rules;

use App\Models\Event as EventModel;
use Illuminate\Contracts\Validation\Rule;
use App\Models\FinalApprover as FinalApproverModel;

/**
 * 对事件参与人进行分值范围验证
 * 对事件终审人进行分值范围权限验证
 */
class ValidateParticipant implements Rule
{
    /**
     * 终审人数据模型.
     * 
     * @var App\Models\FinalApprover
     */
    public $model;

    /**
     * 事件模型.
     * 
     * @var App\Models\Event
     */
    public $event;

    /**
     * 错误消息.
     * 
     * @var string
     */
    public $message;
    
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($final_approver_sn)
    {
        $final = FinalApproverModel::where('staff_sn', $final_approver_sn)->first();

        $this->model = $final;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($this->model === null) {
            return $this->response('终审人填写错误!');
        }

        foreach ($value as $k => $val) {

            $this->event = EventModel::find($val['event_id']);

            if ($this->event === null) {
                return $this->response('事件不存在');
            }

            // 只有其他类型事件才需要判断分值权限
            if (!in_array($val['event_id'], [398])) {
                
                array_walk($val['participants'], [$this, 'checkValue']);
            }

        }

        return true;
    }

    /**
     * 验证内容.
     * 
     * @author 28youth
     * @param  array $v
     * @return void
     */
    public function checkValue($v)
    {
        if ($v['point_a'] == 0 && $v['point_b'] == 0) {
            return $this->response('参与人A分B分至少有一项不能为零');
        }

        if ($v['point_a'] > $this->event->point_a_max || $v['point_a'] < $this->event->point_a_min) {
            return $this->response($this->event->id.'参与人A分范围不能超出'. $this->event->point_a_min. '~' .$this->event->point_a_max);
        }

        if (!empty($this->model)) {
            if ($v['point_a'] > $this->model->point_a_awarding_limit || $v['point_a'] < -$this->model->point_a_deducting_limit) {
                return $this->response('终审人A分审核范围不能超出'. -$this->model->point_a_deducting_limit. '~' .$this->model->point_a_awarding_limit);
            }

            if ($v['point_b'] > $this->event->point_b_max || $v['point_b'] < $this->event->point_b_min) {
                return $this->response($this->event->id.'参与人B分范围不能超出'. $this->event->point_b_min. '~' .$this->event->point_b_max);
            }

            if ($v['point_b'] > $this->model->point_b_awarding_limit || $v['point_b'] < -$this->model->point_b_deducting_limit) {
                return $this->response('终审人B分审核范围不能超出'. -$this->model->point_b_deducting_limit. '~' .$this->model->point_b_awarding_limit);
            }
        }
    }

    protected function response(string $message)
    {
        $this->message = $message;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->message;
    }
}
