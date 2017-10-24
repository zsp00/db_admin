<?php
namespace app\index\controller;
/**
 * Created by PhpStorm.
 * User: TT
 * Date: 2017/9/22
 * Time: 17:08
 */
class Menu extends Common{
    public function getMenu($pId = 0){
        $userInfo = getUserInfo();
        $list = Model('Menu')->getListByEmpNo($userInfo['EMP_NO'],$pId);
        if($list){
            $this->success($list);
        }else{
            $this->error('您无权访问此系统');
        }
    }

    public function getSubmenu($router) {
        $routers = explode('/',$router);
        $info = Model('Menu')->where(['router'=>'/'.$routers[1]])->find();
        if(!$info) {
            $this->error('此功能未找到');
        }
        if($info['pId'] === 0) {
            $pId = $info['id'];
        }else{
            $pId = $info['pId'];
        }
        $userInfo = getUserInfo();
        $list = Model('Menu')->getListByEmpNo($userInfo['EMP_NO'],$pId);
        if($list){
            //$list = Model('Menu')->toSubmenu($list);
            $this->success($list);
        }else{
            $this->error('您无权访问此系统');
        }
    }
}