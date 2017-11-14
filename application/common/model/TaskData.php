<?php
namespace app\common\model;
use think\Db;
use think\Model;

class TaskData extends Model
{
    protected $autoWriteTimestamp = false;

    public $statusMsg = [
        '1'     =>  '未提交',
        '2'     =>  '待确认',
        '3'     =>  '已确认'
    ];

    /* 查询这个任务有没有下发
     * $id 任务的id
     * return 返回 true和false
    */
    public function getTaskDataValue($id)
    {
        $result = $this->where(['status'=>'1','tId'=>$id])->find();
        if($result){
            return true;
        }else{
            return false;
        }
    }
}