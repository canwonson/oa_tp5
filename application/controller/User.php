<?php
namespace app\controller;
use app\controller\Base;

class User extends Base
{
    public function index()
    {
        $plugin = ['table', 'titatoggle', 'sweetalert'];
        set_url('/user/index');
        $param = input('param.');
        $where = [];
        $where['is_del'] = 0;
        (isset($param['user']) && !empty($param['user'])) && $user_id = model('user')->where(['name|account|id'=>$param['user']])->column('id');
        (isset($param['user']) && !empty($param['user']) && empty($user_id)) && $user_id = -1;
        !empty($user_id) && $where['id'] = ['in', $user_id];

        if ((isset($param['project_id']) && !empty($param['project_id']))) {
            $map['project_id'] = $param['project_id'];
            if (isset($where['id'])) {
                $map['id'] = $where['id'];
            }
            $user_id = model('user')->where($map)->column('id');
            $where['id'] = empty($user_id) ? ['in', -1] : ['in', $user_id];
        }

        if ((isset($param['duty_id']) && !empty($param['duty_id']))) {
            $map['duty_id'] = $param['duty_id'];
            if (isset($where['id'])) {
                $map['id'] = $where['id'];
            }
            $user_id = model('user')->where($map)->column('id');
            $where['id'] = empty($user_id) ? ['in', -1] : ['in', $user_id];
        }
        if ((isset($param['position_id']) && !empty($param['position_id']))) {
            $map['position_id'] = $param['position_id'];
            if (isset($where['id'])) {
                $map['id'] = $where['id'];
            }
            $user_id = model('user')->where($map)->column('id');
            $where['id'] = empty($user_id) ? ['in', -1] : ['in', $user_id];
        }

        if ((isset($param['role_id']) && !empty($param['role_id']))) {
            $map['role_id'] = $param['role_id'];
            if (isset($where['id'])) {
                $map['id'] = $where['id'];
            }
            $user_id = model('user')->where($map)->column('id');
            $where['id'] = empty($user_id) ? ['in', -1] : ['in', $user_id];
        }
        $datas = model('User')->where($where)->select();
        $conf_list = $this->getConfList();
        return $this->fetch('index',['datas'=>$datas, 'conf_list'=>$conf_list, 'param'=>$param, 'plugin'=>$plugin]);
    }

    public function edit($id = null)
    {
        set_url('/user/index');
        $plugin = ['date','select2', 'icheck', 'icheck_radio'];
        $data=[
            'name'        => '',
            'account'     => '',
            'project_id'  => '',
            'duty_id'     => '',
            'position_id' => '',
            'role_id'     => '',
            'status'      => 1,
            'entry_time'  => '',
            'create_time' => date('Y-m-d H:i:s', time()),
        ];
        $mode = 'add';
        if ($id) {
            $data = model('user')->get($id);
            $mode = 'edit';
        }
        $conf_list = $this->getConfList();
        return $this->fetch('edit', ['data' => $data, 'mode'=>$mode, 'plugin'=>$plugin, 'conf_list'=> $conf_list]);
    }

    public function passwd()
    {
        set_url('/');
        $data = [
            'user_id' => get_user_id()
        ];
        return $this->fetch('', ['data' => $data]);
    }

    public function passwdSave()
    {
        set_url('/');
        $password = input('post.');
        if (empty($password['new_password'])) {
            $this->error('新密码不能为空!');
        }
        if ($password['new_password'] !== $password['confirm_password']) {
            $this->error('新密码与确认密码不一样!');
        }

        if ($password['new_password'] == $password['old_password']) {
            $this->error('新密码与原密码不能不一样!');
        }

        $where = [
            'id' => $password['user_id']
        ];

        $count = db('user')->where($where)->where(['password' => md5($password['old_password'])])->count();

        if ($count) {
            $result = db('user')->where($where)->update(['password' => md5($password['new_password'])]);
            if ($result) {
                $this->success('修改密码成功!');
            }else{
                $this->error('修改密码失败!');
            }
        }else{
            $this->error('原密码错误!');
        }
    }

    //获取用户姓名拼音账号
    public function getAccount()
    {
        $name = input('post.name');
        $PinYin = new \util\Pinyin();
        $pinyin = $PinYin->getAllPY($name);
        return $pinyin;
    }

    public function reset(){
        $id = input('post.id/d', 0);
        if(!$id){
          $this->error('参数错误');
        }
        $result = model('user')->where(['id' => $id])->setField('password',md5(123456));
        if($result){
          $this->success('重置密码成功');
        }else{
          $this->error('重置密码失败');
        }
    }

  //   public function info()
  //   {
  //       $info = model('User')->field('id, account, create_time, duty_id, email, entry_time, name, position_id, project_id, status, tel')->where(['id'=>input('get.id')])->find();
		// $data = $info->toArray();
		// $role_list = [];
  //       foreach ($info->roles as $role) {
  //           $role_list[] = $role['id'];
  //       }
  //       $data['role_id'] = $role_list;
  //       return json($data);
  //   }

  //   //插入新数据
  //   protected function _insert($name, $data, $field = true)
  //   {
  //       $User = model('User');
  //       //保存对象
  //       $roles = $data['roles'];
		// //插入用户表数据
		// $result = $User->allowField($field)->save($data);
		// //插入用户权限中间表数据
		// $user = $User->get($User->id);
		// $user->roles()->saveAll($roles);
  //       if (false !== $result) {
  //           $this -> success('新增成功!');
  //       }else {
  //           $this -> error('新增失败!');
  //       }
  //   }

  //   //更新数据
  //   protected function _update($name = null, $data, $field = true)
  //   {
  //       $User = model('User');
  //       if (!isset($data['id'])) {
  //           $this -> error('编辑失败-0001!');
  //       }
  //       $id = $data['id'];
  //       //更新用户表数据
  //       $result = $User->allowField($field)->save($data,['id' => $id]);
		// //更新用户权限中间表数据
		// $user = $User->get($id);
		// foreach ($User->roles as $role) {
  //           $roles[] = $role['id'];
  //       }
  //       !empty($roles) && $User->roles()->detach($roles);
  //       $user->roles()->saveAll($data['roles']);
  //       if (false !== $result) {
  //           //成功提示
  //           $this -> success('编辑成功!');
  //       } else {
  //           //错误提示
  //           $this -> error('编辑失败-0002!');
  //       }
  //   }

}
