<?php
namespace app\controller;
use app\controller\Flow;

class Handover extends Flow
{
    public function getParam()
    {
    	
    }

    public function getPlugin()
    {
        return ['date', 'select2', 'select2_user'];
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

        $datas = model('Handover')->where(['status'=>1])->where($where)->order('create_time desc')->paginate(15);
        foreach ($datas as &$data) {
            $data['receiver'] = get_user_name($data['receiver']);
            $data['project_id'] = get_project_id($data['user_id']);
            $data['duty_id'] = get_duty_id($data['user_id']);
        }
        $conf_list = $this->getConfList();
        return $this->fetch('index', ['datas'=>$datas, 'plugin'=>$plugin, 'conf_list'=>$conf_list, 'param'=>$param, 'paginate'=>$datas->render()]);
    }

    public function detail()
    {
        $param = input('param.');
        $info = model('Handover')->where(['flow_id' => $param['flow_id']])->find();
        $info = $info->toArray();
        $info['user'] = get_user_name($info['user_id']);
        $info['receiver'] = get_user_name($info['receiver']);
        $project_id = get_project_id($info['user_id']);
        $duty_id = get_duty_id($info['user_id']);
        $info['project'] = get_project_name($project_id);
        $info['duty'] = get_duty_name($duty_id);
        $info['content'] = str_replace("\n", "<br/>", $info['content']);

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
        <h1 style='text-align: center'>工作交接清单</h1>
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
        <td width='93' valign='center' colspan='2' >交接人</td>
        <td width='200' valign='center' colspan='5' >{$data['info']['receiver']}</td>
        </tr>
        <tr>
        <td width='93' valign='center' colspan='2' >入职时间</td>
        <td width='200' valign='center' colspan='2' >{$data['info']['entry_time']}</td>
        <td width='93' valign='center' colspan='2' >交接时间</td>
        <td width='200' valign='center' colspan='5' >{$data['info']['over_time']}</td>
        </tr>
        <tr>
        <td width='93' valign='center' >所属部门交接内容</td>
        <td width='570' valign='center' colspan='12' >{$data['info']['content']}</td>
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
            'entry_time'        => '',
            'over_time'         => '',
            'receiver'          => 0,
            'content'           => '',
        ];
        if ($flow_id) {
            $data = model('Handover')->get(['flow_id' => $flow_id]);
            $data && $data = $data->toArray();
        }
        return $data;
    }

    public function updateConfirm()
    {
        $receiver = input('receiver/d', 0);
        $data      = [
            'receiver' => $receiver
        ];
        $FlowConfig = model('FlowConfig');
        $flow       = $FlowConfig->getFlow(11, $data);
        $flow_show  = implode(' -> ', $flow['flow_show']);
        return $flow_show;
    }

    protected function saveData($data, $type=1)
    {
        $save_data = [
            'user_id'           => get_user_id(),
            'entry_time'        => strtotime($data['entry_time']),
            'over_time'         => strtotime($data['over_time']),
            'receiver'          => $data['receiver'],
            'content'           => $data['content'],
            'status'            => 0
        ];

        $type == 1 && $save_data['flow_id'] = $data['flow_id'];
        return $save_data;
    }
}
