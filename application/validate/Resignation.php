<?php
namespace app\validate;
use think\Validate;

class Resignation extends Validate
{
    protected $rule = [
		'entry_time'   => 'require',
		'leave_time'   => 'require',
		'leave_type'   => 'require',
		'leave_reason' => 'require',
		'complaints'   => 'require',
		'suggest'      => 'require',
    ];

    protected $message = [
		'entry_time.require'   => '入职时间必须填写',
		'leave_time.require'   => '离职日期必须填写',
		'leave_type.require'   => '离职类型必须填写',
		'leave_reason.require' => '离职原因必须填写',
		'complaints.require'   => '吐槽专属区必须填写',
		'suggest.require'      => '对公司的建议必须填写'
    ];

    protected $scene = [
        'add'   =>  ['entry_time', 'leave_time', 'leave_type', 'leave_reason', 'complaints', 'suggest']
    ];
}
