<?php
namespace app\controller;
use think\Controller;

class Login extends Controller
{
    public function index()
    {
        return view();
    }

    public function check()
    {
        $action = input('action');
        $account = input('post.account');
        $auth = new \util\Auth();

        switch ($action) {
            case 'auto':
                //自动登录
                $code = input('post.code');
                $user = $auth::loginByAuto($account, $code);
                break;
            default:
                //帐号密码登录
                $password = input('post.password');
                $auto = input('post.auto/d');
                if(!$account){
                    return $this->error('帐号不可以为空');
                }elseif(!$password){
                    return $this->error('请输入密码');
                }
                $user = $auth::loginByAccount($account, $password, $auto);
                break;
        }

        if(is_array($user) && !empty($user['id'])){
            $user_auth = [
                'uid'         => $user['id'],
                'account'     => $account,
                'role_id'     => $user['role_id'],
                'project_id'  => $user['project_id'],
                'duty_id'     => $user['duty_id'],
                'position_id' => $user['position_id']

            ];
            session('user_auth', $user_auth);
            cookie('account', $account, 86400*7);
            cookie('code', empty($user['code']) ? null : $user['code'], 86400*7);
            $this->success('登陆成功!', url('/'));
        }else{
            //登录失败
            switch ($user){
                case -1: $error = '帐号不存在或被禁用！'; break;
                case -2: $error = '帐号已被锁定,请稍后再试！'; break;
                case -3: $error = '密码错误!'; break;
                case -4: $error = '帐号未分配权限角色！'; break;
                case -6: $error = '帐号角色权限不存在或被禁用！'; break;
                case -7: $error = '自动登录失败,请输入帐号密码！'; break;
                default: $error = '系统错误,请与相关技术联系！'; break;
            }
            return $this->result([], 0, $error);;
        }
    }
}
