<?php
namespace app\controller;
use app\controller\Base;

class Menu extends Base
{
    public function index()
    {
        $plugin = ['table', 'titatoggle', 'sweetalert'];
        $datas = model('Menu')->getMenuList(1);
        $this->assign('datas', $datas);
        return $this->fetch('index',['datas'=>$datas, 'plugin'=>$plugin]);
    }

    public function create()
    {
        $data = input('post.');
        if (empty($data)) {
            $data = [
                'pid'     => 0,
                'name'    => '',
				'controller' => '',
                'action'     => '',
                'icon'    => '',
                'sort'    => 99,
                'status'  => 1,
                'is_show' => 1,
            ];
        }
        $modules = model('Menu')->getModusList();
        return $this->fetch('create',['data'=>$data, 'modules'=>$modules]);
    }

    public function edit($id)
    {
        $data = model('Menu')->where(['id'=>$id])->find();
        $modules = model('Menu')->getModusList();
        return $this->fetch('edit',['data'=>$data, 'modules'=>$modules]);
    }

}
