<?php

namespace TimeCheer\Weixin\QYAPI;

/**
 * 应用创建菜单
 * @package timecheer.weixin.qyapi
 */
class JsapiTicket extends Base {
    
    const API_GET_JSAPI_TICKET = '/get_jsapi_ticket';

    /**
     * 获取jsapi_ticket
     *
     * @param  varchar     $url  url必须是调用JS接口页面的完整URL。
     */
    public function get($url) {
        $result = $this->doGet(self::API_GET_JSAPI_TICKET);
        $noncestr = rand(10000,99999);
        $timestamp = time();
        $v1 = "noncestr=".$noncestr."&";
        $v2 = "jsapi_ticket=".$result['ticket']."&";
        $v3 = "timestamp=".$timestamp."&";
        $v4 = "url=".$url;
        $array = array($v1,$v2,$v3,$v4);
        sort($array, SORT_STRING);
        $str = implode($array);
        $signature = sha1($str);
        return [
            'signature' =>  $signature,
            'noncestr' =>  $noncestr,
            'timestamp' =>  $timestamp
        ];

    }

}
