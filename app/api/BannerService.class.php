<?php

/**
 * app版本相关接口
 * @author yanghao <yh38615890@sina.cn>
 * @date 17-07-01
 * @copyright (c) gandianli
 */

use com\hlw\ks\interfaces\BannerServiceIf;
use com\hlw\common\dataobject\common\ResultDO;
use Exception;


class api_BannerService extends api_Abstract implements BannerServiceIf
{
    /**
     * 获取Banner
     * @param int $type
     * @return getVersionResultDTO
     */
    public function getBanners($type)
    {
        $type = hlw_lib_BaseUtils::getStr($type, 'int');
        $result = new ResultDO();
        try {
            $model = new model_newexam_banner();
            $condition['type'] = $type;
            $banners = $model->select($condition, 'fileurl,title,sort', '', 'order by sort desc')->items;
            if ($banners) {
                $result->code = 1;
                $result->data = $banners;
            } else {
                $result->code = 0;
                $result->message = $model->getDbError();
                hlw_lib_BaseUtils::addLog(json_encode($model));
            }
            $result->success = TRUE;
        } catch (Exception $ex) {
            $result->success = FALSE;
            $result->code = $ex->getCode();
            hlw_lib_BaseUtils::addLog(json_encode($ex));
        }
        return $result;
    }
}
