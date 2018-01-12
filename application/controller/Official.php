<?php
namespace app\controller;
use app\controller\Flow;

class Official extends Flow
{
	public function getParam()
	{

	}

	public function getPlugin()
	{
		return ['date', 'sweetalert'];
	}

	public function index()
	{
		//页面插件
		$plugin = ['date', 'table', 'flow_details','page'];

		$param = input('param.');
		$param['start_time'] = input('param.start_time', date('Y-m-01'));
		$param['end_time'] = input('param.end_time', date('Y-m-'.date('t')));
		$param['page'] = input('param.page/d', 1);
		$where = $this->getWhere($param);
		($param['start_time'] && $param['end_time']) && $where['create_time'] = ['between', [strtotime($param['start_time']), strtotime($param['end_time'])+86400]];
		($param['start_time'] && !$param['end_time']) && $where['create_time'] = ['>=', strtotime($param['start_time'])];
		(!$param['start_time'] && $param['end_time']) && $where['create_time'] = ['<=', strtotime($param['end_time'])+86400];

		$datas = model('official')->where(['status'=>1])->where($where)->paginate(15);
		foreach ($datas as &$data) {
			$data['project_id'] = get_project_id($data['user_id']);
			$data['duty_id'] = get_duty_id($data['user_id']);
		}

		$conf_list = $this->getConfList();
		return $this->fetch('index', ['datas'=>$datas, 'plugin'=>$plugin, 'conf_list'=>$conf_list, 'param'=>$param, 'paginate'=>$datas->render()]);
	}

	public function details()
	{
		$param = input('param.');
		$where['flow_id'] = $param['flow_id'];
		$data = model('official')->field('self_estimate, self_plan, work_suggest, team_appraise')->where(['status'=>1])->where($where)->find();
		return view('details', ['data'=>$data]);
	}

	public function data($flow_id = null)
	{
		$data = [
			'entry_time'     => '',
			'assess_time'    => '',
			'probation_time' => '',
			'self_estimate'  => '',
			'self_plan'      => '',
			'work_suggest'   => '',
			'team_appraise'  => ''
		];
		if ($flow_id) {
			$data = model('Official')->get(['flow_id' => $flow_id]);
			$data && $data = $data->toArray();
		}
		return $data;
	}

	protected function saveData($data, $type=1)
	{
		$save_data = [
			'user_id'        => get_user_id(),
			'entry_time'     => strtotime($data['entry_time']),
			'assess_time'    => strtotime($data['assess_time']),
			'probation_time' => $data['probation_time'],
			'self_estimate'  => $data['self_estimate'],
			'self_plan'      => $data['self_plan'],
			'work_suggest'   => $data['work_suggest'],
			'team_appraise'  => $data['team_appraise'],
			'status'         => 0
		];

		$type == 1 && $save_data['flow_id'] = $data['flow_id'];
		return $save_data;
	}

	protected function extChange($data)
	{
		//更新用户状态
		$info = [
			'type' => 1,
			'formal_time' => strtotime($data['assess_time'])
		];
		db('user')->where(['id' => $data['user_id']])->update($info);
	}
}
