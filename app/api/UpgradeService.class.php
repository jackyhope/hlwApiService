<?php

/**
 * app版本相关接口
 * @author yanghao <yh38615890@sina.cn>
 * @date 17-07-01
 * @copyright (c) gandianli
 */

use com\hlw\ks\interfaces\UpgradeServiceIf;
use com\hlw\ks\dataobject\upgreade\versionContentResultDTO;
use com\hlw\ks\dataobject\upgreade\getVersionResultDTO;
use Exception;


class api_UpgradeService extends api_Abstract implements UpgradeServiceIf
{
    /**
     * 获取最新的版本信息
     * @param type $versionType
     * @return getVersionResultDTO
     */
    public function getVersion($versionType)
    {
        $versionType = hlw_lib_BaseUtils::getStr($versionType, 'int');
        $result = new getVersionResultDTO();
        try {
            $model = new model_newexam_version();
            $condition['type'] = $versionType;
            $version = $model->selectOne($condition, 'version_id,title,url,type,content,time', '', 'order by id desc');
            if ($version) {
                $result->versionId = $version['version_id'];
                $result->title = $version['title'];
                $result->versiontype = $version['type'];
                $result->time = strtotime('Y-m-d H:i:s', $version['time']);
                $result->url = $version['url'];
            } else {
                $result->success = FALSE;
                $result->code = 0;
                hlw_lib_BaseUtils::addLog(json_encode($model));
            }
        } catch (Exception $ex) {
            $result->success = FALSE;
            $result->code = $ex->getCode();
            hlw_lib_BaseUtils::addLog(json_encode($ex));
        }
        $result->success = TRUE;
        $result->code = 1;
        return $result;
    }

    /**
     * 获取版本的更新内容
     * @param type $versionId
     * @return versionContentResultDTO
     */
    public function getVersionContent($versionId) 
    {
        $versionId = hlw_lib_BaseUtils::getStr($versionId, 'int');
        $result = new versionContentResultDTO();
        try {
            $model = new model_newexam_appversion();
            $condition['version_id'] = $versionId;
            $version = $model->selectOne($condition, 'content,time', '', 'order by id desc');

            if ($version) {
                $result->content = $version['content'];
                $result->time = strtotime('Y-m-d H:i:s', $version['time']);
            } else {
                $result->success = FALSE;
                $result->code = 0;
                hlw_lib_BaseUtils::addLog(json_encode($model));
            }
        } catch (Exception $ex) {
            $result->success = FALSE;
            $result->code = $ex->getCode();
            hlw_lib_BaseUtils::addLog(json_encode($ex));
        }
        $result->success = TRUE;
        $result->code = 1;
        return $result;
    }

}
