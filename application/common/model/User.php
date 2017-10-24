<?php
namespace app\common\model;
use think\Config;
use think\Db;
use think\Model;

abstract class User extends Model
{
    protected $noRemember = 10800;
    protected $remember = 604800;
    
    
    protected abstract function doLogin($info,$remember);

    //登录
    public function login($data,$remember=false){
        return $this->doLogin($data,$remember);
    }
}
