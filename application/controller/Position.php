<?php
namespace app\controller;
use app\controller\Base;

class Position extends Base
{
    public function index()
    {
    	$plugin = ['table', 'titatoggle', 'sweetalert'];
        $datas = model('Position')->select();
        return $this->fetch('index',['datas'=>$datas, 'plugin'=>$plugin]);
    }

    public function edit($id = null)
    {
    	$plugin = ['icheck', 'icheck_radio'];
		$data=[
			'name'      => '',
			'status'    => 1,
		];
		$mode = 'add';
		if ($id) {
			$data = model('Position')->get($id);
			$mode = 'edit';
		}

		return $this->fetch('edit', ['data' => $data, 'mode'=>$mode, 'plugin'=>$plugin]);
    }
}
