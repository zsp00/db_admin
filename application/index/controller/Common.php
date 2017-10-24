<?php
namespace app\index\controller;

use think\Controller;

class Common extends Controller
{
    public function _initialize() {
        if(!checkLogin()){
            $this->result('请先登陆',403);
        }
    }
}
