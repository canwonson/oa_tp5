<?php
namespace app\model;
use app\model\Common;

class Unusual extends Common
{
	protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';

    protected $type = [
        'update_time' => 'timestamp',
        'create_time' => 'timestamp',
		'unusual_time' => 'timestamp:Y-m-d H:i'
    ];
}
