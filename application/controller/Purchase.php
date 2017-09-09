<?php
namespace app\controller;
use app\controller\Flow;
use think\Loader;

class Purchase extends Flow
{
    public function getParam()
    {

    }

    public function getPlugin()
    {
    	return ['upload'];
    }

    public function index()
    {
        //页面插件
        $plugin = ['date', 'table', 'flow_details'];

        $param = input('param.');
        $param['start_time'] = input('param.start_time', date('Y-m-01'));
        $param['end_time'] = input('param.end_time', date('Y-m-'.date('t')));
        $where = $this->getWhere($param);
        ($param['start_time'] && $param['end_time']) && $where['create_time'] = ['between', [strtotime($param['start_time']), strtotime($param['end_time'])]];
        ($param['start_time'] && !$param['end_time']) && $where['create_time'] = ['>=', strtotime($param['start_time'])];
        (!$param['start_time'] && $param['end_time']) && $where['create_time'] = ['<=', strtotime($param['end_time'])];

        $datas = model('purchase')->where(['status'=>1])->where($where)->paginate(15);
        foreach ($datas as &$data) {
            $data['project_id'] = get_project_id($data['user_id']);
            $data['duty_id'] = get_duty_id($data['user_id']);
            $list = json_decode($data['param'],true);
            $num_total = $money_total = 0;
            foreach ($list as $item) {
                $num_total += $item['item_num'];
                $money_total += $item['item_price']*$item['item_num'];
            }
            $data['num_total'] = $num_total;
            $data['money_total'] = $money_total;
            $data['category_total'] = count($list);
        }
        $conf_type = $this->getParam();
        $conf_list = $this->getConfList();
        return $this->fetch('index', ['datas'=>$datas, 'plugin'=>$plugin, 'conf_list'=>$conf_list, 'param'=>$param, 'conf_type'=>$conf_type, 'paginate'=>$datas->render()]);
    }

    public function updateConfirm()
    {
        $total_price = input('total_price/d', 0);
        $data      = [
            'total_price' => $total_price
        ];
        $FlowConfig = model('FlowConfig');
        $flow       = $FlowConfig->getFlow(7, $data);
        $flow_show  = implode(' -> ', $flow['flow_show']);
        return $flow_show;
    }

    public function data($flow_id = null)
    {
        $data = [
            'param' =>  [],
            'files'      => [],
            'files_id'   => substr(microtime(1), -8) * 10000
        ];
        if ($flow_id) {
            $data = model('Purchase')->get(['flow_id' => $flow_id]);
            $total_price = 0;
            if ($data) {
            	$data['param'] = json_decode($data['param'], true);
	            $files         = [];
	            $files         = db('file')->where(['controller'=>'flow', 'controller_id'=>$flow_id, 'is_del'=>0])->column('file_url, file_name', 'id');
				$data['files'] = $files;
                foreach ($data['param'] as $param) {
                    $total_price += $param['item_num'] * $param['item_price'];
                }
            }
            $data && $data = $data->toArray();
            $data['total_price'] = $total_price;
        }
        return $data;
    }

    protected function saveData($data, $type=1)
    {
        $validate = Loader::validate('Purchase');
        foreach ($data['item_name'] as $key => $item_name) {
            $param['item_name']  = $item_name;
            $param['item_num']   = $data['item_num'][$key];
            $param['is_tax']     = isset($data['is_tax'][$key]) ? $data['is_tax'][$key] : 0;
            $param['item_price'] = $data['item_price'][$key];
            $param['item_way']   = $data['item_way'][$key];
            $param['reason']     = $data['reason'][$key];
            $param['remark']     = $data['remark'][$key];
            if(!$validate->check($param)){
                $this->ajaxError($validate->getError());
            }
            $param_list[$key]    = $param;
        }
        $data['param'] = json_encode($param_list);
        $save_data = [
            'user_id' => get_user_id(),
            'param'   => $data['param'],
            'status'  => 0
        ];

        $type == 1 && $save_data['flow_id'] = $data['flow_id'];
        return $save_data;
    }
}
