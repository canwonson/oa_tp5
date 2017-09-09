<?php
namespace app\validate;
use think\Validate;

class Project extends Validate
{
    protected $rule = [
        'name'      => 'require',
        'leader_id' => 'require',
    ];

    protected $message = [
        'name.require'      =>  '名称不能为空!',
        'leader_id.require' =>  '必须选择项目负责人!',
    ];

    protected $scene = [
        'add' =>  ['name', 'leader_id']
    ];

    // 自定义验证规则
}