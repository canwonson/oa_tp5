<?php
namespace app\model;
use app\model\Common;
use think\Session;

class Menu extends Common
{
    public function getMenuList($type = 0, $filed = '')
    {
        if ($type == 1) {
            $obj_list = $this->field($filed)->order('sort asc')->select();
			$list = [];
            foreach ($obj_list as $value) {
                $list[] = $value->toArray();
            }
        }else {
            $auth            = new \util\Auth();
            $super           = $auth::isSuper();
            $where['status'] = 1;
            if (!$super) {
                $access = Session::get('access');
                $access = empty($access) ? [-1] : $access;
                $where  = ['id'=>['in', $access]];
            }
			$filed = $filed == '' ? 'id,pid,name,controller,action,sort,is_show,icon' : $filed;
            $obj_list = $this->field($filed)->where($where)->order('sort asc')->select();
			$list = [];
            foreach ($obj_list as $value) {
                $list[] = $value->toArray();
            }
        }
        $datas = list_to_tree($list, 0, 'id', 'pid', 'menus');
        return $datas;
    }

    public function getModusList()
    {
        $where['pid'] = 0;
        $list = $this->where($where)->column('name','id');
        return $list;
    }

}
