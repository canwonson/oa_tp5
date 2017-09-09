<?php
namespace app\controller;
use app\controller\Flow;

class Leave extends Flow
{
	public function getParam()
    {
		return ['leave_type'=>[1=>'事假',2=>'病假',3=>'年假',4=>'补休',5=>'婚假',6=>'丧假',7=>'其他']];
    }

	public function getPlugin()
	{
		return ['upload', 'date_time'];
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

        $count = $datas = model('leave')->where(['status'=>1])->where($where)->count('distinct user_id');
        $datas = model('leave')->field('user_id, count(*) count, sum(leave_day) as leave_day')->where(['status'=>1])->where($where)->group('user_id')->paginate(15, $count);
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
        $datas = model('leave')->where(['status'=>1])->where($where)->select();
        $leave_type = $this->getParam()['leave_type'];
        return view('details', ['datas'=>$datas, 'leave_type'=>$leave_type]);
    }

    public function updateConfirm()
    {
        $leave_day = input('leave_day/d', 0);
        $data      = [
            'leave_day' => $leave_day
        ];
        $FlowConfig = model('FlowConfig');
        $flow       = $FlowConfig->getFlow(1, $data);
        $flow_show  = implode(' -> ', $flow['flow_show']);
        return $flow_show;
    }

    public function data($flow_id = null)
    {
		$data = [
            'start_time' => '',
            'end_time'   => '',
            'describe'   => '',
            'leave_type' => '',
            'leave_day'  => '',
            'other_type' => '',
            'files'      => [],
            'files_id'   => substr(microtime(1), -8) * 10000
		];
		if ($flow_id) {
            $data          = model('Leave')->get(['flow_id' => $flow_id]);
            $data && $data = $data->toArray();
            $files         = [];
            $files         = db('file')->where(['controller'=>'flow', 'controller_id'=>$flow_id, 'is_del'=>0])->column('file_url, file_name', 'id');
			$data['files'] = $files;
            //补休数据
            if ($data['leave_type'] == 4) {
                $user_id   = $data['user_id'];
                $available = model('Overtime')->where(['user_id'=>$user_id, 'status'=>1])->sum('overtime');
                $used      = model('leave')->where(['flow_id'=>['notin', $flow_id], 'user_id'=>$user_id, 'status'=>1, 'leave_type'=>4, 'status' => ['notin', [2]]])->sum('leave_day');
                $data['overtime'] = [
                    'available' => $available,
                    'used'      => $used,
                ];
            }
            //年假数据
            if ($data['leave_type'] == 3) {
                $user_id   = $data['user_id'];
                $data['annual'] = model('Annual')->getUserAnnual($user_id)['list'];
            }
		}
		return $data;
    }

    protected function saveData($data, $type=1)
    {
        $save_data = [
            'user_id'    => get_user_id(),
            'start_time' => strtotime($data['start_time']),
            'end_time'   => strtotime($data['end_time']),
            'status'     => 0,
            'describe'   => $data['describe'],
            'leave_type' => $data['leave_type'],
            'leave_day'  => $data['leave_day'],
            'other_type' => ''
        ];
        if ($data['leave_type'] == 7) {
            $save_data['other_type'] = $data['other_type'];
        }

        $type == 1 && $save_data['flow_id'] = $data['flow_id'];
        return $save_data;
    }


    public function getAnnual()
    {
        $user_id = get_user_id();
        $data = model('Annual')->getUserAnnual($user_id)['list'];
        $this->assign('data', $data);
        return view('get_annual');
    }

    public function getOvertime()
    {
        $user_id   = get_user_id();
        $available = model('Overtime')->where(['user_id'=>$user_id, 'status'=>1])->sum('overtime');
        $used      = model('leave')->where(['user_id'=>$user_id, 'status'=>1, 'leave_type'=>4, 'status' => ['notin', [2]]])->sum('leave_day');
        $data      = [
            'available' => $available,
            'used'      => $used,
        ];
        $this->assign('data', $data);
        return view('get_overtime');
    }

    protected function extCheck($data)
    {
        $res  = 1;
        $msg  = '';
        switch ($data['leave_type']) {
            case '3':
                $used      = 0;
                $user_id   = get_user_id();
                $year = date('Y');
                $time_61 = strtotime($year . '-6-1');
                $where = array(
                    'user_id' => get_user_id(),
                    'status' => 1
                );
                if ($data['start_time'] >= $time_61) {
                    $where['year'] = $year;
                }
                $leave_info = model('annual')->field('(sum(total_day)-sum(used_day)) as residue_day')->where($where)->find();
                if ($data['leave_day'] > $leave_info['residue_day']) {
                    $res = 0;
                    $msg = '可用年假不足';
                }
                break;

            case '4':
                $used      = 0;
                $user_id   = get_user_id();
                $available = model('Overtime')->where(['user_id'=>$user_id, 'status'=>1])->sum('overtime');
                $where     = [
                    'user_id'=>$user_id,
                    'status'=>1,
                    'leave_type'=>4,
                    'status' => ['notin', [2]],
                ];
                isset($data['id']) && $where['flow_id'] = ['notin', $data['id']];
                $used = model('leave')->where($where)->sum('leave_day');
                if (($available - $used - $data['leave_day']) < 0) {
                    $res = 0;
                    $msg = '补休时间不足';
                }
                break;

            case '7':
                if ($data['other_type'] == '') {
                    $res = 0;
                    $msg = '其他带薪假不能为空';
                }
                break;

            default:
                # code...
                break;
        }

        return ['res'=>$res, 'msg'=>$msg];
    }

    public function extSave($data)
    {
        $res  = 1;
        $msg  = '';
        switch ($data['leave_type']) {
            case '3':
                $result = $this->annualLeave($data);
                $res = $result['res'];
                $msg = $result['msg'];
                break;

            default:
                # code...
                break;
        }

        return ['res'=>$res, 'msg'=>$msg];
    }


    private function annualLeave($data){
        $res  = 1;
        $msg  = '';
        $Annual    = model('Annual');
        $AnnualLog = model('Annual_log');
        $year      = date('Y');
        $time_61   =[
            'user_id' => get_user_id(),
            'status'  => 1
        ];
        $where = [];
        if ($data['start_time'] >= $time_61) {
            $where['year'] = $year;
        }
        $leave_info = $Annual->field('(sum(total_day)-sum(used_day)) as residue_day')->where($where)->find();
        if ($data['leave_day'] > $leave_info['residue_day']) {
            return ['res'=>0, 'msg'=>'剩余年假不足!'];
        }
        //更新年假表
        $min_info = $Annual->field('year, used_day, (total_day-used_day) as residue_day')->where($where)->order('year asc')->limit(1)->find();
        if ($min_info['residue_day'] == 0) {
            $min_info = $Annual->field('year, used_day, (total_day-used_day) as residue_day')->where($where)->order('year desc')->limit(1)->find();
        }
        //相差天数
        $diff_day = $data['leave_day'] - $min_info['residue_day'];
        if ($diff_day <= 0) {
            //最小年度足够扣除年假
            $Annual->where(['user_id' => get_user_id(), 'year' => $min_info['year'], 'status' => 1])->update(['used_day'=> $min_info['used_day']+$data['leave_day']]);
            //插入日志
            $data_log = [
                'flow_id'    => $data['flow_id'],
                'user_id'    => get_user_id(),
                'year'       => $min_info['year'],
                'start_time' => $data['start_time'],
                'end_time'   => $data['end_time'],
                'leave_day'  => $data['leave_day'],
                'status'     => 0
            ];
            $result = $AnnualLog->save($data_log);
            if ($result) {
                $ret = ['res'=>1, 'msg'=>''];
            }else{
                $ret = ['res'=>0, 'msg'=>'插入年假日志错误'];
            }
        }else{
            //最小年度不足扣取年假
            $Annual->where(['user_id' => get_user_id(), 'year' => $min_info['year'], 'status' => 1])->update(['used_day'=> $min_info['used_day']+$min_info['residue_day']]);
            //插入日志
            $data_min_log = [
                'flow_id'    => $data['flow_id'],
                'user_id'    => get_user_id(),
                'year'       => $min_info['year'],
                'start_time' => $data['start_time'],
                'end_time'   => $data['end_time'],
                'leave_day'  => $min_info['residue_day'],
                'status'     => 0
            ];
            $AnnualLog->save($data_min_log);
            //扣取后一年度年假
            $max_info = $Annual->field('year, used_day, (total_day-used_day) as residue_day')->where(['user_id' => get_user_id(), 'status' => 1])->order('year desc')->limit(1)->find();
            $Annual->where(['user_id' => get_user_id(), 'year' => $max_info['year']])->update(['used_day'=>$max_info['used_day']+$diff_day]);
            //插入日志
            $data_max_log = [
                'flow_id'    => $flow_id,
                'user_id'    => get_user_id(),
                'year'       => $max_info['year'],
                'start_time' => $data['start_time'],
                'end_time'   => $data['end_time'],
                'leave_day'  => $diff_day,
                'status'     => 0
            ];
            $result = $AnnualLog->save($data_max_log);
            $result = $AnnualLog->save($data_log);
            if ($result) {
                $ret = ['res'=>1, 'msg'=>''];
            }else{
                $ret = ['res'=>0, 'msg'=>'插入年假日志错误'];
            }
        }

        return $ret;
    }

    public function extDel($flow_id)
    {
        $this->delAnnualLeave($flow_id);
    }

    private function delAnnualLeave($flow_id){
        $Annual    = model('Annual');
        $AnnualLog = model('Annual_log');
        $user_id   = model('Flow')->where(['id'=>$flow_id])->value('user_id');
        $log_list  = $AnnualLog->where(['flow_id'=>$flow_id,'status'=>0,'is_del'=>0])->column('leave_day', 'year');
        $AnnualLog->where(['flow_id'=>$flow_id,'status'=>0,'is_del'=>0])->update(['is_del'=>1]);
        //年假表回退
        foreach ($log_list as $year => $leave_day) {
            $used_day = $Annual->where(['user_id'=>$user_id,'year'=>$year])->value('used_day');
            $Annual->where(['user_id'=>$user_id,'year'=>$year])->update(['used_day'=>$used_day-$leave_day]);
        }
    }

    public function extChange($data)
    {
        switch ($data['leave_type']) {
            case '3':
                $result = $this->changeAnnualLeave($data['flow_id']);
                break;

            default:
                # code...
                break;
        }
    }

    private function changeAnnualLeave($flow_id, $status = 1){
        $user_id = model('Flow')->where(['id'=>$flow_id])->value('user_id');
        model('Annual_log')->where(['user_id'=>$user_id,'flow_id'=>$flow_id,'is_del'=>0])->update(['status'=>$status]);
    }
}
