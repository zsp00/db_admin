<?php

namespace TimeCheer\Weixin\QYAPI;
use think\Config;

/**
 * API基类
 * @package timecheer.weixin.qyapi
 */
class Base extends \TimeCheer\Weixin\Base {
    
    /**
     *
     * @var string 企业号的url前缀 
     */
    protected $apiPrefix = '';
    public function __construct($accessToken)
    {
        parent::__construct($accessToken);
        $this->apiPrefix = Config::get('weixin.qyapi');
    }

    public function request($api, $params = array()) {
        $res = \TimeCheer\Weixin\Util\HTTPClient::get( $this->apiPrefix . $api, $params);

        if (false === $res) {
            $this->setError(-10, '接口请求失败!');
            return false;
        }

        return $res;
    }
}
