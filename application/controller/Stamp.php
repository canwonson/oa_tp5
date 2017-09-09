<?php
namespace app\controller;
use app\controller\Flow;

class Stamp extends Flow
{
    public function getParam()
    {
    	return ['conf_stamp_type' => [1 => '公章', 2 => '其他'], 'conf_is_outer' => ['否', '是']];
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

        $datas = model('stamp')->where(['status'=>1])->where($where)->paginate(15);
        foreach ($datas as &$data) {
            $data['project_id'] = get_project_id($data['user_id']);
            $data['duty_id'] = get_duty_id($data['user_id']);
        }
        $conf_type = $this->getParam();
        $conf_list = $this->getConfList();
        return $this->fetch('index', ['datas'=>$datas, 'plugin'=>$plugin, 'conf_list'=>$conf_list, 'param'=>$param, 'conf_type'=>$conf_type, 'paginate'=>$datas->render()]);
    }

    public function data($flow_id = null)
    {
        $data = [
            'stamp_type' => '',
            'stamp_name' => '',
            'is_outer' => '',
            'outer_time' => '',
			'use_reason' => '',
            'stamp_file_name' => ''
        ];
        if ($flow_id) {
            $data = model('Stamp')->get(['flow_id' => $flow_id]);
            $data && $data = $data->toArray();
            $data['outer_time'] = $data['outer_time'] == '1970-01-01 08:00' ? '' : $data['outer_time'];
        }
        return $data;
    }

    protected function saveData($data, $type=1)
    {
        $save_data = [
            'user_id'         => get_user_id(),
            'stamp_type'      => $data['stamp_type'],
            'stamp_name'      => $data['stamp_name'],
            'is_outer'        => $data['is_outer'],
            'outer_time'      => strtotime($data['outer_time']),
            'use_reason'      => $data['use_reason'],
            'stamp_file_name' => $data['stamp_file_name'],
            'status'          => 0
        ];

        $type == 1 && $save_data['flow_id'] = $data['flow_id'];
        return $save_data;
    }
}
