<?php
namespace app\model;
use app\model\Common;

class Position extends Common
{
	protected $autoWriteTimestamp = true;
    protected $updateTime = false;

    protected $type = [
        'update_time' => 'timestamp',
        'create_time' => 'timestamp',
    ];
}
