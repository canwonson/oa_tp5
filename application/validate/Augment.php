<?php
namespace app\validate;
use think\Validate;

class Augment extends Validate
{
    protected $rule = [
		'project_id'   => 'require',
		'duty_id'      => 'require',
		'apply_number' => 'require',
		'now_number'   => 'require',
		'reach_time'   => 'require',
		'min_pay'      => 'require',
		'max_pay'      => 'require',
		'auth_type'    => 'require',
		'demand'       => 'require'
    ];

    protected $message = [
		'project_id.require'   => '项目必须选择',
		'duty_id.require'      => '职能必须选择',
		'apply_number.require' => '拟增员人数必须填写',
		'now_number.require'   => '现有人数必须填写',
		'reach_time.require'   => '期望到岗时间必须填写',
		'min_pay.require'      => '最低薪资要求必须填写',
		'max_pay.require'      => '最高薪资要求必须填写',
		'auth_type.require'    => '编制情况必须填写',
		'demand.require'       => '拟增员岗位要求必须填写',
    ];

    protected $scene = [
        'add'   =>  ['project_id', 'duty_id', 'apply_number', 'now_number', 'reach_time', 'min_pay', 'max_pay', 'auth_type', 'demand']
    ];
}
