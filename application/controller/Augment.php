<?php
namespace app\controller;
use app\controller\Flow;

class Augment extends Flow
{
    public function getParam()
    {
    	$conf_list = $this->getConfList();
    	$auth_type = [1=>'编制外增员', 2=>'编制内增员', 3=>'替换', 4=>'离职补充'];
    	return ['project_list'=>$conf_list['project_list'], 'duty_list'=>$conf_list['duty_list'], 'auth_type'=>$auth_type];
    }

    public function getPlugin()
    {
        return ['date', 'sweetalert'];
    }

    public function index()
    {
        //页面插件
        $plugin = ['date', 'table', 'flow_details'];

        $param = input('param.');
        $param['start_time'] = input('param.start_time', date('Y-m-01'));
        $param['end_time'] = input('param.end_time', date('Y-m-'.date('t')));
        $where = $this->getWhere($param);
        ($param['start_time'] && $param['end_time']) && $where['create_time'] = ['between', [strtotime($param['start_time']), strtotime($param['end_time'])+86400]];
        ($param['start_time'] && !$param['end_time']) && $where['create_time'] = ['>=', strtotime($param['start_time'])];
        (!$param['start_time'] && $param['end_time']) && $where['create_time'] = ['<=', strtotime($param['end_time'])+86400];

        $datas = model('augment')->where(['status'=>1])->where($where)->paginate(15);
        foreach ($datas as &$data) {
            $data['project_id'] = get_project_id($data['user_id']);
            $data['duty_id'] = get_duty_id($data['user_id']);
        }
        $auth_type = $this->getParam()['auth_type'];
        $conf_list = $this->getConfList();
        return $this->fetch('index', ['datas'=>$datas, 'plugin'=>$plugin, 'conf_list'=>$conf_list, 'param'=>$param, 'auth_type'=>$auth_type, 'paginate'=>$datas->render()]);
    }

    public function details()
    {
        $param = input('param.');
        $flow = $param['id'];
        $where = [];
        $demand = model('augment')->where(['status'=>1])->where(['flow_id'=>$flow])->value('demand');
        return view('details', ['demand'=>$demand]);
    }

    public function data($flow_id = null)
    {
        $data = [
			'project_id'   => '',
			'duty_id'      => '',
			'apply_number' => '',
			'now_number'   => '',
			'reach_time'   => '',
			'min_pay'      => '',
			'max_pay'      => '',
			'auth_type'    => '',
			'demand'       => ''
        ];
        if ($flow_id) {
            $data = model('Augment')->get(['flow_id' => $flow_id]);
            $data && $data = $data->toArray();
        }
        return $data;
    }

    protected function saveData($data, $type=1)
    {
        $save_data = [
            'user_id'      => get_user_id(),
            'project_id'   => $data['project_id'],
            'duty_id'      => $data['duty_id'],
            'apply_number' => $data['apply_number'],
            'now_number'   => $data['now_number'],
            'reach_time'   => strtotime($data['reach_time']),
            'min_pay'      => $data['min_pay'],
            'max_pay'      => $data['max_pay'],
            'auth_type'    => $data['auth_type'],
            'demand'       => $data['demand'],
            'status'       => 0
        ];

        $type == 1 && $save_data['flow_id'] = $data['flow_id'];
        return $save_data;
    }

    public function getBudget()
    {
        $param = input('get.');
        $max_budget  = model('project')->where(['id'=>$param['project_id']])->sum('budget');
        $cur_budget  = model('budget')->where(['project_id'=>$param['project_id']])->sum('budget');
        //剩余预算
        $budget = $max_budget - $cur_budget;
        return $budget;
    }
}
