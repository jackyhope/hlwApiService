<?php
/**
 * date       2019/07/12
 * author     hellocrab
 * 企业会员注册，企业忘记密码，企业审核资料提交
 */
use com\hlw\huiliewang\interfaces\FrontLoginServiceIf;//继承
use com\hlw\huiliewang\dataobject\frontLogin\FrontResultDTO;//定制返回值对象
use com\hlw\common\dataobject\common\ResultDO;//通用返回值对象

class api_FrontLoginService extends api_Abstract implements FrontLoginServiceIf
{

    protected $ResultDO;
    protected $model_member;
    protected $model_loginlog;
    protected $model_company;
    protected $model_companycert;
    protected $model_business;
    protected $model_customer;
    protected $model_companyjob;
    protected $model_companylog;

    protected $model_fineproject;
    protected $model_resume;
    protected $model_fineprojectpresent;

    /**
     * api_FrontLoginService constructor.
     * 自动执行
     */
    public function __construct()
    {
        $this->ResultDO = new ResultDO();//common 公共result
        $this->model_member = new model_huiliewang_member();
        $this->model_loginlog = new model_huiliewang_loginlog();
        $this->model_company = new model_huiliewang_company();
        $this->model_companycert = new model_huiliewang_companycert();
        $this->model_companyjob = new model_huiliewang_companyjob();
        $this->model_companylog = new model_huiliewang_companylog();

        $this->model_business = new model_pinping_business();
        $this->model_customer = new model_pinping_customer();
        $this->model_fineproject = new model_pinping_fineproject();
        $this->model_resume = new model_pinping_resume();
        $this->model_fineprojectpresent = new model_pinping_fineprojectpresent();
    }

    /*
     * @param FrontRequestDTO $frontDo
     * @return FrontResultDTO
     */
    public function loginData(\com\hlw\huiliewang\dataobject\frontLogin\FrontRequestDTO $frontDo)
    {
        $Result = new FrontResultDTO();
        $Result->code=200;
        $Result->success=true;
        //我要手机号，密码，type
        $post_data = $frontDo->post_data;
        $l_type = isset($post_data['l_type']) ? hlw_lib_BaseUtils::getStr($post_data['l_type'],'int',0) : 0;
        $username = isset($post_data['mobile']) ? hlw_lib_BaseUtils::getStr($post_data['mobile'],'string','') : '';//手机号
        $code = isset($post_data['code']) ? hlw_lib_BaseUtils::getStr($post_data['code'],'string',''):'';//验证码/密码
        /***********************************/
        /*$Result->code=500;
        $Result->message='测试数据';
        $Result->data=$post_data;
        return $Result;*/
        /***********************************/
        if($l_type<=0){
            $Result->code=500;
            $Result->message='登录类型必填';
            return $Result;
        }

        if(empty($username)){
            $Result->code=500;
            $Result->message='手机号必填';
            return $Result;
        }

        if(empty($code)){
            $l_type==1 && $Result->message='验证码必填';
            $l_type==2 && $Result->message='登录密码必填';
            $Result->code=500;
            return $Result;
        }

        if(!$this->CheckMoblie($username)){
            $Result->code=500;
            $Result->message='手机号码不正确';
            return $Result;
        }
        //07-13-注意数据库字段拼写   比较妖艳！！！
        $member_msg = $this->model_member->selectOne(['moblie'=>$username]);

        if(empty($member_msg)){
            $Result->code=500;
            $Result->message='用户不存在';
            return $Result;
        }
        if($l_type==1){
            /**阶段二：短信验证码匹配********/
            //验证短信verify码 是否一致
            //查询短信数据表匹配    待做中...

        }

        if($l_type==2){
            /**阶段三：校验密码匹配********/
            $pass = $this->return_encript($code,$member_msg['salt']);//密码加密

            if($pass != $member_msg['password']){
                $Result->code=500;
                $Result->message='账号密码不正确，请重新填写!';
                return $Result;
            }
        }

        //密码相等  登录成功，需要写入登录日志表
        //$this->obj->DB_insert_once("login_log","`uid`='".$user['uid']."',`content`='".$state_content."',`ip`='".$ip."',`usertype`='".$user['usertype']."',`ctime`='".time()."'");
        try{
            $time = time();

            $insert_data = [
                'uid'=>$member_msg['uid'],
                'content'=>'登录成功',
                'ip'=>$post_data['ip'],
                'usertype'=>$member_msg['usertype'],
                'ctime' =>$time
            ];
            /**
             * date 07-13 20:04:40
             * author hellocrab
             * $has_in   查询是防 数据重复插入，接口逻辑依然存在同时访问2次的情况，暂未解决。
             */
            $has_in = $this->model_loginlog->selectOne($insert_data);
            if(count($has_in)==0){
                $this->model_loginlog->insert($insert_data);
                //同时不重的时候，修改member表的登录ip和时间  2019-07-13 20:25   功能待定，揣测要更新，实际需要不需要不知道
                $member_update = [
                    'login_ip'=>$post_data['ip'],
                    'login_date'=>$time,
                    'login_hits'=>$member_msg['login_hits']+1
                ];
                $this->model_member->update(['uid'=>$member_msg['uid']],$member_update);
            }

            $Result->code=200;
            $Result->message='登录成功';
            $Result->data=$member_msg;

            return $Result;
        }catch (Exception $ex) {
            $Result->code=500;
            $Result->message='数据处理失败';
            return $Result;
        }
    }

    public function findData(\com\hlw\huiliewang\dataobject\frontLogin\FrontRequestDTO $findDo)
    {
        $Result = new FrontResultDTO();
        $Result->code=200;
        $Result->success=true;
        //我要手机号，密码，type
        $post_data = $findDo->post_data;
        $verify = isset($post_data['verify']) ? hlw_lib_BaseUtils::getStr($post_data['verify'],'int',0) : 0;//短信验证码
        $username = isset($post_data['mobile']) ? hlw_lib_BaseUtils::getStr($post_data['mobile'],'string','') : '';//手机号
        $code = isset($post_data['code']) ? hlw_lib_BaseUtils::getStr($post_data['code'],'string',''):'';//新密码
        $recode= isset($post_data['recode']) ? hlw_lib_BaseUtils::getStr($post_data['recode'],'string',''):'';//重复新密码

        if(empty($username)){
            $Result->code=500;
            $Result->message='手机号必填';
            return $Result;
        }

        if($verify<=0){
            $Result->code=500;
            $Result->message='短信验证码必填';
            return $Result;
        }

        if(empty($code)){
            $Result->code=500;
            $Result->message='新密码必填';
            return $Result;
        }

        if(empty($recode)){
            $Result->code=500;
            $Result->message='重复密码必填';
            return $Result;
        }

        if($code != $recode){
            $Result->code=500;
            $Result->message='两次密码不一致';
            return $Result;
        }

        /**阶段二：********/
        //验证短信verify码 是否一致
        //查询短信数据表匹配     待做中...
        // ......


        /**阶段三：重设密码,更新入库********/
        try{
            $member_msg = $this->model_member->selectOne(['moblie'=>$username],'uid,password,salt');
            $new_pass = $this->return_encript($code,$member_msg['salt']);
            if($new_pass != $member_msg['password']){

                $update_data = [
                    'password'=>$new_pass
                ];
                $this->model_member->update(['uid'=>$member_msg['uid']],$update_data);

            }
            $Result->code=200;
            $Result->message='操作成功';
            return $Result;
        }catch (Exception $ex) {
            $Result->code=500;
            $Result->message='数据处理失败';
            return $Result;
        }

    }

    /*
     * 企业资料提交到数据表
     * @param  $certifyDo
     */
    public function certifyData(\com\hlw\huiliewang\dataobject\frontLogin\FrontRequestDTO $certifyDo)
    {
        $Result = new FrontResultDTO();
        $Result->code=500;
        $Result->success=false;
        //接收数据
        $post_data = $certifyDo->post_data;
        //第二步：判定type，type=save，那就是新增+更新  ||  type=search 就是渲染编辑页面，只需要查询返回  ||  type=update  那就是编辑修改提交更新
        if(isset($post_data['c_type']) && in_array($post_data['c_type'],['save','search','synchronous'])){

            switch ($post_data['c_type']){
                case 'save':
                    //添加
                    unset($post_data['c_type']);
                    $add_data = $post_data;
                    //查找mobile，注册的那个手机号,作为企业联系人手机号使用
                    $phone = $this->model_member->selectOne(['uid'=>$post_data['uid']],'moblie');
                    $post_data['linktel']=$phone['moblie'];
                    //现在是补全添加
                    // 2019-07-13-待完成
                    try{
                        $is_has_company = $this->model_company->selectOne(['uid'=>$post_data['uid']],'uid,lastupdate');
                        if(count($is_has_company)>0){
                            //有这个uid对应的一条数据了，直接更新吧  ||  做个判断，时间不能小于1分钟，不然判定为重复写入
                            if(($is_has_company['lastupdate']+60)>$post_data['lastupdate']){
                                // 频繁更新时间差为60秒, 小于60秒就不做操作直接返回
                                $Result->code=200;
                                $Result->success=true;
                                $Result->message = '您提交资料将在一个工作日内完成审核，请您耐心等待!';
                                return $Result;
                            }else{
                                //允许更新status
                                $company_data = $post_data;
                                unset($company_data['status']);
                                unset($company_data['wt_yy_photo']);
                                $this->model_company->update(['uid'=>$post_data['uid']],$company_data);//更新到公司表
                                //status状态，username 登录名--同步更
                                $member_data = [
                                    'status'=>$post_data['status'],
                                    'username'=>$post_data['name']
                                ];
                                $this->model_member->update(['uid'=>$post_data['uid']],$member_data);//更新到member会员表
                            }
                        }else{
                            $this->model_member->insert($post_data);
                        }
                        /*********2019-07-15-写入phpyun的company_cert表**/
                        $cert_data = [
                            'check'=>$post_data['wt_yy_photo'],
                            'ctime'=>$post_data['lastupdate'],
                            'step'=>1,
                            'did'=>0,
                            'check2'=>0
                        ];
                        $has_company_cert = $this->model_companycert->selectOne(['uid'=>$post_data['uid'],'type'=>3]);
                        if(count($has_company_cert)>0){
                            if(($has_company_cert['ctime']+60) >$post_data['lastupdate']){
                                //频繁更新时间差为60秒, 小于60秒就不做操作直接返回
                                $Result->code=200;
                                $Result->success=true;
                                $Result->message = '您提交资料将在一个工作日内完成审核，请您耐心等待 !';
                                return $Result;
                            }else{
                                $this->model_companycert->update(['uid'=>$post_data['uid'],'type'=>3],$cert_data);
                            }
                        }else{
                            $this->model_companycert->add($cert_data);
                        }
                        /*********2019-07-15-写入phpyun的company_cert表**/
                        $Result->code=200;
                        $Result->success=true;
                        $Result->message = '您提交资料将在一个工作日内完成审核，请您耐心等待';
                        return $Result;
                    }catch (Exception $ex) {
                        $Result->code=500;
                        $Result->message='数据处理失败';
                        return $Result;
                    }
                    break;
                case 'search':
                    //编辑页面返回查找，为 search 的时候，post_data数组里面只有 uid和 type，根据uid查询
                    $company_msg = $this->model_company->selectOne(['uid'=>$post_data['uid']]);
//                    var_dump($company_msg);die;
                    $Result->code=200;
                    $Result->success=true;
                    $Result->message = '查询成功';
                    $Result->data = $company_msg;
                    return $Result;
                    break;
                case 'synchronous':
                    hlw_lib_BaseUtils::addLog(time(),'sys.log999.txt','/www/wwwroot/service.hellocrab.cn/log/');
                    $id_str = $post_data['id_str'];
                    $company_arr = $this->model_company->select(['uid in ('.$id_str.')']);//查询id组对应的公司信息，审核通过走同步到OA
                    $company_arr = json_decode(json_encode($company_arr),true);
                    $company_arr = $company_arr['items'];
                    $new_up_data = [];
                    if(count($company_arr)>0){
                        $time = time();

                        if(count($company_arr)==1){
                            //只有一个
                            $this->model_customer->update(['customer_id'=>$company_arr[0]['tb_customer_id']],[
                                'name'=>$company_arr[0]['name'],
                                'short_name'=>$company_arr[0]['name'],
                                'origin'=>'慧猎网同步',
                                'location'=>$company_arr[0]['address'],
                                'update_time'=>$time,
                            ]);
                        }else{

                            foreach ($company_arr as $k=>$v){
                                $new_up_data[$k]['customer_id']=$v['tb_customer_id'];
                                $new_up_data[$k]['name']=$v['name'];
                                $new_up_data[$k]['short_name']=$v['name'];
                                $new_up_data[$k]['origin']='慧猎网同步';
                                $new_up_data[$k]['location']=$v['address'];
                                $new_up_data[$k]['update_time']=$time;
                            }
                            $sql = $this->batchUpdate('mx_customer',$new_up_data,'customer_id');
                            $this->model_customer->query($sql);
                        }
                    }
                    $Result->code = 200;
                    $Result->message='操作成功';
                    /*$Result->data = [$sql];*/
                    return $Result;
                    break;
            }

        }else{
            $Result->message='无指向操作';
            return $Result;
        }
    }

    /*
     * 更改某些表的状态字段
     * *  c_type 修改状态类型对比：   1 职位上下架
     * @param  $changeDo
     *
     */
    public function changeData(\com\hlw\huiliewang\dataobject\frontLogin\FrontRequestDTO $changeDo)
    {
        $Result = new FrontResultDTO();
        $Result->code=500;
        $Result->success=false;
        $Result->message='操作失败';
        $allow = [1];//允许的c_type值范围   下面判断用的
        //接收数据--
        $post_data = $changeDo->post_data;
        if(!isset($post_data['c_type']) || empty($post_data['c_type']) || in_array($post_data['c_type'],$allow)){
            $Result->message='修改类型ctype不能为空';
            return $Result;
        }
        if($post_data['c_type']==1){
            //职位上下架
            $huilie_job_id = hlw_lib_BaseUtils::getStr($post_data['huilie_job_id'],'int',0);
            $status = hlw_lib_BaseUtils::getStr($post_data['status'],'int',0);
            if($huilie_job_id<=0){
                $Result->message='职位参数不能为空';
                return $Result;
            }
            if($status<=0){
                $Result->message='状态内容不能为空';
                return $Result;
            }
            //OA端，business表状态修改
            $re = $this->model_business->update(['huilie_job_id'=>$huilie_job_id],['tb_huilie_status'=>$status]);
            //huilie端 company_job表 状态修改
            //涉及有效期  $time=time()+30*24*3600;
            $up_data= ['status'=>$status];
            if($status==2) {$up_data['edate']=0;}
            if($status==1) {$up_data['edate']=(time()+30*24*3600);}
            $re2 = $this->model_companyjob->update(['id'=>$huilie_job_id],$up_data);
            if($re!==false && $re2!==false){
                //修改成功
                $Result->code=200;
                $Result->message='操作成功';
            }
            return $Result;

        }else{
            $Result->code=500;
            $Result->message='没做操作，ctype为'.$post_data['c_type'];
        }
    }

    public function jobShowData(\com\hlw\huiliewang\dataobject\frontLogin\FrontRequestDTO $jobsDo)
    {
        $Result = new FrontResultDTO();
        $Result->code=500;
        $Result->success=false;
        $Result->message='操作失败';
        $post_data = $jobsDo->post_data;
        $uid = hlw_lib_BaseUtils::getStr($post_data['uid'],'int',0);

        //当前页
        if(isset($post_data['page']) && !empty(intval($post_data['page'])) && intval($post_data['page']) > 0){
            $page = intval($post_data['page']);
        }else{
            $page = 1;
        }
        //每页显示个数
        if(isset($post_data['size']) && !empty(intval($post_data['size'])) && intval($post_data['size']) > 0){
            $pageSize = intval($post_data['size']);
        }else{
            $pageSize = 10;
        }
        if($uid<=0){
            $Result->message='请您先登录！';
        }
        $where = ['uid = '.$uid];
        $kwd = hlw_lib_BaseUtils::getStr($post_data['kwd'],'string','');

        if(!empty($kwd)){
            array_push($where,"name like '%".$kwd."%'");
        }
        $this->model_companyjob->setCount(true);
        $this->model_companyjob->setPage($page);//当前第几页
        $this->model_companyjob->setLimit($pageSize);//每页几个

        $jobber = $this->model_companyjob->select($where,'id,name,minsalary,maxsalary,ejob_salary_month,edate','','order by id asc');
        if(gettype($jobber)=='object'){
            $j1 = json_decode(json_encode($jobber),true);
            if(count($j1['items'])>0){
                $job_id_arr = array_column($j1['items'],'id');//job id数组
                $job_ids = implode(',',$job_id_arr);

                $guwen = $this->model_business->query("select business_id,huilie_job_id,joiner,joiner_name from mx_business where huilie_job_id in(".$job_ids.")");
                if(count($guwen)>0){
                    $guwen = array_column($guwen,null,'huilie_job_id');
                }
                $sql = "select a.huilie_job_id,b.id,b.huilie_status from mx_business a left join mx_fine_project b on a.business_id=b.project_id where a.huilie_job_id in(".$job_ids.") and b.huilie_status in(0,1,2,3,4,5,6,7,8,9,10,11)";
                $all_jianli = $this->model_business->query($sql);
                $new_total = $n2 = [];
                if(count($all_jianli)>0){
                    foreach ($job_id_arr as $k1=>$v1){
                        foreach ($all_jianli as $k2=>$v2){
                            if($v2['huilie_job_id']==$v1){
                                $new_total[$v1][]=$v2['huilie_status'];
                            }
                        }

                    }
                    if(count($new_total)>0){
                        foreach ($new_total as $nk=>$nv){
                            $new_total[$nk]=array_count_values($nv);
                            //收到的简历
                            $new_total[$nk] = array_map('intval',$new_total[$nk]);
                            $n2[$nk]['all_total'] = array_sum($new_total[$nk]);//总数，收到的简历
                            //新简历--未查看的 1
                            $n2[$nk]['new_total'] = array_key_exists(1,$new_total[$nk])?$new_total[$nk][1]:0;
                            //下载的简历 4
                            $n2[$nk]['buy_total'] = array_key_exists(4,$new_total[$nk])?$new_total[$nk][4]:0;
                        }
                    }
                }

                $list = $j1['items'];
                unset($j1['items']);
                foreach ($list as $kj=>$vj){
                    $list[$kj]['minsalary'] = intval($vj['minsalary']) * intval($vj['ejob_salary_month']);
                    $list[$kj]['maxsalary'] = intval($vj['maxsalary']) * intval($vj['ejob_salary_month']);
                    if(count($guwen)>0 && array_key_exists($vj['id'],$guwen)){
                        $list[$kj]['joiner'] = array_key_exists('joiner',$guwen[$vj['id']])?$guwen[$vj['id']]['joiner']:0;
                        $list[$kj]['joiner_name'] =array_key_exists('joiner_name',$guwen[$vj['id']])?$guwen[$vj['id']]['joiner_name']:'无';

                    }else{
                        $list[$kj]['joiner'] = 0;
                        $list[$kj]['joiner_name'] = '无';
                    }
                    if(count($n2)>0 && array_key_exists($vj['id'],$n2)){
                        $list[$kj]['all_total']=array_key_exists('all_total',$n2[$vj['id']])?$n2[$vj['id']]['all_total']:0;
                        $list[$kj]['new_total']=array_key_exists('new_total',$n2[$vj['id']])?$n2[$vj['id']]['new_total']:0;
                        $list[$kj]['buy_total']=array_key_exists('buy_total',$n2[$vj['id']])?$n2[$vj['id']]['buy_total']:0;
                    }else{
                        $list[$kj]['all_total']=0;
                        $list[$kj]['new_total']=0;
                        $list[$kj]['buy_total']=0;
                    }
                }
                $Result->code=200;
                $Result->success=true;
                $Result->message='获取成功';
                $Result->data = $j1;
                $Result->datas = $list;
                return $Result;

            }else{
                $Result->message='什么都没找到呢';
                return $Result;
            }
        }else{
            $Result->message='什么都没找到';
            return $Result;
        }
    }

    /**
     * 单条件查询   job_id 和  job_type 互斥
     * uid一定有，job_id可能为0， job_id=0 按uid查询所有职位对应的所有简历  || job_id >0  按job_id 查询对应简历
     *
     */
    public function resumeShowData(\com\hlw\huiliewang\dataobject\frontLogin\FrontRequestDTO $resumesDo)
    {
        $Result = new FrontResultDTO();
        $Result->code=500;
        $Result->success=false;
        $Result->message='操作失败';
        $post_data = $resumesDo->post_data;

        $uid = hlw_lib_BaseUtils::getStr($post_data['uid'],'int',0);

        if(isset($post_data['job_id']) && !empty(intval($post_data['job_id'])) && intval($post_data['job_id']) > 0){
            $job_id = intval($post_data['job_id']);
        }else{
            $job_id = 0;
        }
        $kwd = hlw_lib_BaseUtils::getStr($post_data['kwd'],'string','');
        $is_look = 99;
        if(isset($post_data['is_look'])){
            if($post_data['is_look'] === 0
                || $post_data['is_look'] === '0'
                || $post_data['is_look'] === 1
                || $post_data['is_look'] === '1'
                || $post_data['is_look'] === 2
                || $post_data['is_look'] === '2'
                || $post_data['is_look'] === 3
                || $post_data['is_look'] === '3'
                || $post_data['is_look'] === 4
                || $post_data['is_look'] === '4'
                || $post_data['is_look'] === 5
                || $post_data['is_look'] === '5'
                || $post_data['is_look'] === 6
                || $post_data['is_look'] === '6'
                || $post_data['is_look'] === 7
                || $post_data['is_look'] === '7'
                || $post_data['is_look'] === 8
                || $post_data['is_look'] === '8'
                || $post_data['is_look'] === 9
                || $post_data['is_look'] === '9'
                || $post_data['is_look'] === 10
                || $post_data['is_look'] === '10'
                || $post_data['is_look'] === 11
                || $post_data['is_look'] === '11')
            {
                $is_look = $post_data['is_look'];
            }
        }
        $job_type = 99;
        if(isset($post_data['job_type'])){
            if($post_data['job_type'] === 0
                || $post_data['job_type'] === '0'
                || $post_data['job_type'] === 1
                || $post_data['job_type'] === '1')
            {
                $job_type = $post_data['job_type'];
            }
        }

        //当前页
        if(isset($post_data['page']) && !empty(intval($post_data['page'])) && intval($post_data['page']) > 0){
            $page = intval($post_data['page']);
        }else{
            $page = 1;
        }
        //每页显示个数
        if(isset($post_data['size']) && !empty(intval($post_data['size'])) && intval($post_data['size']) > 0){
            $pageSize = intval($post_data['size']);
        }else{
            $pageSize = 10;
        }

        if($uid<=0){
            $Result->message='uid不能为空！';//代表某个企业，也表示已登录状态
            return $Result;
        }
        $fine_where = [];
        //查简历，传了职位id
        if($job_id>0 && !in_array($job_type,[0,1])){
            //查询可带  是否勾选未查看
            if($is_look!=99 && in_array($is_look,[0,1,2,3,4,5,6,7,8,9,10,11])){
                //表示 huilie_status 不为默认值，而且在可控范围内
                $fine_where = ['huilie_status'=>$is_look];
            }
            //有职位id，直接查business_id
            $bid = $this->model_business->selectOne(['huilie_job_id'=>$job_id],'business_id,joiner,joiner_name');
            if(count($bid)>=1){
                //中点： 得到筛选条件 $fine_where;
                $fine_where['project_id']=$bid['business_id'];
            }else{
                $Result->message='OA系统没有找到该职位(项目)';
                return $Result;
            }
        }
        //没传职位id，传了类型
        //07-20 service_type 值决定职位类型   uid决定是哪个公司 卡限制 $job_type类型只能是 0 或者 1       0表示慧沟通     1表示慧简历
        if($job_id<=0){
            $job_where = ['uid = '.$uid];
            if($job_type != 99){
                array_push($job_where,'service_type = '.$job_type);
            }
            if(!empty($kwd)){
                array_push($job_where,"name like '%".$kwd."%'");
            }
            $job_id_arr = $this->model_companyjob->select($job_where,'id');
            if(gettype($job_id_arr)=='object'){
                $job_id_arr = json_decode(json_encode($job_id_arr),true);
                $job_id_arr = $job_id_arr['items'];
                $job_id_arr = array_column($job_id_arr,'id');//取值
                if(count($job_id_arr)>0){
                    $job_id_arr = implode(',',$job_id_arr);
                    //有多个职位id，直接查business_id
                    $bid_arr = $this->model_business->select(['huilie_job_id in('.$job_id_arr.')'],'business_id,joiner,joiner_name');
                    if(gettype($bid_arr)=='object'){
                        $bid_arr = json_decode(json_encode($bid_arr),true);
                        $bid_arr = $bid_arr['items'];
                        if(count($bid_arr)>0){
                            $bid_arr = array_column($bid_arr,null,'business_id');//取business_id为键名
                            $business_id_arr = array_column($bid_arr,'business_id');//取值
                            $business_id_arr = implode(',',$business_id_arr);
                            if($is_look!=99 && in_array($is_look,[0,1,2,3,4,5,6,7,8,9,10,11])){
                                //表示 huilie_status 不为默认值，而且在可控范围内
                                $fine_where = ['huilie_status = '.$is_look];
                            }
                            $fine_where[]='project_id in('.$business_id_arr.')';
                        }
                    }
                }
            }
        }

        //求简历
        if(count($fine_where)==0){
            $Result->message='OA系统没有找到该职位(项 目)';
            return $Result;
        }
        $this->model_fineproject->setCount(true);
        $this->model_fineproject->setPage($page);//当前第几页
        $this->model_fineproject->setLimit($pageSize);//每页几个
        $f_data = $this->model_fineproject->select($fine_where,'huilie_status,`tjaddtime`,resume_id');
        if(gettype($f_data)=='object'){
            $f_data = json_decode(json_encode($f_data),true);
            $one_data = $f_data;unset($one_data['items']);
            $re_arr2 = $f_data['items']; unset($f_data);
            if(count($re_arr2)>0){
                $re_arr2 = array_column($re_arr2,null,'resume_id');//07-19不考虑 resueme_id是否重复，留注释后期调bug
                $resume_condition = array_column($re_arr2,'resume_id');
                $resume_condition = implode(',',$resume_condition);
                $cur_resume = $this->model_resume->select(["eid in(".$resume_condition.")"]);
                $cur_resume = json_decode(json_encode($cur_resume),true);
                $cur_resume = array_column($cur_resume['items'],null,'eid');
                foreach ($re_arr2 as $rk=>$rv){
                    unset($rv['resume_id']);
                    if(count($cur_resume[$rk])>0){
                        $re_arr2[$rk] = array_merge($rv,$cur_resume[$rk]);
                    }else{
                        unset($re_arr2[$rk]);//注销掉 简历库 没有数据的那些
                    }
                }
                $Result->message = '获取简历成功'.$resume_condition;
                $Result->data = $one_data;
                $Result->datas = $re_arr2;
            }else{
                $Result->message='没有找到相关简历信息';
            }
        }else{
            $Result->message='没有找到相关简历列表';
        }
        return $Result;
    }

    /**
     * $c_type  确认是否到场。到场为 1  未到场为 0
     * @param $presentDo
     * @return FrontResultDTO
     * 分来源：  1、来与huilie端，hr操作的
     *          2、OA端，顾问操作的
     * $is_from_hr=0 不是HR干的，   $is_from_hr=1  就是HR干的
     */
    public function presentData(\com\hlw\huiliewang\dataobject\frontLogin\FrontRequestDTO $presentDo)
    {
        $Result = new FrontResultDTO();
        $Result->code=500;
        $Result->success=false;
        $Result->message='操作失败';
        $post_data = $presentDo->post_data;
        $c_type = hlw_lib_BaseUtils::getStr($post_data['c_type'],'int',0);
        $fine_id = hlw_lib_BaseUtils::getStr($post_data['fine_id'],'int',0);//fine_project表
        $is_from_hr = hlw_lib_BaseUtils::getStr($post_data['is_from_hr'],'int',0);
        $ctime = $post_data['ctime'];//传过来的时间

        if($c_type<=0 || $fine_id<=0){
            $Result->message='数据不正确';
            return $Result;
        }
        //mx_fine_project_present  到场表
        $fine_proj_arr = $this->model_fineproject->selectOne(['fine_id'=>$fine_id],'resume_id,project_id,tj_role_id');
        $resume_msg = $this->model_resume->selectOne(['eid'=>$fine_proj_arr['resume_id']],'name');
        //简历id   fine_id   status状态   huilie_coin 扣点数  add_time   role_id顾问推荐人  is_present 是否到场  is_from_hr 是否hr
        $up_data = [
            'resume_id'=>$fine_proj_arr['resume_id'],
            'resume_name' => $resume_msg['name'],
            'is_present'=> $c_type,
            'is_from_hr'=>$is_from_hr,
            'fine_id' => $fine_id,
            'role_id' => $fine_proj_arr['tj_role_id'],
            'status' =>1,
            'add_time'=>$ctime,
        ];
        $chk_data = [
            'fine_id' => $fine_id,
            'role_id' => $fine_proj_arr['tj_role_id'],
            'is_present'=> $c_type
        ];
        /*****************************二次访问规避屏蔽重复写入**/
        $is_has = $this->model_fineprojectpresent->selectOne($chk_data);
        if(count($is_has) > 0){
            $Result->code=200;
            $Result->success=true;
            $Result->message='操作 成功';
            return $Result;
        }
        /*****************************二次访问规避屏蔽重复写入**/

        $huilie_job = $this->model_business->selectOne(['business_id'=>$fine_proj_arr['project_id']],'huilie_job_id');
        $company_uid = $this->model_companyjob->selectOne(['id'=>$huilie_job['huilie_job_id']],'uid');

        //仅扣除  预扣金币，实际金币无变化
        /************订单商品待定，具体扣的数值待定，从哪个表获取所扣待定。先写固定值-07-20*/
        $start_coin=1;
        /************订单商品待定，具体扣的数值待定，从哪个表获取所扣待定。先写固定值-07-20*/
        $log_data = [
            'uid' => $company_uid['uid'],
            'job_id' => $huilie_job['huilie_job_id'],
            'resume_id' => $fine_proj_arr['resume_id'],
            'payd' => 0,
            'resume_payd' => 0,
            'interview_payd' => 0,
            'interview_payd_expect' => 0,
            'create_time' => $ctime,
        ];
        if($c_type==1){
            $log_data['interview_payd_expect'] = -$start_coin;//扣除点数记录
            //扣除 预扣金币和实际金币，2种情况， 一是 hr点了已到场  二是 OA端顾问点了已到场
            if($is_from_hr==1){
                //hr 搞事情
                $log_data['com_id'] = $company_uid['uid'];
                $log_data['deduct_remark'] = 'HR确认人才已到场';
            }else{
                $log_data['com_id'] = $fine_proj_arr['tj_role_id'];
                $log_data['deduct_remark'] = '顾问确认人才已到场';
            }
            //订单 单笔起扣点，  config_sy_orderstart_faceview
            $up_data['huilie_coin'] = $start_coin;
            $sql = "update phpyun_company set interview_payd_expect=interview_payd_expect-".$start_coin." where uid=".$company_uid['uid'];
            $this->model_company->query($sql);unset($sql);//注销sql变量
            if(empty($this->model_company->getDbError())){
                //扣除成功,继续写表
                $this->model_fineprojectpresent->insert($up_data);
                //写日志 phpyun_company_log
                $this->companyLog($log_data);
            }else{
                //sql执行失败
            }
            //已到场
            $this->model_fineproject->update(['fine_id'=>$fine_id],['huilie_status'=>11]);
        }
        if($c_type==0){
            //未到场，分2种，
            //1、hr点的未到场，写一条记录即可，不扣除任何
            $this->moel_fineprojectpresent->insert($up_data);
            if($is_from_hr==0){
                $log_data['com_id'] = $fine_proj_arr['tj_role_id'];
                $log_data['deduct_remark'] = '顾问确认人才未到场';
                //2、顾问点的未到场, 退还真实金币，扣除预扣金币
                $sql = "update phpyun_company set interview_payd_expect=interview_payd_expect-".$start_coin.",interview_payd=interview_payd+".$start_coin." where uid=".$company_uid['uid'];
                $this->model_company->query($sql);unset($sql);//注销sql变量
                $log_data['interview_payd_expect'] = -$start_coin;//扣除预扣点数记录
                $log_data['interview_payd'] = $start_coin;//返还真实点数记录
                //写日志 phpyun_company_log
                $this->companyLog($log_data);
                //已到场
                $this->model_fineproject->update(['fine_id'=>$fine_id],['huilie_status'=>10]);
            }else{
                //已到场
                $this->model_fineproject->update(['fine_id'=>$fine_id],['huilie_status'=>9]);
            }
        }


    }




    /**
     * 写入companyLog
     * @param $log_data
     * @return mixed
     */
    private function companyLog($log_data){
        $this->model_companylog->insert($log_data);
        return $this->model_companylog->lastInsertId();
    }
    private function CheckMoblie($moblie){
        return preg_match("/1[345789]{1}\d{9}$/",trim($moblie));
    }

    /**
     * 俩条件均不能为空，否则返回 空
     * @param $code 输入的密码
     * @param $salt  用户数据表里面的随机码
     * @return string 返回32位的加密串
     */
    private function return_encript($code,$salt){
        if(!empty($code) && !empty($salt)){
            return md5(md5($code).$salt);//密码加密
        }else{
            return '';
        }
    }

    /**
     * 批量更新函数
     * @param $table string 数据表
     * @param $data array 待更新的数据，二维数组格式
     * @param array $params array 值相同的条件，键值对应的一维数组
     * @param string $field string 值不同的条件，默认为id
     * @return bool|string
     */
    private function batchUpdate($table,$data, $field, $params = [])
    {
        if (!is_array($data) || !$field || !is_array($params)) {
            return false;
        }

        $updates = $this->parseUpdate($data, $field);
        $where = $this->parseParams($params);

        // 获取所有键名为$field列的值，值两边加上单引号，保存在$fields数组中
        // array_column()函数需要PHP5.5.0+，如果小于这个版本，可以自己实现，
        $fields = array_column($data, $field);
        $fields = implode(',', array_map(function($value) {
            return "'".$value."'";
        }, $fields));

        $sql = sprintf("UPDATE `%s` SET %s WHERE `%s` IN (%s) %s", $table, $updates, $field, $fields, $where);

        return $sql;
    }

    /**
     * 将二维数组转换成CASE WHEN THEN的批量更新条件
     * @param $data array 二维数组
     * @param $field string 列名
     * @return string sql语句
     */
    private function parseUpdate($data, $field)
    {
        $sql = '';
        $keys = array_keys(current($data));
        foreach ($keys as $column) {

            $sql .= sprintf("`%s` = CASE `%s`  ", $column, $field);
            foreach ($data as $line) {
                $sql .= sprintf("WHEN '%s' THEN '%s'  ", $line[$field], $line[$column]);
            }
            $sql .= "END,";
        }

        return rtrim($sql, ',');
    }

    /**
     * 解析where条件
     * @param $params
     * @return array|string
     */
    private function parseParams($params)
    {
        $where = [];
        foreach ($params as $key => $value) {
            $where[] = sprintf("`%s` = '%s'", $key, $value);
        }

        return $where ? ' AND ' . implode(' AND ', $where) : '';
    }


}
