<?php
namespace app\controller;
use app\controller\Base;

class Stat extends Base
{
    public function index()
    {
        //页面插件
        $plugin = [];

        $list = db('stat_user')->order('year desc, month desc')->paginate(15);
        $datas = [];

        foreach ($list as $key => $value) {
        	$value['avg'] = ($value['start_day'] + $value['end_day']) / 2;
        	$month_start = strtotime(date("{$value['year']}-{$value['month']}-1"));
        	$month_end = strtotime("+1 month", $month_start);
        	//当月离职人数
        	$out_count = db('user', '', false)->where(['leave_time' => [['>=', $month_start], ['<', $month_end]]])->count();
        	$value['out_ratio'] = round(($out_count/$value['avg'])*100,2);
        	$datas[] = $value;
        }

        return $this->fetch('index', ['datas'=>$datas, 'plugin'=>$plugin, 'paginate'=>$list->render()]);
    }

    public function save()
    {
    	$param = input('post.');
    	if (!$param['start_day'] || !$param['end_day']) {
    		$this->error('在职人数不能为空或0');
    	}

    	$data = $param;

    	$result = db('stat_user')->insert($data, true);
    	if ($result) {
    		$this->success('保存成功');
    	}
    }
}
