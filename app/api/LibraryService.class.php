<?php

/**
 * 图书馆相关接口
 * @author yanghao <yh38615890@sina.cn>
 * @date 17-06-27
 * @copyright (c) gandianli
 */

use com\hlw\ks\interfaces\LibraryServiceIf;
use com\hlw\common\dataobject\common\ResultDO;
use com\hlw\ks\dataobject\library\LibraryResultDTO;
use com\hlw\ks\dataobject\library\LibraryListRequestDTO;
class api_LibraryService extends api_Abstract implements LibraryServiceIf 
{
    public function LibraryCategorylist($admin_reg)
    {
        $resultDO = new ResultDO();
        $condition = array();

        $admin_reg = gdl_lib_BaseUtils::getStr($admin_reg);

        try {
            if ($admin_reg) {
                $modelLibrary = new model_newexam_librarycategory();
                $condition = array(
                    'admin_reg' => $admin_reg,
                    'isdelete' => 0
                );

                $res = $modelLibrary->select(
                                $condition, 'id,c_name', '', 'order by id desc')
                        ->items;
                if (!empty($res)) {
                    $resultDO->data = $res;
                    $resultDO->code = 1;
                } else {
                    $resultDO->code = 0;
                }
            } else {
                $resultDO->code = 0;
            }
            $resultDO->success = true;
            return $resultDO;
        } catch (Exception $e) {
            $resultDO->success = false;
            $resultDO->message = $e->getMessage();
            $resultDO->code = $e->getCode();
        }
        return $resultDO;
    }

    /**
     * 图书馆材料列表
     * @param LibraryListRequestDTO $librarylistDO
     * @return ResultDO
     */
    public function Librarylist(LibraryListRequestDTO $librarylistDO)
    {
        $resultDO = new ResultDO();
        $condition = array();

        $admin_reg = $librarylistDO->admin_reg ? gdl_lib_BaseUtils::getStr($librarylistDO->admin_reg) : '';
        $share = $librarylistDO->share ? gdl_lib_BaseUtils::getStr($librarylistDO->share) : 0;
        $cateid = $librarylistDO->catid ? gdl_lib_BaseUtils::getStr($librarylistDO->catid) : 0;
		$offset = $librarylistDO->offset ? gdl_lib_BaseUtils::getStr($librarylistDO->offset, 'int') : 0;
		$num = $librarylistDO->num ? gdl_lib_BaseUtils::getStr($librarylistDO->num, 'int') : 10;
		$page = $offset*$num;

        try {
            if ($admin_reg) {
                $modelLibrary = new model_newexam_library();
                $where = array(
                    'admin_reg' => $admin_reg,
                    'catid' => $cateid
                );

                $res = $modelLibrary->select(
                                $where, 'title,l_type,keyword,fileurl,id,introduce,create_time', '', 'order by id desc limit '.$page.','.$num)
                        ->items;
                if (!empty($res)) {
                    $resultDO->data = $res;
                    $resultDO->code = 1;
                } else {
                    $resultDO->code = 0;
                }
            } else {
                $resultDO->code = 0;
            }
            $resultDO->success = true;
            return $resultDO;
        } catch (Exception $e) {
            $resultDO->success = false;
            $resultDO->message = $e->getMessage();
            $resultDO->code = $e->getCode();
        }
        return $resultDO;
    }

    /**
     * 获取学习材料
     * @param int $libraryId
     * @return $materailResultDO
     */
    public function getLibrary($libraryId)
    {
        $libraryResultDO = new LibraryResultDTO();

        if (!$libraryId) {
            $materailResultDO->success = FALSE;
            $materailResultDO->message = '缺少图书馆资料ID';
            return $libraryResultDO;
        }

        try {
            $libraryId = gdl_lib_BaseUtils::getStr($libraryId, 'int');
            $modelLibrary = new model_newexam_library();

            $resLibrary = $modelLibrary->selectOne(array('id' => $libraryId), 'id,title,l_type,fileurl');

            if ($resLibrary) {
                $libraryResultDO->success = TRUE;
                $libraryResultDO->message = '读取成功';
                $libraryResultDO->id = $resLibrary['id'];
                $libraryResultDO->title = $resLibrary['title'];
                $libraryResultDO->l_type = $resLibrary['l_type'];
                $libraryResultDO->fileurl = $resLibrary['fileurl'];
            } else {
                $libraryResultDO->success = FALSE;
                $libraryResultDO->message = '未找到资料';
            }
            return $libraryResultDO;
        } catch (Exception $e) {
            $libraryResultDO->success = false;
            $libraryResultDO->message = $e->getMessage();
        }
        return $libraryResultDO;
    }
}
