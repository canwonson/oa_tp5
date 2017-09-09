<?php
namespace app\validate;
use think\Validate;

class Unusual extends Validate
{
    protected $rule = [
        'unusual_time' => 'require',
        'unusual_type' => 'require',
        'sign_type' => 'require',
        'describe' => 'require'
    ];

    protected $message = [
        'unusual_time.require'  =>  '异常出勤时间必须',
        'unusual_type.require'  =>  '异常类型必须',
        'sign_type.require'  =>  '签到情况必须',
        'describe.require'  =>  '异常事由必须',
    ];

    protected $scene = [
        'add'   =>  ['unusual_time', 'unusual_type', 'sign_type', 'describe']
    ];
}
