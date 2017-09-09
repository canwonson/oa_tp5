<?php
namespace app\controller;
use app\controller\Base;

class Notice extends Base
{
    public function index()
    {
        set_url('/notice/index');
		//页面插件
        $plugin = ['date', 'table', 'titatoggle', 'sweetalert'];
        $conf_status = ['草稿', '已发布'];

		$param = input('param.');
        $param['start_time'] = input('param.start_time');
        $param['end_time'] = input('param.end_time');
        $where = $this->getWhere($param);
        $manager = $this->checkAccredit('notice', 'manager');
        if (!$manager) {
            $where['status'] = 1;
        }
        ($param['start_time'] && $param['end_time']) && $where['create_time'] = ['between', [strtotime($param['start_time']), strtotime($param['end_time'])]];
        ($param['start_time'] && !$param['end_time']) && $where['create_time'] = ['>=', strtotime($param['start_time'])];
        (!$param['start_time'] && $param['end_time']) && $where['create_time'] = ['<=', strtotime($param['end_time'])];

        $datas = model('notice')->where(['is_del'=>0])->where($where)->order('create_time desc')->paginate(15);

        //管理权限
    	return $this->fetch('index', ['plugin'=>$plugin, 'datas'=>$datas, 'param'=>$param, 'paginate'=>$datas->render(), 'manager'=>$manager, 'conf_status' => $conf_status]);
    }

    public function read($id)
    {
    	$data = model('notice')->where(['id'=>$id])->find();
    	return $this->fetch('read', ['data'=>$data]);
    }

    public function edit($id = null)
    {
        $manager = $this->checkAccredit('notice', 'manager');
        if(!$manager){
            return $this->error("您没有此权限！");
        }

    	$plugin = ['editor', 'select2', 'select2_user'];
		$data=[
			'title'   => '',
			'content' => '',
            'user_id' => get_user_id(),
			'show'    => [],
            'status'  => 0
		];
		$mode = 'add';
		if ($id) {
			$data = model('notice')->get($id);
			$mode = 'edit';
		}

		$users = model('user')->getUserList();
		$conf_list = $this->getConfList();

    	return $this->fetch('edit', ['plugin'=>$plugin, 'mode'=>$mode, 'data'=>$data, 'users'=>$users, 'conf_list'=>$conf_list]);
    }

    //保存操作
	public function save()
	{
        $manager = $this->checkAccredit('notice', 'manager');
        if(!$manager){
            return $this->error("您没有此权限！");
        }
		set_url('/notice/index');
		$this->_save('Notice');
	}

    public function send($id)
    {
        set_url('/notice/index');
        $plugin = ['select2', 'select2_user'];
        $manager = $this->checkAccredit('notice', 'manager');
        if(!$manager){
            return $this->error("您没有此权限！");
        }

        $users = model('user')->getUserList();
        $conf_list = $this->getConfList();

        $data = model('notice')->where(['id'=> $id])->find();
        return $this->fetch('send', ['data'=>$data, 'users'=>$users, 'conf_list'=>$conf_list, 'plugin' => $plugin]);
    }

    public function _send()
    {
        $data = input('post.');
        $notice = model('notice')->where(['id'=> $data['id']])->find();
        //插入邮件列表
        foreach ($data['show'] as $user_id) {
            $mail_data = [
                'title' => $notice['title'],
                'content' => $notice['content'],
                'address' => get_user_email($user_id)
            ];
            $result = db('email', [], false)->insert($mail_data);
            if (false == $result) {
                $this->success('发送任务添加失败!,失败的用户id:'.$user_id);
            }
        }
        if ($result) {
            model('notice')->where(['id'=> $data['id']])->setInc('send_count');
            $this->success('已添加发送任务','/notice/index');
        }
    }
}
