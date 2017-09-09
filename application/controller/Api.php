<?php
namespace app\controller;
use think\Controller;
use think\Config;

class Api extends Controller{

	public function weixin(){
		$sVerifyMsgSig    = input('get.msg_signature');
		$sVerifyTimeStamp = input('get.timestamp');
		$sVerifyNonce     = input('get.nonce');
		$sVerifyEchoStr   = input('get.echostr');

		if ($sVerifyEchoStr) {
			$wxcpt = new \util\WXBizMsgCrypt();
			Config::load(CONF_PATH.'weixin.php');
			$wxcpt->WXBizMsgCrypt(config('weixin_token'), config('weixin_encodingaeskey'), config('weixin_corpid'));
			$errCode = $wxcpt->VerifyURL($sVerifyMsgSig, $sVerifyTimeStamp, $sVerifyNonce, $sVerifyEchoStr, $sEchoStr);
			if ($errCode == 0) {
				//
				// 验证URL成功，将sEchoStr返回
				echo $sEchoStr;
			} else {
				print("ERR: " . $errCode . "\n\n");
			}
		}else{
			$sReqData = file_get_contents("php://input");
			$this->getUseEvent($sVerifyMsgSig, $sVerifyTimeStamp, $sVerifyNonce, $sReqData);
		}
	}

	public function getUseEvent($sReqMsgSig, $sReqTimeStamp, $sReqNonce, $sReqData){
		$sMsg = "";  // 解析之后的明文
		$wxcpt = new \util\WXBizMsgCrypt();
		Config::load(CONF_PATH.'weixin.php');
		$wxcpt->WXBizMsgCrypt(config('weixin_token'), config('weixin_encodingaeskey'), config('weixin_corpid'));
		//$wxcpt->WXBizMsgCrypt($token, $encodingAesKey, $corpId);
		$errCode = $wxcpt->DecryptMsg($sReqMsgSig, $sReqTimeStamp, $sReqNonce, $sReqData, $sMsg);
		if ($errCode == 0) {
			// 解密成功，sMsg即为xml格式的明文
			// TODO: 对明文的处理
			// For example:
			$xml = new \DOMDocument();
			$xml->loadXML($sMsg);
			// $content = $xml->getElementsByTagName('Content')->item(0)->nodeValue;
			// $to_user_name = $xml->getElementsByTagName('ToUserName')->item(0)->nodeValue;
			// $from_user_name = $xml->getElementsByTagName('FromUserName')->item(0)->nodeValue;
			// if ($content == 'FlowAdd') {
			// 	$msg_content = array(
			// 		'content' => url('/flow/index/view/weixin', $sign, '', true)
			// 		);
			// 	echo $msg = $this->makeMsg($to_user_name,$from_user_name,$agent_id,$msg_content,1);
			// }
			//事件
			$msg_type = $xml->getElementsByTagName('MsgType')->item(0)->nodeValue;
			$to_user_name = $xml->getElementsByTagName('ToUserName')->item(0)->nodeValue;
			$from_user_name = $xml->getElementsByTagName('FromUserName')->item(0)->nodeValue;
			$event = $xml->getElementsByTagName('Event')->item(0)->nodeValue;
			$event_key = $xml->getElementsByTagName('EventKey')->item(0)->nodeValue;
			$agent_id = $xml->getElementsByTagName('AgentID')->item(0)->nodeValue;
			if ($event == 'click') {
				$sign = $this->makeSign($from_user_name);
				if ($event_key == 'FlowAdd') {
					$msg_content = array(
						'title' => '发起审核',
						'description' => '请点击本消息跳转到流程发起页面',
						'pic_url' => '',
						'url' => url('/flow/index/view/weixin?' . $sign, '', '', true),
						);
					echo $msg = $this->makeMsg($to_user_name,$from_user_name,$agent_id,$msg_content,5);
				}
				if ($event_key == 'FlowSubmit') {
					$msg_content = array(
						'title' => '已提交',
						'description' => '请点击本消息跳转到流程已提交页面',
						'pic_url' => '',
						'url' => url('/flow/submit/view/weixin?' . $sign, '', '', true),
						);
					echo $msg = $this->makeMsg($to_user_name,$from_user_name,$agent_id,$msg_content,5);
				}
				if ($event_key == 'FlowConfirm') {
					$msg_content = array(
						'title' => '待审核',
						'description' => '请点击本消息跳转到流程待审核页面',
						'pic_url' => '',
						'url' => url('/flow/confirm/view/weixin?' . $sign, '', '', true),
						);
					echo $msg = $this->makeMsg($to_user_name, $from_user_name, $agent_id, $msg_content, 5);
				}
				if ($event_key == 'FlowFinish') {
					$msg_content = array(
						'title' => '已审核',
						'description' => '请点击本消息跳转到流程已审核页面',
						'pic_url' => '',
						'url' => url('/flow/finish/view/weixin?' . $sign, '', '', true),
						);
					echo $msg = $this->makeMsg($to_user_name, $from_user_name, $agent_id, $msg_content, 5);
				}
			}
		} else {
			print("ERR: " . $errCode . "\n\n");
			//exit(-1);
		}
	}

	public function makeMsg($to_user_name, $from_user_name, $agent_id, $msg_content, $type){
		if ($type == 1) {
			$sRespData = "<xml>
							<ToUserName><![CDATA[".$from_user_name."]]></ToUserName>
							<FromUserName><![CDATA[".$to_user_name."]]></FromUserName>
							<CreateTime><![CDATA[".time()."]]></CreateTime>
							<MsgType><![CDATA[text]]></MsgType>
							<Content><![CDATA[".$msg_content['content']."]]></Content>
							<MsgId><![CDATA[".time().rand(1000,9999)."]]></MsgId>
							<AgentID><![CDATA[".$agent_id."]]></AgentID>
						</xml>";
		}
		if ($type == 5) {
			$sRespData = "<xml>
						    <ToUserName><![CDATA[".$from_user_name."]]></ToUserName>
						    <FromUserName><![CDATA[".$to_user_name."]]></FromUserName>
						   <CreateTime><![CDATA[".time()."]]></CreateTime>
						    <MsgType><![CDATA[news]]></MsgType>
						    <ArticleCount>1</ArticleCount>
						    <Articles>
						        <item>
						            <Title><![CDATA[".$msg_content['title']."]]></Title>
						            <Description><![CDATA[".$msg_content['description']."]]></Description>
						            <PicUrl><![CDATA[".$msg_content['pic_url']."]]></PicUrl>
						            <Url><![CDATA[".$msg_content['url']."]]></Url>
						        </item>
						    </Articles>
						</xml>";
		}
		$sEncryptMsg = ""; //xml格式的密文
		$wxcpt = new \util\WXBizMsgCrypt();
		Config::load(CONF_PATH.'weixin.php');
		$wxcpt->WXBizMsgCrypt(config('weixin_token'), config('weixin_encodingaeskey'), config('weixin_corpid'));
		$errCode = $wxcpt->EncryptMsg($sRespData, time(),rand(1000,9999), $sEncryptMsg);
		if ($errCode == 0) {
			return $sEncryptMsg;
		} else {
			return "ERR: " . $errCode . "\n\n";
			// exit(-1);
		}
	}

	public function makeSign($from_user_name){
		Config::load(CONF_PATH.'weixin.php');
		$params['u']=$from_user_name;
		$params['t']=time();
		ksort($params);
		$params['sign']=md5(http_build_query($params).config('auth_key'));
		return http_build_query($params);
	}
}
