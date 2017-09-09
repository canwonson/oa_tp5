<?php
namespace app\validate;
use think\Validate;

class Official extends Validate
{
    protected $rule = [
		'entry_time'     => 'require',
		'assess_time'    => 'require',
		'probation_time' => 'require',
		'self_estimate'  => 'require',
		'self_plan'      => 'require',
		'work_suggest'   => 'require',
		'team_appraise'  => 'require'
    ];

    protected $message = [
		'entry_time.require'   => '入职时间必须填写',
		'assess_time.require'      => '考核日期必须填写',
		'probation_time.require' => '试用期必须填写',
		'self_estimate.require'   => '自我评价必须填写',
		'self_plan.require'   => '个人规划必须填写',
		'work_suggest.require'      => '工作建议必须填写',
		'team_appraise.require'      => '对团队的评价必须填写'
    ];

    protected $scene = [
        'add'   =>  ['entry_time', 'assess_time', 'probation_time', 'self_estimate', 'self_plan', 'work_suggest', 'team_appraise']
    ];
}
