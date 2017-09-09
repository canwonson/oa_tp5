<?php
namespace app\model;
use think\Model;

class Common extends Model
{
	//配置列表
    public function getConfList(){

        $project_list   = model('Project')->where(['status'=>1])->column('name','id');
        $position_list  = model('Position')->where(['status'=>1])->column('name','id');
        $duty_list      = model('Duty')->where(['status'=>1])->column('name','id');
        $role_list      = model('Role')->where(['status'=>1])->column('name','id');

        $conf_list = [
            'project_list'  => $project_list,
            'position_list' => $position_list,
            'duty_list'     => $duty_list,
            'role_list'     => $role_list,
        ];

        return $conf_list;
    }
}
