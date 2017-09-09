<?php
namespace app\controller;
use app\controller\Base;

class Index extends Base
{
    public function index()
    {
    	$notices = $this->notice();
    	$regulation = $this->regulation();
        return $this->fetch('index', ['notices'=>$notices, 'regulation'=>$regulation]);
    }

	//获取公告列表
	public function notice(){
		$datas = model('notice')->where(['is_del'=>0, 'status'=>1])->order('create_time desc')->paginate(5);
		foreach ($datas as $key => $data) {
			if (empty($data['show'])) {
				unset($datas[$key]);
			}elseif (!in_array(get_user_id(), $data['show'])) {
				unset($datas[$key]);
			}
		}
		return $datas;
	}

	//获取员工守则
	public function regulation(){
		$filename = ROOT_PATH . "/info/regulation.json";
        $str = file_get_contents($filename);
        $data = json_decode($str, true);

		return $data;
	}
}
