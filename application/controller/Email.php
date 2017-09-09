<?php
namespace app\controller;
use app\controller\Base;

class Email extends Base
{
    public function index()
    {
    	$plugin = ['table'];
        $datas = model('Email')->order('create_time desc')->paginate(15);
        return $this->fetch('index',['datas'=>$datas, 'plugin'=>$plugin, 'paginate'=>$datas->render()]);
    }

}
