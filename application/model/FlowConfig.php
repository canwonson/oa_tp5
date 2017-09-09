<?php
namespace app\model;
use app\model\Common;
use app\model\FlowClassify;
use app\model\FlowAuditConfig;
use app\model\FlowAudit;
use app\model\Flow;

class FlowConfig extends Common
{
    /**
     * 获取流程列表
     * @param  array  $type  查询类型 0=所有 , 1=开启状态
     * @param  array  $field  查询字段
     * @return array  $result 流程列表
     */
    public function getFlowList($type = 0, $field = array())
    {
		$where = [];
		$type && $where = ['status' => 1];
		$classify = FlowClassify::where($where)->select();
		$list = [];
		foreach ($classify as $value) {
			$where['classify'] = $value['id'];
			$data['name'] = $value['name'];
			$data['status'] = $value['status'];
			$data['list'] = $this->field($field)->order('id')->where($where)->select();
			$type && $data['list'] = $this->checkShow($data['list']);
			($type == 0 ||count($data['list'])>0) && $list[$value['id']] = $data;
		}
		return $list;
    }

	/**
     * 检测是否显示
     * @param  array  $data  列表
     * @return array  $result 检测后列表
     */
	public function checkShow($data)
	{
		$where=[
 			'project_id' => get_project_id(),
 			'duty_id' => get_duty_id(),
 			'position_id' => get_position_id()
 		];
		foreach ($data as $num => $value) {
			$where['type'] = $value['id'];
		 	$count = FlowAuditConfig::where($where)->count();
			if (!$count) {
				unset($data[$num]);
			}
		}
		return $data;
	 }

    /**
     * 获取流程
     * @param  int    $type        流程类型
     * @param  array  $data        表单内容
     * @param  int    $id 		   流程id
     * @return str    $flow        流程
     */
    public function getFlow($type, $data = [], $id = null, $user_id = null)
    {
        if (!$user_id) {
            $user_id = $id ? db('flow', [], false)->where(['id'=>$id])->value('user_id') : get_user_id();
        }
		$where=[
           'project_id'  => get_project_id($user_id),
           'duty_id'     => get_duty_id($user_id),
           'position_id' => get_position_id($user_id),
           'type'        => $type
	    ];
        $audit_id     = db('flow_audit_config', [], false)->where($where)->value('audit_id');
        $audit_conf   = db('flow_audit', [], false)->where(['id'=>$audit_id])->value('audit_conf');
        $audit_conf   = explode(',', $audit_conf);
        $audit_conf   = $this->verifyFlow($audit_conf, $data);
        $confirm_list = $this->getConfirmList($audit_conf, $data);
        $flow_name    = $this->getFlowName($audit_conf);
        $cur_step     = 0;
        $id && $cur_step = db('flow', [], false)->where(['id'=>$id])->value('step');
        foreach ($flow_name as $step => $name) {
            if (isset($audit_conf[$step-1])) {
                switch ($audit_conf[$step-1][0]) {
                    case 'pr':
                        $name = $name . "({$confirm_list[$step-1]['name']})";
                        break;
                    case 'po':
                        $name = $name . "({$confirm_list[$step-1]['name']})";
                        break;
                    default:
                        # code...
                        break;
                }
            }
            if ($cur_step == $step) {
                $name = "[{$name}]";
            }
            $flow_show[$step] = $name;
        }
        $flow_list = [
            'flow_name'    => $flow_name,
            'confirm_list' => $confirm_list,
            'audit_conf'   => $audit_conf,
            'flow_show'    => $flow_show
        ];
        return $flow_list;
    }

    public function getCommentConf($id)
    {
        $type         = model('Flow')->where(['id' => $id])->value('type');
        $controller   = model('FlowConfig')->where(['id' => $type])->value('controller');
        $data         = controller(ucfirst($controller))->data($id);
        $comment_list = [];
        $confs = $this->getFlow($type, $data, $id)['audit_conf'];
        foreach ($confs as $step => $conf) {
            $comments = [];
            if (empty($conf[3])) {
                $info['comment'] = '意见';
                $info['require'] = 0;
                $info['show'] = 1;
                $comments[] = $info;
            }else{
                $comment_conf = explode('/', $conf[3]);
                foreach ($comment_conf as $comment) {
                    $argument = explode('-', $comment);
                    $info['comment'] = $argument[0];
                    $info['require'] = $argument[1];
                    $info['show'] = $argument[2];
                    $comments[] = $info;
                }
            }
            $comment_list[$step+1] = $comments;
        }
        return $comment_list;
    }

    /**
     * 流程验证
     * @param  array  $flows 流程列表
     * @param  array  $data  数据
     * @return array  $audit_list 验证后流程列表
     */
    public function verifyFlow($audit_conf, $data = [])
    {
        foreach ($audit_conf as $conf) {
			$conf = explode('|', $conf);
            if (isset($conf[2])) {
				$param = explode('/', $conf[2]);
                if (empty($param[1])) {
                    $audit_list[] = $conf;
                    continue;
                }
				!isset($data[$param[0]]) && $data[$param[0]] = 0;
                switch ($param[1]) {
                    case '>':
                        if ($data[$param[0]] >= $param[2]) {
                            $audit_list[] = $conf;
                        }
                        break;
                    case '<':
                        if ($data[$param[0]] < $param[2]) {
                            $audit_list[] = $conf;
                        }
                        break;

                    default:
                        # code...
                        break;
                }
            }else{
                $audit_list[] = $conf;
            }
        }
        return $audit_list;
    }

    /**
     * 获取审核意见列表
     * @param  array  $audit_conf 流程列表
     * @return array  $comments 验证后流程列表
     */
    public function getComment($audit_conf)
    {
        foreach ($audit_conf as $key => $conf) {
            if (isset($conf[3])) {
                $comments = explode('/', $conf[3]);
            }
        }
        return $comments;
    }

    /**
     * 获取节点名称
     * @param  array $flows 流程列表
     * @return array        节点名称列表
     */
    public function getFlowName($audit_conf)
    {
        $flow_list[]='发起申请';
        foreach ($audit_conf as $audit) {
            if ($audit[0] == 'po') {
                $position = get_position_name($audit[1]);
                $flow_list[] = $position;
            }
            if ($audit[0] == 'pr') {
                $flow_list[] = '部门负责人';
            }
            if ($audit[0] == 'ur') {
                $user_name = get_user_name($audit[1]);
                $flow_list[] = $user_name;
            }
        }
        $flow_list[] = '审核完成';
        return $flow_list;
    }

    /**
     * 获取审核列表
     * @param  array $flows 流程列表
     * @return array        审核列表
     */
    public function getConfirmList($flows, $data = [])
    {
        $confirm_list = [];
        foreach ($flows as $flow) {
            $confirm_list[] = $this->_conv_confirm($flow, $data);
        }
        return $confirm_list;
    }

    /**
     * 获取审核人
     * @param  array $flow 流程
     * @return array        审核人
     */
    public function _conv_confirm($audit, $data = [])
    {
        $position_id = isset($data['user_id']) ? get_position_id($data['user_id']) : get_position_id();
        $duty_id     = isset($data['user_id']) ? get_duty_id($data['user_id']) : get_duty_id();
        $project_id  = isset($data['user_id']) ? get_project_id($data['user_id']) : get_project_id();

        $User = db('user', [], false);
        $confirm = [];
        if ($audit[0] == 'po') {
            $position_id = $audit[1];
            $where = [];
            $where['duty_id'] = $duty_id;
            $where['position_id'] = $position_id;
            $where['project_id']  = $project_id;
            $where['is_del'] = 0;
            $user = [];
            $user = $User->where($where)->field('id, name')->find();
            if (!empty($user)) {
                $confirm = $user;
            }else {
                $audit[0] = 'pr';
            }
        }

        if ($audit[0] == 'ur') {
            $user_id = $audit[1];
            $where = [];
            $where['id'] = $user_id;
            $user = [];
            $user = $User->field('id,name')->where($where)->find();
            $confirm = $user;
        }
        if ($audit[0] == 'pr') {
            if (isset($audit[1])) {
                if (!empty($audit[1])) {
                    $param = explode('-', $audit[1]);
                    if ($param[0] == 'change' && isset($data[$param[1]])) {
                        $project_id = $data[$param[1]];
                    }
                }
            }
            $leader_id = db('project')->where(['id'=>$project_id])->value('leader_id');
            $where = [];
            $where['id'] = $leader_id;
            $user = [];
            $user = $User->field('id,name')->where($where)->find();
            $confirm = $user;
        }

        return $confirm;
    }


    public function getStep($step, $confirm_list)
    {
        $user_id = get_user_id();
        while (true) {
			//判断是否为最后一步
			if (!isset($confirm_list[$step])) {
				$step = ['last', $step];
				break;
			}
            if (!isset($confirm_list[$step]['id'])) {
                $step ++;
            }
            if ($confirm_list[$step]['id'] == $user_id) {
                $step ++;
                if ($step == count($confirm_list)) {
                    $step = ['last', $step];
                    break;
                }
            }else {
                break;
            }
        }
        return $step;
    }

    /**
     * 获取流程配置
     * @param  int $type 流程类型
     * @param  string $type 流程控制器
     * @return object       流程配置
     */
    public function getConfig($type = null)
    {
        if ($type) {
            $obj = $this->where(['id'=>$type])->find();
        }else{
            $controller = strtolower(request()->controller());
            $obj = $this->where(['controller' => $controller])->find();
        }

        return $obj;
    }
}
