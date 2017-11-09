<?php
namespace app\common\model;
use think\Db;
use think\Model;

class TaskLogData extends Model
{

    protected $createTime = 'createTime';
    protected $updateTime = false;

    protected $type = [
        'createTime' =>  'timestamp'
    ];

    //获取日志的详细信息
    public function getLogData($id,$deptNo)
    {
        if(!isset($id)){
            return false;
        }
        $result = Model('TaskLogData')->where(['tLId' => $id,'deptNo'=>$deptNo])->select();
        if(!$result){
            return false;
        }
        return $result;
    }

}