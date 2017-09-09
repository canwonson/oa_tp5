<?php
namespace app\validate;
use think\Validate;

class Leave extends Validate
{
    protected $rule = [
        'start_time' => 'require',
        'end_time'   => 'require',
        'leave_day'  => 'require',
        'leave_type' => 'require',
        'describe'   => 'require'
    ];

    protected $message = [
        'start_time.require' =>  '请假开始时间必须',
        'end_time.require'   =>  '请假结束时间必须',
        'leave_day.require'  =>  '请假天数必须',
        'leave_type.require' =>  '请假类型必须',
        'describe.require'   =>  '请假说明必须',
    ];

    protected $scene = [
        'add'   =>  ['start_time', 'end_time', 'leave_day', 'leave_type', 'describe']
    ];
}
