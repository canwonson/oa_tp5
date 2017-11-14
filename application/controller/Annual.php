<?php
namespace app\controller;
use app\controller\Base;

class Annual extends Base{

    private static $annual_status     = [1=>'正常', 2=>'清空', 0=>'停用'];
    private static $annual_log_status = [0=>'未审核', 1=>'通过', 2=>'拒绝',6=>'已撤销'];

	public function index()
    {
    	$plugin = ['date', 'table'];

        $param = input('param.');
        $param['start_time'] = input('param.start_time');
        $param['end_time'] = input('param.end_time');
        $where = $this->getWhere($param);
        if (!empty($where['user_id'])) {
            $where['id'] = $where['user_id'];
            unset($where['user_id']);
        }
        ($param['start_time'] && $param['end_time']) && $where['entry_time'] = ['between', [strtotime($param['start_time']), strtotime($param['end_time'])+86400]];
        ($param['start_time'] && !$param['end_time']) && $where['entry_time'] = ['>=', strtotime($param['start_time'])];
        (!$param['start_time'] && $param['end_time']) && $where['entry_time'] = ['<=', strtotime($param['end_time'])+86400];
        $where['type'] = ['in', [1, 2]];
    	$Annual = model('Annual');
    	$datas = model('user')->where(['status'=>1])->where($where)->field('id as user_id, name, project_id, duty_id, entry_time')->order('entry_time asc')->select();
    	foreach ($datas as &$user) {
    		$info = $Annual->getUserAnnual($user['user_id'])['info'];
            //审核中的年假
            $auditing_annual_day = model('AnnualLog')->where(['user_id'=>$user['user_id'],'is_del'=>0,'status'=>0])->sum('leave_day');
            $info['auditing_day'] = $auditing_annual_day;
            if ($auditing_annual_day) {
                $info['used_day'] -= $auditing_annual_day;
                $info['residue_day'] += $auditing_annual_day;
            }
            $user['info'] = $info;
    	}

        return $this->fetch('index',['datas'=>$datas, 'param'=>$param, 'plugin'=>$plugin]);
    }

    public function details($user_id)
    {   $plugin = ['table'];

        $data = model('Annual')->getUserAnnual($user_id);
        //审核中的年假
        $auditing_annual_log = model('AnnualLog')->where(['user_id'=>$user_id,'is_del'=>0,'status'=>0])->column('sum(leave_day)', 'year');
        foreach ($data["list"] as &$user) {
            $user['auditing_day'] = isset($auditing_annual_log[$user['year']]) ? $auditing_annual_log[$user['year']] : 0;
            if ($user['auditing_day']) {
                $user['used_day'] -= $user['auditing_day'];
                $user['residue_day'] += $user['auditing_day'];
            }
        }
        $annual_log = model('AnnualLog')->where(['user_id'=>$user_id,'is_del'=>0])->select();
        return $this->fetch('details',['data'=>$data, 'annual_log'=>$annual_log, 'plugin'=>$plugin, 'annual_status'=>$this::$annual_status, 'annual_log_status'=>$this::$annual_log_status, 'auditing_annual_log' => $auditing_annual_log]);
    }
}
