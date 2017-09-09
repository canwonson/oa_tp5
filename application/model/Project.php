<?php
namespace app\model;
use app\model\Common;

class Project extends Common
{
	protected $autoWriteTimestamp = true;
    protected $updateTime = false;
    protected $insert = ['status' => 1];

    protected $type = [
        'update_time' => 'timestamp',
        'create_time' => 'timestamp',
    ];
}
