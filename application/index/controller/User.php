<?php
namespace app\index\controller;

use app\common\model\UserEmp;
use app\common\model\UserExtra;

class User extends Common
{
    public function getUserInfo()
    {
        $userInfo = getUserInfo();
        $userExtra = new UserExtra();
        $userInfo['avatar'] = $userExtra->getAvatar($userInfo['EMP_NO']);
        $this->success($userInfo);
    }
    public function noAvatar() {
        echo file_get_contents('.'.DS.'static'.DS.'index'.DS.'images'.DS.'noavatar.png');
        return false;
    }
}
