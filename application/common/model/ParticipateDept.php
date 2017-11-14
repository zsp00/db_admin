<?php
namespace app\common\model;
use think\Db;
use think\Model;

class ParticipateDept extends Model
{
    /*
     * 组织是否参与
     */
    public function isParticipate($deptNo){
        $result = $this->where(['deptNo'=>$deptNo,'status'=>1])->find();
        if(!$result){
            $userInfo = getUserInfo();
            $deptNo = Model('Assist')->where(['EMP_NO'=>$userInfo['EMP_NO']])->value('DEPT_NO');
            $deptNo = Model('OrgDept')->getDeptNo($deptNo);
            $result = $this->where(['deptNo'=>$deptNo,'status'=>1])->find();
        }
        return $result;
    }
}