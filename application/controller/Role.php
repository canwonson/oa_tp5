<?php
namespace app\controller;
use app\controller\Base;

class Role extends Base
{
    public function index()
    {
    	$plugin = ['table', 'titatoggle', 'sweetalert'];
		$Role = model('Role');
        $datas = $Role->all();
        $this->assign('datas', $datas);
        return $this->fetch('index',['datas'=>$datas, 'plugin'=>$plugin]);
    }

    public function create()
    {
        $data = input('post.');
        if (empty($data)) {
            $data = [
                'name'    => '',
				'description' => '',
                'status'  => 1,
                'is_del' => 0,
            ];
        }
        return $this->fetch('create',['data'=>$data]);
    }

    public function edit($id)
    {
        $data = model('Role')->get($id);
        return $this->fetch('edit',['data'=>$data]);
    }

	public function auth($id)
	{
		$Menu = model('Menu');
		$field = 'id,name,pid,status';
		$menus = $Menu->getMenuList(1, $field);
		$data = model('Role')->get($id);
		$mids = model('RoleMenu')->where(['role_id' => $id])->column('menu_id');
		return $this->fetch('auth',['data'=>$data, 'mids'=>$mids, 'role_menus'=>$menus]);
	}

	public function auth_save()
	{
		$RoleMenu = model('RoleMenu');
		if (request()->isPost()) {
			$role_id = request()->param()['id'];
			$mid = request()->param()['mid'];
			if(empty($role_id)){
				$this->error('需要授权的角色不存在');
            }
			$RoleMenu->where(["role_id" => $role_id])->delete();

			if (is_array($mid) && count($mid)>0) {
				foreach ($mid as $v) {
					$data[] = [
						'role_id'   => $role_id,
						'menu_id'   => $v
					];
				}

				if (!empty($data)) {
                    if($RoleMenu->saveAll($data)){
                        $this->success('保存成功');
                    }else{
                        $this->error('保存失败');
                    }
				}
			}else {
				$this->error('没有接收到数据，执行清除授权成功！');
			}
		}
	}

}
