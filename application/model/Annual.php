<?php
namespace app\model;
use app\model\Common;

class Annual extends Common
{
	protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';

    protected $type = [
        'update_time' => 'timestamp',
        'create_time' => 'timestamp'
    ];

    public function getUserAnnual($user_id)
    {
    	$info = $this->where(['user_id' => $user_id, 'status'=>1])->field('sum(total_day) as total_day, sum(used_day) as used_day, sum(total_day) - sum(used_day) as residue_day')->find();
    	$list = $this->where(['user_id' => $user_id])->column('total_day, used_day, total_day - used_day as residue_day, cleared_day, status', 'year');
    	return ['list'=>$list, 'info'=>$info];
    }
}
