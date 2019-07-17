<?php
/**
 *++++++++++++++++++++++++++++++++++++++++++++++++++
 * DESC: 公司信息
 * User: SOSO
 * Date: 2019/7/13
 *+++++++++++++++++++++++++++++++++++++++++++++++++++
 */

use com\hlw\huiliewang\interfaces\company\CompanyInfoServiceIf;
use com\hlw\huiliewang\dataobject\company\CompanyInfoRequestDTO;
use com\hlw\common\dataobject\common\ResultDO;

class api_CompanyInfoService extends api_Abstract implements CompanyInfoServiceIf
{
    protected $memberModel;
    protected $companyModel;
    protected $companyJobModel;
    protected $resultDo;

    /**
     * @desc 修改
     * @param CompanyInfoRequestDTO $infoRequestDo
     */
    public function save(CompanyInfoRequestDTO $infoRequestDo) {
        $this->memberModel = new model_huiliewang_member();
        $this->companyModel = new model_huiliewang_company();
        $this->companyJobModel = new model_huiliewang_companyjob();
        $this->resultDo = new ResultDO();
        $uid = hlw_lib_BaseUtils::getStr($infoRequestDo->uid); //用户ID
        $name = hlw_lib_BaseUtils::getStr($infoRequestDo->name); //公司名
        $address = hlw_lib_BaseUtils::getStr($infoRequestDo->address);#公司地址
        $linkman = hlw_lib_BaseUtils::getStr($infoRequestDo->linkman);#联系人
        $linktel = hlw_lib_BaseUtils::getStr($infoRequestDo->linktel);#联系人电话
        $hy = hlw_lib_BaseUtils::getStr($infoRequestDo->hy, 'int');#企业行业
        $pr = hlw_lib_BaseUtils::getStr($infoRequestDo->pr, 'int');#企业性质
        $provinceid = hlw_lib_BaseUtils::getStr($infoRequestDo->provinceid, 'int');#所在地城市
        $cityid = hlw_lib_BaseUtils::getStr($infoRequestDo->cityid, 'int');#所在地市
        $three_cityid = hlw_lib_BaseUtils::getStr($infoRequestDo->three_cityid, 'int');#所在地地区
        $phoneone = hlw_lib_BaseUtils::getStr($infoRequestDo->phoneone);#固定电话 头部
        $phonetwo = hlw_lib_BaseUtils::getStr($infoRequestDo->phonetwo);#固定电话 中部
        $phonethree = hlw_lib_BaseUtils::getStr($infoRequestDo->phonethree);#固定电话 尾部
        $content = hlw_lib_BaseUtils::getStr($infoRequestDo->content,'html');#企业简介
        $sdate = hlw_lib_BaseUtils::getStr($infoRequestDo->sdate);#创办时间
        $moneytype = hlw_lib_BaseUtils::getStr($infoRequestDo->moneytype, 'int');#企业性质
        $zip = hlw_lib_BaseUtils::getStr($infoRequestDo->zip);#邮政编码
        $linkqq = hlw_lib_BaseUtils::getStr($infoRequestDo->linkqq);#QQ
        $linkmail = hlw_lib_BaseUtils::getStr($infoRequestDo->linkmail);#邮箱
        $website = hlw_lib_BaseUtils::getStr($infoRequestDo->website);#网址
        $busstops = hlw_lib_BaseUtils::getStr($infoRequestDo->busstops);#公交站
        $infostatus = hlw_lib_BaseUtils::getStr($infoRequestDo->infostatus, 'int');#联系方式查看状态
        $comqcode = hlw_lib_BaseUtils::getStr($infoRequestDo->comqcode);#二维码
        $logo = hlw_lib_BaseUtils::getStr($infoRequestDo->logo);#LOGO
        $firmpic = hlw_lib_BaseUtils::getStr($infoRequestDo->firmpic);//firmpic
        $mun = hlw_lib_BaseUtils::getStr($infoRequestDo->mun, 'int');//firmpic
        $linkjob = hlw_lib_BaseUtils::getStr($infoRequestDo->linkjob);//linkjob
        $money = hlw_lib_BaseUtils::getStr($infoRequestDo->money, 'int');//money
        $welfare = hlw_lib_BaseUtils::getStr($infoRequestDo->welfare, 'int');//福利待遇
        $link_phone = '';

        $this->resultDo->success = false;
        $this->resultDo->code = 500;
        if (!$uid) {
            $this->resultDo->message = '缺少uid';
            return $this->resultDo;
        }
        $userInfo = $this->memberModel->selectOne(['uid' => $uid]);
        if (!$userInfo) {
            $this->resultDo->code = 400;
            $this->resultDo->message = '用户信息获取失败';
            return $this->resultDo;
        }
        $this->resultDo->message = '';
        //企业全称不能为空
        !$name && $this->resultDo->message = '企业全称不能为空';
        //从事行业不能为空
        !$hy && $this->resultDo->message = '从事行业不能为空';
        //企业性质不能为空
        !$pr && $this->resultDo->message = '企业性质不能为空';
        //所在地不能为空
        !$provinceid && $this->resultDo->message = '所在地不能为空';
        //企业规模不能为空

        //公司地址不能为空
        !$address && $this->resultDo->message = '公司地址不能为空';
        //企业简介不能为空
        !$content && $this->resultDo->message = '企业简介不能为空';
        //验证电话
        $memberData = [];

        if ($linktel) {
            $where = " uid <> {$uid} and moblie = '{$linktel}'";
            $res = $this->memberModel->selectOne($where);
            $res && $this->resultDo->message = '该电话已经存在';
            //格式验证
            $isMobile = hlw_lib_BaseUtils::IsMobile($linktel);
            !$isMobile && $this->resultDo->message = '手机号格式错误';
            $memberData['moblie'] = $linktel;
        }
        //验证邮箱
        if ($linkmail) {
            $where = " uid <> {$uid} and email = '{$linkmail}'";
            $res = $this->memberModel->selectOne($where);
            $res && $this->resultDo->message = '该邮箱已经存在';
            $isEmail = hlw_lib_BaseUtils::IsEmail($linkmail);
            !$isEmail && $this->resultDo->message = "联系邮箱格式错误";
            $memberData['email'] = $linkmail;
        }

        //公司名是否存在
        if ($name) {
            $companyExist = $this->companyModel->selectOne("uid <> {$uid} and name = '{$name}'");
            $companyExist && $this->resultDo->message = "企业全称已经存在";
        }
        //座机
        $linkPhone = [];
        if ($phonetwo && $phonetwo != '座机号') {
            if ($phoneone == '' || $phoneone == '区号') {
                $this->resultDo->message = "请填写座机区号！";
            } else {
                $linkPhone[] = $phoneone;
            }
            ($phonetwo && $phonetwo != '座机号') && $linkPhone[] = $phonetwo;
            ($phonethree && $phonetwo != '分机号') && $linkPhone[] = $phonethree;
            $link_phone = @implode('-', $linkPhone);
        }
        ($linktel == "" && $link_phone == '') && $this->resultDo->message = "联系手机和固定电话任填一项！";
        if ($this->resultDo->message) {
            $this->resultDo->message = iconv("UTF-8", "GB2312//IGNORE", $this->resultDo->message);
            return $this->resultDo;
        }
        $content = str_replace(array("&amp;", "background-color:#ffffff", "background-color:#fff", "white-space:nowrap;"), array("&", 'background-color:', 'background-color:', 'white-space:'), html_entity_decode($content, ENT_QUOTES, "GB2312"));

        //数据修改
        $data = [
            'name' => $name ? $name : '',
            'hy' => $hy ? $hy : 0,
            'pr' => $pr ? $pr : 0,
            'provinceid' => $provinceid ? $provinceid : 0,
            'cityid' => $cityid ? $cityid : 0,
            'three_cityid' => $three_cityid ? $three_cityid : 0,
            'mun' => $mun ? $mun : 0,
            'sdate' => $sdate ? $sdate : '',
            'money' => $money ? $money : 0,
            'content' => $content ? $content : "",
            'address' => $address ? $address : '',
            'zip' => $zip ? $zip : 0,
            'linkman' => $linkman ? $linkman : '',
            'linkjob' => $linkjob ? $linkjob : '',
            'linkqq' => $linkqq ? $linkqq : 0,
            'linkphone' => $link_phone ? $link_phone : '',
            'linktel' => $linktel ? $linktel : '',
            'linkmail' => $linkmail ? $linkmail : '',
            'website' => $website ? $website : '',
            'logo' => $logo ? $logo : '',
            'firmpic' => $firmpic ? $firmpic : '',
            'busstops' => $busstops ? $busstops : '',
            'infostatus' => $infostatus ? $infostatus : 0,
            'moneytype' => $moneytype ? $moneytype : '',
            'comqcode' => $comqcode ? $comqcode : '',
            'welfare' => $welfare ? $welfare : '',
            'lastupdate' => time(),
        ];
        try {
            $where = ['uid' => $uid];
            $this->companyModel->update($where, $data);
            $companyJobData = ['com_name' => $data['name'], 'pr' => $data['pr'], 'mun' => $data['mun'], 'com_provinceid' => $data['provinceid']];
            $this->companyJobModel->update($where, $companyJobData);
            $this->memberModel->update($where, $memberData);
        } catch (Exception $e) {
            $this->resultDo->message = iconv("UTF-8", "GB2312//IGNORE", $e->getMessage());
            $this->resultDo->success = false;
            $this->resultDo->code = 500;
            return $this->resultDo;
        }
        $this->resultDo->success = TRUE;
        $this->resultDo->code = 200;
        $this->resultDo->message = iconv("UTF-8", "GB2312//IGNORE", '更新成功');
        return $this->resultDo;
    }

}