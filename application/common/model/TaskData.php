<?php
namespace app\common\model;
use think\Db;
use think\Model;

class TaskData extends Model
{

    public $statusMsg = [
        '1'     =>  '未提交',
        '2'     =>  '待确认',
        '3'     =>  '已确认'
    ];
}