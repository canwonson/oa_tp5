<?php
namespace app\model;
use app\model\Common;

class User extends Common
{
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    protected $insert = ['password' => 'e10adc3949ba59abbe56e057f20f883e','status' => 1];

    protected $type = [
        'entry_time' => 'timestamp:Y-m-d',
        'leave_time' => 'timestamp:Y-m-d',
        'formal_time' => 'timestamp:Y-m-d',
        'create_time' => 'timestamp',
    ];

    public function setAccountAttr($value)
    {
        return trim($value);
    }

    public function getUserList()
    {
        $obj_list = $this->field('id, name, duty_id, project_id')->order('create_time asc')->select();
        $list = [];
        foreach ($obj_list as $value) {
            $list[] = $value->toArray();
        }
        $datas = [];
        foreach ($list as $user) {
            $user_info = [
                'id' => $user['id'],
                'name' => $user['name']
            ];
            $datas[$user['project_id']][$user['duty_id']][] = $user_info;
        }
        return $datas;
    }
}
