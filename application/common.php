<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

function list_to_tree($list, $root = 0, $pk = 'id', $pid = 'pid', $child = '_child') {
    // 创建Tree
    $tree = [];
    if (is_array($list)) {
        // 创建基于主键的数组引用
        $refer = [];
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = &$list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = 0;
            if (isset($data[$pid])) {
                $parentId = $data[$pid];
            }
            if ((string)$root == $parentId) {
                $tree[] = &$list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent = &$refer[$parentId];

                    $parent[$child][] = &$list[$key];
                }
            }
        }
    }
    return $tree;
}

function get_user_id()
{
    $user_id = session('user_auth.uid');
    return isset($user_id) ? $user_id : 0;
}

function get_user_name($user_id = null)
{
    $user_id = $user_id ? $user_id : get_user_id();
    $user_name = db('user', [], false)->where(['id' => $user_id])->value('name');
    return $user_name;
}

function get_duty_id($user_id = null)
{
	if ($user_id) {
		$duty_id = db('user', [], false)->where(['id' => $user_id])->value('duty_id');
	}else{
		$duty_id = session('user_auth.duty_id');
	}
    return isset($duty_id) ? $duty_id : 0;
}

function get_duty_name($duty_id = null)
{
    $duty_id = $duty_id ? $duty_id : get_duty_id();
    $duty_name = db('duty', [], false)->where(['id' => $duty_id])->value('name');
    return $duty_name;
}

function get_project_id($user_id = null)
{
	if ($user_id) {
		$project_id = db('user', [], false)->where(['id' => $user_id])->value('project_id');
	}else{
		$project_id = session('user_auth.project_id');
	}
    return isset($project_id) ? $project_id : 0;
}

function get_project_name($project_id = null)
{
    $project_id = $project_id ? $project_id : get_project_id();
    $project_name = db('project', [], false)->where(['id' => $project_id])->value('name');
    return $project_name;
}

function get_position_id($user_id = null)
{
	if ($user_id) {
		$position_id = db('user', [], false)->where(['id' => $user_id])->value('position_id');
	}else{
		$position_id = session('user_auth.position_id');
	}
    return isset($position_id) ? $position_id : 0;
}

function get_position_name($position_id = null)
{
    $position_id = $position_id ? $position_id : get_position_id();
    $position_name = db('position', [], false)->where(['id' => $position_id])->value('name');
    return $position_name;
}

function get_role_id($user_id = null)
{
    if ($user_id) {
        $role_id = db('user', [], false)->where(['id' => $user_id])->value('role_id');
    }else{
        $role_id = session('user_auth.role_id');
    }
    return isset($role_id) ? $role_id : 0;
}

function get_role_name($role_id = null)
{
    $role_id = $role_id ? $role_id : get_role_id();
    $role_name = db('role', [], false)->where(['id' => $role_id])->value('name');
    return $role_name;
}

function set_url($str)
{
	session('url', $str);
}

function get_user_email($user_id = null)
{
    $user_id = $user_id ? $user_id : get_user_id();
    $email = db('user', [], false)->where(['id' => $user_id])->value('email');
    if (!$email) {
        $account = db('user', [], false)->where(['id' => $user_id])->value('account');
        $email = $account . "@txitech.com";
    }
    return isset($email) ? $email : '';
}

function get_account_name($user_id = null)
{
    $user_id = $user_id ? $user_id : get_user_id();
    $account = db('user')->where(['id' => $user_id])->value('account');
    return isset($account) ? $account : '';
}

/**
 * 发放后台邮件
 * @param string $accounts 后台帐号，多个帐号用;隔开
 * @param string $title 邮件标题
 * @param string $content 邮件内容
 * @return boolean
 */
function send_mail($accounts, $title, $content){
    import('mailer.PHPMailer', EXTEND_PATH);
    try {
        $mailer = new \PHPMailer(true);
        $mailer->IsSMTP();
        $mailer->SMTPAuth = true;
        $mailer->Host = config('mail_host');
        $mailer->Port = config('mail_port');
        $mailer->Username = config('mail_user');
        $mailer->Password = config('mail_pwd');
        $mailer->From = config('mail_user');
        $mailer->FromName = config('mail_from_name');
        $mailer->CharSet = 'utf-8';
        $mailer->Timeout = 10;
        $mailer->Subject = $title;//邮件主题
        $mailer->WordWrap = 80;
        $mailer->MsgHTML(preg_replace('/\\\\/', '', $content));
        $mailer->IsHTML(true);

        $accounts = trim($accounts, ';');
        $accounts = explode(';', $accounts);
        foreach ($accounts as $account){
            $mailer->AddAddress($account);//批量添加邮件发送人
        }
        $mailer->Send();
        return true;
    }catch (phpmailerException $e){
        return false;
    }
}
