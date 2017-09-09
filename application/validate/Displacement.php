<?php
namespace app\validate;
use think\Validate;

class Displacement extends Validate
{
    protected $rule = [
		'original_project_id' => 'require',
		'change_project_id'   => 'require',
		'change_duty_id'      => 'require',
		'change_user_id'      => 'require',
		'change_time'         => 'require',
		'demand'              => 'require',
    ];

    protected $message = [
		'original_project_id.require'   => '原部门必须选择',
		'change_project_id.require'      => '异动部门必须选择',
		'change_duty_id.require' => '异动职能必须选择',
		'change_user_id.require'   => '异动人员必须填写',
		'change_time.require'      => '异动时间必须填写',
		'demand.require'      => '异动原因必须填写',
    ];

    protected $scene = [
        'add'   =>  ['original_project_id', 'change_project_id', 'change_duty_id', 'change_user_id', 'change_time', 'demand']
    ];
}
