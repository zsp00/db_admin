<?php
/**
 * Created by PhpStorm.
 * User: TT
 * Date: 2017/8/14
 * Time: 21:33
 */

namespace app\common\model;


interface UserInterface
{
    public function getUserName($empNo);
    public function getUserInfo($empNo);
}