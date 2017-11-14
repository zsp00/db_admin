<?php
namespace app\common\model;
use think\Db;
use think\Model;

class ParticipateComp extends Model
{
    /*
     * 公司是否参与
     */
    public function isParticipate($compNo, $userInfo){
        $result = $this->where(['compNo'=>$compNo,'status'=>1])->find();
        if(!$result){
            $deptNo = Model('Assist')->where(['EMP_NO'=>$userInfo])->value('DEPT_NO');
            $result = Model('OrgDept')->getCompNo($deptNo);
        }
        return $result;
    }

    public function getInfo($compNo) {
        $result = $this->where(['compNo'=>$compNo,'status'=>1])->find();
        if(!$result){
            $userInfo = getUserInfo();
            $deptNo = Model('Assist')->where(['EMP_NO'=>$userInfo['EMP_NO']])->value('DEPT_NO');
            $compNo = Model('OrgDept')->getCompNo($deptNo);
            $result = $this->where(['compNo'=>$compNo,'status'=>1])->find();
        }
        return $result;
    }
}