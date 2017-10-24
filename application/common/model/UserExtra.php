<?php
namespace app\common\model;
use think\Config;
use think\Db;
use think\Model;
use TimeCheer\Weixin\QYAPI\AccessToken;

class UserExtra extends Model
{

    public function initItem($empNo){
        $extraInfo = $this->where(['EMP_NO' =>$empNo])->find();
        if($extraInfo){
            return $extraInfo;

        }else{
            $data = [
                'EMP_NO'    =>  $empNo,
                'avatar'    =>  ''
            ];
            $result = Model('UserExtra')->insert($data);
            if($result){
                return $data;
            }else{
                return false;
            }
        }
    }

    public function updateAvatar($empNo,$personid){
        $info = $this->initItem($empNo);
        if($info){
            $extraUpdate = [
                'avatar'    =>  Model('UserExtra')->getAvatarWx($personid)
            ];
            $this->where(['EMP_NO' =>$empNo])->update($extraUpdate);
        }else{
            return false;
        }
    }

    public function getAvatar($empNo){
        $avatar = $this->where(['EMP_NO'=>$empNo])->value('avatar');
        if($avatar == ''){
            return false;
        }else{
            return $avatar;
        }
    }

    public function getInfo($empNo){
        $info = $this->initItem($empNo);
        return $this->where(['EMP_NO' => $empNo])->find();
    }

    public function getAvatarWx($userId)
    {
        $accessToken = new AccessToken();
        $token = $accessToken->get(Config::get('weixin.corpId'), Config::get('weixin.corpSecret'));
        $user = new \TimeCheer\Weixin\QYAPI\User($token);
        if ($userId == '') {
            $userId = session('personid');
        }
        $userInfo = $user->get($userId);
        if (isset($userInfo['avatar']) && $userInfo['avatar'] != '') {
            return $userInfo['avatar'];
        } else {
            return '';
        }


    }

}