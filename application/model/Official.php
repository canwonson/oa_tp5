<?php
namespace app\model;
use app\model\Common;

class Official extends Common
{
	protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';

    protected $type = [
        'update_time' => 'timestamp',
        'create_time' => 'timestamp',
		'entry_time' => 'timestamp:Y-m-d',
		'assess_time' => 'timestamp:Y-m-d'
    ];
}
