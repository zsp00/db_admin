<?php

namespace TimeCheer\Weixin\QY;

/**
 * PC Web上 用户使用微信企业号登录
 * @package timecheer.weixin.qy
 */
class OAuth2 {

    const URL = 'https://qy.weixin.qq.com/cgi-bin/loginpage';
    const AUTHORIZE_URL = 'https://qy.weixin.qq.com/cgi-bin/authorize';
    
    /**
     * 创建OAuth协议的链接
     *
     * @param string $redirectUri 协议的回调地址
     * @param string $state       可携带的参数, 选填.
     *
     * @return string 协议地址
     */
    public function createUrl($corpId, $redirectUri, $state = '') {
        if (!$corpId || !$redirectUri) {
            $this->setError('参数错误!');

            return false;
        }

        $params = array(
            'corp_id' => $corpId,
            'redirect_uri' => urlencode($redirectUri),
            'state' => $state
        );
        
        $url = self::URL . '?' . http_build_query($params);

        return $url;
    }

    public function authorize($appid, $redirect_uri = '', $response_type = 'code', $scope = 'snsapi_base', $agentid, $state = '', $wechat_redirect = '#wechat_redirect'){

    }

}
