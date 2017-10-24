<?php
namespace app\index\validate;
use think\Validate;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/9/20
 * Time: 22:13
 */
class User extends Validate{
    protected $rule = [
        'username'  =>  'require|max:200|min:6',
        'password' =>  'require|max:200|min:6',
    ];
    protected $message  =   [
        'username.require' => '用户名必须',
        'username.max'     => '用户名最多不能超过200个字符',
        'username.min'     => '用户名最少不能小于6位',
        'password.require' => '密码必须',
        'password.max'     => '密码最多不能超过200个字符',
        'password.min'     => '密码最少不能小于6位',
    ];
}