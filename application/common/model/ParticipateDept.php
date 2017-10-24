<?php
namespace app\common\model;
use think\Db;
use think\Model;

class ParticipateDept extends Model
{
    /*
     * 公司是否参与
     */
    public function isParticipate($compNo){
        return $this->where(['deptNo'=>$compNo,'status'=>1])->find();
    }
}