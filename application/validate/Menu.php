<?php
namespace app\validate;
use think\Validate;

class Menu extends Validate
{
    protected $rule = [
        'name'    => 'require',
        'pid'     => 'require',
        'sort'    => 'require',
        'status'  => 'require',
        'is_show' => 'require'
    ];

    protected $message = [
        'name.require'    =>  '名称不能为空!',
        'pid.require'     =>  '必须选择模块名称!',
        'sort.require'    =>  '菜单排序不能为空!',
        'status.require'  =>  '必须选择状态!',
        'is_show.require' =>  '必须选择显示!',
    ];

    protected $scene = [
        'add' =>  ['name', 'pid', 'sort', 'status', 'is_show']
    ];

    // 自定义验证规则
}
