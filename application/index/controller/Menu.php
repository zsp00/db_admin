<?php
namespace app\index\controller;
/**
 * Created by PhpStorm.
 * User: TT
 * Date: 2017/9/22
 * Time: 17:08
 */
class Menu extends Common{
    
    public function getMenus(){
        $userInfo = getUserInfo();
        $list = Model('Menu')->getListByEmpNo($userInfo['EMP_NO']);
        $list = Model('Menu')->toTree($list);
        if($list){
            $this->success($list);
        }else{
            $this->error('您无权访问此系统');
        }
    }
}