<?php

use com\hlw\huilie\interfaces\YjfpServiceIf;
use com\hlw\huilie\dataobject\yjfp\YjfpResultDTO;
use com\hlw\huilie\dataobject\yjfp\YjfpPrimResultDTO;
use com\hlw\common\dataobject\common\ResultDO;

class api_YjfpService extends api_Abstract implements YjfpServiceIf
{
    protected $com_title;
    protected $com_title2;
    protected $com_title3;
    protected $model;
    protected $model_invoice;
    protected $model_user;
    protected $model_fineproject;
    protected $model_fineprojectcc;
    protected $model_fineprojectenter;
    protected $model_fineprojectinterview;
    protected $model_fineprojectoffer;
    protected $model_fineprojectadviser;
    protected $model_achievement;
    protected $ResultDO;

    public function __construct()
    {
        $this->ResultDO = new ResultDO();//common 公共result
        $this->model = new model_pinping_performancesys();
        $this->model_invoice = new model_pinping_invoice();
        $this->model_fineproject = new model_pinping_fineproject();
        $this->model_fineprojectcc = new model_pinping_fineprojectcc();
        $this->model_fineprojectenter = new model_pinping_fineprojectenter();
        $this->model_fineprojectinterview = new model_pinping_fineprojectinterview();
        $this->model_fineprojectoffer = new model_pinping_fineprojectoffer();
        $this->model_user = new model_pinping_user();
        $this->model_fineprojectadviser = new model_pinping_fineprojectadviser();
        $this->model_achievement = new model_pinping_achievement();
        $this->com_title = [
            'clue'=>'线索提供人',
            'bd'=>'BD',
            'pm'=>'项目经理',
            'delivery'=>'交付'
        ];
        $this->com_title2 = [
            'clue'=>'有效线索提供',
            'contract'=>'合同签订',
            'receivable'=>'回款',
            'demand'=>'立项需求表企业项目对接',
            'resume'=>'候选人简历提供',
            'intention'=>'候选人意向沟通简历报告制作',
            'recommend'=>'候选人推荐面试跟进',
            'offer'=>'候选人薪酬offer谈判',
            'entry'=>'候选人背景调查入职跟进'
        ];
        $this->com_title3 = [
            'clue'=>'有效线索提供',
            'bd'=>'合同签订,回款',
            'pm'=>'立项需求表企业项目对接',
            'delivery'=>'候选人简历提供,候选人意向沟通简历报告制作,候选人推荐面试跟进,候选人薪酬offer谈判,候选人背景调查入职跟进',
        ];

    }

    public function editData(\com\hlw\huilie\dataobject\yjfp\YjfpRequestDTO $yjfpDo)
    {

        //挨个 过滤输入 数据
        $pro_types = $yjfpDo->pro_types?hlw_lib_BaseUtils::getStr($yjfpDo->pro_types,'int'):0;
        $effective_clue = $yjfpDo->effective_clue?hlw_lib_BaseUtils::getStr($yjfpDo->effective_clue,'int'):0;
        $contract_sign = $yjfpDo->contract_sign?hlw_lib_BaseUtils::getStr($yjfpDo->contract_sign,'int'):0;
        $receivable = $yjfpDo->receivable?hlw_lib_BaseUtils::getStr($yjfpDo->receivable,'int'):0;
        $project_docking = $yjfpDo->project_docking?hlw_lib_BaseUtils::getStr($yjfpDo->project_docking,'int'):0;
        $resume_provision = $yjfpDo->resume_provision?hlw_lib_BaseUtils::getStr($yjfpDo->resume_provision,'int'):0;
        $intention_communicate = $yjfpDo->intention_communicate?hlw_lib_BaseUtils::getStr($yjfpDo->intention_communicate,'int'):0;
        $interview_follow = $yjfpDo->interview_follow?hlw_lib_BaseUtils::getStr($yjfpDo->interview_follow,'int'):0;
        $offer_negotiate = $yjfpDo->offer_negotiate?hlw_lib_BaseUtils::getStr($yjfpDo->offer_negotiate,'int'):0;
        $reference_check = $yjfpDo->reference_check?hlw_lib_BaseUtils::getStr($yjfpDo->reference_check,'int'):0;
        $ResultDO = new YjfpResultDTO();
        if (!$pro_types) {
            $ResultDO->code = 500;
            $ResultDO->success = FALSE;
            $ResultDO->message = '请选择业务类型';
            return $ResultDO;
        }
        //计算总和，要等于100
        $s = json_decode(json_encode($yjfpDo),true);
        unset($s['pro_types']);
        $sum = array_sum($s);
        if($sum >0 && $sum !=100){
            $ResultDO->code = 500;
            $ResultDO->success = FALSE;
            $ResultDO->message = '各项数值之和不足100%，请修改';
            if($sum>100)
            $ResultDO->message = '各项数值之和已经超过100%，请修改';

            return $ResultDO;
        }
            $model = $this->model;
        try {
            $this->model->beginTransaction();
            $is_exist = $this->model->selectOne(['pro_types'=>$pro_types],'id');
            $perform_ins = [
                'pro_types'=> $pro_types,
                'effective_clue'=> $effective_clue,
                'contract_sign'=> $contract_sign,
                'receivable'=> $receivable,
                'project_docking'=> $project_docking,
                'resume_provision'=> $resume_provision,
                'intention_communicate'=> $intention_communicate,
                'interview_follow'=> $interview_follow,
                'offer_negotiate'=> $offer_negotiate,
                'reference_check'=> $reference_check,
            ];
            if(is_array($is_exist) && count($is_exist)==1){
                $this->model->delete(['pro_types'=>$pro_types]);//删除所有配置关于该pro_types的配置，重新配
                $perform_ins['id'] = $is_exist['id'];
            }
            $perform_ins['create_time'] = time();
            $this->model->insert($perform_ins);
            $this->model->commit();
            $ResultDO->success = TRUE;
            $ResultDO->code = 200;
            $ResultDO->message = '操作成功';
            return $ResultDO;exit;
        }catch (Exception $ex) {
            $this->model->rollBack();
        }

    }

    public function newData(\com\hlw\huilie\dataobject\yjfp\YjfpPrimDTO $invoiceIdDo)
    {
        $com_title = $this->com_title;
        //   ['bd',$com_title['bd']]    ['pm',$com_title['pm']]     ['delivery',$com_title['delivery']]
        $com_title2 = $this->com_title2;
        //    ['clue',$com_title2['clue']]   ['contract',$com_title2['contract']]   ['receivable',$com_title2['receivable']]   ['demand',$com_title2['demand']]   ['resume',$com_title2['resume']]   ['intention',$com_title2['intention']]   ['recommend',$com_title2['recommend']]   ['offer',$com_title2['offer']]   ['entry',$com_title2['entry']]
        /****common-sec***/
        $ResultDO = new YjfpPrimResultDTO;
        $ResultDO->code = 500;
        $ResultDO->success = FALSE;
        /****common-sec***/
        $invoice_id = $invoiceIdDo->invoice_id?hlw_lib_BaseUtils::getStr($invoiceIdDo->invoice_id,'int'):0;

        if($invoice_id<=0){
            $ResultDO->message = '参数错误';
            return $ResultDO;
        }
        $true_type = [2=>1,6=>1,7=>2,3=>3];
        $invoice  = $this->model_invoice->selectOne(['invoice_id'=>$invoice_id],"money,fine_id,create_role_id,project_type,line_role,bd_role,project_role");
        $pro_types = $true_type[$invoice['project_type']];




        //06-20 计划： 把下面用到的全部加入model文件，然后按顺序调用，写完流程，
        //今天 06-20
        if($pro_types > 0){
            //有值，
            //找人： 交付人的查找  --start-- ↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
            //候选人简历提供
            $jl_tg = $this->model_fineproject->select(['id'=>$invoice['fine_id']],'callist_role_id role_id');
            $jl_tg = json_decode(json_encode($jl_tg),true);
            //CC备注
            $jf_cc = $this->model_fineprojectcc->select(['fine_id'=>$invoice['fine_id']],'role_id');
            $jf_cc = json_decode(json_encode($jf_cc),true);
            //推荐
            $jf_tj = $this->model_fineproject->select(['id'=>$invoice['fine_id'],'tjaddtime'=>['gt',0]],'tj_role_id role_id');
            $jf_tj = json_decode(json_encode($jf_tj),true);
            //顾问面试
            $gw_adv = $this->model_fineprojectadviser->select(['id'=>$invoice['fine_id']],'role_id');
            $gw_adv = json_decode(json_encode($gw_adv),true);
            //面试(此处理解为客户面试，并非顾问面试)
            $jf_ms = $this->model_fineprojectinterview->select(['fine_id'=>$invoice['fine_id']],'role_id');
            $jf_ms = json_decode(json_encode($jf_ms),true);
            //offer
            $jf_offer = $this->model_fineprojectoffer->select(['fine_id'=>$invoice['fine_id']],'role_id');
            $jf_offer = json_decode(json_encode($jf_offer),true);
            //入职
            $jf_rz = $this->model_fineprojectenter->select(['fine_id'=>$invoice['fine_id']],'role_id');
            $jf_rz = json_decode(json_encode($jf_rz),true);
            $merge_all = array_merge($jf_cc['items'],$jf_tj['items'],$jf_ms['items'],$jf_offer['items'],$jf_rz['items'],$gw_adv['items'],$jl_tg['items']);//拼接合并

            $merge_all = array_unique($merge_all,SORT_REGULAR); //去重
            $merge_all = array_filter($merge_all);//去掉无效值|  == false  的值
            $merge_clear = [];
            foreach ($merge_all as $k=>$v){
                $merge_clear[]=$v['role_id'];
            }
            //找人： 交付人的查找  --end--↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑----交付人，多个阶段对应同一个人，多个阶段对应多个人
            $where_in = $merge_clear;
            array_push($where_in,$invoice['line_role'],$invoice['bd_role'],$invoice['project_role']);

            //是否考虑有0值。
            $where_in = array_filter($where_in);//去掉无效值|  == false  的值
            $where_in = array_unique($where_in);//去重
            $uid_arr = $this->model_user->select('role_id in('.implode(',',$where_in).')','user_id,role_id,full_name');//转换为user_id
            $uid_arr = json_decode(json_encode($uid_arr),true);
            $uid_arr_final = [];
            foreach ($uid_arr['items'] as $k=>$v){
                $uid_arr_final[$v['role_id']]=$v;
            }


            $sys = $this->model->selectOne(['pro_types'=>$pro_types]);

            $re = [];//ping 结果数组  四个角色 四个子数组,每个数组包括内容有： user_id, bili,money,name
            //计算比例
            //  ['bd',$com_title['bd']]    ['pm',$com_title['pm']]     ['delivery',$com_title['delivery']]
            //线索提供人 = 有效线索提供
            $re[0] = $uid_arr_final[$invoice['line_role']];
            $re[0]['bli'] = intval($sys['effective_clue']);
            $re[0]['money'] = $re[0]['bli'] * floatval($invoice['money']) * 0.01;
            $re[0]['title'] = ['clue',$com_title['clue']];
            //BD = 合同签订 + 回款
            $re[1] = $uid_arr_final[$invoice['bd_role']];
            $re[1]['bli'] = intval($sys['contract_sign']) + intval($sys['receivable']);
            $re[1]['money'] = $re[1]['bli'] * floatval($invoice['money']) * 0.01;
            $re[1]['title'] = ['bd',$com_title['bd']];
            //项目经理 = 立项需求表、企业项目对接
            $re[2] = $uid_arr_final[$invoice['project_role']];
            $re[2]['bli'] = intval($sys['project_docking']);
            $re[2]['money'] = $re[2]['bli'] * floatval($invoice['money']) * 0.01;
            $re[2]['title'] = ['pm',$com_title['pm']];
            //交付人 = 候选人简历 + 候选人意向沟通、简历报告制作 + 候选人推荐及面试更进 + 候选人薪酬及offer谈判 + 候选人背景调查、入职更进
            $re[3] = $uid_arr_final[$merge_clear[0]];
            $re[3]['bli'] = intval($sys['resume_provision']) + intval($sys['intention_communicate']) + intval($sys['interview_follow']) + intval($sys['offer_negotiate']) + intval($sys['reference_check']);
            $re[3]['money'] = $re[3]['bli'] * floatval($invoice['money']) * 0.01;
            $re[3]['title'] = ['delivery',$com_title['delivery']];
            if(count($merge_clear)>1){
                //交付人有多个,目前找到了具体那些人，对应的哪些位置暂时没查【cc备注，推荐简历，面试(此处理解为客户面试，并非顾问面试)，offer,入职】，后期需要再加 06-19
                foreach ($merge_clear as $kk=>$vv){
                    $re[3]['users'][$kk] = $uid_arr_final[$vv];
                }
            }
            $invoice['info']['role'] = $invoice['info']['process'] = [];

            $invoice['info']['role'] = $re;
                //第二种：流程排序，每一步  一个人，一个数组
            //
            //1、线索提供
            $re2[0] = $re[0];
            $re2[0]['title'] = ['clue',$com_title2['clue']];
            //2、BD--合同签订
            $re2[1] = $uid_arr_final[$invoice['bd_role']];
            $re2[1]['bli'] = intval($sys['contract_sign']);
            $re2[1]['money'] = $re2[1]['bli'] * floatval($invoice['money']) * 0.01;
            $re2[1]['title'] = ['contract',$com_title2['contract']];
            //3、回款
            $re2[2] = $uid_arr_final[$invoice['bd_role']];
            $re2[2]['bli'] = intval($sys['receivable']);
            $re2[2]['money'] = $re2[2]['bli'] * floatval($invoice['money']) * 0.01;
            $re2[2]['title'] = ['receivable',$com_title2['receivable']];
            //4、立项需求表企业项目对接
            $re2[3] = $re[2];
            $re2[3]['title'] = ['demand',$com_title2['demand']];
            //5、候选人简历提供
            $re2[4] = $uid_arr_final[$jl_tg['items']['role_id']];
            $re2[4]['bli'] = intval($sys['resume_provision']);
            $re2[4]['money'] = $re2[4]['bli'] * floatval($invoice['money']) * 0.01;
            $re2[4]['title'] = ['resume',$com_title2['resume']];
            //6、顾问面试  候选人意向沟通、简历报告制作 $this->model_fineprojectadviser
            $re2[5] = $uid_arr_final[$jl_tg['items']['role_id']];
            $re2[5]['bli'] = intval($sys['intention_communicate']);
            $re2[5]['money'] = $re2[5]['bli'] * floatval($invoice['money']) * 0.01;
            $re2[5]['title'] = ['intention',$com_title2['intention']];
            //7、候选人推荐及面试更进
            $re2[6] = $uid_arr_final[$jf_tj['items']['role_id']];
            $re2[6]['bli'] = intval($sys['interview_follow']);
            $re2[6]['money'] = $re2[6]['bli'] * floatval($invoice['money']) * 0.01;
            $re2[6]['title'] = ['recommend',$com_title2['recommend']];
            //8、薪酬offer谈判
            $re2[7] = $uid_arr_final[$jf_offer['items']['role_id']];
            $re2[7]['bli'] = intval($sys['offer_negotiate']);
            $re2[7]['money'] = $re2[7]['bli'] * floatval($invoice['money']) * 0.01;
            $re2[7]['title'] = ['offer',$com_title2['offer']];
            //7、候选人背景调查 入职跟进
            $re2[8] = $uid_arr_final[$jf_rz['items']['role_id']];
            $re2[8]['bli'] = intval($sys['reference_check']);
            $re2[8]['money'] = $re2[8]['bli'] * floatval($invoice['money']) * 0.01;
            $re2[8]['title'] = ['entry',$com_title2['entry']];
            $invoice['info']['process'] = $re2;
            $ResultDO->code = 200;
            $ResultDO->success = TRUE;
            $ResultDO->message = '获取成功';
            $ResultDO->perform = json_encode($invoice);
            return $ResultDO;
        }else{
            $ResultDO->message = '该类型暂未设置分配比例，请先设置！';
            return $ResultDO;
        }


    }

    public function upData(\com\hlw\huilie\dataobject\yjfp\YjfpFormDTO $formDo)
    {
        // TODO: Implement yjfpUp() method.


        $this->ResultDO->success = FALSE;
        $this->ResultDO->code = 500;
            //把传值组成sql，保存；
        $sql_data = json_decode($formDo->invoice_data,true);

        //type判断
        switch($sql_data['type_id']){
            case 1:
                $com_title = $this->com_title3;//按角色分
                break;
            case 2:
                $com_title = $this->com_title2;//按流程分
                break;
        }
        unset($sql_data['type_id']);

        $invoice_id = hlw_lib_BaseUtils::getStr($formDo->invoice_id, 'int',0);
        if($invoice_id == 0){
            $this->ResultDO->message = '发票参数错误';
            return $this->ResultDO;
        }
        $invoice = $this->model_invoice->selectOne(['invoice_id'=>$invoice_id]);
        /////////////////////////////////////
//        $ddas = [$invoice];
//        $this->ResultDO->success = true;
//        $this->ResultDO->code = 233333;
//        $this->ResultDO->message = '测试查询的数组$invoice='.json_encode($invoice);
//        $this->ResultDO->data = ['lii'=>[1,2,3]];
//        $this->ResultDO->datas = $ddas;
//        return $this->ResultDO;
        /////////////////////////////////////
        //↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓ ↓
        //阻断式--屏蔽连续重复写入相同结果
        /**
         * 过程：用到client端和service端传输数据的默认关联，得到键名  clue  ，写查询语句，屏蔽重复写入相同数据
         * 优势：解决重复请求写入相同数据
         * 劣势：每次均重复连续访问2次接口的问题依然没解决
         */
            $search_where = [
                'user_id' => $sql_data['clue']['user_id'],
                'type' => $invoice['project_type'],
                'integral' => $sql_data['clue']['money'],
                'commission' => 0,
                'tikect_type' => $com_title['clue'],
                'com_id' => $invoice['customer_id'],
                'project_id' => $invoice['project_id'],
                'resume_id' => $invoice['resume_id'],
                'arrivetime' => 0
                ];
        $is_has = $this->model_achievement->selectOne($search_where,'id');
        if(is_array($is_has) && count($is_has)==1){
            $this->ResultDO->success = true;
            $this->ResultDO->code = 200;
            $this->ResultDO->message = '操作成功';
            return $this->ResultDO;
        }
        //↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑ ↑
        //重组sql_data

        $sql_str = "INSERT INTO `mx_achievement` (`user_id`, `type`, `integral`, `commission`, `tikect_type`, `com_id`, `project_id`, `resume_id`, `arrivetime`, `addtime`) VALUES";
        foreach ($sql_data as $sk=>$sv){
            $sql_str .= "(".$sv['user_id'].",'".$invoice['project_type']."',".$sv['money'].",0,'".$com_title[$sk]."',".$invoice['customer_id'].",".$invoice['project_id'].",".$invoice['resume_id'].",0,".time()."),";
        }

        $sql_str = rtrim($sql_str,',');
        try{
            $this->model_achievement->beginTransaction();
            $this->model_achievement->query($sql_str);
            $in_first_id = $this->model_achievement->lastInsertId();//插入的第一个id
            $contu = count($sql_data);
            $in_max_id = $in_first_id+$contu-1;
            $stur = '精确时间到秒看看time='.time().' | msectime='.$this->msectime();
            hlw_lib_BaseUtils::addLog($stur,'error.log','/www/wwwroot/service.hellocrab.cn/log/');
            self::writeFile($this->msectime(),'db_sql.log','/www/wwwroot/service.hellocrab.cn/log/');
            $this->model_achievement->commit();
            $this->ResultDO->success = true;
            $this->ResultDO->code = 200;
            $this->ResultDO->message = '操作成功';
            return $this->ResultDO;
        }catch (Exception $ex) {
            $this->model_achievement->rollBack();
            self::writeFile('500','db_sql.log','/www/wwwroot/service.hellocrab.cn/log/');
        }

    }

    /**
     * 返回当前的毫秒时间戳
     */
    private function msectime() {
        list($msec, $sec) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }
    private function readFile($file){
        $handle = fopen($file, "r");//读取二进制文件时，需要将第二个参数设置成'rb'
        //通过filesize获得文件大小，将整个文件一下子读到一个字符串中
        $contents = fread($handle, filesize ($file));
        return $contents;
        fclose($handle);
    }

    private function writeFile($msg, $file = 'db_sql.log', $dir = '/var/log/hlw')
    {
        //$file = $dir . $file . '.' . date('Y-m-d');
        $file = $dir . $file;
        if ((is_dir($dir) || @mkdir($dir, 0755, true)) && is_writable($dir)) {
            //$data = 'Date:' . date('Y-m-d H:i:s') . ' ' . $msg . "\n";
            $f = fopen($file, 'w');
            fwrite($f, $msg, strlen($msg));
            fclose($f);
        }
        return ;
    }

}