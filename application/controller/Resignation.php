<?php
namespace app\controller;
use app\controller\Flow;

class Resignation extends Flow
{
    public function getParam()
    {
    	return ['leave_type'=>[1=>'自动离职',2=>'试用期不合适',3=>'合同到期',4=>'其他']];
    }

    public function getPlugin()
    {
        return ['date'];
    }

    public function index()
    {
        //页面插件
        $plugin = ['date' ,'page'];

        $param = input('param.');
        $param['start_time'] = input('param.start_time', date('Y-m-01'));
        $param['end_time'] = input('param.end_time', date('Y-m-'.date('t')));
        $param['page'] = input('param.page/d', 1);
        $where = $this->getWhere($param);
        ($param['start_time'] && $param['end_time']) && $where['update_time'] = ['between', [strtotime($param['start_time']), strtotime($param['end_time'])+86400]];
        ($param['start_time'] && !$param['end_time']) && $where['update_time'] = ['>=', strtotime($param['start_time'])];
        (!$param['start_time'] && $param['end_time']) && $where['update_time'] = ['<=', strtotime($param['end_time'])+86400];

        $datas = model('Resignation')->where(['status'=>1])->where($where)->order('create_time desc')->paginate(15);
        foreach ($datas as &$data) {
            $data['project_id'] = get_project_id($data['user_id']);
            $data['duty_id'] = get_duty_id($data['user_id']);
        }
        $leave_type = $this->getParam()['leave_type'];
        $conf_list = $this->getConfList();
        return $this->fetch('index', ['datas'=>$datas, 'plugin'=>$plugin, 'conf_list'=>$conf_list, 'param'=>$param, 'leave_type'=>$leave_type, 'paginate'=>$datas->render()]);
    }

    public function detail()
    {
        $param = input('param.');
        $info = model('Resignation')->where(['flow_id' => $param['flow_id']])->find();
        $info = $info->toArray();
        $info['leave_type'] = $this->getParam()['leave_type'][$info['leave_type']];
        $info['user'] = get_user_name($info['user_id']);
        $project_id = get_project_id($info['user_id']);
        $duty_id = get_duty_id($info['user_id']);
        $info['project'] = get_project_name($project_id);
        $info['duty'] = get_duty_name($duty_id);

        $log = model('flow_log')->where(['flow_id' => $param['flow_id'], 'is_del'=>0, 'result' => 1])->order('create_time asc')->select();
        foreach ($log as $value) {
            $confirm[] = [
                'step' => $value['step'],
                'content' => $value['comment'],
                'user' => get_user_name($value['user_id'])
            ];
        }
        $data = [
            'info' => $info,
            'confirm' => $confirm
        ];

        $title = model('FlowConfig')->getConfirmTitle($param['flow_id']);
        echo '
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <xml><w:WordDocument><w:View>Print</w:View></xml>
        </head>';
        echo "<body>
        <h1 style='text-align: center'>辞职申请表</h1>
        <table border='1' cellpadding='3' cellspacing='0' >
        <td width='93' valign='center' colspan='2' rowspan='3'>申请人信息</td>
        <td width='93' valign='center' colspan='2'>姓名</td>
        <td width='200' valign='center' colspan='2'>{$data['info']['user']}</td>
        <td width='93' valign='center' colspan='2'>部门</td>
        <td width='200' valign='center' colspan='5'>{$data['info']['project']}</td>
        </tr>
        <tr>
        <td width='93' valign='center' colspan='2' >职能</td>
        <td width='200' valign='center' colspan='2' >{$data['info']['duty']}</td>
        <td width='93' valign='center' colspan='2' >离职类型</td>
        <td width='200' valign='center' colspan='5' >{$data['info']['leave_type']}</td>
        </tr>
        <tr>
        <td width='93' valign='center' colspan='2' >入职时间</td>
        <td width='200' valign='center' colspan='2' >{$data['info']['entry_time']}</td>
        <td width='93' valign='center' colspan='2' >申请离职时间</td>
        <td width='200' valign='center' colspan='5' >{$data['info']['leave_time']}</td>
        </tr>
        <tr>
        <td width='93' valign='center' >离职原因</td>
        <td width='570' valign='center' colspan='12' >{$data['info']['leave_reason']}</td>
        </tr>
        <tr>
        <td width='93' valign='center' >吐槽专属区       （可对公司环境，制度，项目等进行吐槽）</td>
        <td width='570' valign='center' colspan='12' >{$data['info']['complaints']}</td>
        </tr>
        <tr>
        <td width='93' valign='center' >对公司的建议</td>
        <td width='570' valign='center' colspan='12' >{$data['info']['suggest']}</td>
        </tr>";

        foreach ($data['confirm'] as $value) {
            $value['content'] = str_replace("\n", "<br/>", $value['content']);
            echo "
            <tr>
                <td width='93' valign='center' >{$title[$value['step']]}</td>
                <td width='570' valign='center' colspan='12' >{$value['content']}</td>
            </tr>
            ";
        }
        echo "
        </table>
        <br/>
        &nbsp;&nbsp;本人确认，以上内容代表本人真实意愿，均真实有效。<br/>
        &nbsp;&nbsp;员工签名：<br/><br/>
        &nbsp;&nbsp;日期：
        </body>";
        ob_start(); //打开缓冲区
        header("Cache-Control: public");
        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        if (strpos($_SERVER["HTTP_USER_AGENT"],'MSIE')) {
        header('Content-Disposition: attachment; filename=test.doc');
        }else if (strpos($_SERVER["HTTP_USER_AGENT"],'Firefox')) {
        Header('Content-Disposition: attachment; filename=test.doc');
        } else {
        header('Content-Disposition: attachment; filename=test.doc');
        }
        header("Pragma:no-cache");
        header("Expires:0");
        ob_end_flush();//输出全部内容到浏览器
    }

    public function data($flow_id = null)
    {
        $data = [
            'entry_time' => '',
            'leave_time' => '',
            'leave_type' => '',
            'leave_reason' => '',
            'complaints' => '',
            'suggest' => ''
        ];
        if ($flow_id) {
            $data = model('Resignation')->get(['flow_id' => $flow_id]);
            $data && $data = $data->toArray();
        }
        return $data;
    }

    protected function saveData($data, $type=1)
    {
        $save_data = [
			'user_id'      => get_user_id(),
			'entry_time'   => strtotime($data['entry_time']),
			'leave_time'   => strtotime($data['leave_time']),
			'leave_type'   => $data['leave_type'],
			'leave_reason' => $data['leave_reason'],
			'complaints'   => $data['complaints'],
			'suggest'      => $data['suggest'],
            'status'          => 0
        ];

        $type == 1 && $save_data['flow_id'] = $data['flow_id'];
        return $save_data;
    }
}
