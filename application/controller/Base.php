<?php
namespace app\controller;
use think\Controller;
use think\Loader;
use think\Session;

class Base extends Controller
{
	public function __construct()
	{
		parent::__construct();
		$Auth = new \util\Auth();
		$user = $Auth::isLogin();
		if($user){
			if(!$Auth::check()){
				return $this->error("您没有此权限！");
			}
		}elseif (input('?get.u')) {
			//sign登陆
			$param =[
				'u' => input('get.u'),
				't' => input('get.t'),
				'sign' => input('get.sign')
			];
			$user = $Auth::loginBySign($param);
			if(is_array($user) && !empty($user['id'])){
	            $user_auth = [
	                'uid'         => $user['id'],
	                'account'     => $param['u'],
	                'role_id'     => $user['role_id'],
	                'project_id'  => $user['project_id'],
	                'duty_id'     => $user['duty_id'],
	                'position_id' => $user['position_id']

	            ];
	            session('user_auth', $user_auth);
	        }else{
	            //登录失败
	            switch ($user){
	                case -1: $error = '帐号不存在或被禁用！'; break;
	                case -2: $error = '帐号已被锁定,请稍后再试！'; break;
	                case -3: $error = '密码错误!'; break;
	                case -4: $error = '帐号未分配权限角色！'; break;
	                case -6: $error = '帐号角色权限不存在或被禁用！'; break;
	                case -7: $error = '自动登录失败,请输入帐号密码！'; break;
	                case -8: $error = 'sign验证失败！'; break;
	                case -9: $error = '链接过期请重新获取！'; break;
	                default: $error = '系统错误,请与相关技术联系！'; break;
	            }
	            return $this->error($error);;
	        }
		}else{
			return $this->redirect(url("/login"));
		}
		if (!Session::has('view')) {
			$view = input('param.view/s', 'pc');
			Session::set('view', $view);
		}
	}

	public function logout()
	{
		$Auth = new \util\Auth();
		$Auth::logout();
		return $this->redirect(url("/login"));
	}

	protected function fetch($template = '', $vars = [], $replace = [], $config = [], $plugin = [])
	{
		$this->assign('menus', $this->menus());
		$this->assign('user_info', $this->getUserInfo());
		$vars['view'] = session('view');
		return $this->view->fetch($template, $vars, $replace, $config, $plugin);
	}

	public function menus()
	{
		$menus = $datas = model('Menu')->getMenuList();
		foreach ($menus as &$module) {
			$module['active'] = 0;
			$module['url'] = 'javascript:;';
			$module['badge'] = 0;
			if (isset($module['menus'])) {
				foreach ($module['menus'] as &$menu) {
					$menu['active'] = 0;
					$menu['url']    = '/' . $menu['controller'] . '/' . $menu['action'];
					if ($menu['controller'] == 'flow' && $menu['action'] == 'confirm') {
						$menu['badge'] = $this->badge_count_flow_todo();
						$module['badge'] += $menu['badge'];
					}
				}
			}
		}
		return $menus;
	}

	public function getUserInfo()
	{
		$info = [
			'username' => get_user_name(),
			'duty_name' => get_duty_name(),
			'project_name' => get_project_name(),
			'position_name' => get_position_name()
		];
		return $info;
	}

	public function badge_count_flow_todo()
	{
		$FlowLog = model('FlowLog');
		$count = $FlowLog->where(['user_id' => get_user_id(), 'is_del' => 0])->where('result is null or result = 5')->count('flow_id');
		return $count;
	}

	//改变状态操作
	public function switcher()
	{
		$request = request();
		$controller = $request->controller();
		$this -> _switcher($controller);
	}

	//改变状态
	public function _switcher($name = null)
	{
		$model = model($name);
		$data = [
			(string)input('post.name') => input('post.value'),
		];
		$result = $model -> save($data, ['id'=>input('post.id')]);
		if (false !== $result) {
			//成功提示
			$this -> success('操作成功!');
		} else {
			//错误提示
			$this -> error('操作失败!');
		}
	}

	//保存操作
	public function save()
	{
		$request = request();
		$controller = $request->controller();
		$this -> _save($controller);
	}

	public function _save($name = null, $filed = true)
	{
		$data = input('post.');
		$validate = Loader::validate($name);
		if(!$validate->check($data)){
			$this->error($validate->getError());
		}
		switch ($data['mode']) {
			case 'add':
				unset($data['mode']);
				$this -> _insert($name, $data, $filed);
				break;

			case 'edit':
				unset($data['mode']);
				$this -> _update($name, $data, $filed);
				break;

			default:
			   $this -> error("非法操作");
		}
	}

	//插入新数据
	protected function _insert($name = null, $data, $filed)
	{
		$model = model($name);
		//保存对象
		$result = $model->allowField($filed)->save($data);
		if (false !== $result) {
			$this -> success('新增成功!', session('url'));
		}else {
			$this -> error('新增失败!');
		}
	}

	//更新数据
	protected function _update($name = null, $data, $filed)
	{
		$model = model($name);
		if (!isset($data['id'])) {
			$this -> error('更新失败-0001!');
		}
		$id = $data['id'];
		//保存对象
		$result = $model->allowField($filed)->save($data,['id' => $id]);
		if (false !== $result) {
			//成功提示
			$this -> success('更新成功!', session('url'));
		} else {
			//错误提示
			$this -> error('更新失败-0002!');
		}
	}

	//删除标记操作
	public function del()
	{
		$request = request();
		$controller = $request->controller();
		$this -> _del($controller);
	}

	//删除标记
	protected function _del($name = null) {
		$model = model($name);
		$result = $model->save(['is_del'=>1], ['id'=>input('post.id')]);
		if (false !== $result) {
			$this -> success('删除成功!', session('url'));
			//成功提示
		} else {
			$this -> error('删除失败!');
			//错误提示
		}
	}

	//删除数据操作
	public function destroy()
	{
		$request = request();
		$controller = $request->controller();
		$this -> _destroy($controller);
	}

	//删除标记
	protected function _destroy($name = null) {
		$model = model($name);
		$result = $model->destroy(['id'=>input('post.id')]);
		if (false !== $result) {
			$this -> success('删除成功!', session('url'));
			//成功提示
		} else {
			$this -> error('删除失败!');
			//错误提示
		}
	}

	public function ajaxError($msg, $code = 0)
	{
		$this->result([], $code, $msg);
	}

	//获取查询条件
	protected function getWhere($param)
	{
		$where = [];
		(isset($param['user']) && !empty($param['user'])) && $user_id = model('user')->where(['name|account|id'=>$param['user']])->column('id');
		(isset($param['user']) && !empty($param['user']) && empty($user_id)) && $user_id = -1;
		!empty($user_id) && $where['user_id'] = ['in', $user_id];

		if ((isset($param['project_id']) && !empty($param['project_id']))) {
			$map['project_id'] = $param['project_id'];
			if (isset($where['user_id'])) {
				$map['id'] = $where['user_id'];
			}
			$user_id = model('user')->where($map)->column('id');
			$where['user_id'] = empty($user_id) ? ['in', -1] : ['in', $user_id];
		}

		if ((isset($param['duty_id']) && !empty($param['duty_id']))) {
			$map['duty_id'] = $param['duty_id'];
			if (isset($where['user_id'])) {
				$map['id'] = $where['user_id'];
			}
			$user_id = model('user')->where($map)->column('id');
			$where['user_id'] = empty($user_id) ? ['in', -1] : ['in', $user_id];
		}

		if ((isset($param['type']) && !empty($param['type']))) {
			$where['type'] = $param['type'];
		}

		return $where;
	}

	//配置列表
    public function getConfList(){

        $project_list   = model('Project')->where(['status'=>1])->column('name','id');
        $position_list  = model('Position')->where(['status'=>1])->column('name','id');
        $duty_list      = model('Duty')->where(['status'=>1])->column('name','id');
        $role_list      = model('Role')->where(['status'=>1])->column('name','id');

        $conf_list = [
            'project_list'  => $project_list,
            'position_list' => $position_list,
            'duty_list'     => $duty_list,
            'role_list'     => $role_list,
        ];

        return $conf_list;
    }

    //检测是否对单个功能有授权
    public function checkAccredit($controller, $action)
    {
    	$result = false;
		$manager_rid = db('menu')->where(['controller'=>$controller, 'action'=>$action])->value('id');
	    if (in_array($manager_rid, session('access'))) {
	    	$result = true;
	    }
		$Auth = new \util\Auth();
		$is_super = $Auth::isSuper();
	    if ($is_super) {
	    	$result = true;
	    }
	    return $result;
    }

}
