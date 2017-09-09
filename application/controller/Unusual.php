<?php
namespace app\controller;
use app\controller\Flow;

class Unusual extends Flow
{
	public function getParam()
    {
		return ['unusual_type'=>[1=>'因公',2=>'因私'], 'sign_type'=>[1=>'报道',2=>'签退']];
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
        ($param['start_time'] && $param['end_time']) && $where['unusual_time'] = ['between', [strtotime($param['start_time']), strtotime($param['end_time'])+86400]];
        ($param['start_time'] && !$param['end_time']) && $where['unusual_time'] = ['>=', strtotime($param['start_time'])];
        (!$param['start_time'] && $param['end_time']) && $where['unusual_time'] = ['<=', strtotime($param['end_time'])+86400];

        $count = $datas = model('unusual')->where(['status'=>1])->where($where)->count('distinct user_id');
        $datas = model('unusual')->field('user_id, count(*) count')->where(['status'=>1])->where($where)->group('user_id')->paginate(15, $count);
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
        $where = $this->getWhere($param);
        ($param['start_time'] && $param['end_time']) && $where['unusual_time'] = ['between', [strtotime($param['start_time']), strtotime($param['end_time'])]];
        ($param['start_time'] && !$param['end_time']) && $where['unusual_time'] = ['>=', strtotime($param['start_time'])];
        (!$param['start_time'] && $param['end_time']) && $where['unusual_time'] = ['<=', strtotime($param['end_time'])];
        $datas = model('unusual')->where(['status'=>1])->where($where)->select();
        $unusual_type = $this->getParam()['unusual_type'];
        $sign_type = $this->getParam()['sign_type'];
        return view('details', ['datas'=>$datas, 'unusual_type'=>$unusual_type, 'sign_type'=>$sign_type]);
    }

	public function data($flow_id = null)
	{
		$data = [
			'unusual_time' => '',
			'unusual_type' => '',
			'sign_type' => '',
			'describe' => ''
		];
		if ($flow_id) {
			$data = model('Unusual')->get(['flow_id' => $flow_id]);
			$data && $data = $data->toArray();
		}
		return $data;
	}

    protected function saveData($data, $type=1)
    {
        $save_data = [
            'user_id'      => get_user_id(),
            'unusual_time' => strtotime($data['unusual_time']),
            'unusual_type' => $data['unusual_type'],
            'sign_type'    => $data['sign_type'],
            'describe'     => $data['describe'],
            'status'       => 0
        ];

        $type == 1 && $save_data['flow_id'] = $data['flow_id'];
        return $save_data;
    }
}
