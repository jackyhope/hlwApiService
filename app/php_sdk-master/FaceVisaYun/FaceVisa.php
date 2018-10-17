<?php

namespace FaceVisaYun;

class FaceVisa
{
    // 30 days
    const EXPIRED_SECONDS = 2592000;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_SERVER_ERROR = 500;

    /**
     * return the status message
     */
    public static function statusText($status)
    {
        switch ($status)
        {
        case 0:
          $statusText = 'CONNECT_FAIL';
          break;
        case 200:
          $statusText = 'HTTP OK';
          break;
        case 400:
          $statusText = 'BAD_REQUEST';
          break;
        case 401:
          $statusText = 'UNAUTHORIZED';
          break;
        case 403:
          $statusText = 'FORBIDDEN';
          break;
        case 404:
          $statusText = 'NOTFOUND';
          break;
        case 411:
          $statusText = 'REQ_NOLENGTH';
          break;
        case 423:
          $statusText = 'SERVER_NOTFOUND';
          break;
        case 424:
          $statusText = 'METHOD_NOTFOUND';
          break;
        case 425:
          $statusText = 'REQUEST_OVERFLOW';
          break;
        case 500:
          $statusText = 'INTERNAL_SERVER_ERROR';
          break;
        case 503:
          $statusText = 'SERVICE_UNAVAILABLE';
          break;
        case 504:
          $statusText = 'GATEWAY_TIME_OUT';
          break;
        default:
            $statusText =$status;
            break;
        }
        return $statusText;
    }
    /**
     * return the status message
     */
    public static function getStatusText() {
        $info=Http::info();
        $status=$info['http_code'];
        return self::statusText($status);
    }
   
   /**
     * @brief facecompare
     * @param image_path_a 待比对的A图片数据
     * @param image_path_b 待比对的B图片数据
     * @return 返回的结果，JSON字符串，字段参见API文档
     */
    public static function facecompare($image_path_a, $image_path_b) {

        $real_image_path_a = realpath($image_path_a);
        $real_image_path_b = realpath($image_path_b);
        if (!file_exists($real_image_path_a))
        {
            return array('httpcode' => 0, 'code' => self::HTTP_BAD_REQUEST, 'message' => 'file '.$image_path_a.' not exists', 'data' => array());
        }

        if (!file_exists($real_image_path_b))
        {
            return array('httpcode' => 0, 'code' => self::HTTP_BAD_REQUEST, 'message' => 'file '.$image_path_b.' not exists', 'data' => array());
        }

        $expired = time() + self::EXPIRED_SECONDS;
        $postUrl = Conf::$END_POINT . 'youtu/api/facecompare';
        $sign = Auth::appSign($expired, Conf::$USER_ID);

        $image_data_a = file_get_contents($real_image_path_a);
        $image_data_b = file_get_contents($real_image_path_b);

        $post_data = array(
            'app_id' =>  Conf::$APPID,
            'imageA' =>  base64_encode($image_data_a),
            'imageB' =>  base64_encode($image_data_b)
        );

        $req = array(
            'url' => $postUrl,
            'method' => 'post',
            'timeout' => 10,
            'data' => json_encode($post_data),
            'header' => array(
                'Authorization:'.$sign,
                'Content-Type:text/json',
                'Expect: ',
            ),
        );
        $rsp  = Http::send($req);
         $ret  = json_decode($rsp, true);

        if(!$ret){
            return self::getStatusText();
        }
        return $ret;
    }
	
	public static function facecompareOne($image_path_a, $image_path_b) {
        $expired = time() + self::EXPIRED_SECONDS;
        $postUrl = Conf::$END_POINT . 'v2/base/match';

        $post_data = array(
            'client_id' =>  Conf::$APPID,
            'timestamp' =>  time(),
            'scene_id' =>  3,
            'orient_1' =>  1,
            'orient_2' =>  1,
            'algtype' =>  0,            
        );

        $post_data['sign'] = $sign = Auth::appSign($post_data);

        $obj1 = curl_file_create($image_path_a,'image/jpeg','image_1');
        $obj2 = curl_file_create($image_path_b,'image/jpeg','image_2');
        $post_data['image_1'] = $obj1;
        $post_data['image_2'] = $obj2;

        $req = array(
            'url' => $postUrl,
            'method' => 'post',
            'timeout' => 10,
            'data' => $post_data,
            'header' => array(
                'Authorization:'.$sign,               
                'Expect: ',
            ),
        );

        $rsp  = Http::send($req);        
        $ret  = json_decode($rsp, true);
        $result = array();
        if(!$ret){
            $result = ['similarity'=>0,'errorcode'=>1000,'errormsg'=>self::getStatusText(),'confidence'=>0];
            return  $result;
        }

        $rets = $ret['result'];
        $similarity = 0;
        $similarity = $rets >=1 ? $rets*30*1.2 : 0;  
        $similarity = $similarity >=100 ? 100 : $similarity;  
        $similarity = $ret['confidence']*100;
        $result = [
        'similarity'=>$similarity,'errorcode'=>0,'errormsg'=>'OK','confidence'=>$ret['confidence']*100,'result'=>$ret['result']
        ];
        return $result;
    }


}
