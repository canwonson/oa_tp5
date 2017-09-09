<?php
namespace app\controller;
use app\controller\Flow;

class Displacement extends Flow
{
    public function getParam()
    {
		$conf_list = $this->getConfList();
    	return ['project_list'=>$conf_list['project_list'], 'duty_list'=>$conf_list['duty_list']];
    }

    public function getPlugin()
    {
        return ['date_time'];
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

        $datas = model('displacement')->where(['status'=>1])->where($where)->paginate(15);;
        foreach ($datas as &$data) {
            $data['project_id'] = get_project_id($data['user_id']);
            $data['duty_id'] = get_duty_id($data['user_id']);
        }
        $conf_list = $this->getConfList();
        return $this->fetch('index', ['datas'=>$datas, 'plugin'=>$plugin, 'conf_list'=>$conf_list, 'param'=>$param, 'paginate'=>$datas->render()]);
    }

	public function updateConfirm()
    {
        $original_project_id = input('original_project_id/d', 0);
        $data      = [
            'original_project_id' => $original_project_id
        ];
        $FlowConfig = model('FlowConfig');
        $flow       = $FlowConfig->getFlow(8, $data);
        $flow_show  = implode(' -> ', $flow['flow_show']);
        return $flow_show;
    }

    public function data($flow_id = null)
    {
        $data = [
			'original_project_id' => '',
			'change_project_id'   => '',
			'change_duty_id'      => '',
			'change_user_id'      => '',
			'change_time'         => '',
			'demand'              => ''
        ];
        if ($flow_id) {
            $data = model('Displacement')->get(['flow_id' => $flow_id]);
            $data && $data = $data->toArray();
        }
        return $data;
    }

    protected function saveData($data, $type=1)
    {
        $save_data = [
            'user_id'             => get_user_id(),
            'original_project_id' => $data['original_project_id'],
            'change_project_id'   => $data['change_project_id'],
            'change_duty_id'      => $data['change_duty_id'],
            'change_user_id'      => $data['change_user_id'],
            'change_time'         => strtotime($data['change_time']),
            'demand'              => $data['demand'],
            'status'              => 0
        ];

        $type == 1 && $save_data['flow_id'] = $data['flow_id'];
        return $save_data;
    }

    protected function extCheck($data)
    {
        if ($data['original_project_id'] == get_project_id()) {
            return ['res'=>0, 'msg'=>'原部门不能为你所在部门'];
        }
        return ['res'=>1, 'msg'=>''];
    }
}
