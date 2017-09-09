<?php
namespace app\model;
use app\model\Common;

class Flow extends Common
{
	protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';

    protected $type = [
        'update_time' => 'timestamp',
        'create_time' => 'timestamp'
    ];
}
