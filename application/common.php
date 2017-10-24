<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

function checkLogin(){
    $sM = session('member');
    $cM = cookie('member');
    if(cookie('logintype') == 'protal'){

        $token = cookie('token_'.\think\Config::get('ldap.appNo'));
        $t_time = cookie('t_time');
        $empno = cookie('empno');
        $personid = cookie('personid');
        if($token == '' || $empno == '' || $t_time == '' || $personid == ''){
            return false;
        }
    }
    if((isset($sM['EMP_NO']) && isset($cM['EMP_NO']) && $cM['EMP_NO'] === $sM['EMP_NO']) || (isset($sM['username']) && isset($cM['username']) && $sM['username'] == $cM['username'])){
        return true;
    }else{
        return false;
    }
}

function getUserInfo($empNo = ''){
    if($empNo == ''){
        $sM = session('member');
        $empNo = $sM['EMP_NO'];

    }
    $model = new \app\common\model\UserEmp();

    return $model->getUserInfo($empNo);
}
function getCompNo($empNo = ''){
    $userInfo = getUserInfo($empNo);
    return $userInfo['COMP_NO'];
}