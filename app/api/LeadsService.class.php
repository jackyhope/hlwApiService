<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-06-18
 * Time: 16:33
 */
use com\hlw\huiliewang\interfaces\LeadsServiceIf;
use com\hlw\huiliewang\dataobject\targetInfo\TargetInfoRequestDTO;
use com\hlw\common\dataobject\common\ResultDO;

class api_LeadsService extends api_Abstract implements LeadsServiceIf
{
    //设置晋级业绩
    function setTarget(TargetInfoRequestDTO $targetDo)
    {
        $resultDO = new ResultDO();
        $type = intval($targetDo->type);
        $A2 = $targetDo->rank_A2;
        $A3 = $targetDo->rank_A3;
        $A4 = $targetDo->rank_A4;
        $C1 = $targetDo->rank_C1;
        $C2 = $targetDo->rank_C2;
        $C3 = $targetDo->rank_C3;
        $C4 = $targetDo->rank_C4;
        $C5 = $targetDo->rank_C5;
        $C6 = $targetDo->rank_C6;
        $D1 = $targetDo->rank_D1;
        $D2 = $targetDo->rank_D2;
        $D3 = $targetDo->rank_D3;
        $D4 = $targetDo->rank_D4;
        $D5 = $targetDo->rank_D5;
        $D6 = $targetDo->rank_D6;
        $D7 = $targetDo->rank_D7;
        $D8 = $targetDo->rank_D8;
        $D9 = $targetDo->rank_D9;
        $D10 = $targetDo->rank_D10;
        $P1 = $targetDo->rank_P1;
        $S3 = $targetDo->rank_S3;
        $S4 = $targetDo->rank_S4;
        $S5 = $targetDo->rank_S5;
        $S6 = $targetDo->rank_S6;
        $S7 = $targetDo->rank_S7;
        $S8 = $targetDo->rank_S8;
        $S9 = $targetDo->rank_S9;
        $rankTarget_model = new model_pinping_ranktarget();
        $jobrank_model = new model_pinping_jobrank();
        switch ($type){
            case 5:
                $idA2 = $jobrank_model->selectOne(['name'=>'A2'],'id');
                $rankTarget_model->update(['typeId'=>5,'rankId'=>intval($idA2['id'])],['target'=>$A2]);
                $idC6 = $jobrank_model->selectOne(['name'=>'C6'],'id');
                $rankTarget_model->update(['typeId'=>5,'rankId'=>intval($idC6['id'])],['target'=>$C6]);
                $idS3 = $jobrank_model->selectOne(['name'=>'S3'],'id');
                $rankTarget_model->update(['typeId'=>5,'rankId'=>intval($idS3['id'])],['target'=>$S3]);
                $idS4 = $jobrank_model->selectOne(['name'=>'S4'],'id');
                $rankTarget_model->update(['typeId'=>5,'rankId'=>intval($idS4['id'])],['target'=>$S4]);
                $idS5 = $jobrank_model->selectOne(['name'=>'S5'],'id');
                $rankTarget_model->update(['typeId'=>5,'rankId'=>intval($idS5['id'])],['target'=>$S5]);
                $idS6 = $jobrank_model->selectOne(['name'=>'S6'],'id');
                $rankTarget_model->update(['typeId'=>5,'rankId'=>intval($idS6['id'])],['target'=>$S6]);
                $idS7 = $jobrank_model->selectOne(['name'=>'S7'],'id');
                $rankTarget_model->update(['typeId'=>5,'rankId'=>intval($idS7['id'])],['target'=>$S7]);
                $idS8 = $jobrank_model->selectOne(['name'=>'S8'],'id');
                $rankTarget_model->update(['typeId'=>5,'rankId'=>intval($idS8['id'])],['target'=>$S8]);
                $idS9 = $jobrank_model->selectOne(['name'=>'S9'],'id');
                $rankTarget_model->update(['typeId'=>5,'rankId'=>intval($idS9['id'])],['target'=>$S9]);
                $resultDO->success = TRUE;
                $resultDO->code = 200;
                $resultDO->message = '设置成功';
                break;
            default:
                $idA2 = $jobrank_model->selectOne(['name'=>'A2'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idA2['id'])],['target'=>$A2]);
                $idA3 = $jobrank_model->selectOne(['name'=>'A3'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idA3['id'])],['target'=>$A3]);
                $idA4 = $jobrank_model->selectOne(['name'=>'A4'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idA4['id'])],['target'=>$A4]);
                $idC1 = $jobrank_model->selectOne(['name'=>'C1'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idC1['id'])],['target'=>$C1]);
                $idC2 = $jobrank_model->selectOne(['name'=>'C2'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idC2['id'])],['target'=>$C2]);
                $idC3 = $jobrank_model->selectOne(['name'=>'C3'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idC3['id'])],['target'=>$C3]);
                $idC4 = $jobrank_model->selectOne(['name'=>'C4'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idC4['id'])],['target'=>$C4]);
                $idC5 = $jobrank_model->selectOne(['name'=>'C5'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idC5['id'])],['target'=>$C5]);
                $idC6 = $jobrank_model->selectOne(['name'=>'C6'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idC6['id'])],['target'=>$C6]);
                $idD9 = $jobrank_model->selectOne(['name'=>'D9'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idD9['id'])],['target'=>$D9]);
                $idD8 = $jobrank_model->selectOne(['name'=>'D8'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idD8['id'])],['target'=>$D8]);
                $idD1 = $jobrank_model->selectOne(['name'=>'D1'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idD1['id'])],['target'=>$D1]);
                $idD2 = $jobrank_model->selectOne(['name'=>'D2'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idD2['id'])],['target'=>$D2]);
                $idD3 = $jobrank_model->selectOne(['name'=>'D3'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idD3['id'])],['target'=>$D3]);
                $idD4 = $jobrank_model->selectOne(['name'=>'D4'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idD4['id'])],['target'=>$D4]);
                $idD5 = $jobrank_model->selectOne(['name'=>'D5'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idD5['id'])],['target'=>$D5]);
                $idD6 = $jobrank_model->selectOne(['name'=>'D6'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idD6['id'])],['target'=>$D6]);
                $idD7 = $jobrank_model->selectOne(['name'=>'D7'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idD7['id'])],['target'=>$D7]);
                $idD10 = $jobrank_model->selectOne(['name'=>'D10'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idD10['id'])],['target'=>$D10]);
                $idP1 = $jobrank_model->selectOne(['name'=>'P1'],'id');
                $rankTarget_model->update(['typeId'=>$type,'rankId'=>intval($idP1['id'])],['target'=>$P1]);
                $resultDO->success = TRUE;
                $resultDO->code = 200;
                $resultDO->message = '设置成功';
                break;
        }

        if(empty($type)){
            $resultDO->code = 400;
            $resultDO->message = '业务类型值空';
        }

        return $resultDO;
    }

    //取出业绩数据
    function getTarget($type)
    {
        // TODO: Implement getTarget() method.
        $rankTarget_model = new model_pinping_ranktarget();
        $rank_data = $rankTarget_model->select(['typeId'=>$type],'rankId,target')->items;
        $resultDO = new ResultDO();
        $resultDO ->success = true;
        $resultDO ->code = 200;
        $resultDO ->message = '';
        $resultDO ->datas = $rank_data;
        return $resultDO;
    }

    //显示考勤表数据
    function workfile($p, $listrows)
    {
        // TODO: Implement workfile() method.
        $resultDO = new ResultDO();
        $file_model = new model_pinping_file();
        $file_model->setCount(true);
        $forcount = $file_model->select(['file_type'=>1]);
        $file_model->setLimit($listrows);
        $file_model->setPage($p);
        $data = $file_model->select(['file_type'=>1],'*','','order by create_date desc')->items;
        $data[]['totalcount'] = $forcount->pageSize;
        $resultDO->datas = $data;
        $resultDO->code = 200;
        $resultDO->success = true;
        return $resultDO;
    }

    function deletefile($id)
    {
        // TODO: Implement deletefile() method.
        $resultDO = new ResultDO();

        $resultDO->success = false;
        $resultDO->code = 400;
        $resultDO->message = '删除失败';

        $file_model = new model_pinping_file();
        $data = $file_model->selectOne(['file_id'=>$id]);
        @unlink($data['file_path']);
        $is_deleted = $file_model->delete(['file_id'=>$id]);
        if($is_deleted !== false ){
            $resultDO->success = true;
            $resultDO->code = 200;
            $resultDO->message = '删除成功';
        }
        return $resultDO;
    }
}