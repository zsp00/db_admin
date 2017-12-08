<?php
namespace app\common\model;
use think\Config;
use think\Db;
use think\Model;

class Person extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'person';
    // 设置当前模型的数据库连接
    protected $connection = "uams";

}
