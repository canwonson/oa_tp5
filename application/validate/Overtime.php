<?php
namespace app\validate;
use think\Validate;

class Overtime extends Validate
{
    protected $rule = [
        'start_time'      => 'require',
        'end_time'        => 'require',
        'overtime'        => 'require',
        'overtime_reason' => 'require',
    ];

    protected $message = [
        'start_time.require'      =>  '加班开始时间必须',
        'end_time.require'        =>  '加班结束时间必须',
        'overtime.require'        => '加班时长必须',
        'overtime_reason.require' =>  '加班事由必须',
    ];

    protected $scene = [
        'add'   =>  ['start_time', 'end_time', 'overtime', 'overtime_reason']
    ];
}
