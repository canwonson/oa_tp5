<?php
namespace app\model;
use app\model\Common;

class Leave extends Common
{
	protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';

    protected $type = [
        'update_time' => 'timestamp',
        'create_time' => 'timestamp',
		'start_time' => 'timestamp:Y-m-d H:i',
		'end_time' => 'timestamp:Y-m-d H:i',
    ];
}
