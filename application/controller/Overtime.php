<?php
namespace app\controller;
use app\controller\Flow;

class Overtime extends Flow
{
    public function getParam()
    {

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
        ($param['start_time'] && $param['end_time']) && $where['start_time'] = ['between', [strtotime($param['start_time']), strtotime($param['end_time'])+86400]];
        ($param['start_time'] && !$param['end_time']) && $where['start_time'] = ['>=', strtotime($param['start_time'])];
        (!$param['start_time'] && $param['end_time']) && $where['start_time'] = ['<=', strtotime($param['end_time'])+86400];

        $count = $datas = model('overtime')->where(['status'=>1])->where($where)->count('distinct user_id');
        $datas = model('overtime')->field('user_id, count(*) count, sum(overtime) as overtime')->where(['status'=>1])->where($where)->group('user_id')->paginate(15, $count);
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
        ($param['start_time'] && $param['end_time']) && $where['start_time'] = ['between', [strtotime($param['start_time']), strtotime($param['end_time'])]];
        ($param['start_time'] && !$param['end_time']) && $where['start_time'] = ['>=', strtotime($param['start_time'])];
        (!$param['start_time'] && $param['end_time']) && $where['start_time'] = ['<=', strtotime($param['end_time'])];
        $datas = model('overtime')->where(['status'=>1])->where($where)->select();
        return view('details', ['datas'=>$datas]);
    }

    public function data($flow_id = null)
    {
        $data = [
            'start_time' => '',
            'end_time' => '',
            'overtime' => '',
            'overtime_reason' => ''
        ];
        if ($flow_id) {
            $data = model('Overtime')->get(['flow_id' => $flow_id]);
            $data && $data = $data->toArray();
        }
        return $data;
    }

    protected function saveData($data, $type=1)
    {
        $save_data = [
            'user_id'         => get_user_id(),
            'start_time'      => strtotime($data['start_time']),
            'end_time'        => strtotime($data['end_time']),
            'overtime'        => $data['overtime'],
            'overtime_reason' => $data['overtime_reason'],
            'status'          => 0
        ];

        $type == 1 && $save_data['flow_id'] = $data['flow_id'];
        return $save_data;
    }
}
