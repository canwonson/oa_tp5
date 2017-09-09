<?php
namespace app\controller;
use think\Controller;
use think\Db;

class Tool extends Controller
{
	public function clearFlow()
	{
		$tables = ['flow', 'augment', 'displacement', 'file', 'flow_log', 'flow_report', '`leave`', 'official', 'overtime', 'purchase', 'stamp', 'unusual'];

		foreach ($tables as $table) {
			Db::execute("TRUNCATE TABLE {$table};");
		}
	}
}
