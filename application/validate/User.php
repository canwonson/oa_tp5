<?php
namespace app\validate;
use think\Validate;

class User extends Validate
{
    protected $rule = [
        'name' => 'require',
        'account' => 'require|checkAccount:data',
        'project_id' => 'require',
        'duty_id' => 'require',
        'position_id' => 'require',
        'role_id' => 'require',
        'entry_time' => 'require',
    ];

    protected $message = [
        'name.require'  =>  '姓名不能为空!',
        'account.require'  =>  '账号不能为空!',
        'project_id.require'  =>  '部门不能为空!',
        'duty_id.require'  =>  '职能不能为空!',
        'position_id.require'  =>  '职位不能为空!',
        'role_id.require'  =>  '角色不能为空!',
        'entry_time.require'  =>  '入职时间不能为空!',
    ];

    protected $scene = [
        'add' =>  ['name, account, project_id, duty_id, position_id, role_id, entry_time']
    ];

    // 自定义验证规则
    protected function checkAccount($value,$rule,$data)
    {
        $map['account'] = $data['account'];
        isset($data['id']) && $map['id'] = ['neq', $data['id']];
        $count = model('User') -> where($map) ->count();

        if ($count) {
            return '已经存在的账号';
        }else {
            return true;
        }
    }
}
