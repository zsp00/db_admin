<?php
namespace app\common\model;
use think\Db;
use think\Model;

class TaskType extends Model
{
    protected $createTime = 'createTime';
    protected $updateTime = 'updateTime';

    /*
    * 任务分类列表
    */
    public function getTypeList()
    {
        $result = $this->select();
        foreach($result as $k=>$v){
            $result[$k]['creator'] = Model('UserEmp')->where(['EMP_NO' => $v['creator']])->value('EMP_NAME');
        }
        return $result;
    }
}