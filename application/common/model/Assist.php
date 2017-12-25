<?php
namespace app\common\model;
use think\Model;
use think\db;

class Assist extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'U_ASSIST';
    // 设置当前模型的数据库连接
    protected $connection = "uams";


}