<?php
namespace app\validate;
use think\Validate;

class FlowClassify extends Validate
{
    protected $rule = [
        'name' => 'require',
        'status' => 'require',
    ];

    protected $message = [
        'name.require'  =>  '名称不能为空!',
        'status.require'  =>  '必须选择状态!',
    ];

    protected $scene = [
        'add' =>  ['name', 'status']
    ];
}
