<?php
namespace app\controller;
use app\controller\Base;

class Duty extends Base
{
    public function index()
    {
    	$plugin = ['table', 'titatoggle', 'sweetalert'];
        $datas = model('Duty')->select();
        return $this->fetch('index',['datas'=>$datas, 'plugin'=>$plugin]);
    }

    public function edit($id = null)
    {
    	$plugin = ['select2', 'icheck', 'icheck_radio'];
		$data=[
			'name'      => '',
			'status'    => 1,
		];
		$mode = 'add';
		if ($id) {
			$data = model('Duty')->get($id);
			$mode = 'edit';
		}

		return $this->fetch('edit', ['data' => $data, 'mode'=>$mode, 'plugin'=>$plugin]);
    }
}
