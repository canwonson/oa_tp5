<?php
namespace app\controller;
use app\controller\Base;
use think\Loader;
use think\Config;

class Flow extends Base
{
	private static $status = [
		0 => '审核中', 1 => '通过', 2 => '拒绝', 3 => '回退', 4 => '不建议', 5 => '请求撤销', 6 => '已撤销', 7 => '拒绝撤销'
	];

	//撤销申请配置
	private static $repeal_conf = [
		'type' => [1],//可撤销类型
		'status' => [0, 1, 3, 4]//可撤销的状态
	];

	//流程文件配置
	private static $file_flow = [
		'Leave',
		'Purchase'
	];

	private function checkAccess($flow_id, $action='read')
	{
		$user_id = get_user_id();
		$where['flow_id'] = $flow_id;
		if ($action == 'read') {
			$Auth = new \util\Auth();
			$pandect_check = $Auth::check('flow', 'pandect');
			$count = model('Flow')->where(['user_id'=>$user_id, 'id'=>$flow_id])->count();
			$log_count = model('FlowLog')->where($where)->where(['user_id'=>$user_id, 'is_del'=>0])->count();
			$report_count = model('FlowReport')->where($where)->where(['uid'=>$user_id])->count();
			if ($count || $log_count || $pandect_check || $report_count) {
				return true;
			}
		}
		if ($action == 'confirm') {
			$count = model('FlowLog')->where($where)->where(['user_id'=>$user_id, 'is_del'=>0])->where('result is null or result=5')->count();
			if ($count) {
				return true;
			}
		}
		return false;
	}

	//流程申请主页面
    public function index()
    {
        $FlowConfig = model('FlowConfig');
        $tag_list = $FlowConfig->getFlowList(1, ['id','name','classify','controller']);
        return $this->fetch('index', ['tag_list'=>$tag_list]);
    }

	//流程申请管理页面
	public function manage()
	{
		$FlowConfig = model('FlowConfig');
        $tag_list = $FlowConfig->getFlowList(0, ['id','name','classify','controller', 'status']);
        return $this->fetch('manage', ['tag_list'=>$tag_list]);
	}

	//流程组管理页面
	public function classify($id = null)
	{
		$plugin = ['select2'];
		$FlowClassify = model('FlowClassify');
		$data=[
			'name' => '',
			'status' => 1,
			'is_del' =>0
		];
		$mode = 'add';
		if ($id) {
			$data = $FlowClassify->get($id);
			$mode = 'edit';
		}

		return $this->fetch('classify', ['data' => $data, 'mode'=>$mode, 'plugin'=>$plugin]);
	}

	//流程组保存操作
	public function classifySave()
	{
		$name = 'FlowClassify';
		$this->_save($name);
	}

	//流程配置页面
	public function config($id = null, $cid =null)
	{
		$plugin = ['select2', 'icheck', 'icheck_radio'];
		$FlowConfig = model('FlowConfig');
		$FlowAudit = model('FlowAudit');
		$data = [
			'name' => '',
			'status' => 1,
			'classify' => $cid,
			'report_user' => '',
			'controller' => ''
		];
		$mode = 'add';
		if ($id) {
			$data = $FlowConfig->get($id);
			$audits = $FlowAudit->where(['type' => $id, 'is_del'=>0])->column('name, status, audit_conf', 'id');
			foreach ($audits as $key => &$audit) {
				$audit_conf = explode(',', $audit['audit_conf']);
				$conf = [];
				foreach ($audit_conf as $step => $row) {
					$conf[$step] = explode('|', $row);
				}
				$flow_name = $FlowConfig->getFlowName($conf);
				$audit_name = implode(' -> ', $flow_name);
				$audit['audit_name'] = $audit_name;
			}
			$data['audits'] = $audits;
			$mode = 'edit';
		}
		$users = model('user')->getUserList();

		$user_list = model('user')->column('name', 'id');
		$unset_user = [];
		// foreach ($user_list as $user_id => $name) {
		// 	$flow = $FlowConfig->getFlow($id, [], null, $user_id);
		// 	if (empty($flow['confirm_list'][0])) {
		// 		$unset_user[] = $name . "[{$user_id}]";
		// 	}
		// }
		return $this->fetch('config', ['data' => $data, 'mode'=>$mode, 'plugin'=>$plugin, 'users'=>$users, 'unset_user' => $unset_user]);
	}

	//流程配置保存操作
	public function configSave()
	{
		set_url('/flow/manage');
		$name = 'FlowConfig';
		$this->_save($name);
	}

	//流程走向配置页面
	public function audit($id = null, $tid =null)
	{
		$plugin = ['select2', 'sweetalert'];
		$FlowAudit = model('FlowAudit');
		$FlowAuditConfig = model('FlowAuditConfig');
		$data=[
			'name'        => '',
			'audit_conf'  => '',
			'type'        => $tid,
			'status'      => 1,
			'project_id'  => [],
			'duty_id'     => [],
			'position_id' => []
		];
		$mode = 'add';
		if ($id) {
			$data = $FlowAudit->get($id);
			//获取相关配置
			$data['project_id'] = $FlowAuditConfig->where(['audit_id' => $id])->column('project_id');
			$data['duty_id'] = $FlowAuditConfig->where(['audit_id' => $id])->column('duty_id');
			$data['position_id'] = $FlowAuditConfig->where(['audit_id' => $id])->column('position_id');
			$mode = 'edit';
		}
		$conf_list = model('Common')->getConfList();
		return $this->fetch('audit', ['data' => $data, 'mode'=>$mode, 'conf_list'=>$conf_list, 'plugin'=>$plugin]);
	}

	//流程走向保存操作
	public function auditSave()
	{
		$id = input('post.id');
		$type = input('post.type');
		set_url('/flow/config/id/'.$type);
		$FlowAuditConfig = model('FlowAuditConfig');
		if (!$id) {
			$name = 'FlowAudit';
			$this->_save($name, true);
			exit;
		}
		$project_id = input('post.project_id/a');
		$duty_id = input('post.duty_id/a');
		$position_id = input('post.position_id/a');
		if (is_array($project_id) && count($project_id)>0 && is_array($duty_id) && count($duty_id)>0 && is_array($position_id) && count($position_id)>0) {
			$FlowAuditConfig->where(['audit_id'=>$id, 'type'=>$type])->delete();
			for ($i=0; $i < count($project_id); $i++) {
				for ($d=0; $d < count($duty_id); $d++) {
					for ($p=0; $p < count($position_id); $p++) {
						$data[] = [
							'project_id'  => $project_id[$i],
							'duty_id'     => $duty_id[$d],
							'position_id' => $position_id[$p],
							'type'        => $type,
							'audit_id'    => $id
						];
					}
				}
			}
			if (!empty($data)) {
				if($FlowAuditConfig->saveAll($data)){
					$name = 'FlowAudit';
					$this->_save($name, true);
				}else{
					$this->error('适用配置保存失败');
				}
			}
		}else {
			$this->error('适用配置不能为空!');
		}
	}

	//流程走向删除标记操作
	public function auditDel()
	{
		$id   = input('post.id');
		$type = input('post.type');
		set_url('/flow/config/id/'.$type);
		$FlowAuditConfig = model('FlowAuditConfig');
		$FlowAuditConfig->where(['audit_id'=>$id, 'type'=>$type])->delete();
		$name = 'FlowAudit';
		$this->_del($name, true);
	}

	//流程申请(公用方法)
    public function apply()
    {
		$FlowConfig      = model('FlowConfig');
		$config          = $FlowConfig->getConfig();
		$config['param'] = $this->getParam();
		$plugin          = $this->getPlugin();
        $data = [
            'title' => $config['name'].date('YmdHis').get_user_name(),
        ];
        $default_data = $this->data();
        $default_data && $data += $default_data;
		$show = [
			'log'     => 0,
			'confirm' => 0
		];
		$confirm = $this->getConfirm();
		$urls    = $this->getUrl('apply');
		if (in_array('select2_user', $plugin)) {
			$users = model('user')->getUserList();
			$this->assign('users', $users);
		}

        return $this->fetch('apply', ['data'=>$data, 'config'=>$config, 'mode'=>'add', 'show'=>$show, 'confirm'=>$confirm, 'plugin'=>$plugin, 'urls'=>$urls]);
    }

    public function flowSave()
    {
		$request = request();
		$controller = $request->controller();
		$this->_flow_save($controller);
    }

	public function _flow_save($name = null)
    {
    	$type = model('FlowConfig')->where(['controller' => strtolower($name)])->value('id');
		$data = input('post.');
        $validate = Loader::validate($name);
        if(!$validate->check($data)){
            $this->ajaxError($validate->getError());
        }
        if (method_exists($this, 'extCheck')) {
        	$check = $this->extCheck($data);
	        if (!$check['res']) {
	            $this->ajaxError($check['msg']);
	        }
        }
		switch ($data['mode']) {
            case 'add':
                unset($data['mode']);
                $flow_id = $this->flowAdd($data['title'], $type);
                $data['flow_id'] = $flow_id;
				if(method_exists($this, 'extSave')) {
		        	$save = $this->extSave($data);
					if (!$save['res']) {
			            $this->ajaxError($save['msg']);
			        }
		        }
                $this->_details_insert($name, $data);
                break;

            case 'edit':
                unset($data['mode']);
                $data['flow_id'] =$data['id'];
				if(method_exists($this, 'extDel')) {
					$this->extDel($data['flow_id']);
		        	$save = $this->extSave($data);
					if (!$save['res']) {
			            $this->ajaxError($save['msg']);
			        }
		        }
                $this->_details_update($name, $data);
                break;

            default:
               $this->ajaxError("非法操作");
        }
    }

    //插入数据
	protected function _details_insert($name = null, $data, $filed = false)
	{
		$save_data = $this->saveData($data);
		$result    = model($name)->save($save_data);
		if (false == $result) {
			$this->ajaxError("数据库表{$name}写入数据错误");
		}
        if (in_array($name, self::$file_flow)) {
        	$img_result = controller('file')->update_id($data['files_id'], 'flow', $data['flow_id']);
	        if (true !== $img_result) {
	            $this->ajaxError($img_result);
	        }
        }
        $this->nextStep($data['flow_id']);
        if (false !== $result) {
            $this->success("提交成功!", '/flow/submit');
        }
	}

	//更新数据
    protected function _details_update($name=null, $data, $filed=false)
    {
        $model = model($name);
        if (!isset($data['id'])) {
            $this -> ajaxError('没有找到id');
        }
		$result = $this->flowUpdate($data);
		if (false == $result) {
			$this -> ajaxError('操作flowUpdate错误');
		}
		$save_data = $this->saveData($data, 0);
        //保存对象
        $result = $model->save($save_data, ['flow_id' => $data['id']]);
        if (false !== $result) {
            //成功提示
            $this->nextStep($data['id']);
            $this -> success('修改成功!', '/flow/submit');
        } else {
            //错误提示
            $this -> ajaxError('修改失败-0002!');
        }
    }

	//已提交页面
	public function submit()
	{
		$plugin = ['sweetalert'];
		$Flow  = model('Flow');
		$where = ['user_id' => get_user_id(), 'is_del' => 0];
		$datas = $Flow->where($where)->field('id, title, type, step, status, create_time, flow_log')->order('create_time desc')->paginate(15);
		$list = [];
		foreach ($datas as $data) {
			$data   = $this->getFlowData($data, 'read');
			if (!empty($data['flow_log'])) {
				$flow_log = explode('-', $data['flow_log']);
				$data['step'] = end($flow_log);
			}
			$list[] = $data;
		}
		return $this->fetch('submit', ['list' => $list,'paginate'=>$datas->render(), 'plugin'=>$plugin]);
	}

	//待审核页面
	public function confirm()
	{
		$plugin = [];
		$FlowLog = model('FlowLog');
		$Flow    = model('Flow');
		$log_list = $FlowLog->where(['user_id' => get_user_id(), 'is_del' => 0])->where('result is null or result = 5')->column('flow_id');
		$list = [];
		$paginate = '';
		if ($log_list) {
			$where = ['id' => ['in', $log_list], 'is_del' => 0];
			$datas = $Flow->where($where)->field('id, title, type, step, status, create_time, flow_log')->order('create_time desc')->paginate(15);
			foreach ($datas as $data) {
				$data   = $this->getFlowData($data, 'confirmRead');
				if (!empty($data['flow_log'])) {
					$flow_log = explode('-', $data['flow_log']);
					$data['step'] = end($flow_log);
				}
				$list[] = $data;
			}
			$paginate = $datas->render();
		}
		return $this->fetch('confirm', ['list'=>$list, 'paginate'=>$paginate, 'plugin'=>$plugin]);
	}

	//已审核页面
	public function confirmed()
	{
		$plugin = [];
		$FlowLog = model('FlowLog');
		$Flow    = model('Flow');
		$log_list = $FlowLog->where(['user_id' => get_user_id(), 'is_del' => 0])->where('result is not null and result != 5')->column('flow_id');
		$list = [];
		$paginate = '';
		if ($log_list) {
			$where = ['id' => ['in', $log_list], 'is_del' => 0];
			$datas = $Flow->where($where)->field('id, title, type, step, status, create_time, flow_log')->order('create_time desc')->paginate(15);
			foreach ($datas as $data) {
				$data   = $this->getFlowData($data, 'read');
				if (!empty($data['flow_log'])) {
					$flow_log = explode('-', $data['flow_log']);
					$data['step'] = end($flow_log);
				}
				$list[] = $data;
			}
			$paginate = $datas->render();
		}
		return $this->fetch('confirmed', ['list'=>$list, 'paginate'=>$paginate, 'plugin'=>$plugin]);
	}

	protected function getFlowData($flow_data, $action){
		$FlowConfig              = model('FlowConfig');
		$info                    = $FlowConfig->where(['id' => $flow_data['type']])->field('name,controller')->find();
		$data                    = controller($info['controller'])->data($flow_data['id']);
		$flow                    = $FlowConfig->getFlow($flow_data['type'], $data, $flow_data['id']);
		$flow_data['int_step']   = $flow_data['step'];
		$flow_data['int_status'] = $flow_data['status'];
		$flow_data['int_type']   = $flow_data['type'];
		$flow_data['step']       = isset($flow['flow_name'][$flow_data['step']]) ? $flow['flow_name'][$flow_data['step']] : $flow_data['step'];
		$flow_data['status']     = self::$status[$flow_data['status']];
		$flow_data['type']       = $info['name'];
		$flow_data['url']        = url('/' . $info['controller'] . '/' . $action,['id' => $flow_data['id']]);

		return $flow_data;
	}

	//流程查看页面
	public function read($id)
	{
		$check           = $this->checkAccess($id);
		if (!$check) {
			return $this->error("您没有此权限！");
		}
		$Flow            = model('Flow');
		$FlowConfig      = model('FlowConfig');
		$flow            = $Flow->get($id);
		$config          = $FlowConfig->getConfig($flow['type']);
		$config['param'] = $this->getParam();
		$plugin          = $this->getPlugin();
		$data            = [
			'id'    => $id,
			'title' => $flow['title']
        ];
		$flow_data          = $this->data($id);
		$flow_data && $data += $flow_data;

		$show               = ['log' => 1, 'confirm' => 0];
		$mode               = $flow['is_edit'] && (get_user_id() == $flow_data['user_id']) ? 'edit' : 'read';
		$confirm            = $this->getConfirm($id);
		$flow_log           = $this->getFlowLog($id);
		$urls               = $this->getUrl('apply');
		$comment_conf       = $FlowConfig->getCommentConf($id);
		$is_file            = false;
		($flow['user_id'] == get_user_id()) && $is_file = true;
		//是否可以撤销
		$able_repeal = 0;
		if (in_array($flow['type'], self::$repeal_conf['type']) && in_array($flow['status'], self::$repeal_conf['status']) && $flow_data['user_id'] == get_user_id()) {
			$able_repeal = 1;
		}
		$able_forced_repeal = 0;
		$manager = $this->checkAccredit('flow', 'pandect');
		if ($manager) {
			$able_forced_repeal = 1;
		}
		if (in_array('select2_user', $plugin)) {
			$users = model('user')->getUserList();
			$this->assign('users', $users);
		}
        return $this->fetch('apply', ['data'=>$data, 'config'=>$config, 'mode'=>$mode, 'show'=>$show, 'confirm'=>$confirm, 'flow_log'=>$flow_log, 'plugin'=>$plugin, 'urls'=>$urls, 'comment_conf'=>$comment_conf, 'able_repeal'=>$able_repeal, 'able_forced_repeal' => $able_forced_repeal, 'is_file' => $is_file]);
	}

	//流程审核查看页面
	public function confirmRead($id)
	{
		set_url('/flow/confirm');
		$check           = $this->checkAccess($id, 'confirm');
		if (!$check) {
			return $this->error("您没有此权限！");
		}
		$Flow            = model('Flow');
		$FlowConfig      = model('FlowConfig');
		$flow            = $Flow->get($id);
		$config          = $FlowConfig->getConfig($flow['type']);
		$config['param'] = $this->getParam();
		$plugin          = $this->getPlugin();
		$flow_info       = $Flow->where(['id' => $id])->field('step, title, type, status')->find();
        $data = [
			'id'     => $id,
			'step'   => $flow_info['step'],
			'title'  => $flow_info['title'],
			'status' => $flow_info['status']
        ];
		$flow_data          = $this->data($id);
		$flow_data && $data += $flow_data;
		$show               = ['log' => 1, 'confirm' => 1];
		$mode               = 'confirm';
		$confirm            = $this->getConfirm($id);
		$confirm_list       = explode(' -> ', $confirm);
		$flow_log           = $this->getFlowLog($id);
		$urls               = $this->getUrl('confirm');
		$comment_conf       = $FlowConfig->getCommentConf($id);
		$is_file            = false;
		if (in_array('select2_user', $plugin)) {
			$users = model('user')->getUserList();
			$this->assign('users', $users);
		}
		//按钮显示
		$button = [
			'agree' => 1,
			'reject' => 1,
			'doubt' => 1,
			'reconfirm' => 1,
		];

		$request = request();
		$controller = $request->controller();
		if ($controller == 'Handover') {
			$button = [
				'agree' => 1,
				'reject' => 0,
				'doubt' => 0,
				'reconfirm' => 1,
			];
		}
		//审核模板
		$comment_content = $FlowConfig->getCommentContent($id);
        return $this->fetch('apply', ['data'=>$data, 'config'=>$config, 'mode'=>$mode, 'show'=>$show, 'confirm'=>$confirm, 'flow_log'=>$flow_log, 'confirm_list'=>$confirm_list, 'plugin'=>$plugin, 'urls'=>$urls, 'comment_conf'=>$comment_conf, 'is_file' => $is_file, 'comment_content' => $comment_content, 'button' => $button]);
	}

	//获取审核日志方法
	public function getFlowLog($id)
	{
		$where =[
			'flow_id' => $id,
			'is_del'  => 0,
			'result'  => ['exp', 'is not null and `result`!=5']
		];
		$data = db('flow_log')->where($where)->field('user_id, step, result, comment, update_time')->order('create_time asc')->select();

		foreach ($data as &$value) {
			$value['str_result'] = self::$status[$value['result']];
			$value['comment'] = explode('|', $value['comment']);
		}
		return $data;
	}

	//获取显示流程信息
	public function getConfirm($id = null)
    {
		$data =[];
		$FlowConfig = model('FlowConfig');
		$type = $FlowConfig->getConfig()['id'];
		if ($id) {
			$data = $this->data($id);
		}
        $flow = $FlowConfig->getFlow($type, $data, $id);
		$flow_show  = implode(' -> ', $flow['flow_show']);
        return $flow_show;
    }

	//流程新增操作
	public function flowAdd($title, $type)
	{
        $data_flow = [
			'title'    => $title,
			'type'     => $type,
			'user_id'  => get_user_id(),
			'step'     => 0,
			'status'   => 0,
			'is_del'   => 0,
			'is_edit'  => 1,
			'flow_log' => '发起申请',
        ];
        $Flow = model('Flow');
        $Flow->data($data_flow);
        $Flow->save();
        $flow_id = $Flow->id;
        if (!$flow_id) {
            $this->error("提交失败-0001!请重试或联系管理员");
        }else {
        	return $flow_id;
        }
	}

	//审核下一步操作
	public function nextStep($flow_id)
	{
		$FlowConfig = model('FlowConfig');
		$Flow       = model('Flow');
		$flow_data  = $Flow->get($flow_id);
		$data       = $this->data($flow_id);
		$cur_step   = $flow_data['step'];
		$flow_conf  = $FlowConfig->getFlow($flow_data['type'], $data, $flow_data['id']);
		$step       = $FlowConfig->getStep($cur_step, $flow_conf['confirm_list']);

		if (is_array($step) && $step[0] == 'last') {
			//更新流程步骤
			$flow          = $Flow->get($flow_id);
			$flow->step    = $step[1]+1;
			$flow->status  = 1;
			$flow->is_edit = 0;
			$flow->flow_log = $flow->flow_log . '-审核完成';
			$flow->save();

			//更新流程资料状态
			$flow_controller = $FlowConfig->where(['id'=>$flow_data['type']])->value('controller');
			$model           = model(ucfirst($flow_controller));
			$model->save(['status'=>1],['flow_id'=>$flow_id]);
			//更新拓展库
			if(method_exists($this, 'extChange')) {
				$this->extChange($data);
			}
			//插入审核报告
			$this->flowReport($flow_id, $flow_data['type']);
			//邮件通知
			$report_user = $FlowConfig->where(['id'=>$flow_data['type']])->value('report_user');
			$this->send_mail(2, $flow_id, $flow_data['user_id']);
			$this->send_mail(2, $flow_id, $report_user);
			$this->send_weixin(2, $flow_id, $flow_data['user_id']);
			$this->send_weixin(2, $flow_id, $report_user);
			//不同流程额外信息发送
			foreach ($flow_conf["confirm_list"] as $confirm) {
				if ($confirm['send'] == 1) {
					$this->send_mail(2, $flow_id, $confirm['id']);
					$this->send_weixin(2, $flow_id, $confirm['id']);
				}
			}
		}else{
			$user_id  = $flow_conf['confirm_list'][$step]['id'];
			$step     = $step+1;
			$data_log = [
				'flow_id' => $flow_id,
				'user_id' => $user_id,
				'step'    => $step,
				'is_del'  => 0
			];
			$FlowLog = model('FlowLog');
			$FlowLog->data($data_log);
			$FlowLog->save();

			//更新流程步骤
			$flow = $Flow->get($flow_id);
			$flow->step = $step;
			$flow->flow_log = $flow->flow_log . "-" . $flow_conf['flow_show'][$step];
			$flow->save();

			//邮件通知
			$this->send_mail(1, $flow_id, $user_id);
			$this->send_weixin(1, $flow_id, $user_id);
		}
	}

	//流程更新操作
	public function flowUpdate($data)
	{
		$result = $this->delLog($data['id']);
		if (false == $result) {
			$this->ajaxError("操作delLog出现错误");
		}
		//更新流程表
		$Flow   = model('Flow');
		$result = $Flow->where(['id' => $data['id']])->update(['step' => 0, 'status'=> 0]);
		if (false == $result) {
			$this->ajaxError("操作flowUpdate更新流程表出现错误");
		}

		return true;
	}

	//待审核的flow_log对象
	public function getConfirmFlowLogObject($flow_id, $list = false)
	{
		$where  = [
			'flow_id' => $flow_id,
			'is_del'  => 0,
			'result'  => ['exp', 'is null or result=5']
		];
		!$list && $where['user_id'] = get_user_id();
		$result = true;
		$list   = $this->getFlowLogObject($where);

		return $list;
	}

	//获取flow_log对象
	public function getFlowLogObject($where)
	{
		$FlowLog = model('FlowLog')->where($where)->select();
		return $FlowLog;
	}

	//审核日志更新操作
	public function updateLog($obj_list, $data)
	{
		foreach ($obj_list as $row) {
			$result = $row->save($data);
			if (false == $result) {
				$this->error('操作:updateLog更新数据错误');
			}
		}

		return $result;
	}

	//审核日志标记位操作
	public function delLog($flow_id)
	{
		$obj_list = $this->getConfirmFlowLogObject($flow_id, true);
		if (!empty($obj_list)) {
			$result = $this->updateLog($obj_list, ['is_del' => 1]);
			if (false == $result) {
				$this->error("操作:updateLog失败");
			}
		}
		return true;
	}

	//审核流程同意操作
	public function agree()
	{
		$id       = input('post.id');
		$comments = input('post.comment/a');
		if (count($comments) == 1 && empty($comments[0])) {
			$comments[0] = '同意';
		}
		if (!$id) {
			$this->error('缺少参数(id)！');
		}
		$comment_check = $this->checkComment($id,$comments);
		if ($comment_check !== true) {
			$this->error($comment_check);
		}

		$Flow   = model('Flow');
		//流程当前状态
		$status = $Flow->where(['id' => $id])->value('status');
		if ($status == 5) {
			$result = $this->_repeal($id);
		}else{
			$comment  = implode('|', $comments);
			$obj_list = $this->getConfirmFlowLogObject($id);
			if (!empty($obj_list)) {
				$result = $this->updateLog($obj_list, ['result' => 1, 'comment' => $comment]);
				if (false == $result) {
					$this->error("操作:updateLog失败");
				}
			}

			$Flow->update(['id' => $id, 'is_edit' => 0]);
			$this->nextStep($id);
		}
		if (false !== $result) {
			$this->success('审核成功!', '/flow/confirm');
		}else {
            $this->error('审核失败!');
        }
	}

	//审核流程拒绝操作
	public function reject()
	{
		$id      = input('post.id');
		$comments = input('post.comment/a');
		if (count($comments) == 1 && empty($comments[0])) {
			$comments[0] = '拒绝';
		}
		if (!$id) {
			$this->error('缺少参数(id)！');
		}
		$comment_check = $this->checkComment($id,$comments);
		if ($comment_check !== true) {
			$this->error($comment_check);
		}
		$comment          = implode('|', $comments);
		$flow_status      = 2;
		$Flow             = model('Flow');
		//流程当前状态
		$status = $Flow->where(['id' => $id])->value('status');
		//请求撤销状态
		if ($status == 5) {
			$flow_status   = 7;
			$comment = '拒绝撤销';
		}
		$obj_list = $this->getConfirmFlowLogObject($id);
		if (!empty($obj_list)) {
			$result = $this->updateLog($obj_list, ['result' => 0, 'comment' => $comment]);
			if (false == $result) {
				$this->error("操作:updateLog失败");
			}
		}
		$Flow->update(['id' => $id, 'is_edit' => 0, 'status'=>$flow_status]);
		//更新流程资料状态
		$flow_data         = model('Flow')->where(['id'=>$id])->field('type, user_id')->find();
		$flow_controller   = model('FlowConfig')->where(['id'=>$flow_data['type']])->value('controller');
		$model             = model(ucfirst($flow_controller));
		$model->save(['status'=>2],['flow_id'=>$id]);
		if ($status !== 5) {
			//更新拓展库
			if(method_exists($this, 'extChange')) {
				$data = $this->data($id);
				$this->extChange($data, 2);
			}
			//邮件通知
			$this->send_mail(3, $id, $flow_data['user_id']);
			$this->send_weixin(3, $id, $flow_data['user_id']);
		}
		if (false !== $result) {
			$this->success('审核成功!', '/flow/confirm');
		}else {
            $this->error('审核失败!');
        }
	}

	//审核流程回退操作
	public function reconfirm()
	{
		$id      = input('post.id');
		$restep  = input('post.restep');
		$comments = input('post.comment/a');
		if (!$id) {
			$this->error('缺少参数(id)！');
		}
		if (count($comments) == 1 && empty($comments[0])) {
			$comments[0] = '回退';
		}
		$comment_check = $this->checkComment($id,$comments);
		if ($comment_check !== true) {
			$this->error($comment_check);
		}
		$comment 		  = implode('|', $comments);
		$Flow             = model('Flow');
		$where            = ['flow_id'=>$id, 'user_id'=>get_user_id(), 'is_del'=>0];
		$FlowLog          = model('FlowLog')->where($where)->where('result is null')->find();
		$FlowLog->comment = $comment;
		$FlowLog->result  = 3;
		$result           = $FlowLog->save();
		$data             = ['id' => $id, 'status'=>3, 'step'=>$restep];
		$restep           == 0 && $data['is_edit'] = 1;
		$Flow->update($data);
		//邮件通知
		if ($restep == 0 ) {
			$user_id = $Flow->where(['id' => $id])->value('user_id');
			$this->send_mail(4, $id, $user_id);
			$this->send_weixin(4, $id, $user_id);
		}
		if (false !== $result) {
			$this->success('审核成功!', '/flow/confirm');
		}else {
            $this->error('审核失败!');
        }
	}

	//审核流程存疑操作
	public function doubt()
	{
		$id       = input('post.id');
		$comments = input('post.comment/a');
		if (count($comments) == 1 && empty($comments[0])) {
			$comments[0] = '不建议';
		}
		if (!$id) {
			$this->error('缺少参数(id)！');
		}
		$comment_check = $this->checkComment($id,$comments);
		if ($comment_check !== true) {
			$this->error($comment_check);
		}
		$comment = implode('|', $comments);
		$Flow             = model('Flow');
		$where            = ['flow_id' => $id, 'user_id'=>get_user_id(), 'is_del'=>0];
		$FlowLog          = model('FlowLog')->where($where)->where('result is null')->find();
		$FlowLog->comment = $comment;
		$FlowLog->result  = 4;
		$result           = $FlowLog->save();
		$Flow->update(['id' => $id, 'is_edit' => 0]);
		$this->nextStep($id);
		if (false !== $result) {
			$this->success('审核成功!', '/flow/confirm');
		}else {
            $this->error('审核失败!');
        }
	}

	public function applyRepeal($id)
	{
		set_url('/flow/submit');
		$flow_data = model('flow')->where(['id' => $id])->find();
		if (!in_array($flow_data['type'], self::$repeal_conf['type'])) {
			$this->error('该类型申请不能撤销!');
		}
		if (in_array($flow_data['status'], self::$repeal_conf['status'])) {
			//判断是否审核中
			if (in_array($flow_data['status'], [1])) {
				//已通过的需申请
				$result = model('flow')->where(['id' => $id])->update(['status' => 5]);
				if (!$result) {
					$this->error('flow表更新失败或已是申请审核状态');
				}
				//生成审核流水
				//撤销人
				$user_id   = model('FlowConfig')->where(['id'=>$flow_data['type']])->value('repeal_user');
				$data_log = [
					'flow_id' => $id,
					'user_id' => $user_id,
					'result'  => 5,
					'step'    => 0,
					'is_del'  => 0
				];
				$FlowLog = model('FlowLog');
				$FlowLog->data($data_log);
				$result = $FlowLog->save();
				if ($result) {
					$this->success('撤销申请已提交!');
				}else {
					$this->error('撤销申请提交失败');
				}
			}else {
				$result = $this->_repeal($id);
				if ($result) {
					$this->success('撤销成功!', session('url'));
				}else{
					$this->success('撤销失败!');
				}
			}
		}else {
			$cur_status = self::$status[$flow_data['status']];
			$this->error("当前状态为:{$cur_status},不能进行撤销操作");
		}
	}
	//强制撤销
	public function forcedRepeal($id)
	{
		$result = $this->_repeal($id);
		if ($result) {
			$this->success('撤销成功!', session('url'));
		}else{
			$this->success('撤销失败!');
		}
	}

	public function _repeal($id)
	{
		$count = model('flow')->where(['id' => $id, 'status' => 6])->count();
		if ($count) {
			$this->error('此流程目前是已撤销状态,请勿重复操作!');
		}
		$result = model('flow')->where(['id' => $id])->update(['status' => 6, 'is_edit' => 0]);
		if (!$result) {
			$this->error('table flow update false');
		}
		//设置flow_log状态(审核中)
		$confirm_log = model('FlowLog')->where(['flow_id' => $id,'is_del' => 0])->where('result is null or result=5')->find();
		if ($confirm_log) {
			if ($confirm_log['result'] == 5) {
				$result          = model('FlowLog')->where(['flow_id' => $id,'is_del' => 0])->where('result=5')->update(['comment'=>'撤销','result'=>1]);
			}elseif ($confirm_log['result'] == null) {
				$result          = model('FlowLog')->where(['flow_id' => $id,'is_del' => 0])->where('result is null')->update(['comment'=>'撤销','is_del'=>1]);
			}
			if (!$result) {
				$this->error('table flow_log update false');
			}
		}
		//更新流程资料状态
		$type            = model('flow')->where(['id' => $id])->value('type');
		$flow_controller = model('FlowConfig')->where(['id'=>$type])->value('controller');
		$model           = model(ucfirst($flow_controller));
		$result = $model->save(['status'=>6],['flow_id'=>$id]);
		if (!$result) {
			$this->error('table '. $model .' update false');
		}
		//更新拓展库
		if(method_exists($this, 'extChange')) {
			$data = $this->data($id);
			$this->extChange($data, 6);
		}
		if ($result) {
			return true;
		}
	}

	public function checkComment($id, $comments)
	{
		$comment_conf = model('FlowConfig')->getCommentConf($id);
		$step = model('Flow')->where(['id'=>$id])->value('step');
		if (isset($comment_conf[$step])) {
			foreach ($comment_conf[$step] as $key => $conf) {
				if ($conf['require'] && empty($comments[$key])) {
					return $conf['comment'] . '不能为空';
				}
			}
		}
		return true;
	}

	public function getUrl($type)
	{
		$controller = $this->request->controller();
		switch ($type) {
			case 'apply':
				$urls = [
					'submit'       => url($controller . '/flowSave'),
					'applyRepeal'       => url($controller . '/applyRepeal'),
					'forcedRepeal' => url($controller . '/forcedRepeal'),
				];
				break;
			case 'confirm':
				$urls = [
					'agree'     => url($controller . '/agree'),
					'reject'    => url($controller . '/reject'),
					'reconfirm' => url($controller . '/reconfirm'),
					'doubt'     => url($controller . '/doubt')
				];
				break;
			default:
				break;
		}

		return $urls;
	}

	public function pandect()
	{
		$plugin = ['date', 'page', 'sweetalert'];

		$param = input('param.');
        $param['start_time'] = input('param.start_time');
        $param['end_time'] = input('param.end_time');
        $param['page'] = input('param.page/d', 1);
        $where = $this->getWhere($param);
        ($param['start_time'] && $param['end_time']) && $where['create_time'] = ['between', [strtotime($param['start_time']), strtotime($param['end_time'])+86400]];
        ($param['start_time'] && !$param['end_time']) && $where['create_time'] = ['>=', strtotime($param['start_time'])];
        (!$param['start_time'] && $param['end_time']) && $where['create_time'] = ['<=', strtotime($param['end_time'])+86400];

		$Flow  = model('Flow');
		$where['is_del'] = 0;
		$datas = $Flow->where($where)->order('create_time desc')->paginate(15);
		$list = [];
		foreach ($datas as $data) {
			$data   = $this->getFlowData($data, 'read');
			$data['project_id'] = get_project_id($data['user_id']);
            $data['duty_id'] = get_duty_id($data['user_id']);
            if (!empty($data['flow_log'])) {
				$flow_log = explode('-', $data['flow_log']);
				$data['step'] = end($flow_log);
			}
			$list[] = $data;
		}
		$conf_list = $this->getConfList();
		$types = model('FlowConfig')->column('name','id');

		return $this->fetch('pandect', ['list' => $list, 'plugin' => $plugin, 'conf_list' => $this->getConfList(), 'paginate'=>$datas->render(), 'param'=>$param, 'types' => $types]);
	}

	public function flowReport($flow_id, $flow_type)
	{
		$report_user = model('FlowConfig')->where(['id' => $flow_type])->value('report_user');
		$data = [
			'flow_id' => $flow_id,
			'uid' => $report_user,
			'type' => $flow_type
		];

		model('FlowReport')->save($data);
	}

	public function report()
	{
		$plugin = [];
		$list = [];
        $where = ['uid' => get_user_id()];
		$report_list = model('FlowReport')->where($where)->column('flow_id');
		if ($report_list) {
			$map = ['id'=>['in', $report_list]];
			$datas = model('Flow')->where($map)->field('id, title, type, step, status, create_time')->order('create_time desc')->paginate(15);
			foreach ($datas as $data) {
				$data   = $this->getFlowData($data, 'read');
				$list[] = $data;
			}
			$this->assign('paginate',$datas->render());
		}
		return $this->fetch('report', ['list' => $list, 'plugin'=>$plugin]);
	}

	//邮件通知发送
	public function send_mail($type, $flow_id, $user_id = null, $content = ''){
		$flow_info = model('Flow')->field('title, user_id')->where(['id'=>$flow_id])->find();
		$user_id = isset($user_id) ? $user_id : $flow_info['user_id'];
		$url = 'http://'.$_SERVER["SERVER_NAME"];
		switch ($type) {
			case 1:
				$msg_title   = '流程审批[待审核]';
				$msg_contnet = '您好,有一条流程['.$flow_info["title"].']麻烦您审核.  链接：<a href="'.$url.'">跳到OA</a>';
				break;

			case 2:
				$msg_title   = '流程报告[通过]';
				$msg_contnet = '您好,有一条流程['.$flow_info["title"].']已通过审核.  链接：<a href="'.$url.'">跳到OA</a>';
				break;

			case 3:
				$msg_title   = '流程审批[拒绝]';
				$msg_contnet = '您好,有一条流程['.$flow_info["title"].']被拒绝.  链接：<a href="'.$url.'">跳到OA</a>';
				break;
			case 4:
				$msg_title   = '流程审批[回退]';
				$msg_contnet = '您好,有一条流程['.$flow_info["title"].']被回退至发起申请，请修改后重新提交.  链接：<a href="'.$url.'">跳到OA</a>';
				break;

			case 5:
				$msg_title   = '流程审批[撤销]';
				$msg_contnet = '您好,有一条流程['.$flow_info["title"].']被撤销并回退至发起申请，请修改后重新提交. 撤销理由：'.$content.' 链接：<a href="'.$url.'">跳到OA</a>';
				break;

			default:
				# code...
				break;
		}
		$email = get_user_email($user_id);
		$data = [
			'title'        => $msg_title,
			'content'      => $msg_contnet,
			'address'      => $email,
			'is_send'      => 0,
			'create_time'  => time(),
			'update_time' => time(),
		];
		model('Email')->insert($data);
	}

	public function send_weixin($type, $flow_id, $user_id = null, $content = ''){
		Config::load(CONF_PATH.'weixin.php');
		$flow_info    = model('Flow')->field('title, user_id, type')->where(['id'=>$flow_id])->find();
		$user_id      = isset($user_id) ? $user_id : $flow_info['user_id'];
		$url          = 'http://'.$_SERVER["SERVER_NAME"];
		$agent_id     = 26;
		$msg_type     = 'news';
		$weixin_id    = get_account_name($user_id);
		$sign['u']    = $weixin_id;
		$sign['t']    = time();
		ksort($sign);
		$sign['sign'] = md5(http_build_query($sign) . config('auth_key'));
		$controller   = model('FlowConfig')->where(['id'=>$flow_info['type']])->value('controller');
		$sign_str     = http_build_query($sign);
		switch ($type) {
			case 1:
				$params = [
					'title'       => '流程审批[待审核]',
					'description' => '您好,有一条流程['.$flow_info["title"].']麻烦您审核. 点击本消息跳转至OA。',
					'url'         => "{$url}/{$controller}/confirmRead/id/{$flow_id}/view/weixin?{$sign_str}",
					'picurl'      => '',
					'agentid'     => $agent_id
				];
				break;

			case 2:
				$params = array(
					'title'       => '流程报告[通过]',
					'description' => '您好,有一条流程['.$flow_info["title"].']已通过审核. 点击本消息跳转至OA。',
					'url'         => "{$url}/{$controller}/read/id/{$flow_id}/view/weixin?{$sign_str}",
					'picurl'      => '',
					'agentid'     => $agent_id,
					);
				break;

			case 3:
				$params = array(
					'title'       => '流程审批[拒绝]',
					'description' => '您好,有一条流程['.$flow_info["title"].']被拒绝. 点击本消息跳转至OA。',
					'url'         => "{$url}/{$controller}/read/id/{$flow_id}/view/weixin?{$sign_str}",
					'picurl'      => '',
					'agentid'     => $agent_id,
					);
				break;
			case 4:
				$params = array(
					'title'       => '流程审批[回退]',
					'description' => '您好,有一条流程['.$flow_info["title"].']被回退至发起申请，请修改后重新提交. 点击本消息跳转至OA。',
					'url'         => "{$url}/{$controller}/read/id/{$flow_id}/view/weixin?{$sign_str}",
					'picurl'      => '',
					'agentid'     => $agent_id,
					);
				break;

			case 5:
				$params = array(
					'title'       => '流程审批[撤销]',
					'description' => '您好,有一条流程['.$flow_info["title"].']被撤销并回退至发起申请，请修改后重新提交. 撤销理由：'.$content.' .点击本消息跳转至OA。',
					'url'         => "{$url}/{$controller}/read/id/{$flow_id}?{$sign_str}",
					'picurl'      => '',
					'agentid'     => $agent_id,
					);
				break;

			default:
				# code...
				break;
		}

		$data = array(
			'weixin_id'   => json_encode([$weixin_id]),
			'msg_type'    => $msg_type,
			'msg_params'  => json_encode($params),
			'is_send'     => 0,
			'create_time' => time(),
			'send_count'  => 0,
			'update_time' => time(),
			);
		model('weixin')->insert($data);
	}
}
