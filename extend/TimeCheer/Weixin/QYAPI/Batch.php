<?php

namespace TimeCheer\Weixin\QYAPI;

/**
 * 异步批量任务接口
 */
class Batch extends Base {
    const API_BATCH_REPLACEPARTY = '/batch/replaceparty';
    const API_BATCH_SYNCUSER = '/batch/syncuser';
    const API_BATCH_REPLACEUSER = '/batch/replaceuser';

    /**
     * 全量覆盖部门
     * 接口说明：
     * 1.文件中存在、通讯录中也存在的部门，执行修改操作
     * 2.文件中存在、通讯录中不存在的部门，执行添加操作
     * 3.文件中不存在、通讯录中存在的部门，当部门下没有任何成员或子部门时，执行删除操作
     * 4.CSV文件中，部门名称、部门ID、父部门ID为必填字段，部门ID必须为数字；排序为可选字段，置空或填0不修改排序, order值大的排序靠前。
     *
     * @param string $media_id 上传的csv文件的media_id
     * @param string $callback 回调信息。如填写该项则任务完成后，通过callback推送事件给企业。具体请参考应用回调模式中的相应选项
     * @param string $callbackurl	企业应用接收企业微信推送请求的访问协议和地址，支持http或https协议
     * @param string $token	用于生成签名
     * @param string $encodingaeskey	用于消息体的加密，是AES密钥的Base64编码
     * @return 接口返回结果
     */
    public function replaceparty($media_id,$callbackurl='',$token='',$encodingaeskey=''){
        $data = [
            'media_id'  =>  $media_id
        ];
        if($callbackurl != ''){
            $data['callback'] = [
                'url'   => $callbackurl,
                'token' => $token,
                'encodingaeskey'  =>  $encodingaeskey,
            ];
        }
        return $this->doPost(self::API_BATCH_REPLACEPARTY, $data);
    }

    public function syncuser($media_id,$callbackurl='',$token='',$encodingaeskey=''){
        $data = [
            'media_id'  =>  $media_id
        ];
        if($callbackurl != ''){
            $data['callback'] = [
                'url'   => $callbackurl,
                'token' => $token,
                'encodingaeskey'  =>  $encodingaeskey,
            ];
        }
        return $this->doPost(self::API_BATCH_SYNCUSER, $data);
    }

    public function replaceuser($media_id,$callbackurl='',$token='',$encodingaeskey=''){
        $data = [
            'media_id'  =>  $media_id
        ];
        if($callbackurl != ''){
            $data['callback'] = [
                'url'   => $callbackurl,
                'token' => $token,
                'encodingaeskey'  =>  $encodingaeskey,
            ];
        }
        return $this->doPost(self::API_BATCH_REPLACEUSER, $data);
    }
}