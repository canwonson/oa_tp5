<?php
namespace app\model;
use app\model\Common;

class Handover extends Common
{
	protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';

    protected $type = [
        'update_time' => 'timestamp',
        'create_time' => 'timestamp',
		'entry_time' => 'timestamp:Y-m-d',
		'over_time' => 'timestamp:Y-m-d'
    ];
}
