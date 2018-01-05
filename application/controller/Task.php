<?php
namespace app\controller;
use think\Controller;
use think\Config;

class Task extends Controller{

	public function annual(){
		if (date('H') !== '00' && (date('i') !== "00" || date('i') !== "01") ) {
			exit();
		}
		$users = model('User')->where(['entry_time'=>['>', 0]])->column('entry_time', 'id');
		//调整年度年假天数
		$Annual = model('Annual');
		$year = date('Y', time());
		foreach ($users as $uid => $entry_time) {
			$total_day = $this->getAnnLeaveDay($entry_time);
			//更新数据
			$count = model('Annual')->where(['user_id' => $uid, 'year' => $year])->count();
			if ($count) {
				//若存在则更新
				$Annual->where(['user_id' => $uid, 'year' => $year])->update(['total_day'=>$total_day]);
				//$this -> writeLog(json_encode([$uid,$year,$total_day]));
			}else {
				//若不存在,插入新数据
				$datas[] = [
					'user_id'     => $uid,
					'year'        => $year,
					'total_day'   => $total_day,
					'used_day'    => 0,
					'status'      => 1,
					'cleared_day' => 0
				];
				//$this -> writeLog(json_encode($data));
			}
			//每年6月1号清除上一年度年假
			if (date('n', time()) == '6' && date('j', time()) == '1') {
				$last_year = date('Y', strtotime('-1 years'));
				$last_info = $Annual->field('total_day,used_day,status')->where(['user_id' => $uid, 'year' => $last_year])->find();
				if ($last_info['status'] == 1) {
					$last_residue_day = $last_info['total_day'] - $last_info['used_day'];
					$update_data = [
						'used_day'    => $last_info['total_day'],
						'cleared_day' => $last_residue_day,
						'status'      => 2
					];
					$Annual->where(['user_id' => $uid, 'year' => $last_year])->update($update_data);
				}
			}
		}
		!empty($datas) && model('Annual')->saveAll($datas);
	}

	//计算年假天数
	public function getAnnLeaveDay($entry_time){
		if ($entry_time) {
			//入职月份差
			$diff_m = (date('Y')-date('Y',$entry_time))*12+date('m')-date('m',$entry_time);
			if ((date('d')<date('d',$entry_time))) {
				$diff_m = $diff_m - 1;
			}
			//年假天数  （1）工作满一年未满三年的年假为5天；（2）工作满三年不满五年的年假为7天；（3）工作满五年不满十年的年假为10天；
			if ($diff_m < 12 ) {
				$day = 0;
			}elseif ($diff_m >= 12 && $diff_m < 36) {
				$day = 5;
			}elseif ($diff_m >= 36 && $diff_m < 60) {
				$day = 7;
			}elseif ($diff_m >= 60) {
				$day = 10;
			}
			return $day;
		}else {
			return '--';
		}
	}

	public function writeLog($msg){
		//$file = '/tmp/oa_annual.log';
		$file = '/tmp/oa/oa_annual.log';
		$file = $file . "." . date("Y-m-d");
    	$fp = fopen($file, 'a+');
    	$msg = date("Y-m-d H:i:s") . "\t" . $msg;
    	fwrite($fp, $msg . "\n");
    	fclose($fp);
	}

	public function mail()
	{
		Config::load(CONF_PATH.'mail.php');
		$where['is_send'] = 0;
		$where['send_count'] = ['<', 6];
		$email = db('email', [], false);
		for($i=0; $i<40; $i++){
			$list = $email->where($where)->limit(1)->select();
			foreach ($list as $value) {
				$result = send_mail($value['address'] ,$value['title'], $value['content']);
				if ($result) {
					$email->where(['id'=>$value['id']])->setField('is_send',1);
				}else {
					$email->where(['id'=>$value['id']])->setInc('send_count');
				}
			}
			sleep(3);
		}
	}

	public function weixin()
	{
		$weixin = db('weixin', [], false);
		$where['is_send'] = 0;
		$where['send_count'] = ['<', 2];
		for($i=0; $i<6; $i++){
			$list = $weixin->where($where)->limit(1)->select();
			foreach ($list as &$value) {
				$value['msg_params'] = json_decode($value['msg_params'],true);
				$access_token = $this->getAccessToken(0);
				$url = 'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token='.$access_token;
				$Curl = new \util\Curl();
				$Curl->url = $url;
				$Curl->is_https = true;
				$Curl->method = 'post';
				$Curl->params = $this->getMsgParams(json_decode($value['weixin_id'],true),$value['msg_type'],$value['msg_params']);
				$weixin->where(['id'=>$value['id']])->setInc('send_count');
				$result = $Curl->exec();
				$result = json_decode($result,true);
				if ($result['errcode'] == 0) {
					$weixin->where(['id'=>$value['id']])->setField('is_send',1);
				}else{
					if ($result['errcode'] == 40014 || $result['errcode'] == 41001) {
						$this->getAccessToken($type = 1);
					}else{
						$weixin->where(['id'=>$value['id']])->setField('msg_error',$result['errmsg']);
					}
				}
			}
			sleep(20);
		}
	}

	public function getAccessToken($type = 0){
		if (!cache('weixin_access_token') || $type == 1) {
			Config::load(CONF_PATH.'weixin.php');
			$url = 'https://qyapi.weixin.qq.com/cgi-bin/gettoken';
			$Curl = new \util\Curl();
			$Curl->url = $url;
			$Curl->is_https = true;
			$Curl->params = [
				'corpid' => config('weixin_corpid'),
				'corpsecret' => config('weixin_secret')
			];
			$result = $Curl->exec();
			$result = json_decode($result,true);
			$access_token = $result['access_token'];
			cache('weixin_access_token', $access_token, $result['expires_in']);
		}else{
			$access_token = cache('weixin_access_token');
		}
		return $access_token;

	}

	public function getMsgParams($weixin_id, $msg_type, $msg_params){
		$touser = implode('|', $weixin_id);
		$params = [
			'touser'  => $touser,
			'toparty' => '',
			'totag'   => '',
			'msgtype' => $msg_type,
			'agentid' => $msg_params['agentid'],
		];
		switch ($msg_type) {
			case 'news':
				$content['news']['articles'][] = [
					'title'       => $msg_params['title'],
					'description' => $msg_params['description'],
					'url'         => $msg_params['url'],
					'picurl'      => ''
				];
				break;
			default:
				# code...
				break;
		}
		$params += $content;
		$params = json_encode($params, JSON_UNESCAPED_UNICODE);
		return $params;
	}

	public function user()
	{
		if (date('H') !== '00' && (date('i') !== "00" || date('i') !== "01") && (date('j') !== "1")) {
			exit();
		}
		$time = time();
		//在职人数
		$where['is_del'] = 0;
		$where['type'] = ['in', [1, 2]];
		$count = db('user')->where($where)->count();
		//本月在职人数 = 上月在职人数
		//本月
		$map['year'] = date('Y', $time);
		$map['month'] = date('n', $time);
		$res = db('stat_user')->where($map)->count();
		if ($res) {
			db('stat_user')->where($map)->setField('start_day', $count);
		}else{
			$data = $map;
			$data['start_day'] = $count;
			db('stat_user')->insert($data);
		}
		unset($map);
		unset($data);
		//上月
		$last_time = strtotime('-1 month', $time);
		$map['year'] = date('Y', $last_time);
		$map['month'] = date('n', $last_time);
		$res = db('stat_user')->where($map)->count();
		if ($res) {
			db('stat_user')->where($map)->setField('end_day', $count);
		}else{
			$data = $map;
			$data['end_day'] = $count;
			db('stat_user')->insert($data);
		}
	}
}
