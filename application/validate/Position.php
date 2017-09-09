<?php
namespace app\validate;
use think\Validate;

class Position extends Validate
{
    protected $rule = [
        'name'      => 'require',
    ];

    protected $message = [
        'name.require'      =>  '名称不能为空!',
    ];

    protected $scene = [
        'add' =>  ['name']
    ];

    // 自定义验证规则
}