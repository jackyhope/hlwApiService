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
    protected $model_companypay;

    protected $model_fineproject;
    protected $model_resume;
    protected $model_fineprojectpresent;
    protected $model_cuser;
    protected $model_users;

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
        $this->model_companypay = new model_huiliewang_companypay();

        $this->model_business = new model_pinping_business();
        $this->model_customer = new model_pinping_customer();
        $this->model_fineproject = new model_pinping_fineproject();
        $this->model_resume = new model_pinping_resume();
        $this->model_fineprojectpresent = new model_pinping_fineprojectpresent();
        $this->model_cuser = new model_pinping_cuser();
        $this->model_users = new model_pinping_user();
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
        if ($l_type <= 0) {
            $Result->code = 500;
            $Result->message = '登录类型必填';
            return $Result;
        }

        if (empty($username)) {
            $Result->code = 500;
            $Result->message = '手机号必填';
            return $Result;
        }

        if (empty($code)) {
            $l_type == 1 && $Result->message = '验证码必填';
            $l_type == 2 && $Result->message = '登录密码必填';
            $Result->code = 500;
            return $Result;
        }

        if (!$this->CheckMoblie($username)) {
            $Result->code = 500;
            $Result->message = '手机号码不正确';
            return $Result;
        }
        //07-13-注意数据库字段拼写   比较妖艳！！！
        $member_msg = $this->model_member->selectOne(['moblie' => $username]);

        if (empty($member_msg)) {
            $Result->code = 500;
            $Result->message = '用户不存在';
            return $Result;
        }
        if ($l_type == 1) {
            /**阶段二：短信验证码匹配********/
            //验证短信verify码 是否一致
            //查询短信数据表匹配    待做中...

        }

        if ($l_type == 2) {
            /**阶段三：校验密码匹配********/
            $pass = $this->return_encript($code, $member_msg['salt']);//密码加密

            if ($pass != $member_msg['password']) {
                $Result->code = 500;
                $Result->message = '账号密码不正确，请重新填写!';
                return $Result;
            }
        }

        //密码相等  登录成功，需要写入登录日志表
        //$this->obj->DB_insert_once("login_log","`uid`='".$user['uid']."',`content`='".$state_content."',`ip`='".$ip."',`usertype`='".$user['usertype']."',`ctime`='".time()."'");
        try {
            $time = time();

            $insert_data = [
                'uid' => $member_msg['uid'],
                'content' => '登录成功',
                'ip' => $post_data['ip'],
                'usertype' => $member_msg['usertype'],
                'ctime' => $time
            ];
            /**
             * date 07-13 20:04:40
             * author hellocrab
             * $has_in   查询是防 数据重复插入，接口逻辑依然存在同时访问2次的情况，暂未解决。
             */
            $has_in = $this->model_loginlog->selectOne($insert_data);
            if (count($has_in) == 0) {
                $this->model_loginlog->insert($insert_data);
                //同时不重的时候，修改member表的登录ip和时间  2019-07-13 20:25   功能待定，揣测要更新，实际需要不需要不知道
                $member_update = [
                    'login_ip' => $post_data['ip'],
                    'login_date' => $time,
                    'login_hits' => $member_msg['login_hits'] + 1
                ];
                $this->model_member->update(['uid' => $member_msg['uid']], $member_update);
            }

            $Result->code = 200;
            $Result->message = '登录成功';
            $Result->data = $member_msg;

            return $Result;
        } catch (Exception $ex) {
            $Result->code = 500;
            $Result->message = '数据处理失败';
            return $Result;
        }
    }

    public function findData(\com\hlw\huiliewang\dataobject\frontLogin\FrontRequestDTO $findDo) {
        $Result = new FrontResultDTO();
        $Result->code = 200;
        $Result->success = true;
        //我要手机号，密码，type
        $post_data = $findDo->post_data;
        $verify = isset($post_data['verify']) ? hlw_lib_BaseUtils::getStr($post_data['verify'], 'int', 0) : 0;//短信验证码
        $username = isset($post_data['mobile']) ? hlw_lib_BaseUtils::getStr($post_data['mobile'], 'string', '') : '';//手机号
        $code = isset($post_data['code']) ? hlw_lib_BaseUtils::getStr($post_data['code'], 'string', '') : '';//新密码
        $recode = isset($post_data['recode']) ? hlw_lib_BaseUtils::getStr($post_data['recode'], 'string', '') : '';//重复新密码

        if (empty($username)) {
            $Result->code = 500;
            $Result->message = '手机号必填';
            return $Result;
        }

        if ($verify <= 0) {
            $Result->code = 500;
            $Result->message = '短信验证码必填';
            return $Result;
        }

        if (empty($code)) {
            $Result->code = 500;
            $Result->message = '新密码必填';
            return $Result;
        }

        if (empty($recode)) {
            $Result->code = 500;
            $Result->message = '重复密码必填';
            return $Result;
        }

        if ($code != $recode) {
            $Result->code = 500;
            $Result->message = '两次密码不一致';
            return $Result;
        }

        /**阶段二：********/
        //验证短信verify码 是否一致
        //查询短信数据表匹配     待做中...
        // ......


        /**阶段三：重设密码,更新入库********/
        try {
            $member_msg = $this->model_member->selectOne(['moblie' => $username], 'uid,password,salt');
            $new_pass = $this->return_encript($code, $member_msg['salt']);
            if ($new_pass != $member_msg['password']) {

                $update_data = [
                    'password' => $new_pass
                ];
                $this->model_member->update(['uid' => $member_msg['uid']], $update_data);

            }
            $Result->code = 200;
            $Result->message = '操作成功';
            return $Result;
        } catch (Exception $ex) {
            $Result->code = 500;
            $Result->message = '数据处理失败';
            return $Result;
        }

    }

    /*
     * 企业资料提交到数据表
     * @param  $certifyDo
     */
    public function certifyData(\com\hlw\huiliewang\dataobject\frontLogin\FrontRequestDTO $certifyDo) {
        $Result = new FrontResultDTO();
        $Result->code = 500;
        $Result->success = false;
        //接收数据
        $post_data = $certifyDo->post_data;
        //第二步：判定type，type=save，那就是新增+更新  ||  type=search 就是渲染编辑页面，只需要查询返回  ||  type=update  那就是编辑修改提交更新
        if (isset($post_data['c_type']) && in_array($post_data['c_type'], ['save', 'search', 'synchronous'])) {

            switch ($post_data['c_type']) {
                case 'save':
                    //添加

                    unset($post_data['c_type']);
                    //$add_data = $post_data;
                    //查找mobile，注册的那个手机号,作为企业联系人手机号使用
                    $phone = $this->model_member->selectOne(['uid' => $post_data['uid']], 'moblie');

                    $post_data['linktel'] = $phone['moblie'];

                    //现在是补全添加
                    // 2019-07-13-待完成
                    try {
                        $is_has_company = $this->model_company->selectOne(['uid' => $post_data['uid']], 'uid,lastupdate');

                        if (count($is_has_company) > 0) {
                            $user_msg = $this->model_member->selectOne(['uid' => $post_data['uid']], '*');
                            $Result->data = $user_msg;
                            //有这个uid对应的一条数据了，直接更新吧  ||  做个判断，时间不能小于1分钟，不然判定为重复写入
                            if (($is_has_company['lastupdate'] + 60) > $post_data['lastupdate']) {
                                // 频繁更新时间差为60秒, 小于60秒就不做操作直接返回
                                $Result->code = 200;
                                $Result->success = true;
                                $Result->message = '您提交资料将在一个工作日内完成审核，请您耐心等待!';
                                return $Result;
                            } else {
                                //允许更新status

                                $company_data = [
                                    'name' => hlw_lib_BaseUtils::getStr($post_data['name'], 'string', '未命名'),//企业名称
                                    'provinceid' => hlw_lib_BaseUtils::getStr($post_data['provinceid'], 'int', 0),//省
                                    'cityid' => hlw_lib_BaseUtils::getStr($post_data['cityid'], 'int', 0),//市
                                    'three_cityid' => hlw_lib_BaseUtils::getStr($post_data['three_cityid'], 'int', 0),//区
                                    'pro_name' => hlw_lib_BaseUtils::getStr($post_data['provincename'], 'string', '未命名'),//省会名称
                                    'cit_name' => hlw_lib_BaseUtils::getStr($post_data['cityname'], 'string', '未命名'),//市名称
                                    'thr_name' => hlw_lib_BaseUtils::getStr($post_data['three_cityname'], 'string', '未命名'),//区名称
                                    'address' => hlw_lib_BaseUtils::getStr($post_data['address'], 'string', '未命名'),//详细地址
                                    'hy' => hlw_lib_BaseUtils::getStr($post_data['hy'], 'int', 0),//行业
                                    'hyname' => hlw_lib_BaseUtils::getStr($post_data['hyname'], 'string', '未命名'),//行业名称
                                    'linkman' => hlw_lib_BaseUtils::getStr($post_data['linkman'], 'string', '未命名'),//联系人
                                    'lastupdate' => time(),//更新时间
                                    'linktel' => $post_data['linktel'],//联系电话，获取的
                                    'wt_yy_photo' => hlw_lib_BaseUtils::getStr($post_data['wt_yy_photo'], 'string', '未命名'),//营业执照
                                ];
                                /*$Result->code=666;
                                $Result->message='看到这里了不';
                                $Result->data=$company_data;
                                return $Result;*/
                                $this->model_company->update(['uid' => $post_data['uid']], $company_data);//更新到公司表
                                //status状态，username 登录名--同步更
                                $member_data = [
                                    'status' => $post_data['status'],
                                    'username' => $post_data['name']
                                ];
                                $this->model_member->update(['uid' => $post_data['uid']], $member_data);//更新到member会员表
                            }
                        } else {
                            $this->model_member->insert($post_data);
                        }
                        /*********2019-07-15-写入phpyun的company_cert表**/
                        $cert_data = [
                            'type' => 3,
                            'uid' => $post_data['uid'],
                            'check' => $post_data['wt_yy_photo'],
                            'ctime' => $post_data['lastupdate'],
                            'step' => 1,
                            'did' => 0,
                            'check2' => 0,
                            'status' => 0,//每次编辑初始化为0
                        ];
                        $has_company_cert = $this->model_companycert->selectOne(['uid' => $post_data['uid'], 'type' => 3]);

                        if (count($has_company_cert) > 0) {
                            if (($has_company_cert['ctime'] + 60) > $post_data['lastupdate']) {
                                //频繁更新时间差为60秒, 小于60秒就不做操作直接返回
                                $Result->code = 200;
                                $Result->success = true;
                                $Result->message = '您提交资料将在一个工作日内完成审核，请您耐心等待 !';
                                return $Result;
                            } else {
                                $this->model_companycert->update(['uid' => $post_data['uid'], 'type' => 3], $cert_data);
                            }
                        } else {
                            $this->model_companycert->insert($cert_data);
                        }
                        /*********2019-07-15-写入phpyun的company_cert表**/
                        $Result->code = 200;
                        $Result->success = true;
                        $Result->message = '您提交资料将在一个工作日内完成审核，请您耐心等待';
                        return $Result;
                    } catch (Exception $ex) {
                        $Result->code = 500;
                        $Result->message = '数据处理失败';
                        return $Result;
                    }
                    break;
                case 'search':
                    //编辑页面返回查找，为 search 的时候，post_data数组里面只有 uid和 type，根据uid查询
                    $company_msg = $this->model_company->selectOne(['uid' => $post_data['uid']]);
//                    var_dump($company_msg);die;
                    $Result->code = 200;
                    $Result->success = true;
                    $Result->message = '查询成功';
                    $Result->data = $company_msg;
                    return $Result;
                    break;
                case 'synchronous':
                    hlw_lib_BaseUtils::addLog(time(), 'sys.log999.txt', '/www/wwwroot/service.hellocrab.cn/log/');
                    $id_str = $post_data['id_str'];
                    $company_arr = $this->model_company->select(['uid in (' . $id_str . ')']);//查询id组对应的公司信息，审核通过走同步到OA
                    $company_arr = json_decode(json_encode($company_arr), true);
                    $company_arr = $company_arr['items'];
                    $new_up_data = [];
                    if (count($company_arr) > 0) {
                        $time = time();

                        if (count($company_arr) == 1) {
                            //只有一个
                            $this->model_customer->update(['customer_id' => $company_arr[0]['tb_customer_id']], [
                                'name' => $company_arr[0]['name'],
                                'short_name' => $company_arr[0]['name'],
                                'origin' => '慧猎网同步',
                                'location' => $company_arr[0]['address'],
                                'update_time' => $time,
                            ]);
                        } else {

                            foreach ($company_arr as $k => $v) {
                                $new_up_data[$k]['customer_id'] = $v['tb_customer_id'];
                                $new_up_data[$k]['name'] = $v['name'];
                                $new_up_data[$k]['short_name'] = $v['name'];
                                $new_up_data[$k]['origin'] = '慧猎网同步';
                                $new_up_data[$k]['location'] = $v['address'];
                                $new_up_data[$k]['update_time'] = $time;
                            }
                            $sql = $this->batchUpdate('mx_customer', $new_up_data, 'customer_id');
                            $this->model_customer->query($sql);
                        }
                    }
                    //查询并返回
                    $user_msg = $this->model_member->selectOne(['uid' => $post_data['uid']], '*');
                    $Result->code = 200;
                    $Result->message = '操作成功';
                    $Result->data = $user_msg;
                    return $Result;
                    break;
            }

        } else {
            $Result->message = '无指向操作';
            return $Result;
        }
    }

    /*
     * 更改某些表的状态字段
     * *  c_type 修改状态类型对比：   1 职位上下架
     * status  1 = 上架   |   2 = 下架
     * @param  $changeDo
     *
     */
    public function changeData(\com\hlw\huiliewang\dataobject\frontLogin\FrontRequestDTO $changeDo) {
        $Result = new FrontResultDTO();
        $Result->code = 500;
        $Result->success = false;
        $Result->message = '操作 失败';
        $allow = [1];//允许的c_type值范围   下面判断用的
        //接收数据--
        $post_data = $changeDo->post_data;
        if (!isset($post_data['c_type']) || empty($post_data['c_type']) || !in_array($post_data['c_type'], $allow)) {
            $Result->message = '修改类型ctype不能为空';
            return $Result;
        }
        if ($post_data['c_type'] == 1) {
            //职位上下架
            $huilie_job_id = hlw_lib_BaseUtils::getStr($post_data['huilie_job_id'], 'int', 0);
            $status = hlw_lib_BaseUtils::getStr($post_data['status'], 'int', 0);
            if ($huilie_job_id <= 0) {
                $Result->message = '职位参数不能为空';
                return $Result;
            }
            if ($status <= 0) {
                $Result->message = '状态内容不能为空';
                return $Result;
            }
            if (!in_array($status, [1, 2])) {
                $Result->message = '状态只能为1 or 2';
                return $Result;
            }
            //OA端，business表状态修改
            $re = $this->model_business->update(['huilie_job_id' => $huilie_job_id], ['tb_huilie_status' => $status]);
            //huilie端 company_job表 状态修改
            //涉及有效期  $time=time()+30*24*3600;
            $up_data = ['status' => $status];
            if ($status == 2) {
                $up_data['edate'] = 0;
            }
            if ($status == 1) {
                $up_data['edate'] = (time() + 30 * 24 * 3600);
            }
            $re2 = $this->model_companyjob->update(['id' => $huilie_job_id], $up_data);
            if ($re !== false && $re2 !== false) {
                //修改成功
                $Result->code = 200;
                $Result->message = '操作成功';
            }
            return $Result;

        } else {
            $Result->code = 500;
            $Result->message = '没做操作，ctype为' . $post_data['c_type'];
        }
    }

    public function jobShowData(\com\hlw\huiliewang\dataobject\frontLogin\FrontRequestDTO $jobsDo) {
        $Result = new FrontResultDTO();
        $Result->code = 500;
        $Result->success = false;
        $Result->message = '操作失败';
        $post_data = $jobsDo->post_data;
        $uid = hlw_lib_BaseUtils::getStr($post_data['uid'], 'int', 0);
        $status = hlw_lib_BaseUtils::getStr($post_data['status'], 'int');

        //当前页
        if (isset($post_data['page']) && !empty(intval($post_data['page'])) && intval($post_data['page']) > 0) {
            $page = intval($post_data['page']);
        } else {
            $page = 1;
        }
        //每页显示个数
        if (isset($post_data['size']) && !empty(intval($post_data['size'])) && intval($post_data['size']) > 0) {
            $pageSize = intval($post_data['size']);
        } else {
            $pageSize = 10;
        }
        if ($uid <= 0) {
            $Result->message = '请您先登录！';
        }
        $where = ['uid = ' . $uid, 'status =' . $status];
        $kwd = hlw_lib_BaseUtils::getStr($post_data['kwd'], 'string', '');

        if (!empty($kwd)) {
            array_push($where, "name like '%" . $kwd . "%'");
        }
        $onTotal = $this->model_companyjob->selectOne(['uid = ' . $uid, 'status =1'], 'count(*) as counts');
        $offTotal = $this->model_companyjob->selectOne(['uid = ' . $uid, 'status =2'], 'count(*) as counts');

        $this->model_companyjob->setCount(true);
        $this->model_companyjob->setPage($page);//当前第几页
        $this->model_companyjob->setLimit($pageSize);//每页几个
        $users = $this->model_users->users(['status' => 1]);
        $jobber = $this->model_companyjob->select($where, 'id,name,minsalary,maxsalary,ejob_salary_month,edate,service_type,status,sdate as add_time', '', 'order by id desc');
        if (gettype($jobber) == 'object') {
            $j1 = json_decode(json_encode($jobber), true);
            if (count($j1['items']) > 0) {
                $job_id_arr = array_column($j1['items'], 'id');//job id数组
                $job_ids = implode(',', $job_id_arr);

                $guwen = $this->model_business->query("select business_id,huilie_job_id,joiner,joiner_name,owner_role_id from mx_business where huilie_job_id in(" . $job_ids . ")");
                if (count($guwen) > 0) {
                    $guwen = array_column($guwen, null, 'huilie_job_id');
                }
                $sql = "select a.huilie_job_id,b.id,b.huilie_status from mx_business a left join mx_fine_project b on a.business_id=b.project_id where a.huilie_job_id in(" . $job_ids . ") and b.huilie_status in(0,1,2,3,4,5,6,7,8,9,10,11)";
                $all_jianli = $this->model_business->query($sql);
                $new_total = $n2 = [];
                if (count($all_jianli) > 0) {
                    foreach ($job_id_arr as $k1 => $v1) {
                        foreach ($all_jianli as $k2 => $v2) {
                            if ($v2['huilie_job_id'] == $v1) {
                                $new_total[$v1][] = $v2['huilie_status'];
                            }
                        }

                    }
                    if (count($new_total) > 0) {
                        foreach ($new_total as $nk => $nv) {
                            $new_total[$nk] = array_count_values($nv);
                            //收到的简历
                            $new_total[$nk] = array_map('intval', $new_total[$nk]);
                            $n2[$nk]['all_total'] = array_sum($new_total[$nk]);//总数，收到的简历
                            //新简历--未查看的 1
                            $n2[$nk]['new_total'] = array_key_exists(1, $new_total[$nk]) ? $new_total[$nk][1] : 0;
                            //下载的简历 4
                            $n2[$nk]['buy_total'] = array_key_exists(4, $new_total[$nk]) ? $new_total[$nk][4] : 0;
                            //待面试
                            $n2[$nk]['waiting_interview'] = array_key_exists(6, $new_total[$nk]) ? $new_total[$nk][6] : 0;
                            $n2[$nk]['waiting_interview'] += array_key_exists(8, $new_total[$nk]) ? $new_total[$nk][8] : 0;
                            //已到场
                            $n2[$nk]['already_arrive'] = array_key_exists(11, $new_total[$nk]) ? $new_total[$nk][11] : 0;
                        }
                    }
                }

                $list = $j1['items'];
                unset($j1['items']);
                foreach ($list as $kj => $vj) {
                    $list[$kj]['service_type_name'] = $vj['service_type'] == 0 ? '慧沟通' : '慧简历';
                    $list[$kj]['minsalary'] = round(intval($vj['minsalary']) * intval($vj['ejob_salary_month']) / 10000, 2);
                    $list[$kj]['minsalary'] > 0 && $list[$kj]['minsalary'] .= 'w';
                    $list[$kj]['maxsalary'] = round(intval($vj['maxsalary']) * intval($vj['ejob_salary_month']) / 10000, 2);
                    $list[$kj]['maxsalary'] > 0 && $list[$kj]['maxsalary'] .= 'w';
                    $ownerRole = @$guwen[$vj['id']]['owner_role_id'];
                    $ownerRoles = explode(',', $ownerRole);
                    if (count($guwen) > 0 && array_key_exists($vj['id'], $guwen)) {
                        $list[$kj]['joiner'] = array_key_exists('joiner', $guwen[$vj['id']]) ? $guwen[$vj['id']]['joiner'] : 0;
                        $list[$kj]['joiner_name'] = isset($ownerRoles[1]) ? $users[$ownerRoles[1]] : '待接入';

                    } else {
                        $list[$kj]['joiner'] = 0;
                        $list[$kj]['joiner_name'] = '待接入';
                    }

                    if (count($n2) > 0 && array_key_exists($vj['id'], $n2)) {
                        $list[$kj]['all_total'] = array_key_exists('all_total', $n2[$vj['id']]) ? $n2[$vj['id']]['all_total'] : 0;
                        $list[$kj]['new_total'] = array_key_exists('new_total', $n2[$vj['id']]) ? $n2[$vj['id']]['new_total'] : 0;
                        $list[$kj]['buy_total'] = array_key_exists('buy_total', $n2[$vj['id']]) ? $n2[$vj['id']]['buy_total'] : 0;
                        if ($vj['service_type'] == 1) {
                            //慧简历
                            $list[$kj]['waiting_interview'] = array_key_exists('waiting_interview', $n2[$vj['id']]) ? $n2[$vj['id']]['waiting_interview'] : 0;
                            $list[$kj]['already_arrive'] = array_key_exists('already_arrive', $n2[$vj['id']]) ? $n2[$vj['id']]['already_arrive'] : 0;
                        }
                    } else {
                        $list[$kj]['all_total'] = 0;
                        $list[$kj]['new_total'] = 0;
                        $list[$kj]['buy_total'] = 0;
                        if ($vj['service_type'] == 1) {
                            $list[$kj]['waiting_interview'] = 0;
                            $list[$kj]['already_arrive'] = 0;
                        }
                    }
                }
                $j1['totalOn'] = $onTotal['counts'] > 0 ? $onTotal['counts'] : 0;
                $j1['totalOff'] = $offTotal['counts'] > 0 ? $offTotal['counts'] : 0;
                $j1['cur_all_total'] = count($list);

                $users = $this->model_users->users(['status' => 1]);
                foreach ($list as &$info) {
                    $info['logs'] = $this->getJobStatusList($info['id'],$users);
                }
                $Result->code = 200;
                $Result->success = true;
                $Result->message = '获取成功';
                $Result->data = $j1;
                $Result->datas = $list;
                return $Result;

            } else {
                $Result->message = '什么都没找到呢';
                return $Result;
            }
        } else {
            $Result->message = '什么都没找到';
            return $Result;
        }
    }

    /**
     * 单条件查询   job_id 和  job_type 互斥
     * uid一定有，job_id可能为0， job_id=0 按uid查询所有职位对应的所有简历  || job_id >0  按job_id 查询对应简历
     *
     */
    public function resumeShowData(\com\hlw\huiliewang\dataobject\frontLogin\FrontRequestDTO $resumesDo) {
        $Result = new FrontResultDTO();
        $Result->code = 500;
        $Result->success = false;
        $Result->message = '操作失败';
        $post_data = $resumesDo->post_data;

        $uid = hlw_lib_BaseUtils::getStr($post_data['uid'], 'int', 0);

        if (isset($post_data['job_id']) && !empty(intval($post_data['job_id'])) && intval($post_data['job_id']) > 0) {
            $job_id = intval($post_data['job_id']);
        } else {
            $job_id = 0;
        }
        $kwd = hlw_lib_BaseUtils::getStr($post_data['kwd'], 'string', '');
        $is_look = 99;
        if (isset($post_data['is_look'])) {
            if ($post_data['is_look'] === 0
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
                || $post_data['is_look'] === '11') {
                $is_look = $post_data['is_look'];
            }
        }
        $job_type = 99;
        if (isset($post_data['job_type'])) {
            if ($post_data['job_type'] === 0
                || $post_data['job_type'] === '0'
                || $post_data['job_type'] === 1
                || $post_data['job_type'] === '1') {
                $job_type = $post_data['job_type'];
            }
        }

        //当前页
        if (isset($post_data['page']) && !empty(intval($post_data['page'])) && intval($post_data['page']) > 0) {
            $page = intval($post_data['page']);
        } else {
            $page = 1;
        }
        //每页显示个数
        if (isset($post_data['size']) && !empty(intval($post_data['size'])) && intval($post_data['size']) > 0) {
            $pageSize = intval($post_data['size']);
        } else {
            $pageSize = 10;
        }

        if ($uid <= 0) {
            $Result->message = 'uid不能为空！';//代表某个企业，也表示已登录状态
            return $Result;
        }
        if (!in_array($post_data['c_type'], [1, 4, 8, 99])) {
            $Result->message = '访问类型不能为空！';
            return $Result;
        }
        $fine_where = [];
        //查简历，传了职位id
        if ($job_id > 0 && !in_array($job_type, [0, 1])) {
            if ($post_data['c_type'] == 1) {
                //查询可带  是否勾选未查看
                if ($is_look != 99 && in_array($is_look, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11])) {
                    //表示 huilie_status 不为默认值，而且在可控范围内
                    $fine_where = ['huilie_status=' . $is_look];
                } else {
                    $fine_where = ['huilie_status in(1,2)'];
                }
            }
            if ($post_data['c_type'] == 8) {
                //查询可带  是否勾选未查看
                if ($is_look != 99 && in_array($is_look, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11])) {
                    //表示 huilie_status 不为默认值，而且在可控范围内
                    $fine_where = ['huilie_status=' . $is_look];
                } else {
                    $fine_where = ['huilie_status in(5,6,7)'];
                }
            }
            if ($post_data['c_type'] == 99) {
                $fine_where = ['huilie_status != 99 '];
            }

            //有职位id，直接查business_id
            $bid = $this->model_business->selectOne(['huilie_job_id' => $job_id], 'business_id,joiner,joiner_name');
            if (count($bid) >= 1) {
                //中点： 得到筛选条件 $fine_where;
                array_push($fine_where, 'project_id=' . $bid['business_id']);
                //新增条件 判断是否能直接获取到职位类型
                $z_ak = $this->model_companyjob->selectOne(['id' => $job_id], 'id,service_type');//传了职位查的
                if (count($z_ak) > 0) {
                    $z_ak[0]['project_id'] = $bid['business_id'];
                    $z_ak[0]['name'] = $z_ak['name'];
                    $z_ak = array_column($z_ak, null, 'project_id');

                }
            } else {
                $Result->message = 'OA系统没有找到该职位(项目)';
                return $Result;
            }
        }
        //hlw_lib_BaseUtils::addLog(var_export($post_data,true),'crab0813-night03.log','/home/wwwroot/');
        /*$Result->message='看$fine_where';
        $Result->data=$fine_where;
        return $Result;*/
        //没传职位id，传了类型
        //07-20 service_type 值决定职位类型   uid决定是哪个公司 卡限制 $job_type类型只能是 0 或者 1       0表示慧沟通     1表示慧简历
        if ($job_id <= 0) {
            $job_where = ['uid = ' . $uid];
            if ($job_type != 99) {
                array_push($job_where, 'service_type = ' . $job_type);
            }
//            if(!empty($kwd)){
//                array_push($job_where,"name like '%".$kwd."%'");
//            }
            $job_id_arr = $this->model_companyjob->select($job_where, 'id');
            if (gettype($job_id_arr) == 'object') {
                $job_id_arr = json_decode(json_encode($job_id_arr), true);
                $job_id_arr = $job_id_arr['items'];
                $job_id_arr = array_column($job_id_arr, 'id');//取值
                if (count($job_id_arr) > 0) {
                    $job_id_arr = implode(',', $job_id_arr);
                    //有多个职位id，直接查business_id
                    $bid_arr = $this->model_business->select(['huilie_job_id in(' . $job_id_arr . ')'], 'huilie_job_id,business_id,joiner,joiner_name');
                    //情况二：多个job 新增条件 判断是否能直接获取到职位类型
                    $z_ak = $this->model_companyjob->query('select id,service_type,`name` from phpyun_company_job where id in(' . $job_id_arr . ')');//传了职位查的

                    if (gettype($bid_arr) == 'object') {
                        $bid_arr = json_decode(json_encode($bid_arr), true);
                        $bid_arr = $bid_arr['items'];
                        if (count($bid_arr) > 0) {
                            $bid_arr = array_column($bid_arr, null, 'business_id');//取business_id为键名
                            $new_bid_arr = array_column($bid_arr, null, 'huilie_job_id');//取 huilie_job_id 为键名
                            if (count($z_ak) > 0) {
                                foreach ($z_ak as $zk => $zv) {
                                    $z_ak[$zk]['project_id'] = $new_bid_arr[$zv['id']]['business_id'];
                                }
                                $z_ak = array_column($z_ak, null, 'project_id');
                            }
                            $business_id_arr = array_column($bid_arr, 'business_id');//取值
                            $business_id_arr = implode(',', $business_id_arr);

                            if ($post_data['c_type'] == 1) {
                                //查询可带  是否勾选未查看
                                if ($is_look != 99 && in_array($is_look, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11])) {
                                    //表示 huilie_status 不为默认值，而且在可控范围内
                                    $fine_where = ['huilie_status=' . $is_look];
                                } else {
                                    $fine_where = ['huilie_status in(1,2)'];
                                }
                            }
                            if ($post_data['c_type'] == 8) {
                                //查询可带  是否勾选未查看
                                if ($is_look != 99 && in_array($is_look, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11])) {
                                    //表示 huilie_status 不为默认值，而且在可控范围内
                                    $fine_where = ['huilie_status=' . $is_look];
                                } else {
                                    $fine_where = ['huilie_status in(5,6,7)'];
                                }
                            }
                            if ($post_data['c_type'] == 4) {
                                $fine_where = ['huilie_status in(4,12)'];
                            }
                            if ($post_data['c_type'] == 99) {
                                $fine_where = ['huilie_status != 99'];
                            }
                            array_push($fine_where, 'project_id in(' . $business_id_arr . ')');
                        }
                    }
                }
            }
        }
        //hlw_lib_BaseUtils::addLog('$fine_where='.var_export($fine_where,true),'crab0813-night03.log','/home/wwwroot/');
        /*$Result->message='检测 $fine_where ';
        $Result->data = $fine_where;
        //$Result->datas = $z_ak;
        return $Result;*/
        //求简历
        if (count($fine_where) == 0) {
            $Result->message = 'OA系统没有找到该职位(项 目)';
            return $Result;
        }
        $this->model_fineproject->setCount(true);
        $this->model_fineproject->setPage($page);//当前第几页
        $this->model_fineproject->setLimit($pageSize);//每页几个
        $f_data = $this->model_fineproject->select($fine_where, 'huilie_status,`tjaddtime`,resume_id,project_id,tj_role_id,tjaddtime,id fine_id', '', 'order by tjaddtime DESC');
        //hlw_lib_BaseUtils::addLog('$this->model_fineproject='.var_export($this->model_fineproject,true),'crab0813-night+.log','/home/wwwroot/');
        if (gettype($f_data) == 'object') {
            $f_data = json_decode(json_encode($f_data), true);
            $one_data = $f_data;
            unset($one_data['items']);
            $re_arr2 = $f_data['items'];
            unset($f_data);
            if (count($re_arr2) > 0) {
                $cha_man = array_column($re_arr2, 'tj_role_id');
                $cha_man = implode(',', $cha_man);
                $tui_man = $this->model_cuser->query('select `role_id`, `full_name` from mx_user where role_id in(' . $cha_man . ')');
                if (count($tui_man) > 0) {
                    $tui_man = array_column($tui_man, null, 'role_id');
                }
                $re_arr2 = array_column($re_arr2, null, 'fine_id');//07-19不考虑 resueme_id是否重复，留注释后期调bug
                //hlw_lib_BaseUtils::addLog('$re_arr2='.var_export($re_arr2,true),'crab0813-666.log','/home/wwwroot/');

                $resume_condition = array_column($re_arr2, 'resume_id');
                $resume_condition = implode(',', $resume_condition);
                $resume_where = ["eid in(" . $resume_condition . ")"];
                if (!empty($kwd)) {
                    array_push($resume_where, "name like '%" . $kwd . "%'");
                }
                $cur_resume = $this->model_resume->select($resume_where);
                $cur_resume = json_decode(json_encode($cur_resume), true);
                $cur_resume = array_column($cur_resume['items'], null, 'eid');
                foreach ($re_arr2 as $rk => $rv) {
                    $rv['tj_namer'] = $tui_man[$rv['tj_role_id']]['full_name'];
                    $rv['service_type'] = $z_ak[$rv['project_id']]['service_type'];
                    $rv['job_name'] = $z_ak[$rv['project_id']]['name'];
                    $rk = explode('-', $rk);
                    $rk = $rk[0];
                    if (count($cur_resume[$re_arr2[$rk]['resume_id']]) > 0) {
                        $re_arr2[$rk] = array_merge($rv, $cur_resume[$re_arr2[$rk]['resume_id']]);
                        $re_arr2[$rk]['work_year'] = date('Y') - $cur_resume[$re_arr2[$rk]['resume_id']]['startWorkyear'];
                    } else {
                        unset($re_arr2[$rk]);//注销掉 简历库 没有数据的那些
                    }
                }
                sort($re_arr2);
                $one_data['cur_all_total'] = count($re_arr2);
                $last_names = array_column($re_arr2, 'tjaddtime');
                array_multisort($last_names, SORT_DESC, $re_arr2);
                $Result->code = 200;
                $Result->message = '获取简历成功';
                $Result->data = $one_data;
                $Result->datas = $re_arr2;
            } else {
                $Result->message = '没有找到相关简历信息';
            }
        } else {
            $Result->message = '没有找到相关简历列表';
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
    public function presentData(\com\hlw\huiliewang\dataobject\frontLogin\FrontRequestDTO $presentDo) {
        $Result = new FrontResultDTO();
        $Result->code = 500;
        $Result->success = false;
        $Result->message = '操作失败';
        $post_data = $presentDo->post_data;
        $c_type = hlw_lib_BaseUtils::getStr($post_data['c_type'], 'int', 0);
        $fine_id = hlw_lib_BaseUtils::getStr($post_data['fine_id'], 'int', 0);//fine_project表
        $is_from_hr = hlw_lib_BaseUtils::getStr($post_data['is_from_hr'], 'int', 0);
        $post_data['remark'] = hlw_lib_BaseUtils::getStr($post_data['remark'], 'string', '');
        $ctime = $post_data['ctime'];//传过来的时间

        if ($c_type < 0 || $fine_id <= 0) {
            $Result->message = '数据不正确';
            return $Result;
        }
        //mx_fine_project_present  到场表
        $fine_proj_arr = $this->model_fineproject->selectOne(['id' => $fine_id], 'resume_id,project_id,tj_role_id');
        $resume_msg = $this->model_resume->selectOne(['eid' => $fine_proj_arr['resume_id']], 'name');
        //简历id   fine_id   status状态   huilie_coin 扣点数  add_time   role_id顾问推荐人  is_present 是否到场  is_from_hr 是否hr
        $up_data = [
            'resume_id' => $fine_proj_arr['resume_id'],
            'resume_name' => $resume_msg['name'],
            'is_present' => $c_type,
            'is_from_hr' => $is_from_hr,
            'fine_id' => $fine_id,
            'role_id' => $fine_proj_arr['tj_role_id'],
            'status' => 1,
            'add_time' => $ctime,
        ];
        $chk_data = [
            'fine_id' => $fine_id,
            'role_id' => $fine_proj_arr['tj_role_id'],
            'is_present' => $c_type
        ];
        /*****************************二次访问规避屏蔽重复写入**/
        $is_has = $this->model_fineprojectpresent->selectOne($chk_data);

        if (count($is_has) > 0) {
            $Result->code = 200;
            $Result->success = true;
            $Result->message = '操作 成功';
            return $Result;
        }
        /*****************************二次访问规避屏蔽重复写入**/

        $huilie_job = $this->model_business->selectOne(['business_id' => $fine_proj_arr['project_id']], 'huilie_job_id,name,maxsalary');
        $company_uid = $this->model_companyjob->selectOne(['id' => $huilie_job['huilie_job_id']], 'uid');

        //仅扣除  预扣金币，实际金币无变化
        /************订单商品待定，具体扣的数值待定，从哪个表获取所扣待定。先写固定值-07-20*/
        //订单 单笔扣除多少，按pay表记录来算 start --------------
        $ck_where = [
            'resume_id' => $fine_proj_arr['resume_id'],
            'job_id' => $fine_proj_arr['project_id'],
        ];
        $order_pay_arr = $this->companyPay($ck_where, 'order_price');
        $start_coin = $order_pay_arr['order_price'];
        //hlw_lib_BaseUtils::addLog('接收数组'.var_export($post_data,true),'crab0813+.log','/home/wwwroot/');
        //hlw_lib_BaseUtils::addLog('订单数组'.var_export($order_pay_arr,true),'crab0813+.log','/home/wwwroot/');
        //hlw_lib_BaseUtils::addLog('订单扣多少 ||$start_coin '.$start_coin.' ||','crab0813+.log','/home/wwwroot/');
        //订单扣除金额取值  end -------------------------------

        /************订单商品待定，具体扣的数值待定，从哪个表获取所扣待定。先写固定值-07-20*/
        $log_data = [
            'uid' => $company_uid['uid'],
            'job_id' => $huilie_job['huilie_job_id'],
            'resume_id' => $fine_proj_arr['resume_id'],
            'resume_name' => $resume_msg['name'],
            'payd' => 0,
            'resume_payd' => 0,
            'interview_payd' => 0,
            'interview_payd_expect' => 0,
            'create_time' => $ctime,
        ];
        //到这里，都是慧面试


        if ($c_type == 1) {
            $log_data['interview_payd_expect'] = -$start_coin;//扣除点数记录
            //扣除 预扣金币和实际金币，2种情况， 一是 hr点了已到场  二是 OA端顾问点了已到场
            if ($is_from_hr == 1) {
                //hr 搞事情
                $pay_data = [
                    'order_price' => $start_coin,
                    'order_id' => mktime() . rand(10000, 99999),
                    'pay_time' => $ctime,
                    'pay_state' => 2,
                    'type' => 1,
                    'pay_type' => 2,
                    'resume_id' => $fine_proj_arr['resume_id'],
                    'resume' => $resume_msg['name'],
                    'job' => $huilie_job['name'],
                    'job_id' => $fine_proj_arr['project_id'],
                    'did' => '',
                ];
                $pay_data['com_id'] = $company_uid['uid'];
                $pay_data['pay_remark'] = '慧面试扣除-HR确认人才已到场';

                $log_data['com_id'] = $company_uid['uid'];
                $log_data['deduct_remark'] = 'HR确认人才已到场';
            } else {
                $pay_data = [
                    'com_id' => $fine_proj_arr['tj_role_id'],
                    'pay_remark' => '慧面试扣除-顾问确认人才已到场',
                    'resume_id' => $fine_proj_arr['resume_id'],
                    'resume' => $resume_msg['name'],
                    'job' => $huilie_job['name'],
                    'job_id' => $fine_proj_arr['project_id'],
                ];
                $log_data['com_id'] = $fine_proj_arr['tj_role_id'];
                $log_data['deduct_remark'] = '顾问确认人才已到场';
            }

            $up_data['huilie_coin'] = $start_coin;
            $sql = "update phpyun_company set interview_payd_expect=interview_payd_expect+" . $start_coin . ",interview_payd-" . $start_coin . " where uid=" . $company_uid['uid'];
            //hlw_lib_BaseUtils::addLog('已到场|| '.$sql.' ||','crab0813+.log','/home/wwwroot/');
            $this->model_company->query($sql);
            unset($sql);//注销sql变量
            if (empty($this->model_company->getDbError())) {
                //扣除成功,继续写表
                $this->model_fineprojectpresent->insert($up_data);
                //写日志 phpyun_company_log
                $this->companyLog($log_data);
                $this->companyPay($pay_data);
                //已到场
                $fine_update_data = [
                    'huilie_status' => 11
                ];
                $this->model_fineproject->update(['id' => $fine_id], $fine_update_data);

            } else {
                //sql执行失败
            }
        }

        if ($c_type == 0) {
            //未到场，分2种，
            if ($is_from_hr == 0) {
                $up_data['unarrive_remark'] = $log_data['deduct_remark'] = $post_data['remark'];//顾问点未到场，说明原因
                $log_data['com_id'] = $fine_proj_arr['tj_role_id'];
                //2、顾问点的未到场, 退还真实金币，扣除预扣金币
                $sql = "update phpyun_company set interview_payd_expect=interview_payd_expect+" . $start_coin . " where uid=" . $company_uid['uid'];
                //hlw_lib_BaseUtils::addLog('未到场|| '.$sql.' ||','crab0813+.log','/home/wwwroot/');
                $this->model_company->query($sql);
                unset($sql);//注销sql变量
                $log_data['interview_payd_expect'] = -$start_coin;//扣除预扣点数记录
                $log_data['interview_payd'] = $start_coin;//返还真实点数记录
                //写日志 phpyun_company_log
                $this->companyLog($log_data);
                $pay_data = [
                    'com_id' => $fine_proj_arr['tj_role_id'],
                    'pay_remark' => $post_data['remark'],
                    'resume_id' => $fine_proj_arr['resume_id'],
                    'resume' => $resume_msg['name'],
                    'job' => $huilie_job['name'],
                    'job_id' => $fine_proj_arr['project_id'],
                ];
                $this->companyPay($pay_data);
                //已到场
                $this->model_fineproject->update(['id' => $fine_id], ['huilie_status' => 10]);
            } else {
                //已到场
                $this->model_fineproject->update(['id' => $fine_id], ['huilie_status' => 9]);
            }
            //1、hr点的未到场，写一条记录即可，不扣除任何
            $this->model_fineprojectpresent->insert($up_data);
        }
        $Result->code = 200;
        $Result->success = true;
        $Result->message = '操作成功!';
        return $Result;


    }


    /**
     * 写入companyLog
     * @param $log_data
     * @return mixed
     */
    private function companyLog($log_data) {
        $this->model_companylog->insert($log_data);
    }

    private function companyPay($pay_data, $type = 'save') {
        $model = new model_huiliewang_companypay();
        $ck_where = [
            'resume_id' => $pay_data['resume_id'],
            'job_id' => $pay_data['job_id'],
        ];
        if ($type == 'save') {
            $is_has = $model->selectOne($ck_where, 'id');
            if ($is_has['id'] > 0) {
                $model->update('id=' . $is_has['id'], $pay_data);
                return $model;
            } else {
                $model->insert($pay_data);
                return $model;
            }

        } else {
            //return 一个查询结果数组，通用型 08-07
            $field_str = 'id,' . $type;
            $is_has = $model->selectOne($ck_where, $field_str);
            return $is_has;
        }
    }


    private function CheckMoblie($moblie) {
        return preg_match("/1[345789]{1}\d{9}$/", trim($moblie));
    }

    /**
     * 俩条件均不能为空，否则返回 空
     * @param $code 输入的密码
     * @param $salt  用户数据表里面的随机码
     * @return string 返回32位的加密串
     */
    private function return_encript($code, $salt) {
        if (!empty($code) && !empty($salt)) {
            return md5(md5($code) . $salt);//密码加密
        } else {
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
    private function batchUpdate($table, $data, $field, $params = []) {
        if (!is_array($data) || !$field || !is_array($params)) {
            return false;
        }

        $updates = $this->parseUpdate($data, $field);
        $where = $this->parseParams($params);

        // 获取所有键名为$field列的值，值两边加上单引号，保存在$fields数组中
        // array_column()函数需要PHP5.5.0+，如果小于这个版本，可以自己实现，
        $fields = array_column($data, $field);
        $fields = implode(',', array_map(function ($value) {
            return "'" . $value . "'";
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
    private function parseUpdate($data, $field) {
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
    private function parseParams($params) {
        $where = [];
        foreach ($params as $key => $value) {
            $where[] = sprintf("`%s` = '%s'", $key, $value);
        }

        return $where ? ' AND ' . implode(' AND ', $where) : '';
    }


    /**
     * 勾兑，以前的简历购买改版流程，变成 发起勾兑，顾问去勾兑人才，填写报表
     * @param  $blendingDo
     */
    public function blendingData(\com\hlw\huiliewang\dataobject\frontLogin\FrontRequestDTO $blendingDo) {
        $Result = new FrontResultDTO();
        $Result->code = 500;
        $Result->success = false;
        $Result->message = '操作失败';
        $post_data = $blendingDo->post_data;
        $old = $this->model_resume->selectOne(['eid' => $post_data['eid']], 'name,telephone,curCompany,curDepartment,curStatus,wantsalary,hlocation,marital_status,curSalary');
        if (count($old) == 0) {
            $Result->message = '操作失败,没有找到该简历';
            return $Result;
        }
        //继续组装旧的字段--查出来的
        $blending_data = $post_data;
        $blending_data['old_name'] = $old['name'];
        $blending_data['old_telephone'] = $old['telephone'];
        $blending_data['old_curCompany'] = $old['curCompany'];
        $blending_data['old_curDepartment'] = $old['curDepartment'];
        $blending_data['old_curStatus'] = $old['curStatus'];
        $blending_data['old_wantsalary'] = $old['wantsalary'];
        $blending_data['old_hlocation'] = $old['hlocation'];
        $blending_data['old_marital_status'] = $old['marital_status'];
        $blending_data['old_curSalary'] = $old['curSalary'];
        //写入 blending表
        try {
            $model_blend = new model_pinping_blending();
            //根据更新时间判定是否二次访问
            $is_update = $model_blend->getInfo($blending_data['fine_id'], 'update_time');
            if (count($is_update) > 0) {
                $diff_time = $is_update['update_time'] + 60 - $blending_data['update_time'];
                if ($diff_time > 0) {
                    $Result->code = 200;
                    $Result->message = '操作成功！';
                    return $Result;
                } else {
                    $model_blend->beginTransaction();
                    //第一：更新blend表数据
                    $model_blend->update(['fine_id' => $blending_data['fine_id']], $blending_data);
                    //第二：更新fine_project huilie_status状态为 沟通完成 12
                    $this->model_fineproject->update(['id' => $blending_data['fine_id']], ['huilie_status' => 12]);
                    $model_blend->commit();
                    $Result->code = 200;
                    $Result->message = '更新成功！';
                    return $Result;
                }
            } else {
                $Result->message = '记录不存在，请检查hr是否发起对该简历的慧沟通';
                return $Result;
            }
        } catch (Exception $ex) {
            $model_blend->rollBack();
            $Result->message = '操作失败！数据库操作异常！';
            return $Result;
        }
    }


    /**
     * @desc  获取职位流程
     * @param $jobId
     * @return array|void
     */
    private function getJobStatusList($jobId,$users) {
        if (!$jobId) {
            return;
        }

        //发布职位
        $logs = [];
        $businessInfo = $this->model_business->selectOne(['huilie_job_id' => $jobId]);
        $logs[1] = ['add_time' => $businessInfo['create_time'], 'name' => '发布职位', 'status' => '1小时内分配猎头'];
        $ownerRoleId = $businessInfo['owner_role_id'];
        $ownerList = explode(',', $ownerRoleId);
        $distributeRole = $ownerList[1];
        $distributeRole && $logs[2] = ['add_time' => $businessInfo['create_time'] + 3600, 'name' => '系统完成分配猎头，'.$users[$distributeRole].'为您服务', 'status' => '2小时内职位调研'];
        $distributeRole && $logs[3] = ['add_time' => $businessInfo['create_time'] + 4000, 'name' => '猎头开始职位调研', 'status' => '24小时内进行第一批次推荐'];
        $fineList = $this->model_fineproject->select(['project_id' => $jobId], '', '', 'id asc');
        //慧沟通
        $fines = $fineList->items;
        $fineIds = '';
        foreach ($fines as $fineInfo) {
            $fineIds .= $fineInfo['id'] . ',';
        }
        $fineIds = trim($fineIds, ',');
        //慧沟通
        if ($businessInfo['pro_type'] == 4 && $fineIds) {
            //第一位候选人
            $first = $fines[0];
            $first && $logs[4] = ['add_time' => $first['addtime'], 'name' => $users[$first['tracker']] . '开始推荐第一批候选人', 'status' => '候选人已推荐，等待您发起沟通'];
            //1、发起沟通
            $model_blend = new model_pinping_blending();
            $blending = $model_blend->select("fine_id in ({$fineIds})", '', '', 'create_time asc');
            $blendingFinish = $model_blend->select("fine_id in ({$fineIds}) and name is not null", '', '', 'update_time asc');
            $blendingList = $blending->items;
            $blendingFirst = $blendingList[0];
            $blendingFirst && $logs[5] = ['add_time' => $blendingFirst['create_time'], 'name' => '你发起候选人沟通', 'status' => '24小时内反馈的沟通结果'];
            //2、沟通完成
            $finishOne = $blendingFinish[0];
            $finishCount = count($blendingFinish);
            $finishOne && $logs[6] = ['add_time' => $blendingFirst['update_time'], 'name' => '第一位候选人沟通完成，请查看沟通结果', 'status' => $finishCount . '位候选人沟通完成'];
        }
        //慧面试
        if ($businessInfo['pro_type'] == 8 && $fineIds) {
            $first = $fines[0];
            $first && $logs[7] = ['add_time' => $first['addtime'], 'name' =>$users[$first['tracker']] .'开始推荐第一批候选人', 'status' => '候选人已推荐，等待您邀约'];
            $modelInterview = new model_pinping_fineprojectinterview();
            //发起邀约
            $interviewFirst = $modelInterview->selectOne("fine_id in ({$fineIds}) and is_from_hr = 1", '', '', 'addtime asc');
            $interviewFirst && $logs[8] = ['add_time' => $interviewFirst['addtime'], 'name' => '你发起候选人邀约面试', 'status' => '24小时内反馈的邀约结果'];
            //顾问确定邀约
            $interviewList = $modelInterview->select("fine_id in ({$fineIds}) and is_from_hr = 0", '', '', 'addtime asc');
            $firstByGW = $interviewList[0];
            $firstByGW && $logs[9] = ['add_time' => $firstByGW['addtime'], 'name' => '顾问已确认面试时间，请查收', 'status' => '等待候选人到场面试'];
            //到场时间到
            $presentOne = $this->model_fineprojectpresent->selectOne("fine_id in ({$fineIds})", '', '', 'id asc');
            if (!$presentOne) {
                $interViewTime = strtotime($firstByGW['timestart']);
                if ($interViewTime < time()) {
                    $logs[9] = ['add_time' => $interViewTime, 'name' => '顾问已确认面试时间，请查收', 'status' => '已到达面试时间，请确认到场'];
                }
            } else {
                //是否有到场数据
                $presentOne = $this->model_fineprojectpresent->selectOne("fine_id in ({$fineIds}) and is_present = 1", '', '', 'id asc');
                if ($presentOne) {
                    $countInfo = $this->model_fineprojectpresent->selectOne("fine_id in ({$fineIds}) and is_present = 1", "count(distinct fine_id) as sums");
                    $count = $countInfo['sums'];
                    $logs[10] = ['add_time' => $presentOne['add_time'], 'name' => 'HR已到场确认信息', 'status' => $count . '位候选人到场面试'];
                } else {
                    //未到场
                    $unPresentOne = $this->model_fineprojectpresent->selectOne("fine_id in ({$fineIds}) and is_present = 0", '', '', 'id asc');
                    $fineInfo = $this->model_fineproject->selectOne(['id' => $unPresentOne['fineId']]);
                    $fineInfo['huilie_status'] == 9 && $logs[11] = ['add_time' => $presentOne['add_time'], 'name' => 'HR未到场确认信息', 'status' => '等待顾问核实'];
                    $fineInfo['huilie_status'] == 10 && $logs[11] = ['add_time' => $presentOne['add_time'], 'name' => 'HR未到场确认信息', 'status' => '顾问已核实，候选人未到场'];
                }
            }
        }//krsort
        $list = [];
        foreach ($logs as $k => $logoInfo) {
            $logoInfo['add_time1'] = date('Y-m-d H:i:s', $logoInfo['add_time']);
            $list[$logoInfo['add_time']] = $logoInfo;
        }
        krsort($list);
        $list = array_values($list);
        return json_encode($list);
    }

}
