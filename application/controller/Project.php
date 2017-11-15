<?php
namespace app\controller;
use app\controller\Base;

class Project extends Base
{
	public function index()
	{
		$plugin = ['table', 'titatoggle', 'sweetalert'];
		$list = model('Project')->select();
		$datas = [];
		foreach ($list as $row) {
			$row['user_count'] = db('user', [], false)->where(['project_id'=>$row['id'], 'type' => ['in', [1, 2]], 'is_del' => 0, 'status' => 1])->count('id');
			$datas[] = $row;
		}
		return $this->fetch('index',['datas'=>$datas, 'plugin'=>$plugin]);
	}

	public function edit($id = null)
	{
		set_url('/project/index');
		$plugin = ['select2', 'icheck', 'icheck_radio'];
		$user_count = db('user', [], false)->where(['project_id'=>$id, 'type' => ['in', [1, 2], 'is_del' => 0]])->count('id');
		$data=[
			'name'        => '',
			'budget'      => 0,
			'cur_budget'  => 0,
			'budget_user' => 0,
			'status'      => 1,
			'leader_id'   => '',
		];
		$mode = 'add';
		if ($id) {
			$data = model('Project')->get($id);
			$mode = 'edit';
		}
		$data['user_count'] = $user_count;

		$users = model('user')->getUserList();

		return $this->fetch('edit', ['data' => $data, 'mode'=>$mode, 'plugin'=>$plugin, 'users'=>$users]);
	}

	public function budget($id)
	{
		$plugin  = ['table'];
		$dutys   = model('Duty')->select();
		$datas   = [];

		foreach ($dutys as $duty) {
			$where['duty_id']    = $duty['id'];
			$where['project_id'] = $id;
			$where['status']     = 1;
			$where['is_del']     = 0;
			$where['type']       = ['in', [1,2]];
			$duty['user']        = db('user', [], false)->where($where)->column('name', 'id');
			$duty['user_count']  = count($duty['user']);
			$duty['budget']      = model('budget')->where(['project_id'=>$id, 'duty_id'=>$duty['id']])->value('budget');
			if (count($duty['user']) > 0) {
				$datas[]         = $duty;
			}
		}
		return $this->fetch('', ['datas'=>$datas, 'plugin'=>$plugin, 'project' => $id]);
	}

	public function budgetSave()
	{
		$param = input('post.');
		$project_id = $param['project_id'];
		$duties =  $param['duty'];
		foreach ($duties as $duty_id => $budget) {
			$where = [
				'project_id' => $project_id,
				'duty_id'    => $duty_id
			];

			$count = db('budget', [], false)->where($where)->count();
			if ($count) {
				$result = model('budget')->where($where)->update(['budget'=>$budget, 'update_time'=>time()]);
			}else{
				$result = model('budget')->insert(['project_id'=>$project_id, 'duty_id'=>$duty_id, 'budget'=>$budget, 'update_time'=>time()]);
			}
			if ($result) {

			}
		}

		$this->success("提交成功!", '/project/index');
	}
}
